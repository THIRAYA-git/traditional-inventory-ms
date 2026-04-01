<?php
require_once 'db_connect.php'; 

function verifyLogin($email, $password) {
    $pdo = connectDB();
    
    // Use prepared statements for secure login
    $stmt = $pdo->prepare("SELECT User_ID, Password, Role, is_active FROM Users WHERE Email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user) {
        // Securely compare the submitted password against the stored hash
        if (password_verify($password, $user['Password'])) {
            if (!$user['is_active']) {
                return "Your account has been deactivated. Please contact admin.";
            }
            // Login successful
            session_start();
            $_SESSION['user_id'] = $user['User_ID'];
            $_SESSION['role'] = $user['Role'];
            
            // Redirect based on role
            if ($user['Role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: employee/products.php'); 
            }
            exit();
        }
    }

    // Login failed
    return "User not found or incorrect password.";
}
?>