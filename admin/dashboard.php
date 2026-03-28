<?php
// File: inventory-ms/admin/dashboard.php
include '../includes/header.php'; 
require_once '../core/auth_check.php'; 
check_access('admin'); 

$pdo = connectDB();

// --- 1. DYNAMIC TIME FILTER LOGIC ---
$view = $_GET['view'] ?? 'month';
$interval = match($view) {
    'week' => '7 DAY',
    'year' => '1 YEAR',
    default => '30 DAY',
};

// --- 2. CORE KPI STATS ---
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_stock = $pdo->query("SELECT SUM(quantity) FROM warehouse_stock")->fetchColumn() ?? 0;

$today = date('Y-m-d');
$orders_today_stmt = $pdo->prepare("SELECT COUNT(Order_ID), SUM(Total_Price) FROM orders WHERE DATE(Order_Date) = ?");
$orders_today_stmt->execute([$today]);
list($orders_today_count, $revenue_today) = $orders_today_stmt->fetch(PDO::FETCH_NUM);
$revenue_today = $revenue_today ?? 0;

// --- 3. INTELLIGENCE CALCULATIONS ---
$total_inv_value = $pdo->query("SELECT SUM(ws.quantity * p.Price) FROM warehouse_stock ws JOIN products p ON ws.product_id = p.product_id")->fetchColumn() ?? 0;

$period_stats = $pdo->query("SELECT SUM(Total_Price) as revenue, SUM(od.Quantity * p.Price) as cogs 
    FROM orders o 
    JOIN order_details od ON o.Order_ID = od.Order_ID 
    JOIN products p ON od.Product_ID = p.Product_ID
    WHERE o.Order_Date >= DATE_SUB(CURDATE(), INTERVAL $interval)")->fetch(PDO::FETCH_ASSOC);

$revenue_period = $period_stats['revenue'] ?? 0;
$cogs_period = $period_stats['cogs'] ?? 0;

$turnover_ratio = ($total_inv_value > 0) ? round($cogs_period / $total_inv_value, 2) : 0;
$turnover_days = ($turnover_ratio > 0) ? round(($view == 'year' ? 365 : 30) / $turnover_ratio, 1) : 0;
$sales_ratio = ($total_inv_value > 0) ? round(($revenue_period / $total_inv_value) * 100, 1) : 0;

// --- 4. CHART DATA ---
$trend_data = $pdo->query("SELECT DATE_FORMAT(Order_Date, '%b') as label, SUM(Total_Price) as sales FROM orders GROUP BY label ORDER BY Order_Date ASC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

$warehouse_data = $pdo->query("SELECT w.name, SUM(ws.quantity) as total FROM warehouse_stock ws JOIN warehouses w ON ws.warehouse_id = w.warehouse_id GROUP BY w.name")->fetchAll(PDO::FETCH_ASSOC);

$top_moving_stmt = $pdo->query("SELECT p.Name, SUM(od.Quantity) as total_sold FROM order_details od JOIN products p ON od.Product_ID = p.Product_ID GROUP BY p.Product_ID ORDER BY total_sold DESC LIMIT 5");
$top_moving = $top_moving_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/sidebar.php'; 
?>

<style>
    :root {
        --dash-bg: #f0f2f8;
        --dash-card: #ffffff;
        --dash-text: #2d3748;
        --dash-text-muted: #a0aec0;
        --dash-border: #f0f2f8;
        --dash-progress: #f0f2f8;
    }
    [data-theme="dark"] {
        --dash-bg: #0f172a;
        --dash-card: rgba(30, 41, 59, 0.75);
        --dash-text: #ffffff;
        --dash-text-muted: #a0aec0;
        --dash-border: rgba(255, 255, 255, 0.15);
        --dash-progress: rgba(255, 255, 255, 0.1);
    }

    /* ── Layout ── */
    .content-area {
        margin-left: 250px;
        background: var(--dash-bg);
        padding: 28px 30px;
        min-height: 100vh;
        font-family: 'Segoe UI', system-ui, sans-serif;
        transition: background 0.3s ease;
    }

    /* ── Cards ── */
    .card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        transition: box-shadow 0.2s ease;
    }
    .card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.12); }

    /* ── KPI Top Row ── */
    .kpi-card {
        border-radius: 14px;
        padding: 20px 22px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .kpi-card::after {
        content: '';
        position: absolute;
        right: -18px; top: -18px;
        width: 90px; height: 90px;
        border-radius: 50%;
        background: rgba(255,255,255,0.12);
    }
    .kpi-card .kpi-icon {
        font-size: 1.6rem;
        margin-bottom: 10px;
        opacity: 0.9;
    }
    .kpi-card .kpi-label {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        opacity: 0.85;
        margin-bottom: 4px;
    }
    .kpi-card .kpi-value {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.1;
    }
    .kpi-bg-blue   { background: linear-gradient(135deg, #4e73df, #2e59d9); }
    .kpi-bg-green  { background: linear-gradient(135deg, #1cc88a, #13855c); }
    .kpi-bg-orange { background: linear-gradient(135deg, #f6a435, #e07b0a); }
    .kpi-bg-red    { background: linear-gradient(135deg, #e74a3b, #be2617); }

    /* ── Intelligence Row ── */
    .intel-card {
        border-radius: 14px;
        background: var(--dash-card);
        padding: 16px 20px;
        border-left: 4px solid transparent;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        transition: background 0.3s ease;
        backdrop-filter: blur(12px);
        border: 1px solid var(--dash-border);
    }
    [data-theme="dark"] .intel-card {
        background: var(--dash-card);
    }
    .intel-card .intel-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 5px;
    }
    .intel-card .intel-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--dash-text);
    }

    /* ── Chart Section ── */
    .chart-card {
        border-radius: 14px;
        background: var(--dash-card);
        padding: 0;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        border: 1px solid var(--dash-border);
        transition: background 0.3s ease, border-color 0.3s ease;
    }
    [data-theme="dark"] .chart-card {
        background: var(--dash-card);
    }
    .chart-card .chart-header {
        padding: 16px 22px;
        border-bottom: 1px solid var(--dash-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .chart-card .chart-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--dash-text);
        margin: 0;
    }
    .chart-card .chart-subtitle {
        font-size: 0.72rem;
        color: var(--dash-text-muted);
        margin: 2px 0 0 0;
    }
    .chart-card .chart-body {
        padding: 20px 22px;
    }
    .chart-container {
        position: relative;
        width: 100%;
    }

    /* ── Filter Buttons ── */
    .filter-pill {
        background: var(--dash-card);
        border-radius: 30px;
        padding: 5px 6px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        display: inline-flex;
        gap: 4px;
        border: 1px solid var(--dash-border);
        transition: background 0.3s ease;
    }
    [data-theme="dark"] .filter-pill {
        background: var(--dash-card);
    }
    .filter-pill a {
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 5px 18px;
        text-decoration: none;
        color: #6c757d;
        transition: all 0.15s;
    }
    .filter-pill a.active {
        background: #4e73df;
        color: #fff;
        box-shadow: 0 2px 8px rgba(78,115,223,0.4);
    }
    .filter-pill a:not(.active):hover { background: var(--dash-border); color: var(--dash-text); }

    /* ── Low Stock Alert Row ── */
    .alert-card {
        border-radius: 14px;
        background: #1a202c;
        padding: 20px 24px;
    }
    .alert-card h6 {
        color: #e2e8f0;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .stock-alert-item {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
        padding: 10px 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .stock-alert-item .item-name { font-size: 0.85rem; color: #e2e8f0; font-weight: 600; }
    .stock-alert-item .item-wh   { font-size: 0.72rem; color: #718096; }

    /* ── Sidebar Toggle ── */
    #sidebarToggle {
        position: fixed; right: 25px; bottom: 25px;
        z-index: 1040; border-radius: 50%;
        width: 52px; height: 52px;
        background: linear-gradient(135deg, #e74a3b, #be2617);
        border: none; color: #fff; font-size: 1.1rem;
        box-shadow: 0 4px 15px rgba(231,74,59,0.5);
        cursor: pointer;
        transition: transform 0.2s;
    }
    #sidebarToggle:hover { transform: scale(1.08); }

    /* ── Right Sidebar ── */
    .alert-sidebar {
        position: fixed; top: 0; right: 0;
        width: 310px; height: 100vh;
        background: var(--dash-card);
        z-index: 1050;
        transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), background 0.3s ease;
        box-shadow: -6px 0 30px rgba(0,0,0,0.1);
        padding: 24px;
        overflow-y: auto;
        border-left: 1px solid var(--dash-border);
    }
    [data-theme="dark"] .alert-sidebar {
        background: rgba(30, 41, 59, 0.95);
    }
    .alert-sidebar.hidden { transform: translateX(100%); }
    .progress { border-radius: 6px; background: var(--dash-progress); transition: background 0.3s ease; }
    .progress-bar { border-radius: 6px; }

    /* ── Legend Dots ── */
    .legend-dot {
        display: inline-block;
        width: 10px; height: 10px;
        border-radius: 50%;
        margin-right: 5px;
    }
    .legend-label { font-size: 0.75rem; color: var(--dash-text-muted); margin-right: 14px; }

    /* ── Page Header ── */
    .page-header-title { color: var(--dash-text); }
    .page-header-sub { color: var(--dash-text-muted); }
    [data-theme="dark"] .page-header-title { color: #ffffff; }
    [data-theme="dark"] .page-header-sub { color: #a0aec0; }

    /* ── Right Sidebar Header ── */
    [data-theme="dark"] .sidebar-wh-title { color: #ffffff !important; }
    [data-theme="dark"] .sidebar-wh-sub { color: #a0aec0 !important; }
    [data-theme="dark"] .sidebar-wh-name { color: #ffffff !important; }
</style>

<div class="content-area">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-0 fw-bold page-header-title">Operational Intelligence</h2>
            <p class="mb-0 page-header-sub" style="font-size:0.8rem;">Live overview of your inventory and sales performance</p>
        </div>
        <div class="filter-pill">
            <a href="?view=week"  class="<?= $view == 'week'  ? 'active' : '' ?>">Week</a>
            <a href="?view=month" class="<?= $view == 'month' ? 'active' : '' ?>">Month</a>
            <a href="?view=year"  class="<?= $view == 'year'  ? 'active' : '' ?>">Year</a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="kpi-card kpi-bg-blue">
                <div class="kpi-icon"><i class="fas fa-box-open"></i></div>
                <div class="kpi-label">Total Products</div>
                <div class="kpi-value"><?= $total_products ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card kpi-bg-green">
                <div class="kpi-icon"><i class="fas fa-warehouse"></i></div>
                <div class="kpi-label">Total Stock</div>
                <div class="kpi-value"><?= number_format($total_stock) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card kpi-bg-orange">
                <div class="kpi-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="kpi-label">Orders Today</div>
                <div class="kpi-value"><?= $orders_today_count ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card kpi-bg-red">
                <div class="kpi-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="kpi-label">Revenue Today</div>
                <div class="kpi-value">$<?= number_format($revenue_today, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Intelligence Strip -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="intel-card" style="border-left-color:#4e73df;">
                <div class="intel-label" style="color:#4e73df;">Inventory Value</div>
                <div class="intel-value">$<?= number_format($total_inv_value, 2) ?></div>
                <small style="color:#a0aec0;font-size:0.7rem;">Total stock at cost</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="intel-card" style="border-left-color:#1cc88a;">
                <div class="intel-label" style="color:#1cc88a;">Turnover Ratio</div>
                <div class="intel-value"><?= $turnover_ratio ?>x</div>
                <small style="color:#a0aec0;font-size:0.7rem;">COGS ÷ Inventory value</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="intel-card" style="border-left-color:#36b9cc;">
                <div class="intel-label" style="color:#36b9cc;">Days to Sell</div>
                <div class="intel-value"><?= $turnover_days ?> Days</div>
                <small style="color:#a0aec0;font-size:0.7rem;">Avg sell-through time</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="intel-card" style="border-left-color:#f6c23e;">
                <div class="intel-label" style="color:#f6c23e;">Sales Ratio</div>
                <div class="intel-value"><?= $sales_ratio ?>%</div>
                <small style="color:#a0aec0;font-size:0.7rem;">Revenue vs inventory</small>
            </div>
        </div>
    </div>

    <!-- Trend Chart: Full Width -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <p class="chart-title">Sales Trend vs Inventory Value</p>
                        <p class="chart-subtitle">Monthly revenue compared against current inventory baseline</p>
                    </div>
                    <div>
                        <span class="legend-label"><span class="legend-dot" style="background:#1cc88a;"></span>Sales</span>
                        <span class="legend-label" style="display:inline-flex;align-items:center;">
                            <span style="display:inline-block;width:18px;height:2px;border-top:2px dashed #4e73df;margin-right:5px;"></span>Inventory Value
                        </span>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="chart-container" style="height:300px;">
                        <canvas id="invSalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Charts: Donut + Horizontal Bar -->
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="chart-card h-100">
                <div class="chart-header">
                    <div>
                        <p class="chart-title">Warehouse Distribution</p>
                        <p class="chart-subtitle">Stock spread across locations</p>
                    </div>
                </div>
                <div class="chart-body d-flex flex-column align-items-center">
                    <div class="chart-container" style="height:240px;max-width:280px;">
                        <canvas id="distChart"></canvas>
                    </div>
                    <div id="distLegend" class="d-flex flex-wrap justify-content-center gap-2 mt-3"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="chart-card h-100">
                <div class="chart-header">
                    <div>
                        <p class="chart-title">Top 5 Fast Moving Items</p>
                        <p class="chart-subtitle">Ranked by total units sold</p>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="chart-container" style="height:270px;">
                        <canvas id="topProductsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="row mb-2">
        <div class="col-12">
            <div class="alert-card">
                <h6 class="mb-3 d-flex align-items-center gap-2">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    Urgent — Low Stock Alerts
                </h6>
                <div class="row g-2">
                    <?php 
                    $low_stock = $pdo->query("SELECT p.product_id, p.name, w.name as wh, ws.quantity FROM warehouse_stock ws JOIN products p ON ws.product_id = p.product_id JOIN warehouses w ON ws.warehouse_id = w.warehouse_id WHERE ws.quantity <= p.minimum_stock_level LIMIT 4")->fetchAll();
                    foreach($low_stock as $ls): ?>
                    <div class="col-md-3">
                        <div class="stock-alert-item">
                            <div>
                                <div class="item-name">
                                    <span class="badge me-1" style="background:#1e293b;color:#fff;font-size:0.65rem;">#<?= htmlspecialchars($ls['product_id']) ?></span>
                                    <?= htmlspecialchars($ls['name']) ?>
                                </div>
                                <div class="item-wh"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($ls['wh']) ?></div>
                            </div>
                            <span class="badge rounded-pill" style="background:#e74a3b;font-size:0.75rem;padding:5px 10px;">
                                <?= $ls['quantity'] ?> left
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Warehouse Load Sidebar Toggle -->
<button id="sidebarToggle" onclick="toggleSidebar()">
    <i class="fas fa-warehouse"></i>
</button>

<div id="rightSidebar" class="alert-sidebar hidden">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom:1px solid var(--dash-border);">
        <div>
            <h5 class="mb-0 fw-bold sidebar-wh-title">Warehouse Load</h5>
            <small class="sidebar-wh-sub">Capacity utilization</small>
        </div>
        <button class="btn-close" onclick="toggleSidebar()"></button>
    </div>
    <?php foreach($warehouse_data as $wh): 
        $percent = min(($wh['total'] / 2500) * 100, 100);
        $barColor = $percent > 85 ? '#e74a3b' : ($percent > 60 ? '#f6c23e' : '#1cc88a');
    ?>
    <div class="mb-4">
        <div class="d-flex justify-content-between mb-1">
            <span class="sidebar-wh-name" style="font-size:0.83rem;font-weight:600;"><?= htmlspecialchars($wh['name']) ?></span>
            <span style="font-size:0.78rem;font-weight:700;color:<?= $barColor ?>;"><?= round($percent) ?>%</span>
        </div>
        <div class="progress" style="height:7px;">
            <div class="progress-bar" style="width:<?= $percent ?>%;background:<?= $barColor ?>;"></div>
        </div>
        <small class="sidebar-wh-sub" style="font-size:0.68rem;"><?= number_format($wh['total']) ?> units stored</small>
    </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleSidebar() {
    document.getElementById('rightSidebar').classList.toggle('hidden');
}

const COLORS = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'];

function isDarkMode() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
}

function tooltipStyle() {
    return {
        backgroundColor : 'rgba(26,32,44,0.92)',
        titleColor      : '#e2e8f0',
        bodyColor       : '#cbd5e0',
        borderColor     : 'rgba(255,255,255,0.08)',
        borderWidth     : 1,
        padding         : 10,
        cornerRadius    : 8,
        displayColors   : true,
        boxPadding      : 4,
    };
}

function chartDefaults(dark) {
    return {
        font: { family: "'Segoe UI', system-ui, sans-serif", size: 12 },
        color: dark ? '#e2e8f0' : '#718096',
    };
}

function gridColor(dark) {
    return dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.05)';
}

function axisColor(dark) {
    return dark ? '#a0aec0' : '#4a5568';
}

function initCharts() {
    const dark = isDarkMode();
    Object.assign(Chart.defaults, chartDefaults(dark));

    const trendLabels = <?= json_encode(array_column($trend_data, 'label')) ?>;
    const trendSales  = <?= json_encode(array_column($trend_data, 'sales')) ?>;
    const invBaseline = Array(trendLabels.length).fill(<?= $total_inv_value ?>);

    window.invSalesChart = new Chart(document.getElementById('invSalesChart'), {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [
                {
                    label              : 'Sales ($)',
                    data               : trendSales,
                    borderColor        : '#1cc88a',
                    backgroundColor    : dark ? 'rgba(28,200,138,0.15)' : 'rgba(28,200,138,0.08)',
                    fill               : true,
                    tension            : 0.45,
                    borderWidth        : 2.5,
                    pointBackgroundColor: '#1cc88a',
                    pointRadius        : 5,
                    pointHoverRadius   : 7,
                },
                {
                    label          : 'Inventory Value ($)',
                    data           : invBaseline,
                    borderColor    : '#4e73df',
                    borderDash     : [6, 4],
                    borderWidth    : 2,
                    fill           : false,
                    tension        : 0,
                    pointRadius    : 0,
                    pointHoverRadius: 0,
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend : { display: false },
                tooltip: {
                    ...tooltipStyle(),
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: $${Number(ctx.parsed.y).toLocaleString()}`
                    }
                }
            },
            scales: {
                x: {
                    grid  : { display: false },
                    border: { display: false },
                    ticks : { font: { size: 11 } }
                },
                y: {
                    grid  : { color: gridColor(dark) },
                    border: { display: false },
                    ticks : {
                        font    : { size: 11 },
                        callback: v => '$' + Number(v).toLocaleString()
                    }
                }
            }
        }
    });

    const whLabels = <?= json_encode(array_column($warehouse_data, 'name')) ?>;
    const whTotals = <?= json_encode(array_column($warehouse_data, 'total')) ?>;

    window.distChart = new Chart(document.getElementById('distChart'), {
        type: 'doughnut',
        data: {
            labels: whLabels,
            datasets: [{
                data           : whTotals,
                backgroundColor: COLORS,
                hoverOffset    : 8,
                borderWidth    : 3,
                borderColor    : dark ? '#1e293b' : '#fff',
            }]
        },
        options: {
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend : { display: false },
                tooltip: {
                    ...tooltipStyle(),
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${Number(ctx.parsed).toLocaleString()} units`
                    }
                }
            }
        }
    });

    const legendEl = document.getElementById('distLegend');
    legendEl.innerHTML = '';
    whLabels.forEach((name, i) => {
        const span = document.createElement('span');
        span.style.cssText = `display:inline-flex;align-items:center;gap:5px;font-size:0.73rem;color:${axisColor(dark)};`;
        span.innerHTML = `<span style="width:10px;height:10px;border-radius:50%;background:${COLORS[i]};display:inline-block;"></span>${name}`;
        legendEl.appendChild(span);
    });

    const fmLabels = <?= json_encode(array_column($top_moving, 'Name')) ?>;
    const fmValues = <?= json_encode(array_column($top_moving, 'total_sold')) ?>;

    window.topProductsChart = new Chart(document.getElementById('topProductsChart'), {
        type: 'bar',
        data: {
            labels: fmLabels,
            datasets: [{
                label          : 'Units Sold',
                data           : fmValues,
                backgroundColor: COLORS,
                borderRadius   : 6,
                borderSkipped  : false,
            }]
        },
        options: {
            indexAxis          : 'y',
            maintainAspectRatio: false,
            plugins: {
                legend : { display: false },
                tooltip: {
                    ...tooltipStyle(),
                    callbacks: {
                        label: ctx => ` ${ctx.parsed.x.toLocaleString()} units sold`
                    }
                }
            },
            scales: {
                x: {
                    grid  : { color: gridColor(dark) },
                    border: { display: false },
                    ticks : {
                        font    : { size: 11 },
                        callback: v => v.toLocaleString()
                    }
                },
                y: {
                    grid  : { display: false },
                    border: { display: false },
                    ticks : { font: { size: 12 }, color: axisColor(dark) }
                }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initCharts();

    const observer = new MutationObserver(() => {
        const charts = [window.invSalesChart, window.distChart, window.topProductsChart];
        const dark = isDarkMode();
        const grid = gridColor(dark);
        const axis = axisColor(dark);

        Chart.defaults.color = dark ? '#e2e8f0' : '#718096';

        charts.forEach(chart => {
            if (!chart) return;
            if (chart.options.scales?.y) {
                chart.options.scales.y.grid.color = grid;
            }
            if (chart.options.scales?.x) {
                chart.options.scales.x.grid.color = grid;
            }
            if (chart.options.scales?.y?.ticks) {
                chart.options.scales.y.ticks.color = axis;
            }
            if (chart.data.datasets[0]) {
                if (chart.data.datasets[0].borderDash) {
                    chart.data.datasets[1].borderColor = '#fff';
                }
            }
            chart.update('none');
        });

        const legendEl = document.getElementById('distLegend');
        if (legendEl) {
            legendEl.querySelectorAll('span').forEach((span, i) => {
                span.style.color = axis;
            });
        }
    });

    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
});
</script>

<?php include '../includes/footer.php'; ?>
