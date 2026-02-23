<?php
// 1. Correct Path to core folder
require_once '../core/db_connect.php'; 

if (!function_exists('connectDB')) {
    die("Fatal Error: Database connection function not found.");
}

$pdo = connectDB();
$message = "";

// 2. Combined Transfer Logic (Hub-to-WH and WH-to-WH)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['execute_transfer'])) {
    $product_id = $_POST['product_id'];
    $from_source = $_POST['from_source']; // 'HUB' or a warehouse_id
    $to_wh = $_POST['to_warehouse'];
    $qty = (int)$_POST['quantity'];

    if ($from_source == $to_wh) {
        $message = "<div class='alert alert-danger'>Source and Destination cannot be the same.</div>";
    } else {
        try {
            $pdo->beginTransaction();

            // STEP A: DEDUCT FROM SOURCE
            if ($from_source === 'HUB') {
                $stmt = $pdo->prepare("UPDATE products SET Stock = Stock - ? WHERE Product_ID = ? AND Stock >= ?");
                $stmt->execute([$qty, $product_id, $qty]);
                $from_id_for_log = null; // Represents HUB in your log
            } else {
                $stmt = $pdo->prepare("UPDATE warehouse_stock SET quantity = quantity - ? WHERE warehouse_id = ? AND product_id = ? AND quantity >= ?");
                $stmt->execute([$qty, $from_source, $product_id, $qty]);
                $from_id_for_log = $from_source;
            }

            if ($stmt->rowCount() === 0) {
                throw new Exception("Insufficient stock at the selected source.");
            }

            // STEP B: ADD TO DESTINATION
            $add = $pdo->prepare("INSERT INTO warehouse_stock (warehouse_id, product_id, quantity) 
                                 VALUES (?, ?, ?) 
                                 ON DUPLICATE KEY UPDATE quantity = quantity + ?");
            $add->execute([$to_wh, $product_id, $qty, $qty]);

            // ==========================================
            // STEP C: THE FIX - INSERT INTO HISTORY LOG
            // ==========================================
            $log_query = "INSERT INTO stock_history 
                         (product_id, from_warehouse_id, to_warehouse_id, quantity, transaction_type, created_at) 
                         VALUES (?, ?, ?, ?, 'transfer', NOW())";
            $log_stmt = $pdo->prepare($log_query);
            $log_stmt->execute([$product_id, $from_id_for_log, $to_wh, $qty]);
            // ==========================================

            $pdo->commit();
            $message = "<div class='alert alert-success'>Stock transfer completed and logged successfully!</div>";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

include('../includes/header.php'); 
include('../includes/sidebar.php');
?>

<div class="content-area" style="margin-left: 250px; padding: 20px;">
    <div class="container-fluid">
        <h3 class="mb-4">Internal Stock Distribution & Inter-Warehouse Transfer</h3>
        <?php echo $message; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="fw-bold">Select Product</label>
                            <select name="product_id" class="form-select" required>
                                <option value="">-- Choose Product --</option>
                                <?php
                                $stmt = $pdo->query("SELECT Product_ID, Name FROM products");
                                while ($row = $stmt->fetch()) {
                                    echo "<option value='{$row['Product_ID']}'>{$row['Name']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">From (Source)</label>
                            <select name="from_source" class="form-select" required>
                                <option value="HUB">Global Hub (Main Inventory)</option>
                                <?php
                                $stmt = $pdo->query("SELECT warehouse_id, name FROM warehouses");
                                while ($row = $stmt->fetch()) {
                                    echo "<option value='{$row['warehouse_id']}'>Warehouse: {$row['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">To (Destination)</label>
                            <select name="to_warehouse" class="form-select" required>
                                <option value="">-- Select Destination Warehouse --</option>
                                <?php
                                $stmt = $pdo->query("SELECT warehouse_id, name FROM warehouses");
                                while ($row = $stmt->fetch()) {
                                    echo "<option value='{$row['warehouse_id']}'>{$row['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="fw-bold">Quantity</label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                    </div>

                    <button type="submit" name="execute_transfer" class="btn btn-primary w-100 mt-3">
                        Execute Transfer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>