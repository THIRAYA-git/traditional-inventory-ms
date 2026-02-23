<?php
session_start();
include 'core/db_connect.php';

$pdo = connectDB();
$message = '';
$messageType = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'user'; // Default role for new signups

    if (empty($name) || empty($email) || empty($password)) {
        $message = 'All fields are required.';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT Email FROM Users WHERE Email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $message = 'Email is already registered.';
            } else {
                // Hash password and insert user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO Users (Name, Email, Password, Role) VALUES (?, ?, ?, ?)");
                
                if ($stmt->execute([$name, $email, $hashedPassword, $role])) {
                    $message = 'Registration successful! You can now login.';
                    $messageType = 'success';
                }
            }
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IMS - Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Reuse your login styles here */
        :root { --primary-green: #44627be1; --primary-dark: #515d82ff; }
        body { background: rgba(48, 72, 142, 0.25); min-height: 100vh; display: flex; justify-content: center; align-items: center; }
        .login-wrapper { width: 100%; max-width: 420px; }
        .header-green { background: var(--primary-green); padding: 28px 0; border-radius: 12px 12px 0 0; color: #fff; text-align: center; }
        .login-box { background: #ffffffe6; padding: 32px 28px; border-radius: 0 0 12px 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.12); }
        .btn-success { background-color: var(--primary-green) !important; border: none; height: 48px; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="header-green"><h2>Inventory Management System</h2></div>
        <div class="login-box">
            <h3 class="text-center">Create Account</h3>
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> text-center"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="signup.php" method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="name" placeholder="John Doe" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" placeholder="email@example.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Create Password" required>
                </div>
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-success">Sign Up</button>
                </div>
                <div class="text-center">
                    <p>Already have an account? <a href="login.php" style="color: var(--primary-dark); text-decoration: none; font-weight: bold;">Login</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>