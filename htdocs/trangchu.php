<?php
session_start();

// Kiểm tra xem người dùng đã đăng nhập chưa
// Giả định rằng bạn lưu tên người dùng vào $_SESSION['ten_tv'] trong xuly_dangnhap.php
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['ten_tv'])) {
    header("Location: dangnhap.php?message=" . urlencode("Bạn cần đăng nhập để truy cập trang này."));
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ cá nhân</title>
    <link rel="stylesheet" type="text/css" href="style1.css"> </head>
<body>
    <div style="text-align: center; margin-top: 50px;">
        <h2>Chào mừng, <?php echo htmlspecialchars($_SESSION['ten_tv']); ?>!</h2>
        <p>Bạn đã đăng nhập thành công.</p>
        <p><a href="home.php">Quay lại trang chủ</a></p>
        <p><a href="logout.php">Đăng xuất</a></p>
    </div>
</body>
</html>