<?php
require_once '../core/db_connect.php'; 
$pdo = connectDB();

$search = $_GET['search'] ?? '';

// This query ensures that even if Customer_Name is NULL, it displays a fallback name
$query = "
    SELECT 
        o.Order_ID, 
        o.Order_Date, 
        o.Total_Price, 
        o.invoice_status, 
        IFNULL(NULLIF(o.Customer_Name, ''), 'Arif Khan') AS Display_Name, 
        GROUP_CONCAT(CONCAT(p.Name, ' (x', od.Quantity, ')') SEPARATOR ', ') AS item_summary
    FROM orders o
    LEFT JOIN order_details od ON o.Order_ID = od.Order_ID
    LEFT JOIN products p ON od.Product_ID = p.Product_ID
    WHERE o.Order_ID LIKE ? 
       OR o.invoice_status LIKE ? 
       OR o.Customer_Name LIKE ?
    GROUP BY o.Order_ID
    ORDER BY o.Order_ID DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute(["%$search%", "%$search%", "%$search%"]);
$invoices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html data-theme="light">
<head>
    <title>Sales Invoices Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --inv-bg: #f8f9fc;
            --inv-container: #ffffff;
            --inv-text: #333333;
            --inv-text-muted: #858796;
            --inv-border: #e3e6f0;
            --inv-hover: #f8f9fc;
            --inv-input-bg: #ffffff;
            --inv-input-border: #d1d3e2;
        }
        [data-theme="dark"] {
            --inv-bg: #0f172a;
            --inv-container: rgba(30, 41, 59, 0.9);
            --inv-text: #ffffff;
            --inv-text-muted: #a0aec0;
            --inv-border: rgba(255, 255, 255, 0.15);
            --inv-hover: rgba(255, 255, 255, 0.05);
            --inv-input-bg: rgba(30, 41, 59, 0.9);
            --inv-input-border: rgba(255, 255, 255, 0.2);
        }

        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--inv-bg); padding: 20px; color: var(--inv-text); transition: background 0.3s ease, color 0.3s ease; }
        .container { background: var(--inv-container); padding: 30px; border-radius: 8px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); max-width: 1300px; margin: auto; border: 1px solid var(--inv-border); transition: background 0.3s ease, border-color 0.3s ease; }
        [data-theme="dark"] .container { backdrop-filter: blur(12px); }
        
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { color: #4e73df; margin: 0; transition: color 0.3s ease; }
        
        .search-box { display: flex; gap: 10px; }
        .search-box input { padding: 10px; width: 300px; border: 1px solid var(--inv-input-border); border-radius: 4px; outline: none; background: var(--inv-input-bg); color: var(--inv-text); transition: background 0.3s ease, border-color 0.3s ease, color 0.3s ease; }
        
        table { width: 100%; border-collapse: collapse; background: var(--inv-container); transition: background 0.3s ease; }
        th, td { padding: 12px 15px; border-bottom: 1px solid var(--inv-border); text-align: left; color: var(--inv-text); transition: border-color 0.3s ease; }
        th { background: #4e73df; color: white !important; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tbody tr:hover { background-color: var(--inv-hover); }
        
        .cell-text { color: var(--inv-text); }
        .cell-muted { color: var(--inv-text-muted); }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .status-paid { background: #e1f6ed; color: #1cc88a; }
        .status-draft { background: #fff4e5; color: #f6c23e; }
        
        .btn { padding: 8px 15px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; }
        .btn-bulk { background: #f6c23e; color: white; margin-right: 10px; }
        .btn-bulk:hover { background: #dda20a; }
        .btn-print { background: #36b9cc; color: white; }
        
        .item-list { color: var(--inv-text-muted); font-size: 12px; max-width: 250px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        
.theme-toggle { background: var(--inv-container); border: 1px solid var(--inv-border); border-radius: 4px; padding: 8px 15px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: background 0.3s ease, border-color 0.3s ease; font-size: 13px; font-weight: 600; color: var(--inv-text); }
        .theme-toggle i { font-size: 14px; }
        
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    
<div class="container">
        <div class="header-actions no-print">
            <h2 class="page-title">Sales Invoice Management</h2>
            <div>
                <button type="button" onclick="toggleTheme()" class="theme-toggle">
                    <i class="fas fa-moon" id="themeIcon"></i>
                    <span id="themeText">Dark Mode</span>
                </button>
                <button type="button" onclick="bulkPrint()" class="btn btn-bulk">
                    <i class="fas fa-layer-group"></i> Bulk Print Selected
                </button>
                <button type="button" onclick="window.print()" class="btn btn-print">
                    <i class="fas fa-print"></i> Print Page
                </button>
            </div>
        </div>
        
        <form method="GET" class="search-box no-print" style="margin-bottom: 20px;">
            <input type="text" name="search" placeholder="Search ID, Customer, or Status..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn" style="background:#4e73df; color:white;">Search</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th class="no-print"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th class="no-print text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $row): ?>
                <tr>
                    <td class="no-print">
                        <input type="checkbox" class="invoice-checkbox" value="<?php echo $row['Order_ID']; ?>">
                    </td>
                    <td><strong class="cell-text">#<?php echo $row['Order_ID']; ?></strong></td>
                    <td><small class="cell-muted"><?php echo date('M d, Y', strtotime($row['Order_Date'])); ?></small></td>
                    <td class="cell-text"><?php echo htmlspecialchars($row['Display_Name']); ?></td>
                    <td><span class="item-list"><?php echo htmlspecialchars($row['item_summary'] ?: 'N/A'); ?></span></td>
                    <td><strong class="cell-text">$<?php echo number_format($row['Total_Price'], 2); ?></strong></td>
                    <td>
                        <span class="badge <?php echo (strtolower($row['invoice_status']) == 'paid') ? 'status-paid' : 'status-draft'; ?>">
                            <?php echo strtoupper($row['invoice_status']); ?>
                        </span>
                    </td>
                    <td class="no-print" style="text-align: center;">
                        <a href="print_view.php?id=<?php echo $row['Order_ID']; ?>&type=sales" target="_blank" class="btn btn-print" style="padding: 4px 8px;">
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
            const checkboxes = document.querySelectorAll('.invoice-checkbox');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }

        function bulkPrint() {
            const selected = Array.from(document.querySelectorAll('.invoice-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) { alert("Please select an invoice."); return; }
            window.open(`print_view.php?id=${selected.join(',')}&type=sales`, '_blank');
        }

        function toggleTheme() {
            const html = document.documentElement;
            const icon = document.getElementById('themeIcon');
            const text = document.getElementById('themeText');
            const current = html.getAttribute('data-theme');
            const newTheme = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('inv-theme', newTheme);
            if (newTheme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
                text.textContent = 'Light Mode';
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
                text.textContent = 'Dark Mode';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const saved = localStorage.getItem('inv-theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
            const icon = document.getElementById('themeIcon');
            const text = document.getElementById('themeText');
            if (saved === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
                text.textContent = 'Light Mode';
            }
        });
    </script>
</body>
</html>