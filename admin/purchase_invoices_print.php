<?php
require_once '../core/db_connect.php'; 
$pdo = connectDB();

$search = $_GET['search'] ?? '';

// Comprehensive Query: Joins Suppliers and Products to show detailed purchase summaries
$query = "
    SELECT 
        po.PO_ID, 
        po.Order_Date, 
        po.Total_Cost, 
        po.Status, 
        s.Name AS Supplier_Name,
        GROUP_CONCAT(CONCAT(p.Name, ' (x', pod.Quantity, ')') SEPARATOR ', ') AS item_summary
    FROM purchase_orders po
    JOIN suppliers s ON po.Supplier_ID = s.Supplier_ID
    LEFT JOIN po_details pod ON po.PO_ID = pod.PO_ID
    LEFT JOIN products p ON pod.Product_ID = p.Product_ID
    WHERE po.PO_ID LIKE ? 
       OR po.Status LIKE ? 
       OR s.Name LIKE ?
    GROUP BY po.PO_ID
    ORDER BY po.Order_Date DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute(["%$search%", "%$search%", "%$search%"]);
$invoices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Purchase Invoices Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fc; padding: 20px; color: #333; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); max-width: 1300px; margin: auto; }
        
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-box input { padding: 10px; width: 300px; border: 1px solid #d1d3e2; border-radius: 4px; outline: none; }
        
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #e3e6f0; text-align: left; }
        th { background: #4e73df; color: white; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tr:hover { background-color: #f8f9fc; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-received { background: #e1f6ed; color: #1cc88a; }
        .status-pending { background: #fff4e5; color: #f6c23e; }
        
        .btn { padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; }
        .btn-bulk { background: #f6c23e; color: white; margin-right: 10px; }
        .btn-bulk:hover { background: #dda20a; }
        .btn-print { background: #36b9cc; color: white; }
        
        .item-list { color: #858796; font-size: 12px; max-width: 250px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        
        @media print { .no-print { display: none !important; } .container { box-shadow: none; width: 100%; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions no-print">
            <h2 style="color: #4e73df; margin: 0;">Purchase Invoice Management</h2>
            <div>
                <button type="button" onclick="bulkPrint()" class="btn btn-bulk">
                    <i class="fas fa-layer-group"></i> Bulk Print Selected
                </button>
                <button type="button" onclick="window.print()" class="btn btn-print">
                    <i class="fas fa-print"></i> Print Page
                </button>
            </div>
        </div>
        
        <form method="GET" class="search-box no-print">
            <input type="text" name="search" placeholder="Search PO ID, Supplier, or Status..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn" style="background:#4e73df; color:white;">Search</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th class="no-print"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                    <th>PO ID</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>Items Purchased</th>
                    <th>Total Cost</th>
                    <th>Status</th>
                    <th class="no-print text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $row): ?>
                <tr>
                    <td class="no-print">
                        <input type="checkbox" class="po-checkbox" value="<?php echo $row['PO_ID']; ?>">
                    </td>
                    <td><strong>#<?php echo $row['PO_ID']; ?></strong></td>
                    <td><small><?php echo date('M d, Y', strtotime($row['Order_Date'])); ?></small></td>
                    <td><?php echo htmlspecialchars($row['Supplier_Name']); ?></td>
                    <td><span class="item-list" title="<?php echo htmlspecialchars($row['item_summary']); ?>">
                        <?php echo htmlspecialchars($row['item_summary'] ?: 'No items'); ?>
                    </span></td>
                    <td><strong>$<?php echo number_format($row['Total_Cost'], 2); ?></strong></td>
                    <td>
                        <?php 
                            $statusClass = (strtolower($row['Status']) == 'received') ? 'status-received' : 'status-pending';
                        ?>
                        <span class="badge <?php echo $statusClass; ?>">
                            <?php echo $row['Status']; ?>
                        </span>
                    </td>
                    <td class="no-print" style="text-align: center;">
                        <a href="print_view.php?id=<?php echo $row['PO_ID']; ?>&type=purchase" target="_blank" class="btn btn-print" style="padding: 4px 8px;">
                            <i class="fas fa-file-pdf"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('.po-checkbox');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }

        function bulkPrint() {
            const selected = Array.from(document.querySelectorAll('.po-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) { alert("Please select at least one purchase order."); return; }
            
            // Passes multiple IDs and set type to 'purchase'
            window.open(`print_view.php?id=${selected.join(',')}&type=purchase`, '_blank');
        }
    </script>
</body>
</html>