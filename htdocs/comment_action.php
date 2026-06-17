<?php
// FILE: comment_action.php
// Handle user comment and rating submissions for products and sales items

session_start();
require_once __DIR__ . '/dbconnect.php';
require_once __DIR__ . '/includes/BlockchainAuditService.php';

header('Content-Type: application/json; charset=utf-8');

app_require_post_csrf(true);

if (!isset($_SESSION['id_tv'], $_SESSION['ten_tv'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'message' => 'Bạn cần đăng nhập để bình luận.']);
  exit;
}

$product_type = isset($_POST['product_type']) ? $_POST['product_type'] : '';
$product_id   = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating       = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$content      = isset($_POST['content']) ? trim($_POST['content']) : '';

if (!in_array($product_type, ['sanpham','sale'], true) || $product_id <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'Dữ liệu sản phẩm không hợp lệ']);
  exit;
}
if ($rating < 1 || $rating > 5) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'Điểm đánh giá phải từ 1 đến 5 sao']);
  exit;
}
if ($content === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'Nội dung bình luận không được để trống']);
  exit;
}

// Insert comment as pending
$sql = "INSERT INTO comments (product_type, product_id, user_id, username, rating, content, status)
        VALUES (?, ?, ?, ?, ?, ?, 'approved')";
$stmt = $ketnoi->prepare($sql);
if ($stmt === false) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Lỗi hệ thống']);
  exit;
}
$user_id = (int)$_SESSION['id_tv'];
$username = $_SESSION['ten_tv'];
$stmt->bind_param('siisis', $product_type, $product_id, $user_id, $username, $rating, $content);
$ok = $stmt->execute();
$comment_id = (int)$stmt->insert_id;
$stmt->close();

if (!$ok) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Không thể lưu bình luận']);
  exit;
}

blockchain_audit_record($ketnoi, 'comment', $comment_id, 'comment_submitted', [
  'comment_id' => $comment_id,
  'product_type' => $product_type,
  'product_id' => $product_id,
  'rating' => $rating,
  'status' => 'approved',
], ['type' => 'user', 'id' => $user_id, 'name' => $username]);

echo json_encode(['ok' => true, 'message' => 'Đã đăng bình luận.']);
