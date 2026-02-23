<?php
require_once '../../core/db.php';
$pdo = connectDB();

$events = $pdo->query("
    SELECT Order_ID, Total_Price, Order_Date
    FROM orders
    ORDER BY Order_Date DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($events);



?>

<script>function loadActivityFeed() {
    fetch('api/activity_feed.php')
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(e => {
                html += `<div class="small border-bottom py-2">
                    🟢 Order #${e.Order_ID} • $${e.Total_Price} • ${e.Order_Date}
                </div>`;
            });
            document.getElementById('activityFeed').innerHTML = html;
        });
}

setInterval(loadActivityFeed, 10000);
loadActivityFeed();
</script>