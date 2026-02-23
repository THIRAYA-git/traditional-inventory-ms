<?php
include '../includes/header.php'; // Includes session_start() and security check (Employee role)
// include '../core/db_connect.php'; 

$pdo = connectDB();
$message = '';

// --- 1. HANDLE ORDER PLACEMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];
    
    if ($product_id === false || $quantity === false || $quantity <= 0) {
        echo "<script>alert('Invalid input for quantity.');</script>";
    } else {
        try {
            $pdo->beginTransaction();

            // A. Check Stock and Price
            $stmt = $pdo->prepare("SELECT Stock, Price, Name FROM Products WHERE Product_ID = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product || $product['Stock'] < $quantity) {
                $pdo->rollBack();
                echo "<script>alert('Not enough stock.');</script>";
            } else {
                $unit_price = $product['Price'];
                $total_price = $unit_price * $quantity;
                $new_stock = $product['Stock'] - $quantity;

                // B. Create New Order
                $stmt = $pdo->prepare("INSERT INTO Orders (User_ID, Total_Price, Order_Date) VALUES (?, ?, NOW())");
                $stmt->execute([$user_id, $total_price]);
                $order_id = $pdo->lastInsertId();

                // C. Add to Order Details
                $stmt = $pdo->prepare("INSERT INTO Order_Details (Order_ID, Product_ID, Quantity, Unit_Price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $product_id, $quantity, $unit_price]);

                // D. Update Product Stock (Decrement)
                $stmt = $pdo->prepare("UPDATE Products SET Stock = ? WHERE Product_ID = ?");
                $stmt->execute([$new_stock, $product_id]);

                $pdo->commit();
                echo "<script>alert('Order placed successfully! Stock updated.');</script>";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "<script>alert('Order failed: " . $e->getMessage() . "');</script>";
        }
    }
}


// --- 2. READ OPERATION (Fetch products for display, using filter and search) ---
$search_query = $_GET['search'] ?? '';
$category_filter = $_GET['category_id'] ?? '';

$sql = "
    SELECT 
        p.Product_ID, p.Name, p.Price, p.Stock, 
        c.Name AS CategoryName 
    FROM Products p
    JOIN Categories c ON p.Category_ID = c.Category_ID
";

$where_conditions = [];
$params = [];

if (!empty($category_filter)) {
    $where_conditions[] = "p.Category_ID = ?";
    $params[] = $category_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = "p.Name LIKE ?";
    $params[] = '%' . $search_query . '%';
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " ORDER BY p.Name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Fetch categories for the filter dropdown
$categories = $pdo->query("SELECT Category_ID, Name FROM Categories ORDER BY Name ASC")->fetchAll();

include '../includes/sidebar.php'; 
?>

<div style="margin-left: 275px; width:80%">
<h2 style="padding-top: 20px;" class="mb-4">Products</h2>

<div class="d-flex justify-content-between mb-4">
    <form method="GET" class="me-3" style="width: 30%;" >
        <select name="category_id" class="form-select" onchange="this.form.submit()">
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['Category_ID']; ?>" 
                    <?php echo ($category_filter == $cat['Category_ID']) ? 'selected' : ''; ?>>
                    <?php echo $cat['Name']; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($search_query)): ?>
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
        <?php endif; ?>
    </form>

    <form method="GET" class="w-100">
        <div class="input-group">
            <input type="text" class="form-control" name="search" 
                   placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
            <?php if (!empty($category_filter)): ?>
                <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_filter); ?>">
            <?php endif; ?>
            <?php if (!empty($search_query) || !empty($category_filter)): ?>
                <a href="products.php" class="btn btn-outline-danger" title="Clear Filters"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
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
                <td><?php echo $p['Product_ID']; ?></td>
                <td><?php echo $p['Name']; ?></td>
                <td><?php echo $p['CategoryName']; ?></td>
                <td>$<?php echo number_format($p['Price'], 2); ?></td>
                <td>
                    <span class="badge bg-<?php 
                        if ($p['Stock'] > 5) echo 'success'; 
                        elseif ($p['Stock'] > 0) echo 'warning'; 
                        else echo 'danger'; 
                    ?>"><?php echo $p['Stock']; ?></span>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-success order-button" 
                            data-bs-toggle="modal" 
                            data-bs-target="#orderModal"
                            data-id="<?php echo $p['Product_ID']; ?>"
                            data-price="<?php echo $p['Price']; ?>"
                            data-stock="<?php echo $p['Stock']; ?>"
                            <?php echo ($p['Stock'] == 0) ? 'disabled' : ''; ?>>
                        Order
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="products.php" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="orderModalLabel">Place Order</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="order_product_id">
                    <input type="hidden" id="order_unit_price">
                    <input type="hidden" id="order_max_stock">

                    <div class="mb-3">
                        <label for="order_quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="order_quantity" name="quantity" min="1" required value="1">
                        <small class="form-text text-muted" id="stock_info"></small>
                    </div>

                    <p class="mt-3">Total: $<span id="order_total">0.00</span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="place_order" class="btn btn-success">Place Order</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<script>
    var orderModal = document.getElementById('orderModal');
    orderModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        
        var id = button.getAttribute('data-id');
        var price = parseFloat(button.getAttribute('data-price'));
        var stock = parseInt(button.getAttribute('data-stock'));

        // Set hidden fields
        orderModal.querySelector('#order_product_id').value = id;
        orderModal.querySelector('#order_unit_price').value = price;
        orderModal.querySelector('#order_max_stock').value = stock;

        // Set initial values and update info
        var quantityInput = orderModal.querySelector('#order_quantity');
        quantityInput.value = 1; // Default to 1
        quantityInput.setAttribute('max', stock);
        
        orderModal.querySelector('#stock_info').textContent = 'Max stock: ' + stock;

        // Function to calculate and update total
        function updateTotal() {
            var qty = parseInt(quantityInput.value);
            if (isNaN(qty) || qty < 1) {
                qty = 1;
                quantityInput.value = 1;
            }
            if (qty > stock) {
                alert('Quantity exceeds available stock (' + stock + ').');
                qty = stock;
                quantityInput.value = stock;
            }
            
            var total = qty * price;
            orderModal.querySelector('#order_total').textContent = total.toFixed(2);
        }

        // Add event listener to quantity field
        quantityInput.oninput = updateTotal;
        
        // Initial call
        updateTotal();
    });
</script>

<?php include '../includes/footer.php'; ?>