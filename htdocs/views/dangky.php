<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php?go=" . basename(__FILE__, ".php"));
    exit();
}
$message = isset($_GET['message']) ? trim((string)$_GET['message']) : '';
?>
<section class="auth-page" data-reveal>
    <div class="auth-intro">
        <span class="eyebrow">Trở thành độc giả Kim Đồng</span>
        <h1>Một tài khoản,<br>nhiều thế giới.</h1>
        <p>Tạo tài khoản để mua sách nhanh hơn và theo dõi toàn bộ đơn hàng của bạn.</p>
    </div>
    <form class="auth-form" action="xuly_dangky.php" method="post">
        <?php echo app_csrf_field(); ?>
        <div class="auth-form-head">
            <span>02 / Đăng ký</span>
            <h2>Thông tin độc giả</h2>
        </div>
        <?php if ($message !== ''): ?>
            <p class="form-message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <label for="register-username">Tên đăng nhập</label>
        <input id="register-username" type="text" name="ten_tv" autocomplete="username" required>
        <label for="register-email">Email</label>
        <input id="register-email" type="email" name="email_tv" autocomplete="email" required>
        <label for="register-phone">Số điện thoại</label>
        <input id="register-phone" type="tel" name="sdt_tv" pattern="[0-9]{9,11}" autocomplete="tel" required>
        <label for="register-address">Địa chỉ</label>
        <input id="register-address" type="text" name="diachi_tv" autocomplete="street-address" required>
        <label for="register-password">Mật khẩu</label>
        <input id="register-password" type="password" name="mk_tv" minlength="6" autocomplete="new-password" required>
        <button class="button button-primary" type="submit">Tạo tài khoản</button>
        <p class="auth-switch">Đã có tài khoản? <a href="index.php?go=dangnhap">Đăng nhập</a></p>
    </form>
</section>
