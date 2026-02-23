<?php
require_once '../core/db_connect.php'; 
$pdo = connectDB();

header('Content-Type: application/json');

$filter = $_GET['filter'] ?? 'month';
$data = [];

if ($filter === 'week') {
    // Get last 7 days of purchase orders
    $sql = "SELECT DATE_FORMAT(Order_Date, '%a') as label, SUM(Total_Price) as total 
            FROM orders 
            WHERE Order_Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(Order_Date) 
            ORDER BY Order_Date ASC";
} else {
    // Get last 6 months of purchase orders
    $sql = "SELECT DATE_FORMAT(Order_Date, '%b') as label, SUM(Total_Price) as total 
            FROM orders 
            WHERE Order_Date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY MONTH(Order_Date) 
            ORDER BY Order_Date ASC";
}

$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
exit;