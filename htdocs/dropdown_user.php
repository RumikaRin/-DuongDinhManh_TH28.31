<?php if (isset($_SESSION['ten_tv'])): ?>
    <details class="user-menu">
        <summary>
            <span class="user-avatar"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($_SESSION['ten_tv'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="user-greeting">Chào, <?php echo htmlspecialchars($_SESSION['ten_tv'], ENT_QUOTES, 'UTF-8'); ?></span>
        </summary>
        <div class="user-menu-panel">
            <a href="index.php?go=hoso">Hồ sơ của tôi</a>
            <a href="index.php?go=donhang">Đơn hàng của tôi</a>
            <a href="index.php?go=xemgiohang">Giỏ hàng của tôi</a>
            <a href="index.php?go=caidat">Cài đặt tài khoản</a>
            <form action="xuly_dangxuat.php" method="post">
                <?php echo app_csrf_field(); ?>
                <button type="submit">Đăng xuất</button>
            </form>
        </div>
    </details>
<?php else: ?>
    <a href="index.php?go=dangnhap" id="login-btn">
        <span>Đăng nhập</span>
    </a>
<?php endif; ?>
