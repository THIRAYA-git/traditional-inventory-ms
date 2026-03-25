<?php
ob_start();
// File: inventory-ms/admin/edit_po.php
include '../includes/header.php'; 
// include '../core/db_connect.php'; 

// Security Check
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../employee/dashboard.php'); 
    exit;
}

$pdo = connectDB();
$po_id = $_GET['id'] ?? null;
$error_message = '';
$success_message = '';

if (!$po_id || !is_numeric($po_id)) {
    header('Location: purchase_orders.php');
    exit;
}

// ----------------------------------------------------
// 1. Handle Status Update POST Request
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $new_status = $_POST['status'] ?? null;
    $new_payment_status = $_POST['payment_status'] ?? null;

    $pdo->beginTransaction();
    try {
        // A. Check if the status is being updated to 'Received'
        // This is the trigger to update inventory stock
        if ($new_status === 'Received') {
            
            // Fetch the destination warehouse for this PO
            $warehouse_stmt = $pdo->prepare("SELECT Warehouse_ID FROM purchase_orders WHERE PO_ID = ?");
            $warehouse_stmt->execute([$po_id]);
            $warehouse_id = $warehouse_stmt->fetchColumn();

            // Fetch all products and quantities associated with this PO
            $details_stmt = $pdo->prepare("SELECT Product_ID, Quantity FROM po_details WHERE PO_ID = ?");
            $details_stmt->execute([$po_id]);
            $details = $details_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Loop through details and update both product stock and warehouse stock
            foreach ($details as $item) {
                // Update general product stock
                $update_stock = $pdo->prepare("UPDATE products SET Stock = Stock + ? WHERE Product_ID = ?");
                $update_stock->execute([$item['Quantity'], $item['Product_ID']]);

                // Update warehouse stock (insert or update)
                if ($warehouse_id) {
                    $update_warehouse_stock = $pdo->prepare("
                        INSERT INTO warehouse_stock (warehouse_id, product_id, quantity) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE quantity = quantity + ?
                    ");
                    $update_warehouse_stock->execute([$warehouse_id, $item['Product_ID'], $item['Quantity'], $item['Quantity']]);
                }
            }
        }
        
        // B. Update the PO Header Statuses
        $update_po = $pdo->prepare("
            UPDATE purchase_orders 
            SET Status = ?, Payment_Status = ? 
            WHERE PO_ID = ?
        ");
        $update_po->execute([$new_status, $new_payment_status, $po_id]);

        $pdo->commit();
        $success_message = "Purchase Order #{$po_id} statuses updated successfully. Inventory stock adjusted if set to 'Received'.";
        // To refresh data after update, we redirect or clear post
        header("Location: edit_po.php?id={$po_id}&success=" . urlencode($success_message));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Error updating PO: " . $e->getMessage();
    }
}


// ----------------------------------------------------
// 2. Fetch Existing PO Data
// ----------------------------------------------------

// Fetch PO Header
$po_header_stmt = $pdo->prepare("
    SELECT po.*, s.Name AS Supplier_Name, u.Name AS Creator_Name 
    FROM purchase_orders po
    JOIN suppliers s ON po.Supplier_ID = s.Supplier_ID
    JOIN users u ON po.User_ID = u.User_ID
    WHERE po.PO_ID = ?
");
$po_header_stmt->execute([$po_id]);
$po = $po_header_stmt->fetch(PDO::FETCH_ASSOC);

if (!$po) {
    header('Location: purchase_orders.php');
    exit;
}

// Fetch PO Line Items
$po_details_stmt = $pdo->prepare("
    SELECT pod.*, p.Name AS Product_Name
    FROM po_details pod
    JOIN products p ON pod.Product_ID = p.Product_ID
    WHERE pod.PO_ID = ?
");
$po_details_stmt->execute([$po_id]);
$po_details = $po_details_stmt->fetchAll(PDO::FETCH_ASSOC);


// Get messages from GET parameters after redirection
if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}


include '../includes/sidebar.php'; 
?>

<div style="margin-left: 250px; width:80%" class="content-area">
    <div class="container-fluid">
        <h2 class="h3 pt-3 mb-4">Edit Purchase Order #<?php echo htmlspecialchars($po['PO_ID']); ?></h2>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php elseif ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                Purchase Order Summary
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Supplier:</strong> <?php echo htmlspecialchars($po['Supplier_Name']); ?></p>
                        <p><strong>Created By:</strong> <?php echo htmlspecialchars($po['Creator_Name']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Order Date:</strong> <?php echo date('Y-m-d H:i', strtotime($po['Order_Date'])); ?></p>
                        <p><strong>Expected Delivery:</strong> <?php echo htmlspecialchars($po['Expected_Delivery'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="h4 text-end"><strong>Total Cost:</strong> <span class="text-primary">$<?php echo number_format($po['Total_Cost'], 2); ?></span></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-info text-white">Update Status & Payment</div>
            <div class="card-body">
                <form method="POST" action="edit_po.php?id=<?php echo $po['PO_ID']; ?>">
                    <input type="hidden" name="action" value="update_status">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="status" class="form-label">Order Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <?php $statuses = ['Pending', 'Ordered', 'Received', 'Canceled']; ?>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo $status; ?>" 
                                            <?php if ($po['Status'] === $status) echo 'selected'; ?>>
                                        <?php echo $status; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-danger">NOTE: Setting status to 'Received' will add quantities to inventory stock.</small>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select class="form-select" id="payment_status" name="payment_status" required>
                                <?php $payment_statuses = ['Pending', 'Paid', 'Partial']; ?>
                                <?php foreach ($payment_statuses as $p_status): ?>
                                    <option value="<?php echo $p_status; ?>" 
                                            <?php if ($po['Payment_Status'] === $p_status) echo 'selected'; ?>>
                                        <?php echo $p_status; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end mb-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sync"></i> Update
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header bg-secondary text-white">Products Ordered</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50%;">Product Name</th>
                                <th style="width: 20%;">Quantity</th>
                                <th style="width: 15%;">Unit Cost</th>
                                <th style="width: 15%;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($po_details as $detail): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detail['Product_Name']); ?></td>
                                <td><?php echo htmlspecialchars($detail['Quantity']); ?></td>
                                <td>$<?php echo number_format($detail['Unit_Cost'], 2); ?></td>
                                <td>$<?php echo number_format($detail['Quantity'] * $detail['Unit_Cost'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                <td><strong>$<?php echo number_format($po['Total_Cost'], 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>