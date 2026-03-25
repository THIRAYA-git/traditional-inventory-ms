<?php
// File: inventory-ms/admin/create_po.php
include '../includes/header.php';

// 1. Correct Path to core folder
require_once '../core/db_connect.php';

// --- SECURITY CHECK ---
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../employee/dashboard.php');
    exit;
}

$pdo = connectDB();
$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Fetch data for dropdowns
$suppliers = $pdo->query("SELECT Supplier_ID, Name FROM suppliers ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT Product_ID, Name, Cost FROM products ORDER BY Name")->fetchAll(PDO::FETCH_ASSOC);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = $_POST['supplier_id'] ?? null;
    $destination = $_POST['warehouse_id'] ?? ''; // Can be 'HUB' or a numeric ID
    $po_products = $_POST['po_product'] ?? [];
    $po_quantities = $_POST['po_quantity'] ?? [];
    $po_costs = $_POST['po_cost'] ?? [];

    if (empty($supplier_id) || empty($po_products) || empty($destination)) {
        $error_message = "Please select a supplier, destination, and at least one product.";
    } else {
        $total_cost = 0;
        for ($i = 0; $i < count($po_products); $i++) {
            $total_cost += (int)$po_quantities[$i] * (float)$po_costs[$i];
        }

        $pdo->beginTransaction();
        try {
            // Insert Purchase Order (include warehouse_id)
            $po_insert = $pdo->prepare("INSERT INTO purchase_orders (Supplier_ID, User_ID, Warehouse_ID, Total_Cost, Status, Payment_Status) VALUES (?, ?, ?, ?, 'Ordered', 'Pending')");
            $po_insert->execute([$supplier_id, $user_id, $destination, $total_cost]);
            $po_id = $pdo->lastInsertId();

            // Insert Details
            $detail_insert = $pdo->prepare("INSERT INTO po_details (PO_ID, Product_ID, Quantity, Unit_Cost) VALUES (?, ?, ?, ?)");
            for ($i = 0; $i < count($po_products); $i++) {
                $detail_insert->execute([$po_id, $po_products[$i], (int)$po_quantities[$i], (float)$po_costs[$i]]);
            }

            $pdo->commit();
            $success_message = "Purchase Order #{$po_id} created for " . ($destination === 'HUB' ? "Global Hub" : "Warehouse") . ".";
            $_POST = array();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Database Error: " . $e->getMessage();
        }
    }
}

include '../includes/sidebar.php';
?>

<div style="margin-left: 250px; width:80%" class="content-area">
    <div class="container-fluid">
        <h2 class="h3 pt-3 mb-4">Create New Purchase Order</h2>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php elseif ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form action="create_po.php" method="POST">
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-primary text-white">PO Header Details</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Supplier *</label>
                            <select class="form-select" name="supplier_id" required>
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?= $s['Supplier_ID'] ?>"><?= htmlspecialchars($s['Name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>



                        <div class="col-md-4 mb-3">
                            <label class="form-label">Destination Selection *</label>
                            <select name="warehouse_id" class="form-select" required>
                                <?php
                                $stmt = $pdo->query("SELECT warehouse_id, name FROM warehouses");
                                while ($row = $stmt->fetch()) {
                                    echo "<option value='{$row['warehouse_id']}'>Warehouse: {$row['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Order Date</label>
                            <input type="text" class="form-control" value="<?= date('Y-m-d H:i'); ?>" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    PO Line Items
                    <button type="button" class="btn btn-light btn-sm" id="add-item-btn">Add Product</button>
                </div>
                <div class="card-body">
                    <table class="table table-hover" id="po-items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Cost</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="text-end mb-5">
                <button type="submit" class="btn btn-primary btn-lg px-5">Submit Purchase Order</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableBody = document.querySelector('#po-items-table tbody');
        const addItemBtn = document.getElementById('add-item-btn');
        const totalCostDisplay = document.getElementById('total_cost_display');

        // Products data needed for JavaScript
        const productsData = <?php echo json_encode($products); ?>;

        let rowCount = 0;

        function updateTotals() {
            let grandTotal = 0;
            const rows = tableBody.querySelectorAll('tr');

            rows.forEach(row => {
                const quantityInput = row.querySelector('input[name^="po_quantity"]');
                const costInput = row.querySelector('input[name^="po_cost"]');
                const subtotalCell = row.querySelector('.subtotal');

                const quantity = parseFloat(quantityInput.value) || 0;
                const cost = parseFloat(costInput.value) || 0;
                const subtotal = quantity * cost;

                subtotalCell.textContent = '$' + subtotal.toFixed(2);
                grandTotal += subtotal;
            });

            totalCostDisplay.value = '$' + grandTotal.toFixed(2);
        }

        function createNewRow() {
            rowCount++;
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-row-id', rowCount);

            // Generate Product Dropdown Options
            let productOptions = '<option value="">Select Product</option>';
            productsData.forEach(p => {
                productOptions += `<option value="${p.Product_ID}" data-cost="${p.Cost}">
                                    ${p.Name} ($${parseFloat(p.Cost).toFixed(2)})
                                   </option>`;
            });

            newRow.innerHTML = `
                <td>
                    <select class="form-select product-select" name="po_product[]" required>
                        ${productOptions}
                    </select>
                </td>
                <td><input type="number" class="form-control po-quantity" name="po_quantity[]" value="1" min="1" required></td>
                <td><input type="number" step="0.01" class="form-control po-cost" name="po_cost[]" value="0.00" min="0.01" required></td>
                <td class="subtotal">$0.00</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-item-btn"><i class="fas fa-trash"></i></button>
                </td>
            `;

            tableBody.appendChild(newRow);

            // Attach event listeners to the new row elements
            const inputs = newRow.querySelectorAll('.po-quantity, .po-cost');
            inputs.forEach(input => input.addEventListener('input', updateTotals));

            // Set initial cost when product is selected
            const productSelect = newRow.querySelector('.product-select');
            productSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const costInput = newRow.querySelector('.po-cost');
                if (selectedOption.dataset.cost) {
                    costInput.value = parseFloat(selectedOption.dataset.cost).toFixed(2);
                }
                updateTotals();
            });

            // Remove button handler
            newRow.querySelector('.remove-item-btn').addEventListener('click', function() {
                newRow.remove();
                updateTotals(); // Recalculate after removal
            });

            updateTotals();
        }

        // Initial row and Add Item Button handler
        createNewRow();
        addItemBtn.addEventListener('click', createNewRow);
    });
</script>

</html>