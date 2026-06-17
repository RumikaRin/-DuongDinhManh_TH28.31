<?php
// FILE: views/partials/profile_sidebar.php
// Expected variables: $ten_tv, $email_tv, $activeTab

$initials = 'U';
if (!empty($ten_tv)) {
    $parts = explode(' ', trim($ten_tv));
    if (count($parts) > 1) {
        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
    } else {
        $initials = mb_strtoupper(mb_substr($ten_tv, 0, 2));
    }
}

$isAdmin = false;
if (isset($_SESSION['quyen']) && $_SESSION['quyen'] == 'admin') {
    $isAdmin = true;
}
?>

<aside class="flof-col-4 flof-card flof-sidebar">
    <div class="flof-avatar-block">
        <div class="flof-avatar">
            <?= htmlspecialchars($initials) ?>
        </div>
        <div class="flof-user-info">
            <h2><?= htmlspecialchars(!empty($ten_tv) ? $ten_tv : "Khách hàng") ?></h2>
            <span><?= htmlspecialchars(!empty($email_tv) ? $email_tv : "Chưa cập nhật email") ?></span>
        </div>
    </div>

    <div class="flof-nav">
        <?php if ($isAdmin): ?>
            <a href="admin/index.php" class="flof-nav-link admin-link">
                <span>Trang quản trị Admin</span>
                <span class="hidden lg:inline">→</span>
            </a>
        <?php endif; ?>

        <a href="index.php?go=donhang" class="flof-nav-link <?= ($activeTab === 'donhang') ? 'active' : '' ?>">
            <span>Lịch sử mua hàng</span>
        </a>

        <a href="index.php?go=hoso" class="flof-nav-link <?= ($activeTab === 'hoso') ? 'active' : '' ?>">
            <span>Thông tin cá nhân</span>
        </a>

        <a href="index.php?go=caidat" class="flof-nav-link <?= ($activeTab === 'caidat') ? 'active' : '' ?>">
            <span>Đổi mật khẩu</span>
        </a>

        <a href="index.php?go=edit_hoso" class="flof-nav-link <?= ($activeTab === 'edit_hoso') ? 'active' : '' ?>">
            <span>Cập nhật thông tin</span>
        </a>

        <a href="xuly_dangxuat.php" class="flof-nav-link logout-link">
            <span>Đăng xuất</span>
        </a>
    </div>
</aside>
