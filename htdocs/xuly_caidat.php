<?php
session_start();
include "dbconnect.php";
require_once __DIR__ . "/includes/BlockchainAuditService.php";
app_require_post_csrf();

// Kiểm tra đăng nhập
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php?go=dangnhap");
    exit();
}

// Chỉ xử lý khi có POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php?go=caidat");
    exit();
}

$username = $_SESSION['ten_tv'];
$oldpass = $_POST['oldpass'] ?? '';
$newpass = $_POST['newpass'] ?? '';
$confirmpass = $_POST['confirm_newpass'] ?? '';

// Kiểm tra các trường không được để trống
if (empty($oldpass) || empty($newpass) || empty($confirmpass)) {
    $_SESSION['message'] = "⚠ Vui lòng điền đầy đủ tất cả các trường!";
    $_SESSION['message_type'] = "error";
    header("Location: index.php?go=caidat");
    exit();
}

// Kiểm tra mật khẩu mới và xác nhận mật khẩu có khớp không
if ($newpass !== $confirmpass) {
    $_SESSION['message'] = "⚠ Mật khẩu mới và xác nhận mật khẩu không khớp!";
    $_SESSION['message_type'] = "error";
    header("Location: index.php?go=caidat");
    exit();
}

// Kiểm tra độ dài mật khẩu mới (tùy chọn - có thể bỏ qua)
if (strlen($newpass) < 6) {
    $_SESSION['message'] = "⚠ Mật khẩu mới phải có ít nhất 6 ký tự!";
    $_SESSION['message_type'] = "error";
    header("Location: index.php?go=caidat");
    exit();
}

// Lấy mật khẩu hiện tại từ database
$sql = "SELECT mk_tv FROM users WHERE ten_tv = ?";
$stmt = $ketnoi->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $current_hashed_pass = $row['mk_tv'];
    
    // Kiểm tra mật khẩu cũ có đúng không
    if (password_verify($oldpass, $current_hashed_pass)) {
        // Mật khẩu cũ đúng, tiến hành đổi mật khẩu
        $hashed_newpass = password_hash($newpass, PASSWORD_DEFAULT);
        
        $update_sql = "UPDATE users SET mk_tv = ? WHERE ten_tv = ?";
        $update_stmt = $ketnoi->prepare($update_sql);
        $update_stmt->bind_param("ss", $hashed_newpass, $username);
        
        if ($update_stmt->execute()) {
            $_SESSION['message'] = "Đổi mật khẩu thành công!";
            $_SESSION['message_type'] = "success";
            blockchain_audit_record($ketnoi, 'user', (int)($_SESSION['id_tv'] ?? 0), 'password_changed', [
                'user_id' => (int)($_SESSION['id_tv'] ?? 0),
                'source' => 'settings_page',
            ], blockchain_audit_current_actor('user'));
        } else {
            $_SESSION['message'] = "❌ Có lỗi xảy ra, vui lòng thử lại!";
            $_SESSION['message_type'] = "error";
        }
        
        $update_stmt->close();
    } else {
        $_SESSION['message'] = "❌ Mật khẩu cũ không đúng!";
        $_SESSION['message_type'] = "error";
    }
} else {
    $_SESSION['message'] = "❌ Không tìm thấy thông tin người dùng!";
    $_SESSION['message_type'] = "error";
}

$stmt->close();
$ketnoi->close();

// Chuyển hướng về trang index.php?go=caidat
header("Location: index.php?go=caidat");
exit();
?>
