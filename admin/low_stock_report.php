<?php
// File: inventory-ms/admin/low_stock_report.php

// 1. Include necessary files
include '../includes/header.php'; 

// --- SECURITY CHECK ---
if (!isset($is_admin) || !$is_admin) {
    header('Location: ../employee/dashboard.php'); 
    exit;
}

// Check if functions exist and fetch data
$low_stock_items = [];
$low_stock_count = 0;
$warehouse_alerts = [];
$warehouse_alert_count = 0;

if (!function_exists('getLowStockItems') || !function_exists('getWarehouseLowStock')) {
    $error_message = "System Error: Required alert functions are missing in alert_functions.php.";
} else {
    try {
        // Fetch General Low Stock (Global level)
        $low_stock_items = getLowStockItems();
        $low_stock_count = count($low_stock_items);

        // Fetch Warehouse-Specific Low Stock (Location level)
        $warehouse_alerts = getWarehouseLowStock();
        $warehouse_alert_count = count($warehouse_alerts);

    } catch (Exception $e) {
        $error_message = "Error loading report data: " . $e->getMessage();
    }
}

include '../includes/sidebar.php'; 
?>

<div style="margin-left: 250px; width:80%" class="content-area">
    <div class="container-fluid">
        <h2 class="h3 pt-3 mb-4"><i class="fas fa-bell me-2 text-danger"></i> Low Stock Alert Report</h2>
        <p class="text-muted">
            This report lists products that have reached or fallen below defined minimum stock levels, both globally and at specific warehouse locations.
        </p>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="card mb-5 shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span><i class="fas fa-globe me-2"></i> General Inventory Low Stock</span>
                <span class="badge bg-light text-dark"><?php echo $low_stock_count; ?> Items</span>
            </div>
            <div class="card-body">
                <?php if ($low_stock_count === 0): ?>
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle"></i> All global stock levels are healthy.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th style="width: 80px;">PID</th>
                                    <th>Product Name</th>
                                    <th class="text-center">Current Total Stock</th>
                                    <th class="text-center">Minimum Level</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sn_global = 1; // Serial Number Counter for Global Table
                                foreach ($low_stock_items as $item): 
                                ?>
                                <tr>
                                    <td class="text-muted"><?php echo $sn_global++; ?></td>
                                    <td><span class="badge bg-secondary">#<?php echo htmlspecialchars($item['Product_ID']); ?></span></td>
                                    <td><?php echo htmlspecialchars($item['Name']); ?></td>
                                    <td class="text-center text-danger fw-bold"><?php echo htmlspecialchars($item['Stock']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($item['Minimum_Stock_Level']); ?></td>
                                    <td class="text-end">
                                        <a href="create_po.php?product=<?php echo $item['Product_ID']; ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-cart-plus"></i> Create PO
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-5 shadow-sm">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <span><i class="fas fa-exclamation-triangle me-2"></i> Warehouse Location Alerts (At or Below Minimum Level)</span>
                <span class="badge bg-light text-danger"><?php echo $warehouse_alert_count; ?> Alerts</span>
            </div>
            <div class="card-body">
                <?php if ($warehouse_alert_count === 0): ?>
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle"></i> No individual warehouses are running low on specific items.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-secondary">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th style="width: 80px;">PID</th>
                                    <th>Product Name</th>
                                    <th>Warehouse Location</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sn_warehouse = 1; // Serial Number Counter for Warehouse Table
                                foreach ($warehouse_alerts as $alert): 
                                ?>
                                <tr>
                                    <td class="text-muted"><?php echo $sn_warehouse++; ?></td>
                                    <td><span class="badge bg-light text-dark border">#<?php echo htmlspecialchars($alert['Product_ID']); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($alert['Product_Name']); ?></strong></td>
                                    <td><i class="fas fa-warehouse me-1 text-muted"></i> <?php echo htmlspecialchars($alert['Warehouse_Name']); ?></td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-danger" style="font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($alert['Current_Stock']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="transfer_stock.php?product_id=<?php echo $alert['Product_ID']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-exchange-alt"></i> Transfer
                                        </a>
                                        <a href="create_po.php?product=<?php echo $alert['Product_ID']; ?>" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-cart-plus"></i> PO
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>