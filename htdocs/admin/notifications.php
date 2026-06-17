<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../dbconnect.php';
app_require_admin(true);

$lastSeen = (int)($_SESSION['last_seen_order_id'] ?? 0);
$result = $ketnoi->query(
    "SELECT id_dh, hoten, sdt, tongtien, trangthai, ngaydat
     FROM donhang
     ORDER BY id_dh DESC
     LIMIT 20"
);

$items = [];
$maxId = $lastSeen;
$unread = 0;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $id = (int)$row['id_dh'];
        $items[] = [
            'id' => $id,
            'name' => $row['hoten'],
            'phone' => $row['sdt'],
            'status' => $row['trangthai'],
            'total' => (int)$row['tongtien'],
            'time' => date('d/m H:i', strtotime($row['ngaydat'])),
        ];
        $unread += $id > $lastSeen ? 1 : 0;
        $maxId = max($maxId, $id);
    }
}

app_json_response([
    'unread' => $unread,
    'last_max_id' => $maxId,
    'items' => $items,
]);
