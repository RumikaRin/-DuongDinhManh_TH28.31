<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php?go=" . basename(__FILE__, ".php"));
    exit();
}
$message = isset($_GET['message']) ? trim((string)$_GET['message']) : '';
?>
<section class="auth-page" data-reveal>
    <div class="auth-intro">
        <span class="eyebrow">Chào mừng trở lại</span>
        <h1>Tiếp tục hành trình đọc của bạn.</h1>
        <p>Đăng nhập để quản lý giỏ hàng, theo dõi đơn hàng và lưu thông tin giao nhận.</p>
        <a href="index.php?go=sanpham">Khám phá tủ sách</a>
    </div>
    <form class="auth-form" action="xuly_dangnhap.php" method="post">
        <?php echo app_csrf_field(); ?>
        <div class="auth-form-head">
            <span>01 / Đăng nhập</span>
            <h2>Tài khoản độc giả</h2>
        </div>
        <?php if ($message !== ''): ?>
            <p class="form-message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <label for="login-username">Tên đăng nhập</label>
        <input id="login-username" type="text" name="ten_tv" autocomplete="username" required>
        <label for="login-password">Mật khẩu</label>
        <input id="login-password" type="password" name="mk_tv" autocomplete="current-password" required>
        <button class="button button-primary" type="submit">Đăng nhập</button>
        <p class="auth-switch">Chưa có tài khoản? <a href="index.php?go=dangky">Đăng ký ngay</a></p>
    </form>
</section>
