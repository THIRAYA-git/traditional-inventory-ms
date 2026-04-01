<?php
session_start();
include 'core/db_connect.php';

$pdo = connectDB();
$message = '';

if (isset($_SESSION['user_role'])) {
    $role = strtolower($_SESSION['user_role']); 
    if ($role === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } elseif ($role === 'user' || $role === 'employee') {
        header('Location: employee/products.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = 'Email and Password are required.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT User_ID, Name, Email, Password, Role, is_active FROM Users WHERE Email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['Password'])) {
                if (!$user['is_active']) {
                    $message = 'Your account has been deactivated. Please contact admin.';
                } else {
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['User_ID'];
                    $_SESSION['user_name'] = $user['Name'];
                    $_SESSION['user_role'] = $user['Role'];
                    $_SESSION['login_time'] = time();

                    $dbRole = strtolower(trim($user['Role']));

                    echo "<script>
                    sessionStorage.setItem('session_fp', '" . md5($user['User_ID'] . $user['Role'] . $_SERVER['HTTP_USER_AGENT']) . "');
                    sessionStorage.setItem('session_init', '1');
                    </script>";

                    if ($dbRole === 'admin') {
                        header('Location: admin/dashboard.php');
                        exit;
                    } else if ($dbRole === 'user' || $dbRole === 'employee') {
                        header('Location: employee/products.php');
                        exit;
                    } else {
                        $message = "Role '{$user['Role']}' not recognized. Please contact admin.";
                    }
                }
            } else {
                $message = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $message = 'Database error. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
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
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
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
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }

        .login-box h3 {
            font-size: 1.35rem;
            margin-bottom: 20px;
            font-weight: 600;
            color: var(--primary-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            height: 48px;
            border-radius: 8px;
            border: 1px solid #a2bfdcff;
            transition: 0.2s;
        }

        /* Adjusting border radius for input groups */
        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .input-group .btn {
            border: 1px solid #a2bfdcff;
            border-left: none;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            background-color: white;
            color: gray;
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

        #forgetPassword {
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
            <h3 class="text-center">Login</h3>

            <?php if ($message): ?>
                <div class="alert alert-danger text-center">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" placeholder="Enter Email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="password" id="passwordInput" placeholder="Enter Password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="text-center mb-3">
                    <a href="forgot_password.php" id="forgetPassword" style="color: gray; text-decoration: none;">Forget Your Password?</a>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Login</button>
                </div>
                <div class="text-center mt-3">
                    <span>Don't have an account? </span>
                    <a href="signup.php" style="color: var(--primary-dark); text-decoration: none; font-weight: bold;">Sign Up</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#passwordInput');

        togglePassword.addEventListener('click', function () {
            // Toggle the type attribute
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle the eye icon
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    </script>

</body>
</html>