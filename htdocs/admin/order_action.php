<?php

session_start();
require_once __DIR__ . '/../dbconnect.php';
require_once __DIR__ . '/../includes/BlockchainAuditService.php';

app_require_admin();
app_require_post_csrf();

$id = (int)($_POST['id'] ?? 0);
$status = trim((string)($_POST['status'] ?? ''));
$allowed = ['Chờ xử lý', 'Đã xác nhận', 'Đang vận chuyển', 'Vận chuyển thành công', 'Đã hủy'];

if ($id > 0 && in_array($status, $allowed, true)) {
    $stmt = $ketnoi->prepare('SELECT trangthai FROM donhang WHERE id_dh = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $current = (string)($stmt->get_result()->fetch_assoc()['trangthai'] ?? '');
    $stmt->close();

    $delivered = in_array($current, ['Vận chuyển thành công', 'Hoàn thành'], true);
    if (!$delivered || !in_array($status, ['Đã hủy', 'Đã xác nhận'], true)) {
        $stmt = $ketnoi->prepare('UPDATE donhang SET trangthai = ? WHERE id_dh = ?');
        $stmt->bind_param('si', $status, $id);
        $updated = $stmt->execute();
        $stmt->close();

        if ($updated && $current !== $status) {
            blockchain_audit_record($ketnoi, 'order', $id, 'order_status_updated', [
                'order_id' => $id,
                'previous_status' => $current,
                'new_status' => $status,
            ], ['type' => 'admin', 'id' => (int)($_SESSION['id_tv'] ?? 0), 'name' => $_SESSION['ten_tv'] ?? 'admin']);
        }
    }
}

header('Location: index.php?route=manage_orders');
exit;
