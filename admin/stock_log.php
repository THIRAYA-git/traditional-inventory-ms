<?php
// 1. Correct Database Connection
require_once('../core/db_connect.php'); 

// Ensure you use the function from your db_connect to get the PDO instance
if (function_exists('connectDB')) {
    $db = connectDB();
} else {
    // Fallback if the variable is defined directly in db_connect.php
    $db = $pdo; 
}

include('../includes/header.php'); 
include('../includes/sidebar.php');

// Logic to Clear All History
if (isset($_POST['clear_all'])) {
    $db->query("DELETE FROM stock_history");
    echo "<script>window.location.href='stock_log.php';</script>";
    exit;
}

// FETCH LOGS: Note the COALESCE functions used to handle "Global Hub" labels
$query = "SELECT h.*, p.Name as product_name, 
                 COALESCE(w1.name, 'Global Hub') as from_warehouse_name, 
                 COALESCE(w2.name, 'Global Hub') as to_warehouse_name 
          FROM stock_history h
          JOIN products p ON h.product_id = p.Product_ID
          LEFT JOIN warehouses w1 ON h.from_warehouse_id = w1.warehouse_id
          LEFT JOIN warehouses w2 ON h.to_warehouse_id = w2.warehouse_id
          ORDER BY h.created_at DESC";

$logs = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    :root {
        --sl-bg: #f1f5f9;
        --sl-card: #ffffff;
        --sl-text: #1e293b;
        --sl-border: #e2e8f0;
        --sl-muted: #64748b;
        --sl-input-bg: #ffffff;
    }
    [data-theme="dark"] {
        --sl-bg: #0f172a;
        --sl-card: rgba(30, 41, 59, 0.75);
        --sl-text: #ffffff;
        --sl-border: rgba(255, 255, 255, 0.15);
        --sl-muted: #94a3b8;
        --sl-input-bg: rgba(30, 41, 59, 0.9);
    }

    .content-area { width: 100%; padding: 25px; margin-left: 250px; background: var(--sl-bg); }
    .table-card { background: var(--sl-card); border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; border: 1px solid var(--sl-border); transition: background 0.3s ease, border-color 0.3s ease; }
    [data-theme="dark"] .table-card { backdrop-filter: blur(12px); }
    
    .page-title { color: var(--sl-text); }
    .search-input-group .input-group-text { background: var(--sl-input-bg); border: 1px solid var(--sl-border); transition: background 0.3s ease, border-color 0.3s ease; }
    .search-input-group .form-control { background: var(--sl-input-bg); border: 1px solid var(--sl-border); color: var(--sl-text); transition: background 0.3s ease, border-color 0.3s ease; }
    [data-theme="dark"] .search-input-group .form-control::placeholder { color: var(--sl-muted); }
    [data-theme="dark"] .search-input-group .form-control:focus { background: var(--sl-input-bg); color: #ffffff; }
    
    /* Status Badge Colors */
    .badge-transfer { background-color: #0dcaf0; color: #000; }
    
    /* Table Styles */
    .table-dark-custom { background: var(--sl-card); }
    [data-theme="dark"] .table-dark-custom thead th {
        background-color: rgba(0, 0, 0, 0.4) !important;
        border-bottom: 1px solid var(--sl-border) !important;
        color: #ffffff !important;
    }
    [data-theme="dark"] #historyTable td {
        background-color: rgba(255, 255, 255, 0.02) !important;
        border-bottom: 1px solid var(--sl-border) !important;
        color: var(--sl-text) !important;
    }
    [data-theme="dark"] #historyTable tbody tr:hover td {
        background-color: rgba(255, 255, 255, 0.05) !important;
    }
    .table-text { color: var(--sl-text); }
    .table-muted { color: var(--sl-muted); }
    
    @media print {
        .no-print, .sidebar, .navbar, .btn, .input-group, form { display: none !important; }
        .content-area { width: 100% !important; position: absolute; left: 0; top: 0; margin: 0; }
        .table { width: 100% !important; border: 1px solid #000; }
    }

    @media screen and (max-width: 992px) {
        .content-area { margin-left: 0 !important; padding: 15px !important; }
    }
</style>

<div class="content-area">
    <div class="container-fluid p-0">
        <div class="row mb-4 align-items-center no-print">
            <div class="col-md-3"><h2 class="m-0 page-title">Stock History</h2></div>
            <div class="col-md-4">
                <div class="input-group shadow-sm search-input-group">
                    <span class="input-group-text"><i class="fas fa-search table-muted"></i></span>
                    <input type="text" id="logSearch" class="form-control" placeholder="Search history...">
                </div>
            </div>
            <div class="col-md-5 text-end">
                <form method="POST" style="display: inline;" onsubmit="return confirm('Clear ALL history?');">
                    <button type="submit" name="clear_all" class="btn btn-danger shadow-sm me-1">
                        <i class="fas fa-trash"></i> Clear All
                    </button>
                </form>
                <button onclick="window.print()" class="btn btn-outline-primary me-1"><i class="fas fa-print"></i> Print</button>
                <a href="export_logs.php" class="btn btn-success shadow-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="historyTable" style="width: 100%;">
                    <thead class="table-dark table-dark-custom">
                        <tr>
                            <th style="padding: 15px;">Date & Time</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 table-muted">No stock history found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($logs as $log): ?>
                            <tr>
                                <td class="text-nowrap table-text">
                                    <?= date('M d, Y | H:i', strtotime($log['created_at'])) ?>
                                </td>
                                <td class="table-text"><strong><?= htmlspecialchars($log['product_name']) ?></strong></td>
                                <td>
                                    <span class="badge badge-transfer">
                                        <?= ucfirst(htmlspecialchars($log['transaction_type'])) ?>
                                    </span>
                                </td>
                                <td class="table-text"><?= htmlspecialchars($log['from_warehouse_name']) ?></td>
                                <td class="table-text"><?= htmlspecialchars($log['to_warehouse_name']) ?></td>
                                <td class="fw-bold table-text"><?= number_format($log['quantity']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('logSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#historyTable tbody tr');
    rows.forEach(row => {
        // Skip the "No history found" row if it exists
        if(row.cells.length > 1) {
            row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
        }
    });
});
</script>

<?php include('../includes/footer.php'); ?>