<?php
include '../includes/header.php'; // Includes session_start() and security check
// include '../core/db_connect.php'; 

$pdo = connectDB();
$message = '';

// --- HELPER FUNCTIONS ---

// Function to fetch FK data for dropdowns
function get_fk_data($pdo) {
    $categories = $pdo->query("SELECT Category_ID, Name FROM Categories ORDER BY Name ASC")->fetchAll();
    $suppliers = $pdo->query("SELECT Supplier_ID, Name FROM Suppliers ORDER BY Name ASC")->fetchAll();
    return ['categories' => $categories, 'suppliers' => $suppliers];
}
$fk_data = get_fk_data($pdo);

// --- 1. HANDLE CRUD OPERATIONS ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate all inputs
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT);
    $minimum_stock_level = filter_var($_POST['minimum_stock_level'], FILTER_VALIDATE_INT) ?: 0;
    $category_id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
    $supplier_id = filter_var($_POST['supplier_id'], FILTER_VALIDATE_INT);
    $product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);

    if ($price === false || $stock === false || $category_id === false || $supplier_id === false) {
        $message = '<div class="alert alert-danger">Error: Invalid input for Price, Stock, Category, or Supplier.</div>';
    } else {
        try {
            if ($product_id) {
                // UPDATE Operation
                $stmt = $pdo->prepare("UPDATE Products
                    SET Name = ?, Description = ?, Price = ?, Stock = ?, Minimum_Stock_Level = ?, Category_ID = ?, Supplier_ID = ?
                    WHERE Product_ID = ?");
                $stmt->execute([$name, $description, $price, $stock, $minimum_stock_level, $category_id, $supplier_id, $product_id]);
                $message = '<div class="alert alert-success">Product updated successfully!</div>';
            } else {
                // CREATE Operation
                $stmt = $pdo->prepare("INSERT INTO Products (Name, Description, Price, Stock, Minimum_Stock_Level, Category_ID, Supplier_ID)
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $stock, $minimum_stock_level, $category_id, $supplier_id]);
                $new_product_id = $pdo->lastInsertId();
                // Auto-create warehouse_stock rows for all active warehouses
                $warehouses = $pdo->query("SELECT warehouse_id FROM warehouses WHERE status = 'active'")->fetchAll();
                foreach ($warehouses as $w) {
                    $pdo->prepare("INSERT INTO warehouse_stock (warehouse_id, product_id, quantity) VALUES (?, ?, 0)")->execute([$w['warehouse_id'], $new_product_id]);
                }
                $message = '<div class="alert alert-success">Product added successfully!</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Database Error: Could not process product.</div>';
        }
    }
}

// B. DELETE Operation
if (isset($_GET['delete_id'])) {
    $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    
    try {
        // Check if any order detail is linked before deleting
        $check = $pdo->prepare("SELECT COUNT(*) FROM Order_Details WHERE Product_ID = ?");
        $check->execute([$delete_id]);
        if ($check->fetchColumn() > 0) {
             $message = '<div class="alert alert-danger">Cannot delete: Product is linked to past orders.</div>';
        } else {
            $stmt = $pdo->prepare("DELETE FROM Products WHERE Product_ID = ?");
            $stmt->execute([$delete_id]);
            $message = '<div class="alert alert-success">Product deleted successfully!</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// C. READ Operation (Fetch all products with Category and Supplier names)
// Include search functionality as per the screenshot
$search_query = $_GET['search'] ?? '';
$sql = "
    SELECT 
        p.*, 
        c.Name AS CategoryName, 
        s.Name AS SupplierName 
    FROM Products p
    JOIN Categories c ON p.Category_ID = c.Category_ID
    JOIN Suppliers s ON p.Supplier_ID = s.Supplier_ID
";

$where_clause = '';
$params = [];

if (!empty($search_query)) {
    $where_clause = " WHERE p.Name LIKE ? ";
    $params[] = '%' . $search_query . '%';
}

$sql .= $where_clause . " ORDER BY p.Name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include '../includes/sidebar.php'; 
?>

<div style="margin-left: 283px; width: 80%">
<h2 class="mb-4" style="padding-top: 20px;">Product Management</h2>
<?php echo $message; ?>

<div class="d-flex justify-content-between mb-4">
    <form method="GET" class="w-75">
        <div class="input-group">
            <input type="text" class="form-control" name="search" 
                   placeholder="Search products by name..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
            <?php if (!empty($search_query)): ?>
                <a href="products.php" class="btn btn-outline-danger" title="Clear Search"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </div>
    </form>
    
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="fas fa-plus me-2"></i> Add Product
    </button>
</div>


<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Supplier</th>
                <th>Price</th>
                <th>Stock</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($products) == 0): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No products found.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><?php echo $p['Name']; ?></td>
                <td><?php echo $p['CategoryName']; ?></td>
                <td><?php echo $p['SupplierName']; ?></td>
                <td>$<?php echo number_format($p['Price'], 2); ?></td>
                <td>
                    <span class="badge bg-<?php 
                        if ($p['Stock'] > 10) echo 'success'; 
                        elseif ($p['Stock'] > 0) echo 'warning'; 
                        else echo 'danger'; 
                    ?>"><?php echo $p['Stock']; ?></span>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-info text-white"
                            data-bs-toggle="modal"
                            data-bs-target="#editModal"
                            data-id="<?php echo $p['Product_ID']; ?>"
                            data-cat-id="<?php echo $p['Category_ID']; ?>"
                            data-supp-id="<?php echo $p['Supplier_ID']; ?>"
                            data-name="<?php echo htmlspecialchars($p['Name']); ?>"
                            data-price="<?php echo $p['Price']; ?>"
                            data-stock="<?php echo $p['Stock']; ?>"
                            data-min-level="<?php echo $p['Minimum_Stock_Level']; ?>"
                            data-desc="<?php echo htmlspecialchars($p['Description']); ?>">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <a href="products.php?delete_id=<?php echo $p['Product_ID']; ?>" 
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Are you sure you want to delete this product?');">
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
            <form action="products.php" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="createModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="create_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_description" class="form-label">Description</label>
                        <textarea class="form-control" id="create_description" name="description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="create_price" class="form-label">Price ($)</label>
                            <input type="number" step="0.01" class="form-control" id="create_price" name="price" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="create_stock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="create_stock" name="stock" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="create_minimum_stock_level" class="form-label">Minimum Stock Level</label>
                            <input type="number" class="form-control" id="create_minimum_stock_level" name="minimum_stock_level" value="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="create_category_id" class="form-label">Category</label>
                        <select class="form-select" id="create_category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($fk_data['categories'] as $cat): ?>
                                <option value="<?php echo $cat['Category_ID']; ?>"><?php echo $cat['Name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="create_supplier_id" class="form-label">Supplier</label>
                        <select class="form-select" id="create_supplier_id" name="supplier_id" required>
                            <option value="">Select Supplier</option>
                            <?php foreach ($fk_data['suppliers'] as $supp): ?>
                                <option value="<?php echo $supp['Supplier_ID']; ?>"><?php echo $supp['Name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="products.php" method="POST">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="editModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_price" class="form-label">Price ($)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_price" name="price" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_stock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="edit_stock" name="stock" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_minimum_stock_level" class="form-label">Minimum Stock Level</label>
                            <input type="number" class="form-control" id="edit_minimum_stock_level" name="minimum_stock_level" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category_id" class="form-label">Category</label>
                        <select class="form-select" id="edit_category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($fk_data['categories'] as $cat): ?>
                                <option value="<?php echo $cat['Category_ID']; ?>"><?php echo $cat['Name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_supplier_id" class="form-label">Supplier</label>
                        <select class="form-select" id="edit_supplier_id" name="supplier_id" required>
                            <option value="">Select Supplier</option>
                            <?php foreach ($fk_data['suppliers'] as $supp): ?>
                                <option value="<?php echo $supp['Supplier_ID']; ?>"><?php echo $supp['Name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<style>
/* Universal fix to prevent padding from expanding the 100% width */
*, *::before, *::after {
    box-sizing: border-box !important;
}

@media screen and (max-width: 992px) {
    /* 1. RESET MAIN WRAPPER */
    /* Target whatever margin you used (283px or 275px) to fill the screen */
    div[style*="margin-left"] {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100vw !important;
        padding: 10px !important;
        display: block !important;
        overflow-x: hidden !important; 
    }

    /* 2. HEADER ICONS (No Shrinking) */
    /* Keeps Bell and Logout at full size and pinned right */
    .top-header, .navbar, .d-flex.align-items-center {
        margin-left: 0 !important;
        width: 100% !important;
        display: flex !important;
        justify-content: flex-end !important;
        gap: 12px !important;
    }

    .btn-logout, .fa-bell, #notificationBell {
        flex-shrink: 0 !important; /* Forces icons to stay original size */
    }

    /* 3. PRODUCT ACTION BUTTONS */
    /* Decreased padding (slimmer) and increased margin (spacing) */
    .btn, .btn-sm, .btn-primary, .btn-danger, .btn-warning {
        padding: 4px 10px !important; 
        margin: 5px 2px !important;  
        font-size: 12px !important;
        display: inline-block !important;
    }

    /* 4. PRODUCT TABLE FIX */
    /* Allows the table to be wide enough to show Product Name, Price, and Stock */
    .table-responsive {
        width: 100% !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        margin-top: 15px !important;
    }

    table {
        min-width: 600px !important; /* Prevents text from squishing */
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
        var price = button.getAttribute('data-price');
        var stock = button.getAttribute('data-stock');
        var minLevel = button.getAttribute('data-min-level');
        var catId = button.getAttribute('data-cat-id');
        var suppId = button.getAttribute('data-supp-id');

        // Target the modal input fields
        editModal.querySelector('#edit_product_id').value = id;
        editModal.querySelector('#edit_name').value = name;
        editModal.querySelector('#edit_description').value = desc;
        editModal.querySelector('#edit_price').value = price;
        editModal.querySelector('#edit_stock').value = stock;
        editModal.querySelector('#edit_minimum_stock_level').value = (minLevel === '0' || minLevel === '' || minLevel === null) ? 0 : minLevel;
        
        // Set the selected options for the dropdowns
        editModal.querySelector('#edit_category_id').value = catId;
        editModal.querySelector('#edit_supplier_id').value = suppId;
    });
</script>

<?php include '../includes/footer.php'; ?>