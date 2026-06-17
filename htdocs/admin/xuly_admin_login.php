<?php
// FILE: admin/xuly_admin_login.php
session_start();
require_once __DIR__ . '/../dbconnect.php';
app_require_post_csrf();

$ten_tv_input = isset($_POST['ten_tv']) ? trim($_POST['ten_tv']) : '';
$mk_tv_input  = isset($_POST['mk_tv'])  ? trim($_POST['mk_tv'])  : '';

if ($ten_tv_input === '' || $mk_tv_input === '') {
    header('Location: login_error.php?message=' . urlencode('Vui lòng nhập đầy đủ tài khoản và mật khẩu.'));
    exit();
}

$sql = "SELECT id_tv, ten_tv, mk_tv, email_tv, sdt_tv, diachi_tv FROM users WHERE ten_tv = ?";
$stmt = $ketnoi->prepare($sql);
if ($stmt === false) {
    error_log('Lỗi chuẩn bị truy vấn: ' . $ketnoi->error);
    header('Location: login_error.php?message=' . urlencode('Lỗi hệ thống. Vui lòng thử lại sau.'));
    exit();
}

$stmt->bind_param('s', $ten_tv_input);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    if (password_verify($mk_tv_input, $row['mk_tv'])) {
        if (mb_strtolower($row['ten_tv'], 'UTF-8') !== 'admin') {
            header('Location: login_error.php?message=' . urlencode('Tài khoản này không có quyền quản trị.'));
            exit();
        }
        session_regenerate_id(true);
        $_SESSION['loggedin'] = true;
        $_SESSION['id_tv']    = $row['id_tv'];
        $_SESSION['ten_tv']   = $row['ten_tv'];
        $_SESSION['email_tv'] = $row['email_tv'];
        $_SESSION['sdt_tv']   = $row['sdt_tv'];
        $_SESSION['diachi_tv']= $row['diachi_tv'];
        header('Location: index.php?route=dashboard');
        exit();
    } else {
        header('Location: login_error.php?message=' . urlencode('Sai mật khẩu!'));
        exit();
    }
} else {
    header('Location: login_error.php?message=' . urlencode('Tài khoản không tồn tại!'));
    exit();
}
