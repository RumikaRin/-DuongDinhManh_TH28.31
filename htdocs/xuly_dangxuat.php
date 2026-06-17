<?php
session_start();
require_once __DIR__ . '/includes/app.php';
app_require_post_csrf();

// Hủy tất cả các biến session
$_SESSION = array();

// Nếu muốn hủy hoàn toàn session, cũng xóa cookie session.
// Lưu ý: Điều này sẽ làm mất session ID và buộc trình duyệt tạo ID mới.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Cuối cùng, hủy session
session_destroy();

// Chuyển hướng về trang chủ
header("Location: index.php"); // Hoặc index.php nếu đó là trang chính của bạn
exit();
?>
