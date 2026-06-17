<?php
// FILE: admin/news_action.php
// Xử lý AJAX requests cho quản lý tin tức

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../dbconnect.php';
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra quyền admin
app_require_admin(true);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        // Lấy thông tin tin tức theo ID
        $id = (int)($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit;
        }
        
        try {
            $stmt = $ketnoi->prepare("SELECT * FROM tintuc WHERE id_sp = ?");
            if (!$stmt) {
                throw new RuntimeException('Cannot prepare news query.');
            }
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                echo json_encode([
                    'success' => true,
                    'news' => $row
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy tin tức với ID: ' . $id]);
            }
        } catch (Exception $e) {
            error_log("News Action - Exception: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Không thể tải tin tức.']);
        }
        break;
        
    case 'list':
        // Lấy danh sách tin tức (có thể dùng cho AJAX pagination)
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        
        $search = $_GET['search'] ?? '';
        $where_clause = '';
        $params = [];
        
        if (!empty($search)) {
            $where_clause = "WHERE title_tintuc LIKE ? OR noidung_tintuc LIKE ?";
            $search_term = "%$search%";
            $params = [$search_term, $search_term];
        }
        
        // Đếm tổng số
        $count_query = "SELECT COUNT(*) as total FROM tintuc $where_clause";
        $count_stmt = $ketnoi->prepare($count_query);
        if (!empty($params)) {
            $count_stmt->bind_param("ss", ...$params);
        }
        $count_stmt->execute();
        $total = $count_stmt->get_result()->fetch_assoc()['total'];
        
        // Lấy dữ liệu
        $query = "SELECT * FROM tintuc $where_clause ORDER BY date_tintuc DESC LIMIT ? OFFSET ?";
        $stmt = $ketnoi->prepare($query);
        if (!empty($params)) {
            $all_params = array_merge($params, [$limit, $offset]);
            $stmt->bind_param("ssii", ...$all_params);
        } else {
            $stmt->bind_param("ii", $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $news = [];
        while ($row = $result->fetch_assoc()) {
            $news[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $news,
            'total' => $total,
            'page' => $page,
            'total_pages' => ceil($total / $limit)
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}
?>
