<?php
include('../includes/header.php'); 
$pdo = connectDB();

try {
    $pdo->beginTransaction();

    // 1. Create 3 Test Warehouses
    $warehouses = ['North Distribution', 'South Annex', 'East Cold Storage'];
    foreach ($warehouses as $name) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO warehouses (name, location, status) VALUES (?, 'Test Location', 'active')");
        $stmt->execute([$name]);
    }

    // 2. Get IDs for Warehouses and Products
    $w_ids = $pdo->query("SELECT warehouse_id FROM warehouses")->fetchAll(PDO::FETCH_COLUMN);
    $p_ids = $pdo->query("SELECT product_id FROM products")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($p_ids)) {
        echo "<div class='alert alert-warning'>Please add some products first!</div>";
    } else {
        // 3. Add Random Stock to each warehouse
        foreach ($w_ids as $wid) {
            foreach ($p_ids as $pid) {
                $qty = rand(10, 500); // Random quantity between 10 and 500
                $stmt = $pdo->prepare("INSERT INTO warehouse_stock (warehouse_id, product_id, quantity) 
                                       VALUES (?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                $stmt->execute([$wid, $pid, $qty, $qty]);
            }
        }
        $pdo->commit();
        echo "<div class='alert alert-success'>Successfully seeded random stock for testing!</div>";
    }
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>