<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../dbconnect.php';
app_require_admin(true);
app_require_post_csrf(true);

$maxId = 0;
$result = $ketnoi->query('SELECT MAX(id_dh) AS max_id FROM donhang');
if ($result && $row = $result->fetch_assoc()) {
    $maxId = (int)$row['max_id'];
}

$_SESSION['last_seen_order_id'] = $maxId;
app_json_response(['ok' => true, 'last_seen_order_id' => $maxId]);
