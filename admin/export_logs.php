<?php
// 1. Correct the path to reach core/ from the admin/ folder
require_once(__DIR__ . '/../core/db_connect.php');

// 2. IMPORTANT: You must CALL the function defined in your db_connect.php
try {
    $pdo = connectDB(); // This initializes the connection defined in your core file
} catch (Exception $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// 3. Ensure the connection exists before running the query
if (!$pdo) {
    die("Error: connectDB() failed to return a valid PDO instance.");
}

try {
    // Fetch logs with joined product and warehouse names
    $query = "SELECT h.created_at, p.Name as product, h.transaction_type, 
                     w1.name as from_warehouse, w2.name as to_warehouse, h.quantity 
              FROM stock_history h
              JOIN products p ON h.product_id = p.Product_ID
              LEFT JOIN warehouses w1 ON h.from_warehouse_id = w1.warehouse_id
              LEFT JOIN warehouses w2 ON h.to_warehouse_id = w2.warehouse_id
              ORDER BY h.created_at DESC";
              
    $stmt = $pdo->query($query);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Clean output buffer to ensure no PHP warnings leak into the CSV
    if (ob_get_length()) ob_end_clean();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Stock_History_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    // Set Header Row
    fputcsv($output, ['Date & Time', 'Product', 'Type', 'From', 'To', 'Quantity']);

    foreach ($logs as $row) {
        fputcsv($output, [
            $row['created_at'],
            $row['product'],
            ucfirst($row['transaction_type']),
            $row['from_warehouse'] ?: 'Manual Adjustment',
            $row['to_warehouse'] ?: 'N/A',
            $row['quantity']
        ]);
    }
    fclose($output);
    exit();

} catch (PDOException $e) {
    die("Export Failed: " . $e->getMessage());
}