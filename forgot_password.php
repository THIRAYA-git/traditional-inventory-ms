<?php
// forgot_password.php
include 'core/db_connect.php'; 

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ⚠️ CORRECTED PATH: Use this path based on your folder structure ⚠️
require_once 'PHPMailer/src/Exception.php'; 
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

$pdo = connectDB();
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = 'Please enter your email address.';
        $message_type = 'danger';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT User_ID FROM Users WHERE Email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $user_id = $user['User_ID'];
                $token = bin2hex(random_bytes(32)); 
                $expires_at = date('Y-m-d H:i:s', strtotime('+60 minutes'));
                
                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $token, $expires_at]);
                
                // Adjust 'yourwebsite.com' to your actual domain/localhost path
                $reset_link = "http://localhost/traditional-inventory-ms/reset_password.php?token=" . $token;

                $mail = new PHPMailer(true);

                try {
                    // ⚠️ CONFIGURE THESE FOR YOUR GMAIL/SMTP ACCOUNT ⚠️
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'thirayafathima@gmail.com'; 
                    $mail->Password   = 'ryje unjc myfp smwb'; 
                    $mail->SMTPSecure = 'tls'; 
                    $mail->Port       = 587;

                    $mail->setFrom('no-reply@yourwebsite.com', 'Password Reset');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request';
                    $mail->Body    = '
                        <h1>Password Reset</h1>
                        <p>Click the link below to reset your password. Valid for 60 minutes:</p>
                        <p><a href="' . $reset_link . '">' . $reset_link . '</a></p>
                    ';
                    $mail->AltBody = 'To reset your password, visit the link: ' . $reset_link;

                    $mail->send();

                } catch (Exception $e) {
                    // error_log("Mailer Error: {$mail->ErrorInfo}");
                }
                
            } 
            
            $message = 'If an account exists for ' . htmlspecialchars($email) . ', a password reset link has been sent.';
            $message_type = 'success';


        } catch (PDOException $e) {
    // Change this temporarily to see the specific error
    $message = 'Debug Error: ' . $e->getMessage(); 
    $message_type = 'danger';
}
    }
}
?>
<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Inventory Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* YOUR CUSTOM STYLES */
        :root {
            --primary-green: #44627be1;
            --primary-dark: #515d82ff;
            --bg-light: #6a93d1ff;
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

        .login-wrapper {
            width: 100%;
            max-width: 420px;
        }

        .header-green {
            background: var(--primary-green);
            padding: 28px 0;
            border-radius: 12px 12px 0 0;
            color: #ffffffff;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.12);
        }

        .header-green h2 {
            font-size: 1.45rem;
            font-weight: 600;
            margin: 0;
        }

        .login-box {
            background: #ffffffe6;
            padding: 32px 28px;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }

        .login-box h3 {
            font-size: 1.25rem;
            margin-bottom: 20px;
            font-weight: 600;
            color: var(--primary-dark);
            letter-spacing: 0.5px;
        }

        .form-control {
            height: 48px;
            border-radius: 8px;
            border: 1px solid #a2bfdcff;
            transition: 0.2s;
        }

        .form-control:focus {
            border-color: #a2bfdcff;
            box-shadow: 0 0 0 0.15rem rgba(21, 127, 79, 0.25);
        }

        .btn-success {
            background-color: var(--primary-green) !important;
            border-color: var(--primary-green) !important;
            height: 48px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: 0.25s;
        }

        .btn-success:hover {
            background-color: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 10px;
            padding: 12px;
        }
        #forgetPassword{
            color: gray;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="header-green">
            <h2>Inventory Management System</h2>
        </div>
        
        <div class="login-box">
            <h3 class="text-center">Password Reset</h3>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> text-center">
        <?php echo $message; ?>
        </div>
        <?php endif; ?>

            <form action="forgot_password.php" method="POST">
                <p class="text-muted text-center mb-4">Enter your email to receive a password reset link.</p>
                
                <div class="mb-3">
                    <label for="email_input" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email_input" name="email" placeholder="Enter Email" required>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-success">Send Reset Link</button>
                </div>
                
                <div class="text-center">
                    <a href="login.php" id="forgetPassword" class="text-decoration-none">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>