<?php
// Database connection configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'inventory_ms'); 
define('DB_USER', 'root');         
define('DB_PASS', '');             
define('DB_CHARSET', 'utf8mb4');

function connectDB() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
         return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (\PDOException $e) {
         // Display a secure error message for production, detailed for development
         die("Database connection failed. Please check core/db_connect.php. Error: " . $e->getMessage());
    }
}
