<?php
/**
 * reset_password.php
 * Handles the second half of the "Forgot Password" flow.
 * Verifies the unique token and updates the user's password in the database.
 */

include 'core/db_connect.php'; 

$pdo = connectDB();
$message = '';
$message_type = 'danger'; // Default to error styling
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$can_reset = false;

// 1. Initial Token Check (on page load via GET link)
if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $reset_record = $stmt->fetch();

        if ($reset_record) {
            $current_time = new DateTime();
            $expiry_time = new DateTime($reset_record['expires_at']);

            if ($current_time > $expiry_time) {
                $message = 'The password reset link has expired. Please request a new one.';
            } else {
                $can_reset = true; // Token is valid, allows form to display
            }
        } else {
            $message = 'Invalid or used password reset token.';
        }
    } catch (PDOException $e) {
        $message = 'A system error occurred. Please try again later.';
    }
}

// 2. Handle Password Submission (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $post_token = $_POST['token'];

    try {
        // Re-verify token validity (Security: prevents manipulation between load and submit)
        $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
        $stmt->execute([$post_token]);
        $reset_record = $stmt->fetch();

        if ($reset_record) {
            $current_time = new DateTime();
            $expiry_time = new DateTime($reset_record['expires_at']);

            if ($current_time > $expiry_time) {
                $message = 'Session expired. Please request a new link.';
            } elseif ($new_password !== $confirm_password) {
                $message = 'Passwords do not match.';
                $can_reset = true; // Keep form open
            } elseif (strlen($new_password) < 8) {
                $message = 'New password must be at least 8 characters long.';
                $can_reset = true; // Keep form open
            } else {
                // SUCCESS: Perform the update
                $user_id = $reset_record['user_id'];
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $pdo->beginTransaction();

                // Update the User table
                $update_stmt = $pdo->prepare("UPDATE Users SET Password = ? WHERE User_ID = ?");
                $update_stmt->execute([$hashed_password, $user_id]);

                // Invalidate the token immediately so it cannot be used again
                $delete_stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                $delete_stmt->execute([$post_token]);

                $pdo->commit();

                // Redirect to login with success flag
                header('Location: login.php?reset_success=1');
                exit;
            }
        } else {
            $message = 'Invalid token submission.';
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = 'Could not update password. Please contact support.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-green: #44627be1;
            --primary-dark: #515d82ff;
        }
        body {
            font-family: "Segoe UI", sans-serif;
            background: rgba(48, 72, 142, 0.25);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }
        .login-wrapper { width: 100%; max-width: 420px; }
        .header-green {
            background: var(--primary-green);
            padding: 28px 0;
            border-radius: 12px 12px 0 0;
            color: #ffffff;
            text-align: center;
        }
        .login-box {
            background: #ffffff;
            padding: 32px 28px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        .btn-success {
            background-color: var(--primary-green) !important;
            border: none;
            height: 48px;
            font-weight: 600;
        }
        .btn-success:hover { background-color: var(--primary-dark) !important; }
        #link-alt { color: #6c757d; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="header-green">
            <h2>Inventory System</h2>
        </div>
        
        <div class="login-box">
            <h3 class="text-center mb-4" style="color: var(--primary-dark); font-weight:600;">Reset Password</h3>

            <?php if ($message): ?>
                <div class="alert alert-danger text-center"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($can_reset): ?>
                <form action="reset_password.php" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" placeholder="Min. 8 characters" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Repeat password" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">Update Password</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center">
                    <p class="text-muted">Please request a new reset link to proceed.</p>
                    <a href="forgot_password.php" class="btn btn-outline-secondary w-100">Go to Forgot Password</a>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="login.php" id="link-alt">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>