<?php
include '../includes/header.php'; // Includes session_start() and security check
// include '../core/db_connect.php'; 

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
            
            // Handle Profile Picture Upload
            if (!empty($profile_pic['name'])) {
                $target_dir = "../uploads/profile_pics/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                
                $file_extension = pathinfo($profile_pic['name'], PATHINFO_EXTENSION);
                $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
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
             if ($e->getCode() == '23000') {
                $message = '<div class="alert alert-danger shadow-sm">Error: Email already exists.</div>';
            } else {
                $message = '<div class="alert alert-danger shadow-sm">Database error: ' . $e->getMessage() . '</div>';
            }
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

if (!$user) {
    header('Location: ../logout.php');
    exit;
}

// Default profile pic if none exists
$profile_img_path = !empty($user['profile_picture']) ? "../uploads/profile_pics/" . $user['profile_picture'] : "../assets/img/default-avatar.png";

include '../includes/sidebar.php'; 
?>

<style>
    .profile-card { border: none; border-radius: 15px; overflow: hidden; }
    .profile-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100px; }
    .avatar-wrapper { position: relative; margin-top: -60px; display: inline-block; }
    .avatar-preview { width: 120px; height: 120px; border-radius: 50%; border: 5px solid white; object-fit: cover; background: #eee; }
    .upload-btn-wrapper { position: absolute; bottom: 5px; right: 5px; }
    .btn-circle { width: 35px; height: 35px; border-radius: 50%; padding: 0; display: flex; align-items: center; justify-content: center; }
    .form-control:read-only { background-color: #f8f9fa; border-color: transparent; }
    .form-control:focus { box-shadow: 0 0 0 0.25rem rgba(118, 75, 162, 0.25); }
</style>

<div style="margin-left: 283px; padding: 40px 20px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold text-dark m-0">Account Settings</h2>
                    <span class="badge bg-soft-primary text-primary">Employee Account</span>
                </div>

                <?php echo $message; ?>

                <div class="card profile-card shadow">
                    <div class="profile-header"></div>
                    <div class="card-body text-center pt-0">
                        
                        <form action="profile.php" method="POST" id="profileForm" enctype="multipart/form-data">
                            <div class="avatar-wrapper mb-4">
                                <img src="<?php echo $profile_img_path; ?>" id="avatarImage" class="avatar-preview shadow-sm">
                                <div class="upload-btn-wrapper" id="uploadBtnArea" style="<?php echo $edit_mode ? '' : 'display: none;'; ?>">
                                    <label for="profile_pic" class="btn btn-primary btn-circle shadow">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                    <input type="file" id="profile_pic" name="profile_pic" hidden accept="image/*">
                                </div>
                            </div>

                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                            <input type="hidden" name="edit_mode" id="editModeIndicator" value="<?php echo $edit_mode ? '1' : '0'; ?>">

                            <div class="text-start">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label small fw-bold text-muted">Full Name</label>
                                        <input type="text" class="form-control form-control-lg" name="name" 
                                               value="<?php echo htmlspecialchars($user['Name']); ?>" 
                                               <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label small fw-bold text-muted">Email Address</label>
                                        <input type="email" class="form-control form-control-lg" name="email" 
                                               value="<?php echo htmlspecialchars($user['Email']); ?>" 
                                               <?php echo $edit_mode ? '' : 'readonly'; ?> required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label small fw-bold text-muted">Residential Address</label>
                                        <input type="text" class="form-control form-control-lg" name="address" 
                                               value="<?php echo htmlspecialchars($user['Address']); ?>" 
                                               <?php echo $edit_mode ? '' : 'readonly'; ?>>
                                    </div>

                                    <div class="col-md-12 mb-4 password-field" style="<?php echo $edit_mode ? '' : 'display: none;'; ?>">
                                        <label class="form-label small fw-bold text-muted">Update Password</label>
                                        <input type="password" class="form-control form-control-lg" name="password" 
                                               placeholder="Leave blank to keep current">
                                    </div>
                                </div>
                            </div>

                            <div id="viewButtons" style="<?php echo $edit_mode ? 'display: none;' : ''; ?>">
                                <button type="button" class="btn btn-primary px-5 py-2 fw-bold" id="editBtn">
                                    <i class="fas fa-edit me-2"></i>Edit Profile
                                </button>
                            </div>

                            <div id="editButtons" style="<?php echo $edit_mode ? '' : 'display: none;'; ?>">
                                <button type="submit" name="save_profile" class="btn btn-success px-4 py-2 fw-bold">
                                    Save Changes
                                </button>
                                <button type="button" class="btn btn-light px-4 py-2" id="cancelBtn">Cancel</button>
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

        if (editBtn) {
            editBtn.addEventListener('click', () => setReadOnly(false));
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => window.location.href = 'profile.php');
        }

        // Image Preview Logic
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => avatarImage.src = e.target.result;
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Initialize state
        setReadOnly(editModeIndicator.value !== '1');
    });
</script>

<?php include '../includes/footer.php'; ?>