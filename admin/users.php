<?php
include '../includes/header.php'; // Includes session_start() and security check
// include '../core/db_connect.php'; 

$pdo = connectDB();
$message = '';

// --- 1. HANDLE CRUD OPERATIONS (CREATE/UPDATE) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    // Note: The role field now passes 'user' instead of 'employee'
    $role = trim($_POST['role']);
    $address = trim($_POST['address']);
    $user_id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    $password = $_POST['password'] ?? null;
    $password_confirm = $_POST['password_confirm'] ?? null;

    // Basic validation
    if (empty($name) || empty($email) || empty($role)) {
        $message = '<div class="alert alert-danger">Name, Email, and Role are required fields.</div>';
    } elseif ($user_id === null && empty($password)) { // Password required for CREATE
        $message = '<div class="alert alert-danger">Password is required for a new user.</div>';
    } elseif ($password && $password !== $password_confirm) { // Password confirmation for both CREATE and UPDATE
         $message = '<div class="alert alert-danger">New password fields do not match.</div>';
    } else {
        try {
            $hashed_password = null;
            if ($password) {
                // Only hash and set password if a new one is provided
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            }

            if ($user_id) {
                // UPDATE Operation
                $sql = "UPDATE Users SET Name = ?, Email = ?, Role = ?, Address = ?";
                $params = [$name, $email, $role, $address];
                
                if ($hashed_password) {
                    $sql .= ", Password = ?";
                    $params[] = $hashed_password;
                }
                $sql .= " WHERE User_ID = ?";
                $params[] = $user_id;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $message = '<div class="alert alert-success">User updated successfully!</div>';
            } else {
                // CREATE Operation
                $stmt = $pdo->prepare("INSERT INTO Users (Name, Email, Password, Role, Address) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashed_password, $role, $address]);
                $message = '<div class="alert alert-success">User added successfully!</div>';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $message = '<div class="alert alert-danger">Error: Email already exists.</div>';
            } else {
                $message = '<div class="alert alert-danger">Database Error: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// B. TOGGLE ACTIVE/INACTIVE STATUS
if (isset($_GET['toggle_id'])) {
    $toggle_id = filter_var($_GET['toggle_id'], FILTER_VALIDATE_INT);

    // Prevent admin from deactivating their own account
    if ($toggle_id == $_SESSION['user_id']) {
        $message = '<div class="alert alert-danger">You cannot deactivate your own account.</div>';
    } else {
        try {
            // Get current status
            $check = $pdo->prepare("SELECT is_active FROM Users WHERE User_ID = ?");
            $check->execute([$toggle_id]);
            $current = $check->fetchColumn();

            if ($current === false) {
                $message = '<div class="alert alert-danger">User not found.</div>';
            } else {
                $new_status = $current ? 0 : 1;
                $action = $new_status ? 'activated' : 'deactivated';
                $stmt = $pdo->prepare("UPDATE Users SET is_active = ? WHERE User_ID = ?");
                $stmt->execute([$new_status, $toggle_id]);
                $message = '<div class="alert alert-success">User ' . $action . ' successfully!</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error updating user status: ' . $e->getMessage() . '</div>';
        }
    }
}

// C. READ Operation (Fetch all users including inactive)
$search_query = $_GET['search'] ?? '';
$sql = "SELECT User_ID, Name, Email, Role, Address, is_active FROM Users";

$where_clause = '';
$params = [];

if (!empty($search_query)) {
    $where_clause = " WHERE Name LIKE ? OR Email LIKE ? OR Role LIKE ? ";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
}

$sql .= $where_clause . " ORDER BY is_active ASC, Role DESC, Name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

include '../includes/sidebar.php'; 
?>

<div style="margin-left: 283px; width:80%">
<h2 style="padding-top: 20px;" class="mb-4">User Management</h2>
<?php echo $message; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white fw-bold">Add New User</div>
            <div class="card-body">
                <form action="users.php" method="POST">
                    <div class="mb-3">
                        <label for="create_name" class="form-label">User Name</label>
                        <input type="text" class="form-control" id="create_name" name="name" placeholder="Enter Name" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_email" class="form-label">User Email</label>
                        <input type="email" class="form-control" id="create_email" name="email" placeholder="Enter Email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="create_password" name="password" placeholder="******" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_password_confirm" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="create_password_confirm" name="password_confirm" placeholder="******" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_address" class="form-label">User Address</label>
                        <input type="text" class="form-control" id="create_address" name="address" placeholder="Enter Address">
                    </div>
                    <div class="mb-3">
                        <label for="create_role" class="form-label">Select Role</label>
                        <select class="form-select" id="create_role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="Employee">Employee</option>
                            <option value="user">User</option> 
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add User</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search users..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                        <?php if (!empty($search_query)): ?>
                            <a href="users.php" class="btn btn-outline-danger" title="Clear Search"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 5%">ID</th>
                                <th style="width: 25%">Name</th>
                                <th style="width: 30%">Email</th>
                                <th style="width: 15%">Role</th>
                                <th style="width: 10%">Status</th>
                                <th style="width: 20%" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No users found.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo $u['User_ID']; ?></td>
                                <td><?php echo $u['Name']; ?></td>
                                <td><?php echo $u['Email']; ?></td>
                                <td>
                                    <?php
                                    if ($u['Role'] == 'admin'):
                                        echo 'Admin';
                                    else:
                                        echo 'User';
                                    endif;
                                    ?>
                                </td>
                                <td>
                                    <?php if ($u['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($u['is_active']): ?>
                                        <a href="users.php?toggle_id=<?php echo $u['User_ID']; ?>"
                                           class="btn btn-sm btn-warning"
                                           onclick="return confirm('Deactivate user <?php echo $u['Name']; ?>?');"
                                           <?php echo ($u['User_ID'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-ban"></i> Deactivate
                                        </a>
                                    <?php else: ?>
                                        <a href="users.php?toggle_id=<?php echo $u['User_ID']; ?>"
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Activate user <?php echo $u['Name']; ?>?');">
                                            <i class="fas fa-check"></i> Activate
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- <form action="users.php" method="POST">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="editModalLabel">Edit User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                     <div class="mb-3">
                        <label for="edit_address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="edit_address" name="address">
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">New Password (optional)</label>
                        <input type="password" class="form-control" id="edit_password" name="password" placeholder="Leave blank to keep current password">
                    </div>
                    <div class="mb-3">
                        <label for="edit_password_confirm" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="edit_password_confirm" name="password_confirm">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white">Save Changes</button>
                </div>
            </form> -->
        </div>
    </div>
</div>

<script>
    var editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        
        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var email = button.getAttribute('data-email');
        var address = button.getAttribute('data-address');
        var role = button.getAttribute('data-role');

        editModal.querySelector('#edit_user_id').value = id;
        editModal.querySelector('#edit_name').value = name;
        editModal.querySelector('#edit_email').value = email;
        editModal.querySelector('#edit_address').value = address;
        
        // Handle role setting: If the saved role is 'employee', set the select to 'user'
        if (role === 'employee') {
            editModal.querySelector('#edit_role').value = 'user';
        } else {
            editModal.querySelector('#edit_role').value = role;
        }

        // Clear password fields on modal open
        editModal.querySelector('#edit_password').value = '';
        editModal.querySelector('#edit_password_confirm').value = '';
    });
</script>

<?php include '../includes/footer.php'; ?>