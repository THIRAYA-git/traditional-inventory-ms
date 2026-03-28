<?php
// File: inventory-ms/includes/header.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../core/db_connect.php'; 
require_once __DIR__ . '/../core/alert_functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../index.php'); 
    exit;
}

// Tab session isolation - check if another tab logged in with different credentials
$session_fingerprint = md5($_SESSION['user_id'] . $_SESSION['user_role'] . $_SERVER['HTTP_USER_AGENT']);
echo "<script>
(function() {
    var fp = '" . $session_fingerprint . "';
    var storedFp = sessionStorage.getItem('session_fp');
    var isFirstLoad = sessionStorage.getItem('session_init');
    
    if (!isFirstLoad) {
        // First time in this tab - set fingerprint
        sessionStorage.setItem('session_fp', fp);
        sessionStorage.setItem('session_init', '1');
    } else if (storedFp && storedFp !== fp) {
        // Fingerprint changed - another user logged in elsewhere, logout this tab
        sessionStorage.removeItem('session_fp');
        sessionStorage.removeItem('session_init');
        alert('Another user logged in. This session has been logged out for security.');
        window.location.href = '../logout.php';
    }
})();
</script>";

$current_role = $_SESSION['user_role'];
$root_path = '../'; 
$is_admin = ($current_role === 'admin');
$user_display_name = ucfirst($current_role);
$profile_pic_src = $root_path . 'assets/img/default-avatar.png'; 

if (isset($_SESSION['user_id'])) {
    $pdo = connectDB(); 
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("SELECT Name, profile_picture FROM Users WHERE User_ID = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (!empty($user['Name'])) {
                $user_display_name = htmlspecialchars($user['Name']);
            }
            if (isset($user['profile_picture']) && !empty($user['profile_picture'])) {
                $profile_pic_src = $root_path . 'uploads/profile_pics/' . $user['profile_picture'];
            }
        }
    } catch (PDOException $e) {
        // Fallback for missing column error shown in your screenshot
        $stmt = $pdo->prepare("SELECT Name FROM Users WHERE User_ID = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) $user_display_name = htmlspecialchars($user['Name']);
    }
}

$low_stock_stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE (Minimum_Stock_Level IS NOT NULL AND Stock <= Minimum_Stock_Level) OR (Minimum_Stock_Level IS NULL AND Stock <= 0)");
$low_stock_stmt->execute();
$total_alert_count = $low_stock_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InventoSmart | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        :root {
            /* Light Mode Vars */
            --bg-color: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --border-color: #e2e8f0;
            --nav-bg: #1e293b;
        }

        [data-theme="dark"] {
            /* Deep Midnight Base */
            --bg-color: #0f172a; 
            /* Transparent Charcoal Gray for elements */
            --card-bg: rgba(30, 41, 59, 0.75); 
            /* Force Pure White Text */
            --text-main: #ffffff; 
            --text-muted: #e2e8f0; 
            --border-color: rgba(255, 255, 255, 0.15); 
            --nav-bg: #020617; 
        }

        body { 
            background-color: var(--bg-color) !important; 
            color: var(--text-main) !important; 
            padding-top: 55px; 
            transition: background 0.3s ease;
        }

        /* Global Text Overrides for Dark Mode */
        [data-theme="dark"] h1, [data-theme="dark"] h2, [data-theme="dark"] h3, 
        [data-theme="dark"] p, [data-theme="dark"] span, [data-theme="dark"] label,
        [data-theme="dark"] .table, [data-theme="dark"] td, [data-theme="dark"] th {
            color: #ffffff !important;
        }

        /* Navbar & Sidebar */
        .navbar-top {
            background-color: var(--nav-bg); 
            position: fixed; top: 0; width: 100%; z-index: 1030; height: 55px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .sidebar { 
            width: 250px; background-color: var(--nav-bg); 
            min-height: 100vh; position: fixed; left: 0;
        }
        
        /* Layout Structure */
        .content-area, .main-content { 
            margin-left: 250px; padding: 2rem; width: calc(100% - 250px); 
        }

        /* Transparent Gray Containers */
        [data-theme="dark"] .card, 
        [data-theme="dark"] .profile-card,
        [data-theme="dark"] .content-wrapper {
            background-color: var(--card-bg) !important;
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-color) !important;
            border-radius: 12px;
        }

        /* Transparent Table Styling */
        [data-theme="dark"] .table {
            background-color: transparent !important;
            border-collapse: separate;
            border-spacing: 0;
        }
        [data-theme="dark"] .table thead th {
            background-color: rgba(0, 0, 0, 0.4) !important;
            border-bottom: 1px solid var(--border-color) !important;
        }
        [data-theme="dark"] .table td {
            background-color: rgba(255, 255, 255, 0.02) !important;
            border-bottom: 1px solid var(--border-color) !important;
        }

        .nav-profile-img {
            width: 35px; height: 35px; object-fit: cover;
            border-radius: 50%; border: 2px solid rgba(255,255,255,0.2);
        }

        .theme-btn { color: white; cursor: pointer; font-size: 1.1rem; margin-right: 15px; }

        @media(max-width: 992px) { 
            .content-area, .main-content { margin-left: 0; width: 100%; } 
            .sidebar { display: none; }
        }
    </style>
</head>
<body>

<nav class="navbar-top d-flex justify-content-between align-items-center px-3">
    <a href="<?php echo $root_path . ($is_admin ? 'admin/dashboard.php' : 'employee/dashboard.php'); ?>">
        <img src="../images/crop.png" alt="logo" style="height:32px;">
    </a>

    <div class="d-flex align-items-center">
        <a href="low_stock_report.php" class="theme-btn me-2" title="Low Stock Report";">
            <i class="fas fa-bell" style="color:#fff;"></i>
        </a>

        <div class="theme-btn" id="theme-toggle" title="Switch Theme">
            <i class="fas fa-moon" id="theme-icon"></i>
        </div>

        <div class="d-flex align-items-center border-start ps-3" style="border-color: rgba(255,255,255,0.1) !important;">
            <span class="text-white me-2 d-none d-md-inline small"><?php echo $user_display_name; ?></span>
            <a href="profile.php">
                <img src="<?php echo $profile_pic_src; ?>" class="nav-profile-img shadow-sm">
            </a>
            <a href="<?php echo $root_path; ?>logout.php" class="text-white ms-3" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</nav>

<div class="wrapper d-flex">
<nav class="sidebar">
    <ul class="list-unstyled mt-4 w-100">
        <?php $page = basename($_SERVER['PHP_SELF']); ?>
        <li class="nav-item">
            <a href="dashboard.php" class="text-white text-decoration-none p-3 d-block <?php echo ($page=='dashboard.php')?'bg-primary':''; ?>">
                <i class="fas fa-chart-line me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="products.php" class="text-white text-decoration-none p-3 d-block <?php echo ($page=='products.php')?'bg-primary':''; ?>">
                <i class="fas fa-box me-2"></i> Products
            </a>
        </li>
        <li class="nav-item">
            <a href="profile.php" class="text-white text-decoration-none p-3 d-block <?php echo ($page=='profile.php')?'bg-primary':''; ?>">
                <i class="fas fa-user-circle me-2"></i> Profile
            </a>
        </li>
    </ul>
</nav>