<?php
include('../includes/header.php'); 
include('../includes/sidebar.php');
$pdo = connectDB();

$message = ""; // Initialize an empty message variable

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $warehouse_id = $_POST['warehouse_id'];
    $new_qty = $_POST['quantity'];

    try {
        // Update or Insert the stock level
        $stmt = $pdo->prepare("INSERT INTO warehouse_stock (warehouse_id, product_id, quantity) 
                               VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE quantity = ?");
        $stmt->execute([$warehouse_id, $product_id, $new_qty, $new_qty]);

        // Log this in history
        $stmt_log = $pdo->prepare("INSERT INTO stock_history (product_id, to_warehouse_id, quantity, transaction_type) VALUES (?, ?, ?, 'adjustment')");
        $stmt_log->execute([$product_id, $warehouse_id, $new_qty]);

        // Store success message in variable instead of echoing immediately
        $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                        Stock adjusted successfully!
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

$warehouses = $pdo->query("SELECT * FROM warehouses")->fetchAll();
$products = $pdo->query("SELECT * FROM products")->fetchAll();
?>

<div class="content-area">
    <div class="container-fluid">
        <h2 class="mb-4">Manual Stock Adjustment</h2>

        <?php if (!empty($message)) echo $message; ?>

        <div class="card shadow">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Warehouse</label>
                            <select name="warehouse_id" class="form-select" required>
                                <option value="">Select Warehouse</option>
                                <?php foreach($warehouses as $w): ?>
                                    <option value="<?= $w['warehouse_id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Product</label>
                            <select name="product_id" class="form-select" required>
                                <option value="">Select Product</option>
                                <?php foreach($products as $p): ?>
                                    <option value="<?= $p['Product_ID'] ?>"><?= htmlspecialchars($p['Name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label">New Quantity</label>
                            <input type="number" name="quantity" class="form-control" min="0" required>
                        </div>

                        <div class="col-md-2 mb-3 d-grid align-items-end">
                            <button type="submit" class="btn btn-warning">Update Stock</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>