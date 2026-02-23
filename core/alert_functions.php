<?php
// File: inventory-ms/core/alert_functions.php

/**
 * Fetches products that are at or below their minimum stock level.
 * @return array An array of low stock items or an empty array on failure.
 */
function getLowStockItems() {
    try {
        // Use the connectDB function defined in db_connect.php
        $pdo = connectDB(); 
        
        $stmt = $pdo->prepare("
            SELECT Product_ID, Name, Stock, Minimum_Stock_Level
            FROM products 
            WHERE Stock <= Minimum_Stock_Level
            ORDER BY Stock ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (\PDOException $e) {
        // Log the error but return gracefully
        error_log("Database Error in getLowStockItems: " . $e->getMessage());
        return [];
    }
}

function getWarehouseLowStock() {
    $pdo = connectDB(); 
    try {
        // Added ws.id to act as a serial/row identifier
        $query = "SELECT ws.id AS Alert_ID, 
                         p.product_id AS Product_ID, 
                         p.name AS Product_Name, 
                         w.name AS Warehouse_Name, 
                         ws.quantity AS Current_Stock
                  FROM warehouse_stock ws
                  JOIN products p ON ws.product_id = p.product_id
                  JOIN warehouses w ON ws.warehouse_id = w.warehouse_id
                  WHERE ws.quantity < 20
                  GROUP BY p.product_id, w.warehouse_id
                  ORDER BY p.name ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return [];
    }
}