<?php
// Enhanced Comment System Handler
// Supports both products and sales items with advanced features

session_start();
require_once 'dbconnect.php';
require_once __DIR__ . '/includes/BlockchainAuditService.php';

header('Content-Type: application/json; charset=utf-8');

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? 'submit';

if (in_array($action, ['submit', 'helpful', 'delete'], true)) {
    app_require_post_csrf(true);
}

switch ($action) {
    case 'submit':
        handleCommentSubmit();
        break;
    case 'load':
        loadComments();
        break;
    case 'helpful':
        markHelpful();
        break;
    case 'delete':
        deleteComment();
        break;
    case 'stats':
        getProductStats();
        break;
    default:
        echo json_encode(['ok' => false, 'message' => 'Invalid action']);
}

// Submit new comment
function handleCommentSubmit() {
    global $ketnoi;
    
    if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
        echo json_encode(['ok' => false, 'message' => 'Vui lòng đăng nhập để bình luận']);
        return;
    }
    
    $product_type = $_POST['product_type'] ?? '';
    $product_id = intval($_POST['product_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    
    // Validate input
    if (!in_array($product_type, ['sanpham', 'sale'])) {
        echo json_encode(['ok' => false, 'message' => 'Loại sản phẩm không hợp lệ']);
        return;
    }
    
    if ($product_id <= 0) {
        echo json_encode(['ok' => false, 'message' => 'ID sản phẩm không hợp lệ']);
        return;
    }
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['ok' => false, 'message' => 'Đánh giá phải từ 1-5 sao']);
        return;
    }
    
    if (empty($content)) {
        echo json_encode(['ok' => false, 'message' => 'Nội dung không được để trống']);
        return;
    }
    
    // Check if user already commented
    $checkSQL = "SELECT id FROM comments WHERE product_type = ? AND product_id = ? AND user_id = ?";
    $checkStmt = $ketnoi->prepare($checkSQL);
    $checkStmt->bind_param("sii", $product_type, $product_id, $_SESSION['id_tv']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['ok' => false, 'message' => 'Bạn đã đánh giá sản phẩm này rồi']);
        return;
    }
    
    // Insert comment
    $sql = "INSERT INTO comments (product_type, product_id, user_id, username, rating, content) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $ketnoi->prepare($sql);
    $stmt->bind_param("siisis", 
        $product_type, 
        $product_id, 
        $_SESSION['id_tv'], 
        $_SESSION['ten_tv'], 
        $rating, 
        $content
    );
    
    if ($stmt->execute()) {
        $commentId = (int)$stmt->insert_id;
        blockchain_audit_record($ketnoi, 'comment', $commentId, 'comment_submitted', [
            'comment_id' => $commentId,
            'product_type' => $product_type,
            'product_id' => $product_id,
            'rating' => $rating,
            'status' => 'approved',
        ], ['type' => 'user', 'id' => (int)$_SESSION['id_tv'], 'name' => (string)$_SESSION['ten_tv']]);

        echo json_encode([
            'ok' => true, 
            'message' => 'Đánh giá của bạn đã được gửi thành công!',
            'comment_id' => $commentId
        ]);
    } else {
        echo json_encode(['ok' => false, 'message' => 'Lỗi khi gửi đánh giá']);
    }
}

// Load comments for a product
function loadComments() {
    global $ketnoi;
    
    $product_type = $_GET['product_type'] ?? '';
    $product_id = intval($_GET['product_id'] ?? 0);
    $page = intval($_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Get total comments
    $countSQL = "SELECT COUNT(*) as total FROM comments 
                 WHERE product_type = ? AND product_id = ? AND status = 'approved'";
    $countStmt = $ketnoi->prepare($countSQL);
    $countStmt->bind_param("si", $product_type, $product_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    
    // Get comments
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM comment_helpful WHERE comment_id = c.id) as helpful_count
            FROM comments c
            WHERE c.product_type = ? AND c.product_id = ? AND c.status = 'approved'
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $ketnoi->prepare($sql);
    $stmt->bind_param("siii", $product_type, $product_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $row['created_at_formatted'] = date('d/m/Y H:i', strtotime($row['created_at']));
        $row['is_owner'] = isset($_SESSION['id_tv']) && $_SESSION['id_tv'] == $row['user_id'];
        $comments[] = $row;
    }
    
    echo json_encode([
        'ok' => true,
        'comments' => $comments,
        'total' => $total,
        'pages' => ceil($total / $limit),
        'current_page' => $page
    ]);
}

// Mark comment as helpful
function markHelpful() {
    global $ketnoi;
    
    if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
        echo json_encode(['ok' => false, 'message' => 'Vui lòng đăng nhập']);
        return;
    }
    
    $comment_id = intval($_POST['comment_id'] ?? 0);
    
    // Check if already marked
    $checkSQL = "SELECT * FROM comment_helpful WHERE user_id = ? AND comment_id = ?";
    $checkStmt = $ketnoi->prepare($checkSQL);
    $checkStmt->bind_param("ii", $_SESSION['id_tv'], $comment_id);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        // Remove helpful mark
        $sql = "DELETE FROM comment_helpful WHERE user_id = ? AND comment_id = ?";
        $message = 'Đã bỏ đánh dấu hữu ích';
        $auditAction = 'comment_helpful_unmarked';
    } else {
        // Add helpful mark
        $sql = "INSERT INTO comment_helpful (user_id, comment_id) VALUES (?, ?)";
        $message = 'Đã đánh dấu là hữu ích';
        $auditAction = 'comment_helpful_marked';
    }
    
    $stmt = $ketnoi->prepare($sql);
    $stmt->bind_param("ii", $_SESSION['id_tv'], $comment_id);
    
    if ($stmt->execute()) {
        // Update helpful count
        $updateSQL = "UPDATE comments SET helpful_count = 
                     (SELECT COUNT(*) FROM comment_helpful WHERE comment_id = ?) 
                     WHERE id = ?";
        $updateStmt = $ketnoi->prepare($updateSQL);
        $updateStmt->bind_param("ii", $comment_id, $comment_id);
        $updateStmt->execute();

        blockchain_audit_record($ketnoi, 'comment', $comment_id, $auditAction, [
            'comment_id' => $comment_id,
        ], ['type' => 'user', 'id' => (int)$_SESSION['id_tv'], 'name' => (string)$_SESSION['ten_tv']]);
        
        echo json_encode(['ok' => true, 'message' => $message]);
    } else {
        echo json_encode(['ok' => false, 'message' => 'Lỗi xử lý']);
    }
}

// Delete own comment
function deleteComment() {
    global $ketnoi;
    
    if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
        echo json_encode(['ok' => false, 'message' => 'Vui lòng đăng nhập']);
        return;
    }
    
    $comment_id = intval($_POST['comment_id'] ?? 0);
    
    // Check ownership
    $sql = "DELETE FROM comments WHERE id = ? AND user_id = ?";
    $stmt = $ketnoi->prepare($sql);
    $stmt->bind_param("ii", $comment_id, $_SESSION['id_tv']);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        blockchain_audit_record($ketnoi, 'comment', $comment_id, 'comment_deleted', [
            'comment_id' => $comment_id,
            'source' => 'user_comment_handler',
        ], ['type' => 'user', 'id' => (int)$_SESSION['id_tv'], 'name' => (string)$_SESSION['ten_tv']]);

        echo json_encode(['ok' => true, 'message' => 'Đã xóa bình luận']);
    } else {
        echo json_encode(['ok' => false, 'message' => 'Không thể xóa bình luận này']);
    }
}

// Get product statistics
function getProductStats() {
    global $ketnoi;
    
    $product_type = $_GET['product_type'] ?? '';
    $product_id = intval($_GET['product_id'] ?? 0);
    
    $sql = "SELECT 
            COUNT(*) as total_reviews,
            AVG(rating) as avg_rating,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM comments 
            WHERE product_type = ? AND product_id = ? AND status = 'approved'";
    
    $stmt = $ketnoi->prepare($sql);
    $stmt->bind_param("si", $product_type, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    // Calculate percentages
    if ($stats['total_reviews'] > 0) {
        $stats['five_star_percent'] = round(($stats['five_star'] / $stats['total_reviews']) * 100);
        $stats['four_star_percent'] = round(($stats['four_star'] / $stats['total_reviews']) * 100);
        $stats['three_star_percent'] = round(($stats['three_star'] / $stats['total_reviews']) * 100);
        $stats['two_star_percent'] = round(($stats['two_star'] / $stats['total_reviews']) * 100);
        $stats['one_star_percent'] = round(($stats['one_star'] / $stats['total_reviews']) * 100);
        $stats['avg_rating'] = round($stats['avg_rating'], 1);
    } else {
        $stats['avg_rating'] = 0;
    }
    
    echo json_encode(['ok' => true, 'stats' => $stats]);
}
?>
