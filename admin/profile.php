<?php
include '../includes/header.php'; // Includes session_start() and security check (Employee role)
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
    
    if (empty($name) || empty($email)) {
        $message = '<div class="alert alert-danger">Name and Email are required fields.</div>';
    } else {
        try {
            $sql = "UPDATE Users SET Name = ?, Email = ?, Address = ?";
            $params = [$name, $email, $address];
            
            if (!empty($password)) {
                // Hash and update the new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", Password = ?";
                $params[] = $hashed_password;
            }
            
            $sql .= " WHERE User_ID = ?";
            $params[] = $user_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Update session variables if email or name changed
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            $message = '<div class="alert alert-success">Profile updated successfully!</div>';
        } catch (PDOException $e) {
             if ($e->getCode() == '23000') {
                $message = '<div class="alert alert-danger">Error: Email already exists for another user.</div>';
            } else {
                // In a real application, log this error
                $message = '<div class="alert alert-danger">Database error. Could not update profile.</div>';
            }
        }
    }
}

// Check if we should switch to edit mode (from JavaScript or form submission error)
if (isset($_POST['edit_mode']) || strpos($message, 'danger') !== false) {
    $edit_mode = true;
}


// --- 2. FETCH CURRENT USER DATA (READ) ---
$stmt = $pdo->prepare("SELECT Name, Email, Address FROM Users WHERE User_ID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // Should not happen if session is valid
    header('Location: ../logout.php');
    exit;
}

include '../includes/sidebar.php'; 
?>

<div style="margin-left: 283px; width:80%">
<h2 class="mb-4" style="padding-top: 20px;">User Profile</h2>
<?php echo $message; ?>

<div class="card shadow-sm" style="max-width: 450px;">
    <div class="card-body">
        <form action="profile.php" method="POST" id="profileForm">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <input type="hidden" name="edit_mode" id="editModeIndicator" value="<?php echo $edit_mode ? '1' : '0'; ?>">

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?php echo htmlspecialchars($user['Name']); ?>" 
                       <?php echo $edit_mode ? '' : 'readonly'; ?> required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($user['Email']); ?>" 
                       <?php echo $edit_mode ? '' : 'readonly'; ?> required>
            </div>
            
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" class="form-control" id="address" name="address" 
                       value="<?php echo htmlspecialchars($user['Address']); ?>" 
                       <?php echo $edit_mode ? '' : 'readonly'; ?>>
            </div>

            <div class="mb-3 password-field" style="<?php echo $edit_mode ? '' : 'display: none;'; ?>">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Enter new password (optional)">
            </div>
            
            <div id="viewButtons" style="<?php echo $edit_mode ? 'display: none;' : ''; ?>">
                <button type="button" class="btn btn-warning w-100" id="editBtn">Edit Profile</button>
            </div>

            <div id="editButtons" style="<?php echo $edit_mode ? '' : 'display: none;'; ?>">
                <button type="submit" name="save_profile" class="btn btn-success">Save Changes</button>
                <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
            </div>
        </form>
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
        const editModeIndicator = document.getElementById('editModeIndicator');
        const viewButtons = document.getElementById('viewButtons');
        const editButtons = document.getElementById('editButtons');

        function setReadOnly(readOnly) {
            inputs.forEach(input => {
                if (input.id !== 'password') {
                    input.readOnly = readOnly;
                }
            });
            passwordField.style.display = readOnly ? 'none' : '';
            viewButtons.style.display = readOnly ? '' : 'none';
            editButtons.style.display = readOnly ? 'none' : '';
            editModeIndicator.value = readOnly ? '0' : '1';
        }

        // Toggle to edit mode
        if (editBtn) {
            editBtn.addEventListener('click', function() {
                setReadOnly(false);
            });
        }

        // Cancel edit mode
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                // Reload the page to revert changes and exit edit mode
                window.location.href = 'profile.php'; 
            });
        }
        
        // Initial setup based on server-side flag (in case of validation error)
        if (editModeIndicator.value === '1') {
            setReadOnly(false);
        } else {
             setReadOnly(true);
        }
    });
</script>


<?php include '../includes/footer.php'; ?>