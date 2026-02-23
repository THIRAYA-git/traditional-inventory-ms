<?php
// File: inventory-ms/employee/orders.php
include '../includes/header.php'; 
// include '../core/db_connect.php'; 
// NOTE: Assuming auth_check is included elsewhere or handled in header.php

$pdo = connectDB();
// Get the User_ID of the currently logged-in employee
$user_id = $_SESSION['user_id'];

// --- START: SQL QUERY WITH PRODUCT NAME AND CALCULATIONS ---
$orders_stmt = $pdo->prepare("
    SELECT 
        o.Order_ID, 
        o.Order_Date, 
        o.Total_Price,
        
        /* Subquery to find the name of the first product in the order */
        (
            SELECT p.Name
            FROM order_details od_sub
            JOIN products p ON od_sub.Product_ID = p.Product_ID
            WHERE od_sub.Order_ID = o.Order_ID
            ORDER BY od_sub.Detail_ID ASC /* Assumes the lowest Detail_ID is the first item */
            LIMIT 1
        ) AS Primary_Product_Name,
        
        SUM(od_calc.`Quantity`) AS total_units_ordered, /* Total Quantity */
        o.Total_Price / SUM(od_calc.`Quantity`) AS average_unit_price /* Calculated Average Unit Price */
    FROM 
        Orders o
    
    /* Join to calculate totals (Quantity/Avg Price) */
    LEFT JOIN 
        order_details od_calc ON o.Order_ID = od_calc.Order_ID
    
    WHERE 
        o.User_ID = :user_id 
    GROUP BY
        o.Order_ID, o.Order_Date, o.Total_Price 
    ORDER BY 
        o.Order_Date DESC
");
$orders_stmt->bindParam(':user_id', $user_id);
$orders_stmt->execute();
$my_orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
// --- END: SQL QUERY ---

// Include the sidebar
include '../includes/sidebar.php'; 
?>

<div class="content-area">
    <div  class="container-fluid">
        <h2 class="mb-4" >My Placed Orders</h2>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>S. No.</th> <th>Order ID</th> 
                        <th>Date</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($my_orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No orders have been placed yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php $sno = 1; /* <<< INITIALIZE COUNTER */ ?>
                        <?php foreach ($my_orders as $order): ?>
                        <tr>
                            <td><?php echo $sno; ?></td> <td><?php echo htmlspecialchars($order['Order_ID']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['Order_Date'])); ?></td>
                            
                            <td><?php echo htmlspecialchars($order['Primary_Product_Name']); ?></td> 
                            
                            <td><?php echo htmlspecialchars($order['total_units_ordered']); ?></td>
                            
                            <td>$<?php echo number_format($order['average_unit_price'], 2); ?></td>

                            <td>$<?php echo number_format($order['Total_Price'], 2); ?></td>
                        </tr>
                        <?php $sno++; /* <<< INCREMENT COUNTER */ ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php 
// The footer will close the main wrapper div and </body>
include '../includes/footer.php'; 
?>