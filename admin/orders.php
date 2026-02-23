<?php
include '../includes/header.php'; // Includes session_start() and security check
// include '../core/db_connect.php'; 

$pdo = connectDB();
$message = '';

// --- SEARCH LOGIC ---
$search = $_GET['search'] ?? '';

// --- READ OPERATION (Fetch orders with search filtering) ---
$sql = "
    SELECT 
        o.Order_ID,
        u.Name AS CustomerName,
        u.Address AS CustomerAddress,
        p.Name AS ProductName,
        c.Name AS CategoryName,
        od.Quantity,
        o.Total_Price,
        o.Order_Date
    FROM Orders o
    JOIN Users u ON o.User_ID = u.User_ID
    JOIN Order_Details od ON o.Order_ID = od.Order_ID
    JOIN Products p ON od.Product_ID = p.Product_ID
    JOIN Categories c ON p.Category_ID = c.Category_ID
    WHERE u.Name LIKE ? 
    OR p.Name LIKE ? 
    OR c.Name LIKE ?
    ORDER BY o.Order_Date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%", "%$search%"]);
$orders = $stmt->fetchAll();

include '../includes/sidebar.php'; 
?>

<div style="margin-left: 275px; width:80%">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <h2>Order Management</h2>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print List
            </button>
            <button onclick="window.print()" class="btn btn-success">
                <i class="fas fa-file-pdf"></i> Download PDF
            </button>
        </div>
    </div>

    <?php echo $message; ?>

    <div class="row mb-4 no-print">
        <div class="col-md-5">
            <form method="GET" class="input-group">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by Name, Product, or Category..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-outline-secondary" type="submit">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if ($search): ?>
                    <a href="orders.php" class="btn btn-outline-danger">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>S NO</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Total Price</th>
                    <th>Order Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No orders found.</td>
                    </tr>
                <?php endif; ?>
                <?php $sn = 1; ?>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo $sn++; ?></td>
                    <td><?php echo htmlspecialchars($order['CustomerName']); ?></td>
                    <td><?php echo htmlspecialchars($order['CustomerAddress']); ?></td>
                    <td><?php echo htmlspecialchars($order['ProductName']); ?></td>
                    <td><?php echo htmlspecialchars($order['CategoryName']); ?></td>
                    <td><?php echo $order['Quantity']; ?></td>
                    <td>$<?php echo number_format($order['Total_Price'], 2); ?></td>
                    <td><?php echo date('n/j/Y', strtotime($order['Order_Date'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
/* Print optimization: Hides UI elements to keep the document clean */
@media print {
    .no-print, .sidebar, .navbar, .btn, .input-group, form {
        display: none !important;
    }
    div[style*="margin-left: 275px"] {
        margin-left: 0 !important;
        width: 100% !important;
    }
    .table-responsive {
        overflow: visible !important;
    }
}

/* Your existing responsive styles */
@media screen and (max-width: 992px) {
    div[style*="margin-left: 275px"] {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100vw !important;
        padding: 10px !important;
        box-sizing: border-box !important;
        overflow-x: hidden !important; 
    }
    .top-header, .navbar, .d-flex.align-items-center {
        margin-left: 0 !important;
        width: 100% !important;
        display: flex !important;
        justify-content: flex-end !important;
        gap: 12px !important;
        padding-right: 10px !important;
    }
    .btn-logout, .notification-bell, .fa-bell {
        flex-shrink: 0 !important; 
        min-width: fit-content !important;
    }
    .btn {
        padding: 3px 10px !important;
        margin: 5px 2px !important;
        display: inline-block !important;
    }
    .table-responsive {
        width: 100% !important;
        overflow-x: auto !important;
        display: block !important;
        -webkit-overflow-scrolling: touch;
    }
    table {
        min-width: 700px !important; 
    }
}
</style>
<?php include '../includes/footer.php'; ?>