<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php?go=" . basename(__FILE__, ".php"));
    exit();
}
if (!isset($_SESSION['ten_tv'])) {
    header("Location: index.php?go=dangnhap");
    exit();
}

$ten_tv = $_SESSION['ten_tv'];

// Lấy dữ liệu từ MySQL (thêm diachi_tv)
$email_tv = '';
$sdt_tv   = '';
$diachi_tv = '';

$sql = "SELECT email_tv, sdt_tv, diachi_tv FROM users WHERE ten_tv = ?";
$stmt = $ketnoi->prepare($sql);
$stmt->bind_param("s", $ten_tv);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $email_tv = !empty($row['email_tv']) ? $row['email_tv'] : 'Chưa cập nhật';
    $sdt_tv   = !empty($row['sdt_tv']) ? $row['sdt_tv'] : 'Chưa cập nhật';
    $diachi_tv = !empty($row['diachi_tv']) ? $row['diachi_tv'] : 'Chưa cập nhật';
}

$stmt->close();
?>
<link rel="stylesheet" href="css/profile.css?v=<?php echo filemtime(__DIR__ . '/../css/profile.css'); ?>">

<div class="flof-container fade-in-up">
    <div class="flof-grid-12">
        <!-- Sidebar (4 columns) -->
        <?php 
        $activeTab = 'hoso';
        include __DIR__ . '/partials/profile_sidebar.php'; 
        ?>

        <!-- Content (8 columns) -->
        <div class="flof-col-8">
            <div class="flof-card">
                <h3 class="flof-section-title">Thông tin cá nhân</h3>

                <div class="profile-content" style="padding: 0;">
                    <div class="profile-info" style="max-width: 100%; margin: 0;">
                        <div class="info-row" style="border-bottom: 1px solid var(--warm-100);">
                            <div class="info-label" style="color: var(--warm-500);">Tên đăng nhập</div>
                            <div class="info-value" style="color: var(--warm-900);"><?= htmlspecialchars($ten_tv) ?></div>
                        </div>
                        
                        <div class="info-row" style="border-bottom: 1px solid var(--warm-100);">
                            <div class="info-label" style="color: var(--warm-500);">Email</div>
                            <div class="info-value" style="color: var(--warm-900);"><?= htmlspecialchars($email_tv) ?></div>
                        </div>
                        
                        <div class="info-row" style="border-bottom: 1px solid var(--warm-100);">
                            <div class="info-label" style="color: var(--warm-500);">Số điện thoại</div>
                            <div class="info-value" style="color: var(--warm-900);"><?= htmlspecialchars($sdt_tv) ?></div>
                        </div>

                        <div class="info-row">
                            <div class="info-label" style="color: var(--warm-500);">Địa chỉ</div>
                            <div class="info-value" style="color: var(--warm-900);"><?= htmlspecialchars($diachi_tv) ?></div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 24px; display: flex; gap: 12px;">
                    <a href="index.php?go=edit_hoso" class="flof-btn flof-btn-primary">Chỉnh sửa hồ sơ</a>
                    <a href="index.php?go=caidat" class="flof-btn" style="background-color: var(--warm-200); color: var(--warm-900);">Đổi mật khẩu</a>
                </div>
            </div>
        </div>
    </div>
</div>
