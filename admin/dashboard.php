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

// NEW: Query for Fast Moving Items
$top_moving_stmt = $pdo->query("SELECT p.Name, SUM(od.Quantity) as total_sold FROM order_details od JOIN products p ON od.Product_ID = p.Product_ID GROUP BY p.Product_ID ORDER BY total_sold DESC LIMIT 5");
$top_moving = $top_moving_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/sidebar.php'; 
?>

<style>
    .content-area { margin-left: 250px; background: #f8f9fc; padding: 25px; min-height: 100vh; }
    .card { border: none; border-radius: 12px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); }
    .kpi-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
    .filter-btn { border-radius: 20px; font-size: 0.8rem; padding: 5px 18px; }
    .chart-container { position: relative; height: 320px; width: 100%; }
    #sidebarToggle { position: fixed; right: 25px; bottom: 25px; z-index: 1040; border-radius: 50%; width: 55px; height: 55px; }
    .alert-sidebar { position: fixed; top: 0; right: 0; width: 320px; height: 100vh; background: #fff; z-index: 1050; transition: 0.4s; box-shadow: -5px 0 15px rgba(0,0,0,0.05); }
    .alert-sidebar.hidden { transform: translateX(100%); }
</style>

<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 text-gray-800 fw-bold">Operational Intelligence</h2>
        <div class="d-flex gap-2">
            <div class="btn-group bg-white p-1 shadow-sm" style="border-radius: 25px;">
                <a href="?view=week" class="btn filter-btn <?= $view == 'week' ? 'btn-primary text-white' : 'btn-light' ?>">Week</a>
                <a href="?view=month" class="btn filter-btn <?= $view == 'month' ? 'btn-primary text-white' : 'btn-light' ?>">Month</a>
                <a href="?view=year" class="btn filter-btn <?= $view == 'year' ? 'btn-primary text-white' : 'btn-light' ?>">Year</a>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3"><div class="card bg-primary text-white p-3 h-100"><div class="kpi-label opacity-75">Total Products</div><div class="h2 mb-0 font-weight-bold"><?= $total_products ?></div></div></div>
        <div class="col-md-3"><div class="card bg-success text-white p-3 h-100"><div class="kpi-label opacity-75">Total Stock</div><div class="h2 mb-0 font-weight-bold"><?= number_format($total_stock) ?></div></div></div>
        <div class="col-md-3"><div class="card bg-warning text-white p-3 h-100"><div class="kpi-label opacity-75">Orders Today</div><div class="h2 mb-0 font-weight-bold"><?= $orders_today_count ?></div></div></div>
        <div class="col-md-3"><div class="card bg-danger text-white p-3 h-100"><div class="kpi-label opacity-75">Revenue Today</div><div class="h2 mb-0 font-weight-bold">$<?= number_format($revenue_today, 2) ?></div></div></div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3"><div class="card bg-white p-3 border-start border-primary border-4"><div class="kpi-label text-primary">Inventory Value</div><div class="h5 mb-0 font-weight-bold">$<?= number_format($total_inv_value, 2) ?></div></div></div>
        <div class="col-md-3"><div class="card bg-white p-3 border-start border-success border-4"><div class="kpi-label text-success">Turnover Ratio</div><div class="h5 mb-0 font-weight-bold"><?= $turnover_ratio ?>x</div></div></div>
        <div class="col-md-3"><div class="card bg-white p-3 border-start border-info border-4"><div class="kpi-label text-info">Days to Sell</div><div class="h5 mb-0 font-weight-bold"><?= $turnover_days ?> Days</div></div></div>
        <div class="col-md-3"><div class="card bg-white p-3 border-start border-warning border-4"><div class="kpi-label text-warning">Sales Ratio</div><div class="h5 mb-0 font-weight-bold"><?= $sales_ratio ?>%</div></div></div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Inventory vs Sales Analysis</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container"><canvas id="invSalesChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3 text-primary"><b>Warehouse Stock Distribution</b></div>
                <div class="card-body d-flex justify-content-center">
                    <div class="chart-container" style="height: 280px;"><canvas id="distChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3 text-primary"><b>Fast Moving Items (Units Sold)</b></div>
                <div class="card-body">
                    <div class="chart-container" style="height: 280px;"><canvas id="topProductsChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card bg-dark text-white p-3 shadow-sm">
                <h6 class="mb-3 font-weight-bold border-bottom border-secondary pb-2">Urgent Action Required</h6>
                <div class="row">
                    <?php 
                    $low_stock = $pdo->query("SELECT p.name, w.name as wh, ws.quantity FROM warehouse_stock ws JOIN products p ON ws.product_id = p.product_id JOIN warehouses w ON ws.warehouse_id = w.warehouse_id WHERE ws.quantity < 5 LIMIT 4")->fetchAll();
                    foreach($low_stock as $ls): ?>
                    <div class="col-md-3 mb-2">
                        <div class="d-flex justify-content-between align-items-center p-2 border border-secondary rounded">
                            <span><?= $ls['name'] ?> <br><small class="text-muted"><?= $ls['wh'] ?></small></span>
                            <span class="badge bg-danger rounded-pill"><?= $ls['quantity'] ?> left</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<button id="sidebarToggle" class="btn btn-danger shadow" onclick="toggleSidebar()"><i class="fas fa-warehouse"></i></button>
<div id="rightSidebar" class="alert-sidebar hidden p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h5 class="mb-0">Warehouse Load</h5>
        <button class="btn-close" onclick="toggleSidebar()"></button>
    </div>
    <?php foreach($warehouse_data as $wh): 
        $percent = min(($wh['total'] / 2500) * 100, 100); ?>
    <div class="mb-4">
        <div class="d-flex justify-content-between small mb-1"><span><?= htmlspecialchars($wh['name']) ?></span><span><?= round($percent) ?>%</span></div>
        <div class="progress" style="height: 8px;"><div class="progress-bar <?= ($percent > 85) ? 'bg-danger' : 'bg-success' ?>" style="width: <?= $percent ?>%"></div></div>
    </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleSidebar() { document.getElementById('rightSidebar').classList.toggle('hidden'); }

document.addEventListener('DOMContentLoaded', function() {
    // 1. Inventory vs Sales Analysis
    new Chart(document.getElementById('invSalesChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($trend_data, 'label')) ?>,
            datasets: [{
                label: 'Sales Amount',
                data: <?= json_encode(array_column($trend_data, 'sales')) ?>,
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Inventory Value Snapshot',
                data: [<?= str_repeat($total_inv_value . ',', count($trend_data)) ?>],
                borderColor: '#4e73df',
                borderDash: [5, 5],
                fill: false
            }]
        },
        options: { maintainAspectRatio: false }
    });

    // 2. Stock Distribution (Donut)
    new Chart(document.getElementById('distChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($warehouse_data, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($warehouse_data, 'total')) ?>,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
            }]
        },
        options: { maintainAspectRatio: false, cutout: '70%' }
    });

    // 3. Fast Moving Items (Horizontal Bar)
    new Chart(document.getElementById('topProductsChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($top_moving, 'Name')) ?>,
            datasets: [{
                label: 'Units Sold',
                data: <?= json_encode(array_column($top_moving, 'total_sold')) ?>,
                backgroundColor: '#1cc88a',
                borderRadius: 5
            }]
        },
        options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
});
</script>

<?php include '../includes/footer.php'; ?>