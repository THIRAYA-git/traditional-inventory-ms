<?php
require_once '../../core/db.php';
$pdo = connectDB();

$data = $pdo->query("
    SELECT 
        product_id, 
        warehouse_id, 
        quantity
    FROM warehouse_stock
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
?>

<script>
function updateWarehouseStock() {
    fetch('api/warehouse_stock_live.php')
        .then(res => res.json())
        .then(data => {

            let totals = {};

            data.forEach(item => {
                let cell = document.querySelector(
                    `.stock-cell[data-product="${item.product_id}"][data-warehouse="${item.warehouse_id}"]`
                );

                if (cell) {
                    let qtySpan = cell.querySelector('.qty');
                    qtySpan.innerText = item.quantity;

                    // Visual alert
                    cell.classList.toggle('text-danger', item.quantity < 10);
                    cell.classList.toggle('fw-bold', item.quantity < 10);

                    // Row total
                    totals[item.product_id] = (totals[item.product_id] || 0) + parseInt(item.quantity);
                }
            });

            // Update total column
            Object.keys(totals).forEach(pid => {
                let totalCell = document.querySelector(`.row-total[data-product="${pid}"]`);
                if (totalCell) totalCell.innerText = totals[pid];
            });
        });
}

// Auto refresh every 12 seconds
setInterval(updateWarehouseStock, 12000);
updateWarehouseStock();
</script>
