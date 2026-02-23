<?php
include '../includes/header.php'; // Includes session_start() and security check

$pdo = connectDB();
$message = '';

// --- 1. HANDLE CRUD OPERATIONS ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $supplier_id = filter_var($_POST['supplier_id'] ?? null, FILTER_VALIDATE_INT);

    if (empty($name) || empty($email) || empty($phone)) {
        $message = '<div class="alert alert-danger">Name, Email, and Phone are required fields.</div>';
    } else {
        try {
            if ($supplier_id) {
                // UPDATE Operation
                $stmt = $pdo->prepare("UPDATE Suppliers SET Name = ?, Email = ?, Phone = ?, Address = ? WHERE Supplier_ID = ?");
                $stmt->execute([$name, $email, $phone, $address, $supplier_id]);
                $message = '<div class="alert alert-success">Supplier updated successfully!</div>';
            } else {
                // CREATE Operation
                $stmt = $pdo->prepare("INSERT INTO Suppliers (Name, Email, Phone, Address) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $address]);
                $message = '<div class="alert alert-success">Supplier added successfully!</div>';
            }
        } catch (PDOException $e) {
            // Check for duplicate entry (Email is often unique)
            if ($e->getCode() == '23000') {
                $message = '<div class="alert alert-danger">Error: Email or Name already exists.</div>';
            } else {
                $message = '<div class="alert alert-danger">Database Error: Could not process supplier.</div>';
            }
        }
    }
}

// B. DELETE Operation
if (isset($_GET['delete_id'])) {
    $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    
    try {
        // Check if any product is linked to this supplier before deleting
        $check = $pdo->prepare("SELECT COUNT(*) FROM Products WHERE Supplier_ID = ?");
        $check->execute([$delete_id]);
        if ($check->fetchColumn() > 0) {
             $message = '<div class="alert alert-danger">Cannot delete: Supplier is linked to existing products.</div>';
        } else {
            $stmt = $pdo->prepare("DELETE FROM Suppliers WHERE Supplier_ID = ?");
            $stmt->execute([$delete_id]);
            $message = '<div class="alert alert-success">Supplier deleted successfully!</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error deleting supplier: ' . $e->getMessage() . '</div>';
    }
}

// C. READ Operation (Fetch all suppliers)
$search_query = $_GET['search'] ?? '';
$sql = "SELECT * FROM Suppliers";

$where_clause = '';
$params = [];

if (!empty($search_query)) {
    $where_clause = " WHERE Name LIKE ? OR Email LIKE ? OR Phone LIKE ? ";
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
}

$sql .= $where_clause . " ORDER BY Name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$suppliers = $stmt->fetchAll();

include '../includes/sidebar.php'; 
?>

<div style="margin-left: 283px; width:80%">
<h2 class="mb-4" style="padding-top: 20px;">Supplier Management</h2>
<?php echo $message; ?>

<div class="d-flex justify-content-between mb-4">
    <form method="GET" class="w-75">
        <div class="input-group">
            <input type="text" class="form-control" name="search" 
                   placeholder="Search suppliers..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
            <?php if (!empty($search_query)): ?>
                <a href="suppliers.php" class="btn btn-outline-danger" title="Clear Search"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </div>
    </form>
    
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="fas fa-plus me-2"></i> Add Supplier
    </button>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($suppliers) == 0): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No suppliers found.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($suppliers as $s): ?>
            <tr>
                <td><?php echo $s['Supplier_ID']; ?></td>
                <td><?php echo $s['Name']; ?></td>
                <td><?php echo $s['Email']; ?></td>
                <td><?php echo $s['Phone']; ?></td>
                <td><?php echo $s['Address']; ?></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-info text-white me-1" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editModal"
                            data-id="<?php echo $s['Supplier_ID']; ?>"
                            data-name="<?php echo htmlspecialchars($s['Name']); ?>"
                            data-email="<?php echo htmlspecialchars($s['Email']); ?>"
                            data-phone="<?php echo htmlspecialchars($s['Phone']); ?>"
                            data-address="<?php echo htmlspecialchars($s['Address']); ?>">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <a href="suppliers.php?delete_id=<?php echo $s['Supplier_ID']; ?>" 
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('WARNING: Deleting a supplier is permanent. Are you sure?');">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="suppliers.php" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="createModalLabel">Add New Supplier</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="create_name" name="name" placeholder="Supplier name" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="create_email" name="email" placeholder="Supplier email" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="create_phone" name="phone" placeholder="Supplier phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="create_address" name="address" placeholder="Supplier address">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="suppliers.php" method="POST">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="editModalLabel">Edit Supplier</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="supplier_id" id="edit_supplier_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="edit_phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="edit_address" name="address">
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
<style>
/* 1. ELIMINATE TOP PADDING FOR HEADING */
/* Pulls title to the top to match the 'before' container alignment */
h2.mb-4 {
    padding-top: 0 !important; 
    margin-top: 0 !important;
    margin-bottom: 10px !important;
}

@media screen and (max-width: 992px) {
    /* 2. MATCH CONTAINER ALIGNMENT */
    div[style*="margin-left"], .content-area {
        margin-left: 0 !important;
        width: 96% !important;
        margin: 0 auto !important;
        padding: 0px 5px 10px 5px !important; /* Zero top padding */
        box-sizing: border-box !important;
    }

    /* 3. TARGET TABLE BUTTONS ONLY - DECREASE SPACE */
    /* Scoping to 'td' ensures the top Logout button is not interrupted */
    td .btn-sm {
        margin-bottom: 0 !important;
        padding: 6px 12px !important; /* Reduced from 13.5px to decrease row height */
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        height: 32px !important;
    }

    /* 4. TIGHTEN ACTION COLUMN */
    /* Using smaller padding here directly decreases the overall cell height */
    td.text-center, .table td:last-child {
        display: flex !important;
        flex-direction: row-reverse !important; 
        justify-content: center !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 8px 5px !important; /* Tight padding to minimize vertical space */
    }

    /* 5. PROTECT LOGOUT BUTTON */
    /* Ensures the logout button remains compact regardless of table changes */
    .btn-outline-light, a[href*="logout"] {
        padding: 4px 10px !important;
        font-size: 14px !important;
    }

    /* 6. HEADER ORGANIZATION */
    .d-flex.justify-content-between.mb-4 {
        flex-direction: column !important;
        gap: 8px !important;
    }

    .btn-primary[data-bs-target="#createModal"] {
        width: 100% !important;
        max-width: 250px !important;
        order: -1; 
        margin: 0 auto !important;
    }
}
</style>
<!-- <style>
/* 1. REMOVE EXCESS HEADING PADDING */
/* Overrides inline styles to pull the header up for a cleaner look */
h2.mb-4 {
    padding-top: 5px !important; 
    margin-top: 0 !important;
    margin-bottom: 15px !important;
}

@media screen and (max-width: 992px) {
    /* 2. BALANCED MOBILE CONTAINER */
    div[style*="margin-left"], .content-area {
        margin-left: 0 !important;
        width: 96% !important;
        max-width: 96% !important;
        margin: 0 auto !important;
        padding: 5px 5px 10px 5px !important;
        box-sizing: border-box !important;
    }

    /* 3. CENTERED BUTTONS & VERTICAL SPACING */
    /* Swaps buttons (Delete on left) and ensures they are vertically centered */
    td.text-center, .table td:last-child {
        display: flex !important;
        flex-direction: row-reverse !important; 
        justify-content: center !important;
        align-items: center !important; /* Perfect vertical alignment */
        gap: 10px !important;
        padding: 20px 5px !important; /* Increased padding for better touch target and centering */
    }

    /* Adds breathing room below each button */
    .btn-sm {
        margin-bottom: 0 !important; /* Reset to rely on cell padding for centering */
        padding: 13.5px 12px !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
    }

    /* 4. HEADER ORGANIZATION */
    .d-flex.justify-content-between.mb-4 {
        flex-direction: column !important;
        gap: 12px !important;
        align-items: center !important;
    }

    /* Prominent Add Supplier button above Search */
    .btn-primary[data-bs-target="#createModal"] {
        width: 100% !important;
        max-width: 250px !important;
        order: -1; 
    }

    .table-responsive {
        width: 100% !important;
        border: none !important;
        overflow-x: auto !important;
    }
}
</style> -->
<script>
    var editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        
        // Get data attributes from the button
        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name');
        var email = button.getAttribute('data-email');
        var phone = button.getAttribute('data-phone');
        var address = button.getAttribute('data-address');

        // Target the modal input fields
        editModal.querySelector('#edit_supplier_id').value = id;
        editModal.querySelector('#edit_name').value = name;
        editModal.querySelector('#edit_email').value = email;
        editModal.querySelector('#edit_phone').value = phone;
        editModal.querySelector('#edit_address').value = address;
    });
</script>

<?php include '../includes/footer.php'; ?>