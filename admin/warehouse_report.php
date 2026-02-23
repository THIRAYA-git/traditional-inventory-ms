<?php
include('../includes/header.php'); 
include('../includes/sidebar.php');
$pdo = connectDB();

// 1. Fetch all active warehouses for columns
$warehouses = $pdo->query("SELECT warehouse_id, name FROM warehouses WHERE status='active' GROUP BY name ORDER BY name")->fetchAll();
// 2. Fetch all products
$products = $pdo->query("SELECT product_id, name, SKU FROM products ORDER BY name")->fetchAll();
?>

<div class="content-area">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0 text-gray-800">Global Warehouse Stock Report</h2>
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-dark">
                <h6 class="m-0 font-weight-bold text-white">Stock Level Matrix</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product Name (SKU)</th>
                                <?php foreach ($warehouses as $w): ?>
                                    <th class="text-center"><?php echo htmlspecialchars($w['name']); ?></th>
                                <?php endforeach; ?>
                                <th class="table-primary text-center">Total Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($p['name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($p['SKU']); ?></small>
                                    </td>
                                    <?php 
                                    $total_row_stock = 0;
                                    foreach ($warehouses as $w): 
                                        // Get stock for this specific product in this specific warehouse
                                        $stmt = $pdo->prepare("SELECT quantity FROM warehouse_stock WHERE product_id = ? AND warehouse_id = ?");
                                        $stmt->execute([$p['product_id'], $w['warehouse_id']]);
                                        $qty = $stmt->fetchColumn() ?: 0;
                                        $total_row_stock += $qty;
                                        
                                        $text_color = ($qty < 10) ? 'text-danger fw-bold' : '';
                                    ?>
                                        <td class="text-center <?php echo $text_color; ?>">
                                            <?php echo $qty; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    
                                    <td class="text-center table-primary">
                                        <strong><?php echo $total_row_stock; ?></strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
@media screen and (max-width: 992px) {
    /* 1. RESET SIDEBAR MARGIN */
    /* Target the specific div that usually has the desktop margin-left */
    div[style*="margin-left"], .content-area {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100vw !important;
        /* 5px padding makes the content 'a little wider' on mobile */
        padding: 5px !important; 
        box-sizing: border-box !important;
        overflow-x: hidden !important;
    }

    /* 2. PROTECT THE HEADER ICONS */
    /* Ensures the Logout button and Bell stay full-sized */
    .top-header, .navbar, .d-flex.align-items-center {
        display: flex !important;
        justify-content: flex-end !important;
        flex-wrap: nowrap !important;
    }

    .fa-bell, .btn-logout, [class*="logout"] {
        flex-shrink: 0 !important; /* Prevents shrinking seen in image_b19a41.png */
        width: auto !important;
    }

    /* 3. TABLE HORIZONTAL SCROLL */
    /* Essential for this report since it has many warehouse columns */
    .table-responsive {
        width: 100% !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }
}
</style>
<?php include('../includes/footer.php'); ?>