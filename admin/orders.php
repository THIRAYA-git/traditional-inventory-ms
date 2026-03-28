<?php
include '../includes/header.php'; // Includes session_start() and security check
// include '../core/db_connect.php';

$pdo = connectDB();
$message = '';

// --- SEARCH LOGIC ---
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, filter_var($_GET['page'], FILTER_VALIDATE_INT)) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// --- COUNT TOTAL ORDERS ---
$count_sql = "
    SELECT COUNT(DISTINCT o.Order_ID)
    FROM Orders o
    JOIN Users u ON o.User_ID = u.User_ID
    JOIN Order_Details od ON o.Order_ID = od.Order_ID
    JOIN Products p ON od.Product_ID = p.Product_ID
    JOIN Categories c ON p.Category_ID = c.Category_ID
    WHERE u.Name LIKE ?
    OR p.Name LIKE ?
    OR c.Name LIKE ?
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute(["%$search%", "%$search%", "%$search%"]);
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $per_page);

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
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%", "%$search%"]);
$orders = $stmt->fetchAll();

include '../includes/sidebar.php';
?>

<div style="margin-left: 283px; width: 80%">
    <h2 class="mb-3" style="padding-top: 20px;">Order Management</h2>

    <div class="d-flex justify-content-between mb-3 align-items-center gap-3 no-print">
        <form method="GET" class="flex-grow-1 mb-0">
            <div class="input-group mb-0">
                <input type="text" name="search" class="form-control"
                       placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-outline-secondary" type="submit">
                    <i class="fas fa-search"></i>
                </button>
                <?php if ($search): ?>
                    <a href="orders.php" class="btn btn-outline-danger"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <div class="d-flex gap-2 align-items-center">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-1"></i> Print
            </button>
            <button onclick="window.print()" class="btn btn-success">
                <i class="fas fa-file-pdf me-1"></i> Download PDF
            </button>
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
                <?php $sn = $offset + 1; ?>
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

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="d-flex justify-content-center no-print">
        <nav aria-label="Orders pagination">
            <ul class="pagination mb-0">
                <?php
                $queryParams = [];
                if (!empty($search)) $queryParams['search'] = $search;
                ?>
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($queryParams, ['page' => $page - 1])); ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($queryParams, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($queryParams, ['page' => $page + 1])); ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>

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