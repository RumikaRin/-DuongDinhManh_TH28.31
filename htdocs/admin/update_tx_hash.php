<?php
session_start();
require_once __DIR__ . '/../dbconnect.php';

// Kiểm tra quyền admin (Giả sử session role = 1 hoặc is_admin)
// Nếu hệ thống admin hiện tại không có check riêng cho AJAX, ta tạm comment hoặc thay bằng check tương ứng
/*
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? '';
    $tx_hash = $_POST['tx_hash'] ?? '';

    if ($order_id && $tx_hash) {
        $sql = "UPDATE donhang SET blockchain_tx_hash = ?, block_timestamp = NOW() WHERE id_dh = ?";
        $stmt = $ketnoi->prepare($sql);
        $stmt->bind_param("si", $tx_hash, $order_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Lưu mã hash thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật CSDL']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
