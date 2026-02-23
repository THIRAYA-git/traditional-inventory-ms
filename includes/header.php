<?php
// File: inventory-ms/includes/header.php

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// >>> CRITICAL FIX: INCLUDE DB CONNECTION FILE HERE <<<
require_once __DIR__ . '/../core/db_connect.php'; 
require_once __DIR__ . '/../core/alert_functions.php';

// --- SECURITY CHECK AND REDIRECTION ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../index.php'); 
    exit;
}

$current_role = $_SESSION['user_role'];
$root_path = '../'; 
$is_admin = ($current_role === 'admin');
$user_display_name = ucfirst($current_role);

if (isset($_SESSION['user_id'])) {
    $pdo = connectDB(); 

    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT Name FROM users WHERE User_ID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['Name'])) {
        $user_display_name = htmlspecialchars($user['Name']);
    } else {
        $user_display_name = 'User (' . ucfirst($current_role) . ')';
    }
}


// --- LOW STOCK CHECK LOGIC (UPDATED FOR TOTAL ALERTS) ---
$pdo = connectDB(); 

// 1. Get Global Low Stock Count
$low_stock_stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE Stock <= Minimum_Stock_Level");
$low_stock_stmt->execute();
$global_low_count = $low_stock_stmt->fetchColumn();

// 2. Get Warehouse Specific Alerts Count
// We use the existing function from alert_functions.php
$warehouse_alerts = function_exists('getWarehouseLowStock') ? getWarehouseLowStock() : [];
$warehouse_low_count = count($warehouse_alerts);

// 3. Final Sum for Badge
$total_alert_count = $global_low_count + $warehouse_low_count;


// --- HTML START ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InventoSmart | <?php echo ucfirst($current_role); ?> Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 56px; 
        }
        .navbar-top {
            background-color: #2c3e50; 
            color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 10px 20px;
            position: fixed; 
            top: 0;
            width: 100%;
            z-index: 1030;
            height: 50px;
        }
        .wrapper {
            display: flex; 
            min-height: 100vh;
            width: 100%;
        }
        .content-area {
            flex-grow: 1; 
            padding: 1.25em;
        }
    </style>
</head>
<body>

<nav class="navbar-top d-flex justify-content-between align-items-center" style="padding:0;">
    <a href="<?php echo $root_path; ?><?php echo $is_admin ? 'admin' : 'dashboard.php'; ?>" >
      <img id="hideLogo" style="height:3.2rem; width:15.65rem;" src="../images/crop.png" alt="logo">
    </a>

    <div class="d-flex align-items-center" style="padding-right: 1rem;">
        <?php if ($is_admin): ?>
        <a href="<?php echo $root_path; ?>admin/low_stock_report.php" class="btn position-relative me-3 text-white" title="Low Stock Alerts">
            <i class="fas fa-bell fa-lg"
               style="<?php echo ($total_alert_count > 0) ? 'color: #ffc107;' : 'color: #ccc;'; ?>"></i>

            <?php if ($total_alert_count > 0): ?>
                <span class="position-absolute badge rounded-pill bg-danger p-1" style="font-size: 0.6em; top: 0px; right: 0px; transform: translate(10%, -10%);">
                    <?php echo $total_alert_count; ?>
                    <span class="visually-hidden">Low Stock Alerts</span>
                </span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <span class="text-white me-3 d-none d-sm-inline"><?php echo $user_display_name; ?></span>
        <a href="<?php echo $root_path; ?>logout.php" class="btn btn-outline-light btn-sm">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</nav>
<div class="wrapper">

<style>
    .sidebar {
        width: 250px;
        flex-shrink: 0; 
        min-height: 100vh;
        background-color: #212529; 
        color: white;
        padding: 0;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    }
    .sidebar .sidebar-header {
        padding: 1.25em;
        text-align: center;
        font-size: 1.5rem;
        font-weight: bold;
        background-color: #157347; 
    }
    .sidebar ul.components {
        padding: 1.25em 0;
        list-style: none;
    }
    .sidebar ul li a {
        padding: 15px 20px;
        font-size: 1.1em;
        display: block;
        color: #adb5bd; 
        text-decoration: none;
        transition: all 0.3s;
    }
    .sidebar ul li a:hover {
        color: #fff;
        background: #343a40; 
    }
    .sidebar ul li.active > a, 
    .sidebar ul li.active > a:hover {
        color: #fff;
        background: #0d6efd; 
    }
    .sidebar ul li a i {
        margin-right: 10px;
        width: 1.25em;
        text-align: center;
    }

    .content-area {
        margin-left: 250px; 
        flex-grow: 1; 
        padding: 1.5rem;
        width: 100%;
        overflow-x: hidden;
    }

    @media(max-width: 992px) { 
        .content-area {
            margin-left: 0 !important; 
            width: 100% !important; 
        }
    }
</style>

<nav class="sidebar">
    <ul class="components">
        <?php 
        $current_page = basename($_SERVER['PHP_SELF']);
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        
        if ($is_admin): ?>
        <li class="<?php echo ($current_page == 'dashboard.php' ? 'active' : ''); ?>">
            <a href="<?php echo $root_path; ?>admin/dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
        </li>
        <li class="<?php echo ($current_page == 'products.php' && $current_dir == 'admin' ? 'active' : ''); ?>">
            <a href="<?php echo $root_path; ?>admin/products.php"><i class="fas fa-box"></i> Products</a>
        </li>
        <li class="<?php echo ($current_page == 'categories.php' ? 'active' : ''); ?>">
            <a href="<?php echo $root_path; ?>admin/categories.php"><i class="fas fa-list-alt"></i> Categories</a>
        </li>
        <li class="<?php echo ($current_page == 'orders.php' && $current_dir == 'admin' ? 'active' : ''); ?>">
            <a href="<?php echo $root_path; ?>admin/orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
        </li>
        <li class="<?php echo ($current_page == 'purchase_orders.php' || $current_page == 'create_po.php' || $current_page == 'edit_po.php' ? 'active' : ''); ?>">
            <a href="<?php echo $root_path; ?>admin/purchase_orders.php"><i class="fas fa-file-invoice"></i> P. Orders</a>
        </li>
        <li class="<?php echo ($current_page == 'suppliers.php' ? 'active' : ''); ?>">
            <a href="<?php echo $root_path; ?>admin/suppliers.php"><i class="fas fa-truck"></i> Suppliers</a>
        </li>
        <li class="<?php echo ($current_page == 'users.php' ? 'active' : ''); ?>">
            <a href="<?php echo $root_path; ?>admin/users.php"><i class="fas fa-users"></i> Users</a>
        </li>
        <?php else: ?>
        <li class="<?php echo ($current_page == 'products.php' && $current_dir == 'employee' ? 'active' : ''); ?>">
            <a href="<?php echo $root_path; ?>employee/products.php"><i class="fas fa-box-open"></i> Products</a>
        </li>
        <li class="<?php echo ($current_page == 'orders.php' && $current_dir == 'employee' ? 'active' : ''); ?>">
            <a href="<?php echo $root_path; ?>employee/orders.php"><i class="fas fa-receipt"></i> Orders</a>
        </li>
        <?php endif; ?>
        <li class="<?php echo ($current_page == 'profile.php' ? 'active' : ''); ?>">
            <a href="<?php echo $root_path; ?><?php echo $is_admin ? 'admin' : 'employee'; ?>/profile.php"><i class="fas fa-user-circle"></i> Profile</a>
        </li>
        <li>
            <a href="<?php echo $root_path; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </li>
    </ul>
</nav>

