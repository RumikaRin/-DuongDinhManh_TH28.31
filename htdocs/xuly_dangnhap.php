<?php
session_start();
require_once 'dbconnect.php';
app_require_post_csrf();

// Nhận dữ liệu từ form
$ten_tv_input = isset($_POST['ten_tv']) ? trim($_POST['ten_tv']) : '';
$mk_tv_input  = isset($_POST['mk_tv'])  ? trim($_POST['mk_tv'])  : '';

// Kiểm tra trống
if (empty($ten_tv_input) || empty($mk_tv_input)) {
    header("Location: index.php?go=dangnhap&message=" . urlencode("Vui lòng nhập đầy đủ tài khoản và mật khẩu."));
    exit();
}

// Truy vấn lấy toàn bộ thông tin user (id_tv, mk_tv...)
$sql = "SELECT id_tv, ten_tv, mk_tv, email_tv, sdt_tv, diachi_tv FROM users WHERE ten_tv = ?";
$stmt = $ketnoi->prepare($sql);
if ($stmt === false) {
    error_log("Lỗi chuẩn bị truy vấn: " . $ketnoi->error);
    header("Location: index.php?go=dangnhap&message=" . urlencode("Lỗi hệ thống. Vui lòng thử lại sau."));
    exit();
}

$stmt->bind_param("s", $ten_tv_input);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();

    // Xác minh mật khẩu
    if (password_verify($mk_tv_input, $row['mk_tv'])) {
        session_regenerate_id(true);
        // Đăng nhập thành công → lưu session
        $_SESSION['loggedin'] = true;
        $_SESSION['id_tv']    = $row['id_tv'];    // quan trọng để lọc đơn hàng
        $_SESSION['ten_tv']   = $row['ten_tv'];
        $_SESSION['email_tv'] = $row['email_tv'];
        $_SESSION['sdt_tv']   = $row['sdt_tv'];
        $_SESSION['diachi_tv']= $row['diachi_tv'];
        $_SESSION['just_logged_in'] = true; // Flag để hiển thị thông báo chào mừng
        header("Location: index.php"); // chuyển hướng
        exit();
    } else {
        header("Location: index.php?go=dangnhap&message=" . urlencode("Sai mật khẩu!"));
        exit();
    }
} else {
    header("Location: index.php?go=dangnhap&message=" . urlencode("Tài khoản không tồn tại!"));
    exit();
}

$stmt->close();
$ketnoi->close();
