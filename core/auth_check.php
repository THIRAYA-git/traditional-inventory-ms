<?php
// File: inventory-ms/core/auth_check.php

/**
 * Checks if the logged-in user has the required access role.
 * Redirects to the appropriate dashboard or login page if access is denied.
 * @param string $required_role The role required ('admin' or 'user').
 */
function check_access($required_role) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_role'])) {
        header('Location: ../index.php');
        exit;
    }

    $user_role = $_SESSION['user_role'];

    if ($user_role !== $required_role) {
        if ($user_role === 'admin') {
            header('Location: ../admin/dashboard.php');
        } elseif ($user_role === 'user' || $user_role === 'employee') {
            header('Location: ../employee/products.php');
        } else {
            header('Location: ../logout.php');
        }
        exit;
    }
}
?>