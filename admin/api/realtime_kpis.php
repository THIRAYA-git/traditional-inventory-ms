<?php
require_once '../../core/db.php';
$pdo = connectDB();

$response = [];

/* Orders in last 5 minutes */
$response['recent_orders'] = $pdo->query("
    SELECT COUNT(*) FROM orders
    WHERE Order_Date >= NOW() - INTERVAL 5 MINUTE
")->fetchColumn();

/* Current Revenue Today */
$response['revenue_today'] = $pdo->query("
    SELECT IFNULL(SUM(Total_Price),0) FROM orders
    WHERE DATE(Order_Date) = CURDATE()
")->fetchColumn();

/* Critical Low Stock */
$response['critical_stock'] = $pdo->query("
    SELECT COUNT(*) FROM warehouse_stock
    WHERE quantity < 3
")->fetchColumn();

/* System Load Indicator */
$response['system_status'] = 'LIVE';

echo json_encode($response);

?>
<span id="liveStatus" class="badge bg-secondary">Offline</span>

<div class="card shadow-sm mt-4">
    <div class="card-header bg-white text-primary fw-bold">
        Live Operational Feed
    </div>
    <div class="card-body" id="activityFeed">
        Loading events...
    </div>
</div>



<script>
function loadRealtimeKPIs() {
    fetch('api/realtime_kpis.php')
        .then(res => res.json())
        .then(data => {

            document.getElementById('liveStatus').className =
                'badge ' + (data.system_status === 'LIVE' ? 'bg-success' : 'bg-danger');

            document.getElementById('liveStatus').innerText =
                data.system_status === 'LIVE' ? 'LIVE' : 'DOWN';

            if (data.critical_stock > 0) {
                document.getElementById('liveStatus').classList.add('pulse');
            }
        });
}

/* Poll every 15 seconds */
setInterval(loadRealtimeKPIs, 15000);
loadRealtimeKPIs();
</script>
