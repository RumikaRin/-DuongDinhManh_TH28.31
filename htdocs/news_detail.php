<?php
// Endpoint để lấy chi tiết tin tức
header('Content-Type: application/json');

// Đảm bảo dbconnect.php đã được include
if (!isset($ketnoi)) {
    require_once 'dbconnect.php';
}

// Lấy ID tin tức từ request
$news_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($news_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID tin tức không hợp lệ']);
    exit;
}

try {
    // Lấy chi tiết tin tức
    $query = "SELECT * FROM tintuc WHERE id_sp = ?";
    $stmt = $ketnoi->prepare($query);
    $stmt->bind_param("i", $news_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $news = $result->fetch_assoc();
        
        // Định dạng dữ liệu
        $formatted_news = [
            'id' => $news['id_sp'],
            'title' => htmlspecialchars($news['title_tintuc']),
            'content' => nl2br(htmlspecialchars($news['noidung_tintuc'])),
            'image' => htmlspecialchars($news['anh_tintuc']),
            'date' => date('d/m/Y', strtotime($news['date_tintuc'])),
            'date_full' => date('l, d F Y', strtotime($news['date_tintuc'])),
            'author' => 'Nhà xuất bản Kim Đồng'
        ];
        
        echo json_encode($formatted_news);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy tin tức']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
}
?>
