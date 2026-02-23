<?php
include('../includes/header.php'); 
include('../includes/sidebar.php');
$pdo = connectDB();

function generateEAN13() {
    $digits = "";
    for ($i = 0; $i < 12; $i++) {
        $digits .= random_int(0, 9);
    }
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += $digits[$i] * ($i % 2 === 0 ? 1 : 3);
    }
    $checkDigit = (10 - ($sum % 10)) % 10;
    return $digits . $checkDigit;
}

try {
    // 1. Automatically find the Primary Key column name
    $pkQuery = $pdo->query("SHOW KEYS FROM products WHERE Key_name = 'PRIMARY'");
    $pkData = $pkQuery->fetch();
    $primaryKey = $pkData['Column_name']; // This will be 'product_id', 'id', etc.

    // 2. Fetch products missing barcodes using the detected key
    $stmt = $pdo->query("SELECT $primaryKey FROM products WHERE barcode IS NULL OR barcode = ''");
    $products = $stmt->fetchAll();

    if (count($products) > 0) {
        $pdo->beginTransaction();
        $updateStmt = $pdo->prepare("UPDATE products SET barcode = ? WHERE $primaryKey = ?");

        $success_count = 0;
        foreach ($products as $p) {
            $newBarcode = generateEAN13();
            if ($updateStmt->execute([$newBarcode, $p[$primaryKey]])) {
                $success_count++;
            }
        }
        $pdo->commit();
        $status = "success";
        $msg = "Success! Generated <strong>$success_count</strong> barcodes using column: <code>$primaryKey</code>";
    } else {
        $status = "info";
        $msg = "All products already have barcodes.";
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $status = "danger";
    $msg = "Error: " . $e->getMessage();
}
?>

<div class="content-area">
    <div class="container-fluid">
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Barcode Automation Tool</h5>
            </div>
            <div class="card-body text-center py-5">
                <div class="alert alert-<?php echo $status; ?> d-inline-block px-5">
                    <?php echo $msg; ?>
                </div>
                <div class="mt-4">
                    <a href="generate_barcodes.php" class="btn btn-primary">
                        <i class="fas fa-barcode"></i> View & Print Labels
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>