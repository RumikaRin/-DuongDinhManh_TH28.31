<!doctype html>
<?php
session_start(); // Bắt đầu session
include("dbconnect.php"); // Kết nối database

$requestedPage = isset($_GET['go']) ? trim((string)$_GET['go']) : 'home';
$loginRequiredPages = ['donhang', 'hoso', 'edit_hoso', 'caidat', 'thanhtoan'];
if (in_array($requestedPage, $loginRequiredPages, true) && empty($_SESSION['id_tv'])) {
    header('Location: index.php?go=dangnhap');
    exit;
}
if ($requestedPage === 'thanhtoan' && empty($_SESSION['cart'])) {
    header('Location: index.php?go=xemgiohang');
    exit;
}
?>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Khám phá sách, truyện và ấn phẩm mới từ Nhà xuất bản Kim Đồng.">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(app_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <title>Nhà xuất bản Kim Đồng</title>
    <link rel="icon" href="img/sp/logo.webp" type="image/webp">
    <!-- Unified CSS -->
    <link rel="stylesheet" type="text/css" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
    <link rel="stylesheet" type="text/css" href="redesign.css?v=<?php echo filemtime(__DIR__ . '/redesign.css'); ?>">
    <link rel="stylesheet" type="text/css" href="premium.css?v=<?php echo file_exists(__DIR__ . '/premium.css') ? filemtime(__DIR__ . '/premium.css') : '1'; ?>">
    <link rel="stylesheet" type="text/css" href="css/flof-layout.css?v=<?php echo file_exists(__DIR__ . '/css/flof-layout.css') ? filemtime(__DIR__ . '/css/flof-layout.css') : '1'; ?>">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>

<body data-theme="light">
    <a class="skip-link" href="#main-content">Bỏ qua đến nội dung chính</a>
    <!-- Bỏ thông báo chào mừng sau đăng nhập theo yêu cầu -->
    
    <!-- Header luôn hiển thị -->
    <?php include("header.php"); ?>

    <!-- Nội dung chính - routing system -->
    <main class="main-content" id="main-content">
        <?php include 'url.php'; ?>
    </main>

    <!-- Side navigation button -->
    <?php 
    if (file_exists("sidenavbutton.php")) {
        include("sidenavbutton.php"); 
    }
    ?>

    <!-- Footer luôn hiển thị -->
    <?php include("footer.php"); ?>

    <!-- Script chính - PHẢI LOAD CUỐI CÙNG -->
    <script src="script.js?v=<?php echo filemtime(__DIR__ . '/script.js'); ?>"></script>
    <script src="js/performance.js?v=<?php echo filemtime(__DIR__ . '/js/performance.js'); ?>"></script>

</body>
</html>
<?php
// Đóng kết nối CSDL (tùy chọn)
// $ketnoi->close();
?>
