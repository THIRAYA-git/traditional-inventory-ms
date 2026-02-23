<?php
include '../includes/header.php'; // Includes session_start() and security check

$pdo = connectDB();
$message = '';

// --- 1. HANDLE CRUD OPERATIONS ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');
    $category_id = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);

    if (empty($name)) {
        $message = '<div class="alert alert-danger">Category Name is required.</div>';
    } else {
        try {
            if ($category_id) {
                // UPDATE Operation
                $stmt = $pdo->prepare("UPDATE Categories SET Name = ?, Description = ? WHERE Category_ID = ?");
                $stmt->execute([$name, $description, $category_id]);
                $message = '<div class="alert alert-success">Category updated successfully!</div>';
            } else {
                // CREATE Operation
                $stmt = $pdo->prepare("INSERT INTO Categories (Name, Description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                $message = '<div class="alert alert-success">Category added successfully!</div>';
            }
        } catch (PDOException $e) {
            // Check for duplicate name error
            if ($e->getCode() == '23000') {
                $message = '<div class="alert alert-danger">Error: A category with this name already exists.</div>';
            } else {
                $message = '<div class="alert alert-danger">Database Error: Could not process category.</div>';
            }
        }
    }
}

// B. DELETE Operation
if (isset($_GET['delete_id'])) {
    $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    
    try {
        // Check if any product is linked to this category before deleting
        $check = $pdo->prepare("SELECT COUNT(*) FROM Products WHERE Category_ID = ?");
        $check->execute([$delete_id]);
        if ($check->fetchColumn() > 0) {
             $message = '<div class="alert alert-danger">Cannot delete: Category is linked to existing products.</div>';
        } else {
            $stmt = $pdo->prepare("DELETE FROM Categories WHERE Category_ID = ?");
            $stmt->execute([$delete_id]);
            $message = '<div class="alert alert-success">Category deleted successfully!</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting category: ' . $e->getMessage() . '</div>';
    }
}

// C. READ Operation (Fetch all categories)
$search_query = $_GET['search'] ?? '';
$sql = "SELECT * FROM Categories";

$where_clause = '';
$params = [];

if (!empty($search_query)) {
    $where_clause = " WHERE Name LIKE ? OR Description LIKE ? ";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
}

$sql .= $where_clause . " ORDER BY Name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll();

include '../includes/sidebar.php'; 
?>
<div style="margin-left: 283px; width:80%">
<h2 style="padding-top: 20px;" class="mb-4">Category Management</h2>
<?php echo $message; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white fw-bold">Add New Category</div>
            <div class="card-body">
                <form action="categories.php" method="POST">
                    <div class="mb-3">
                        <label for="create_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="create_name" name="name" placeholder="Enter category name" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_description" class="form-label">Description</label>
                        <textarea class="form-control" id="create_description" name="description" rows="2" placeholder="Category description (optional)"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add Category</button>
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
                               placeholder="Search categories..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                        <?php if (!empty($search_query)): ?>
                            <a href="categories.php" class="btn btn-outline-danger" title="Clear Search"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 10%">ID</th>
                                <th style="width: 45%">Name</th>
                                <th style="width: 25%" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($categories) == 0): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No categories found.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($categories as $c): ?>
                            <tr>
                                <td><?php echo $c['Category_ID']; ?></td>
                                <td><?php echo $c['Name']; ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info text-white me-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal"
                                            data-id="<?php echo $c['Category_ID']; ?>"
                                            data-name="<?php echo htmlspecialchars($c['Name']); ?>"
                                            data-desc="<?php echo htmlspecialchars($c['Description']); ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="categories.php?delete_id=<?php echo $c['Category_ID']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('WARNING: Deleting a category is permanent and will fail if linked to products. Are you sure?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
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

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="categories.php" method="POST">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="editModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<style>@media screen and (max-width: 992px) {
    /* 1. LOCK CONTENT WIDTH ONLY */
    div[style*="margin-left"] {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100vw !important;
        padding: 12px !important;
        box-sizing: border-box !important;
        display: block !important;
    }

    /* 2. BUTTON ALIGNMENT (Delete on Left, Edit on Right) */
    td:last-child {
        display: flex !important;
        flex-direction: row-reverse !important;
        justify-content: center !important;
        gap: 8px !important;
        padding: 8px 5px !important;
    }

    /* 3. SLIM TABLE ACTION BUTTONS (Edit/Delete) */
    /* Keeps these slim as requested previously */
    td .btn-sm, td .btn {
        width: auto !important;
        padding: 3px 8px !important;
        margin: 0 !important;
        font-size: 11px !important;
    }

    /* 4. PRESERVE HEADER & MAKE LOGOUT BIGGER */
    header, .top-header, .navbar {
        width: 100% !important;
        margin-left: 0 !important;
        padding: 0 10px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
    }

    /* Target the Logout button specifically to increase its size */
    .btn-logout, a[href*="logout"], button[type*="logout"] {
        padding: 6px 12px !important; /* Larger padding for a bigger button */
        font-size: 13px !important;    /* Slightly larger text */
        flex-shrink: 0 !important;      /* Prevents it from getting smaller */
        display: inline-flex !important;
        align-items: center !important;
    }

    /* Ensure Bell icon also doesn't shrink */
    .fa-bell, #notificationBell {
        font-size: 18px !important;
        flex-shrink: 0 !important;
        margin-right: 5px !important;
    }
}
</style>
<script>
    var editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        
        // Get data attributes from the button
        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var desc = button.getAttribute('data-desc');

        // Target the modal input fields
        editModal.querySelector('#edit_category_id').value = id;
        editModal.querySelector('#edit_name').value = name;
        editModal.querySelector('#edit_description').value = desc;
    });
</script>

<?php include '../includes/footer.php'; ?>