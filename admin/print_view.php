<?php
require_once '../core/db_connect.php'; 
$pdo = connectDB();

$ids = $_GET['id'] ?? '';
$type = $_GET['type'] ?? '';

if (!$ids || !$type) {
    die("Error: Missing ID or Type.");
}

$idArray = explode(',', $ids);
$placeholders = implode(',', array_fill(0, count($idArray), '?'));

if ($type === 'sales') {
    $query = "SELECT o.*, o.Customer_Name as party_name FROM orders o WHERE o.Order_ID IN ($placeholders)";
    $label = "BILL TO";
    $title = "SALES INVOICE";
    $note_header = "Payment Instructions";
    $note_content = "Please make checks payable to InventoSmart. Payment is due within 15 days of receipt.";
} else {
    $query = "SELECT po.*, s.Name as party_name, s.Address as party_address, po.PO_ID as Order_ID 
              FROM purchase_orders po 
              JOIN suppliers s ON po.Supplier_ID = s.Supplier_ID 
              WHERE po.PO_ID IN ($placeholders)";
    $label = "BILL FROM";
    $title = "PURCHASE ORDER";
    $note_header = "Terms & Conditions";
    $note_content = "Goods are subject to inspection upon arrival. Please notify of any discrepancies immediately.";
}

$stmt = $pdo->prepare($query);
$stmt->execute($idArray);
$records = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; margin: 0; padding: 20px; background: #f4f7f6; }
        .invoice-card { background: white; padding: 50px; border-radius: 8px; box-shadow: 0 0 20px rgba(0,0,0,0.1); margin: 0 auto 40px auto; max-width: 850px; page-break-after: always; position: relative; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #4e73df; padding-bottom: 25px; margin-bottom: 30px; }
        .logo h1 { color: #4e73df; margin: 0; font-size: 28px; }
        .details-row { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .bill-info h3 { font-size: 14px; color: #858796; text-transform: uppercase; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f8f9fc; color: #4e73df; padding: 15px; text-align: left; border-bottom: 2px solid #e3e6f0; }
        td { padding: 15px; border-bottom: 1px solid #e3e6f0; }
        .total-box { text-align: right; margin-top: 30px; border-top: 2px solid #4e73df; padding-top: 15px; margin-bottom: 40px; }
        
        /* New Notes Section Styling */
        .notes-section { margin-top: 40px; padding: 20px; background: #f8f9fc; border-left: 4px solid #4e73df; border-radius: 4px; }
        .notes-section h4 { margin: 0 0 10px 0; color: #4e73df; font-size: 14px; text-transform: uppercase; }
        .notes-section p { margin: 0; font-size: 13px; color: #5a5c69; line-height: 1.5; }
        
        .no-print-btn { background: #4e73df; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin: 20px auto; display: block; }
        @media print { .no-print-btn { display: none; } .invoice-card { box-shadow: none; border: none; margin: 0; padding: 20px; width: 100%; } body { background: white; padding: 0; } }
    </style>
</head>
<body>
    <button class="no-print-btn" onclick="window.print()">Confirm Print / Download PDF</button>

    <?php foreach ($records as $record): 
        if ($type === 'sales') {
            // Using calculated unit price to fix SQL error
            $item_query = "SELECT p.Name, od.Quantity, (o.Total_Price / od.Quantity) as Unit_Price 
                           FROM order_details od 
                           JOIN products p ON od.Product_ID = p.Product_ID 
                           JOIN orders o ON od.Order_ID = o.Order_ID
                           WHERE od.Order_ID = ?";
            $total_amt = $record['Total_Price'];
            $status = $record['invoice_status'];
        } else {
            $item_query = "SELECT p.Name, pod.Quantity, pod.Unit_Cost as Unit_Price 
                           FROM po_details pod 
                           JOIN products p ON pod.Product_ID = p.Product_ID 
                           WHERE pod.PO_ID = ?";
            $total_amt = $record['Total_Cost'];
            $status = $record['Status'];
        }
        $item_stmt = $pdo->prepare($item_query);
        $item_stmt->execute([$record['Order_ID']]);
        $items = $item_stmt->fetchAll();
    ?>

    <div class="invoice-card">
        <div class="header">
            <div class="logo">
                <h1>InventoSmart</h1>
                <p>Digital Inventory Management</p>
            </div>
            <div style="text-align: right;">
                <h2 style="margin:0; color:#4e73df;"><?php echo $title; ?></h2>
                <p>Reference: <strong>#<?php echo $record['Order_ID']; ?></strong></p>
                <p>Date: <?php echo date('M d, Y', strtotime($record['Order_Date'])); ?></p>
            </div>
        </div>

        <div class="details-row">
            <div class="bill-info">
                <h3><?php echo $label; ?></h3>
                <p><strong><?php echo htmlspecialchars($record['party_name'] ?: 'Arif Khan'); ?></strong></p>
                <p><?php echo htmlspecialchars($record['party_address'] ?? 'Primary Business Address'); ?></p>
            </div>
            <div class="bill-info" style="text-align: right;">
                <h3>Status</h3>
                <p style="font-weight: bold; color: #1cc88a;"><?php echo strtoupper($status); ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['Name']); ?></td>
                    <td><?php echo $item['Quantity']; ?></td>
                    <td>$<?php echo number_format($item['Unit_Price'], 2); ?></td>
                    <td style="text-align: right;">$<?php echo number_format($item['Unit_Price'] * $item['Quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-box">
            <p style="font-size: 20px;"><strong>GRAND TOTAL: $<?php echo number_format($total_amt, 2); ?></strong></p>
        </div>

        <div class="notes-section">
            <h4><?php echo $note_header; ?></h4>
            <p><?php echo $note_content; ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</body>
</html>