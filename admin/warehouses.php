<?php
include('../includes/header.php'); // This contains your DB connection and session checks
include('../includes/sidebar.php');

// Handle Form Submission for adding a warehouse
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_warehouse'])) {
    $pdo = connectDB();
    $name = $_POST['warehouse_name'];
    $location = $_POST['location'];
    
    $stmt = $pdo->prepare("INSERT INTO warehouses (name, location) VALUES (?, ?)");
    if ($stmt->execute([$name, $location])) {
        $new_warehouse_id = $pdo->lastInsertId();
        // Auto-create warehouse_stock rows for all existing products
        $products = $pdo->query("SELECT product_id FROM products")->fetchAll();
        foreach ($products as $p) {
            $pdo->prepare("INSERT INTO warehouse_stock (warehouse_id, product_id, quantity) VALUES (?, ?, 0)")->execute([$new_warehouse_id, $p['product_id']]);
        }
        echo "<script>alert('Warehouse added successfully!'); window.location.href='warehouses.php';</script>";
    }
}
?>

<div class="content-area">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0 text-gray-800">Warehouse Management</h2>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addWarehouseModal">
                <i class="fas fa-plus"></i> Add New Warehouse
            </button>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Warehouse Name</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pdo = connectDB();
                            $stmt = $pdo->query("SELECT * FROM warehouses ORDER BY warehouse_id DESC");
                            while ($row = $stmt->fetch()) {
                                $status_class = ($row['status'] == 'active') ? 'badge bg-success' : 'badge bg-danger';
                                echo "<tr>
                                    <td>{$row['warehouse_id']}</td>
                                    <td><strong>{$row['name']}</strong></td>
                                    <td>{$row['location']}</td>
                                    <td><span class='{$status_class}'>" . ucfirst($row['status']) . "</span></td>
                                    <td>
                                        <a href='edit_warehouse.php?id={$row['warehouse_id']}' class='btn btn-sm btn-outline-primary'><i class='fas fa-edit'></i></a>
                                        <button class='btn btn-sm btn-outline-danger'><i class='fas fa-trash'></i></button>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addWarehouseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Add New Warehouse</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Warehouse Name</label>
                        <input type="text" name="warehouse_name" class="form-control" placeholder="e.g. North Wing Depot" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location/Address</label>
                        <textarea name="location" class="form-control" rows="3" placeholder="Enter physical address..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_warehouse" class="btn btn-success">Save Warehouse</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>