<?php
// File: inventory-ms/admin/purchase_orders.php
include '../includes/header.php'; 

// --- SECURITY CHECK: Only Admin should access this page ---
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../employee/dashboard.php'); // Redirect non-admins
    exit;
}

$pdo = connectDB();

// --- SEARCH LOGIC ---
$search = $_GET['search'] ?? '';

// READ Operation: Fetch Purchase Orders with search filtering for Supplier, Creator, or Status
$po_stmt = $pdo->prepare("
    SELECT 
        po.PO_ID, 
        po.Order_Date, 
        po.Total_Cost, 
        po.Status,
        po.Payment_Status,
        s.Name AS Supplier_Name,
        u.Name AS Creator_Name
    FROM 
        purchase_orders po
    JOIN 
        suppliers s ON po.Supplier_ID = s.Supplier_ID
    JOIN
        users u ON po.User_ID = u.User_ID
    WHERE 
        s.Name LIKE ? 
        OR u.Name LIKE ? 
        OR po.Status LIKE ? 
        OR po.PO_ID LIKE ?
    ORDER BY 
        po.Order_Date DESC
");
$po_stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%"]);
$purchase_orders = $po_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/sidebar.php'; 
?>

<div style="margin-left: 250px; width:80%" class="content-area">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 pt-3">
            <h2 class="h3">Purchase Orders Management</h2>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print
                </button>
                <button onclick="window.print()" class="btn btn-info text-white">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <a href="create_po.php" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> New PO
                </a>
            </div>
        </div>

        <div class="row mb-4 no-print">
            <div class="col-md-5">
                <form method="GET" class="input-group">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search Supplier, Creator, or Status..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if ($search): ?>
                        <a href="purchase_orders.php" class="btn btn-outline-danger">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>PO ID</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Total Cost</th>
                        <th>Status</th>
                        <th>Payment Status</th>
                        <th>Created By</th>
                        <th class="text-center no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($purchase_orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No Purchase Orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($purchase_orders as $po): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($po['PO_ID']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($po['Order_Date'])); ?></td>
                            <td><?php echo htmlspecialchars($po['Supplier_Name']); ?></td>
                            <td>$<?php echo number_format($po['Total_Cost'], 2); ?></td>
                            <td>
                                <?php
                                    $status_class = match ($po['Status']) {
                                        'Received' => 'bg-success',
                                        'Pending' => 'bg-warning text-dark',
                                        'Ordered' => 'bg-info text-dark',
                                        'Canceled' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($po['Status']); ?></span>
                            </td>
                            <td>
                                <?php
                                    $payment_class = match ($po['Payment_Status']) {
                                        'Paid' => 'bg-success',
                                        'Pending' => 'bg-danger',
                                        'Partial' => 'bg-primary',
                                        default => 'bg-secondary',
                                    };
                                ?>
                                <span class="badge <?php echo $payment_class; ?>"><?php echo htmlspecialchars($po['Payment_Status']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($po['Creator_Name']); ?></td>
                            <td class="text-center no-print">
                                <a href="edit_po.php?id=<?php echo $po['PO_ID']; ?>" class="btn btn-sm btn-info text-white">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Print optimization: Hides non-essential UI elements */
@media print {
    .no-print, .sidebar, .navbar, .btn, .input-group, form {
        display: none !important;
    }
    div[style*="margin-left"], .content-area {
        margin-left: 0 !important;
        width: 100% !important;
    }
    .table-responsive {
        overflow: visible !important;
    }
    th.no-print, td.no-print {
        display: none !important;
    }
}

/* Your existing responsive styles */
@media screen and (max-width: 992px) {
    div[style*="margin-left"], .content-area {
        margin-left: 0 !important;
        width: 100% !important; 
        max-width: 100% !important;
        margin-right: auto !important;
        margin-left: auto !important;
        padding: 10px 6px !important; 
        box-sizing: border-box !important;
        display: block !important;
    }

    .top-header, .navbar, .d-flex.align-items-center {
        width: 100% !important;
        margin-left: 0 !important;
        display: flex !important;
        justify-content: flex-end !important;
        gap: 10px !important;
        padding: 0 10px !important;
    }

    .btn-logout, .fa-bell {
        flex-shrink: 0 !important;
    }

    .table-responsive {
        width: 100% !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        border: none !important;
    }

    th {
        white-space: nowrap !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>