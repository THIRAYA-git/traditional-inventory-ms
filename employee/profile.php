<?php
include '../includes/header.php'; 
$pdo = connectDB();
$user_id = $_SESSION['user_id'];
$message = '';
$edit_mode = false;

// --- 1. HANDLE PROFILE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $profile_pic = $_FILES['profile_pic'];
    
    if (empty($name) || empty($email)) {
        $message = '<div class="alert alert-danger shadow-sm">Name and Email are required fields.</div>';
    } else {
        try {
            $sql = "UPDATE Users SET Name = ?, Email = ?, Address = ?";
            $params = [$name, $email, $address];
            
            if (!empty($profile_pic['name'])) {
                $target_dir = "../uploads/profile_pics/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                
                $file_extension = pathinfo($profile_pic['name'], PATHINFO_EXTENSION);
                $new_filename = "emp_" . $user_id . "_" . time() . "." . $file_extension;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($profile_pic['tmp_name'], $target_file)) {
                    $sql .= ", profile_picture = ?";
                    $params[] = $new_filename;
                }
            }

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", Password = ?";
                $params[] = $hashed_password;
            }
            
            $sql .= " WHERE User_ID = ?";
            $params[] = $user_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $message = '<div class="alert alert-success shadow-sm">Profile updated successfully!</div>';
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger shadow-sm">Database error occurred.</div>';
        }
    }
}

if (isset($_POST['edit_mode']) || strpos($message, 'danger') !== false) {
    $edit_mode = true;
}

// --- 2. FETCH CURRENT USER DATA ---
$stmt = $pdo->prepare("SELECT Name, Email, Address, profile_picture FROM Users WHERE User_ID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$profile_img_path = !empty($user['profile_picture']) ? "../uploads/profile_pics/" . $user['profile_picture'] : "../assets/img/default-avatar.png";

include '../includes/sidebar.php'; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    :root {
        --profile-bg: #f8f9fa;
        --profile-card: #ffffff;
        --profile-text: #212529;
        --profile-text-muted: #6c757d;
        --profile-border: #dee2e6;
        --profile-input-bg: #ffffff;
        --profile-input-readonly-bg: #f1f3f5;
    }
    [data-theme="dark"] {
        --profile-bg: #0f172a;
        --profile-card: rgba(30, 41, 59, 0.9);
        --profile-text: #ffffff;
        --profile-text-muted: #a0aec0;
        --profile-border: rgba(255, 255, 255, 0.15);
        --profile-input-bg: rgba(30, 41, 59, 0.9);
        --profile-input-readonly-bg: rgba(0, 0, 0, 0.3);
    }

    /* Main layout adjustment */
    .main-content {
        margin-left: 280px;
        padding: 30px;
        background-color: var(--profile-bg);
        min-height: 100vh;
        transition: background-color 0.3s ease;
    }
    .profile-card {
        border: none;
        border-radius: 15px;
        background: var(--profile-card);
        border: 1px solid var(--profile-border);
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }
    [data-theme="dark"] .profile-card {
        backdrop-filter: blur(12px);
    }
    .profile-header-bg {
        background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);
        height: 120px;
        border-radius: 15px 15px 0 0;
    }
    .avatar-container {
        margin-top: -60px;
        position: relative;
        display: inline-block;
        margin-bottom: 20px;
    }
    .avatar-preview {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        border: 5px solid var(--profile-card);
        object-fit: cover;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .upload-badge {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background: #2193b0;
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 3px solid var(--profile-card);
    }
    .form-label {
        font-weight: 600;
        color: var(--profile-text);
        font-size: 0.85rem;
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    .form-control-custom {
        border-radius: 8px;
        padding: 12px 15px;
        border: 1px solid var(--profile-border);
        background-color: var(--profile-input-bg);
        color: var(--profile-text);
        transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
    }
    .form-control-custom:read-only {
        background-color: var(--profile-input-readonly-bg);
        border-color: transparent;
    }
    [data-theme="dark"] .form-control-custom:read-only {
        color: var(--profile-text-muted);
    }

    .page-title { color: var(--profile-text); }
    .page-subtitle { color: var(--profile-text-muted); }
    .user-name { color: var(--profile-text); }
    .user-email { color: var(--profile-text-muted); }
    .divider { border-color: var(--profile-border); }
</style>

<div class="main-content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-8">
                
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h3 class="fw-bold mb-0 page-title">User Settings</h3>
                    <span class="badge rounded-pill bg-info text-dark px-3 py-2">Employee Portal</span>
                </div>

                <?php echo $message; ?>

                <div class="card profile-card shadow-sm">
                    <div class="profile-header-bg"></div>
                    <div class="card-body px-4 pb-4">
                        <form action="profile.php" method="POST" id="profileForm" enctype="multipart/form-data">
                            
                            <div class="text-center">
                                <div class="avatar-container">
                                    <img src="<?php echo $profile_img_path; ?>" id="avatarImage" class="avatar-preview">
                                    <label for="profile_pic" class="upload-badge" id="uploadBtnArea" style="<?php echo $edit_mode ? '' : 'display: none;'; ?>">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                    <input type="file" id="profile_pic" name="profile_pic" hidden accept="image/*">
                                </div>
                                <h4 class="fw-bold mb-1 user-name"><?php echo htmlspecialchars($user['Name']); ?></h4>
                                <p class="small mb-4 user-email"><?php echo htmlspecialchars($user['Email']); ?></p>
                            </div>

                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                            <input type="hidden" name="edit_mode" id="editModeIndicator" value="<?php echo $edit_mode ? '1' : '0'; ?>">

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control form-control-custom" name="name" 
                                           value="<?php echo htmlspecialchars($user['Name']); ?>" 
                                           <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control form-control-custom" name="email" 
                                           value="<?php echo htmlspecialchars($user['Email']); ?>" 
                                           <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Office Address</label>
                                    <input type="text" class="form-control form-control-custom" name="address" 
                                           value="<?php echo htmlspecialchars($user['Address']); ?>" 
                                           <?php echo $edit_mode ? '' : 'readonly'; ?>>
                                </div>
                                <div class="col-12 password-field" style="<?php echo $edit_mode ? '' : 'display: none;'; ?>">
                                    <label class="form-label">Update Password</label>
                                    <input type="password" class="form-control form-control-custom" name="password" 
                                           placeholder="Type new password or leave blank">
                                </div>
                            </div>

                            <hr class="my-4 divider opacity-25">

                            <div id="viewButtons" class="text-center" style="<?php echo $edit_mode ? 'display: none;' : ''; ?>">
                                <button type="button" class="btn btn-primary px-5 py-2 fw-bold rounded-pill" id="editBtn">
                                    <i class="fas fa-user-edit me-2"></i>Edit Profile
                                </button>
                            </div>

                            <div id="editButtons" class="text-center" style="<?php echo $edit_mode ? '' : 'display: none;'; ?>">
                                <button type="submit" name="save_profile" class="btn btn-success px-4 py-2 fw-bold rounded-pill me-2">
                                    Save Changes
                                </button>
                                <button type="button" class="btn btn-light px-4 py-2 rounded-pill shadow-sm" id="cancelBtn">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('profileForm');
        const editBtn = document.getElementById('editBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const inputs = form.querySelectorAll('input:not([type="hidden"])');
        const passwordField = form.querySelector('.password-field');
        const uploadBtnArea = document.getElementById('uploadBtnArea');
        const editModeIndicator = document.getElementById('editModeIndicator');
        const viewButtons = document.getElementById('viewButtons');
        const editButtons = document.getElementById('editButtons');
        const fileInput = document.getElementById('profile_pic');
        const avatarImage = document.getElementById('avatarImage');

        function setReadOnly(readOnly) {
            inputs.forEach(input => {
                if (input.type !== 'file' && input.name !== 'password') {
                    input.readOnly = readOnly;
                }
            });
            passwordField.style.display = readOnly ? 'none' : 'block';
            uploadBtnArea.style.display = readOnly ? 'none' : 'block';
            viewButtons.style.display = readOnly ? 'block' : 'none';
            editButtons.style.display = readOnly ? 'none' : 'block';
            editModeIndicator.value = readOnly ? '0' : '1';
        }

        if (editBtn) editBtn.addEventListener('click', () => setReadOnly(false));
        if (cancelBtn) cancelBtn.addEventListener('click', () => window.location.href = 'profile.php');

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => avatarImage.src = e.target.result;
                reader.readAsDataURL(this.files[0]);
            }
        });

        setReadOnly(editModeIndicator.value !== '1');
    });
</script>

<?php include '../includes/footer.php'; ?>