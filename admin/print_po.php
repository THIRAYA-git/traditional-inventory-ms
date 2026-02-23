<?php
include('../includes/header.php'); 
$pdo = connectDB();

$po_id = $_GET['id'] ?? 0;

// Fetch PO, Supplier, and Warehouse info
$stmt = $pdo->prepare("SELECT po.*, s.name as supplier_name, s.email, s.address, w.name as warehouse_name 
                       FROM purchase_orders po 
                       JOIN suppliers s ON po.supplier_id = s.supplier_id 
                       LEFT JOIN warehouses w ON po.warehouse_id = w.warehouse_id 
                       WHERE po.po_id = ?");
$stmt->execute([$po_id]);
$po = $stmt->fetch();

if (!$po) die("Purchase Order not found.");

// Fetch items
$items = $pdo->prepare("SELECT pi.*, p.name, p.SKU FROM po_items pi 
                        JOIN products p ON pi.product_id = p.product_id 
                        WHERE pi.po_id = ?");
$items->execute([$po_id]);
$po_items = $items->fetchAll();
?>

<div class="content-area bg-white">
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-6">
                <h1 class="display-5 fw-bold">PURCHASE ORDER</h1>
                <p class="text-muted">InventoSmart Systems</p>
            </div>
            <div class="col-6 text-end">
                <h4>PO #<?= str_pad($po['po_id'], 5, '0', STR_PAD_LEFT) ?></h4>
                <p>Date: <?= date('M d, Y', strtotime($po['created_at'])) ?></p>
                <span class="badge bg-<?= $po['status'] == 'Received' ? 'success' : 'warning' ?>">
                    <?= $po['status'] ?>
                </span>
            </div>
        </div>

        <hr>

        <div class="row mb-5">
            <div class="col-6">
                <h6><strong>VENDOR:</strong></h6>
                <address>
                    <strong><?= htmlspecialchars($po['supplier_name']) ?></strong><br>
                    <?= nl2br(htmlspecialchars($po['address'])) ?><br>
                    Email: <?= htmlspecialchars($po['email']) ?>
                </address>
            </div>
            <div class="col-6 text-end">
                <h6><strong>SHIP TO:</strong></h6>
                <address>
                    <strong><?= htmlspecialchars($po['warehouse_name']) ?></strong><br>
                    (Warehouse Destination)
                </address>
            </div>
        </div>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>SKU</th>
                    <th>Product Description</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $grand_total = 0; foreach ($po_items as $item): 
                    $total = $item['quantity'] * $item['unit_price'];
                    $grand_total += $total;
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['SKU']) ?></td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td class="text-center"><?= $item['quantity'] ?></td>
                    <td class="text-end">$<?= number_format($item['unit_price'], 2) ?></td>
                    <td class="text-end">$<?= number_format($total, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end text-uppercase">Grand Total</th>
                    <th class="text-end h5">$<?= number_format($grand_total, 2) ?></th>
                </tr>
            </tfoot>
        </table>

        <div class="mt-5 no-print">
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print Now</button>
            <a href="purchase_orders.php" class="btn btn-outline-secondary">Back to List</a>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .navbar, .no-print, .open-sidebar-button { display: none !important; }
    .content-area { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    body { background-color: white !important; }
}
</style>