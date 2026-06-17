<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php?go=" . basename(__FILE__, ".php"));
    exit();
}
if (!isset($_SESSION['ten_tv'])) {
    header("Location: index.php?go=dangnhap");
    exit();
}
require_once __DIR__ . '/../includes/BlockchainAuditService.php';

$ten_tv = $_SESSION['ten_tv'];
$user_id = isset($_SESSION['id_tv']) ? (int)$_SESSION['id_tv'] : 0;
$message = '';
$message_type = '';

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    app_require_post_csrf();
    $email_tv = trim($_POST['email_tv']);
    $sdt_tv = trim($_POST['sdt_tv']);
    $diachi_tv = trim($_POST['diachi_tv']);

    $sql = "UPDATE users SET email_tv = ?, sdt_tv = ?, diachi_tv = ? WHERE ten_tv = ?";
    $stmt = $ketnoi->prepare($sql);
    $stmt->bind_param("ssss", $email_tv, $sdt_tv, $diachi_tv, $ten_tv);
    
    if ($stmt->execute()) {
        $message = "Cập nhật thông tin thành công!";
        $message_type = "success";
        blockchain_audit_record($ketnoi, 'user', $user_id, 'profile_updated', [
            'user_id' => $user_id,
            'source' => 'profile_page',
            'updated_fields' => ['email_tv', 'sdt_tv', 'diachi_tv'],
        ], blockchain_audit_current_actor('user'));
    } else {
        $message = "Có lỗi xảy ra. Vui lòng thử lại.";
        $message_type = "error";
    }
    $stmt->close();
}

// Lấy thông tin hiện tại
$email_tv = '';
$sdt_tv = '';
$diachi_tv = '';

$sql = "SELECT email_tv, sdt_tv, diachi_tv FROM users WHERE ten_tv = ?";
$stmt = $ketnoi->prepare($sql);
$stmt->bind_param("s", $ten_tv);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $email_tv = $row['email_tv'] ?? '';
    $sdt_tv = $row['sdt_tv'] ?? '';
    $diachi_tv = $row['diachi_tv'] ?? '';
}

$stmt->close();
?>
<div class="flof-container fade-in-up">
    <div class="flof-grid-12">
        <!-- Sidebar -->
        <?php 
        $activeTab = 'edit_hoso';
        include __DIR__ . '/partials/profile_sidebar.php'; 
        ?>

        <!-- Content -->
        <div class="flof-col-8">
            <div class="flof-card">
                <h3 class="flof-section-title">Cập nhật thông tin</h3>

                <?php if ($message): ?>
                    <div class="message <?= $message_type ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form class="edit-form" method="POST" action="">
                    <?php echo app_csrf_field(); ?>
                    
                    <div class="flof-form-group">
                        <label class="flof-label">Tên đăng nhập</label>
                        <input type="text" value="<?= htmlspecialchars($ten_tv) ?>" class="flof-input" style="background-color: var(--warm-200); color: var(--warm-500); cursor: not-allowed;" readonly>
                    </div>

                    <div class="flof-form-group">
                        <label class="flof-label">Email</label>
                        <input type="email" name="email_tv" value="<?= htmlspecialchars($email_tv) ?>" class="flof-input" placeholder="Nhập email của bạn">
                    </div>

                    <div class="flof-form-group">
                        <label class="flof-label">Số điện thoại</label>
                        <input type="tel" name="sdt_tv" value="<?= htmlspecialchars($sdt_tv) ?>" class="flof-input" placeholder="Nhập số điện thoại" pattern="[0-9]{9,11}" title="Số điện thoại phải là 9-11 chữ số">
                    </div>

                    <div class="flof-form-group">
                        <label class="flof-label">Địa chỉ</label>
                        <textarea name="diachi_tv" class="flof-input" placeholder="Nhập địa chỉ của bạn" rows="3"><?= htmlspecialchars($diachi_tv) ?></textarea>
                    </div>
                    
                    <div style="margin-top: 24px; display: flex; gap: 12px;">
                        <button type="submit" class="flof-btn flof-btn-primary">Lưu thay đổi</button>
                        <a href="index.php?go=hoso" class="flof-btn" style="background-color: var(--warm-200); color: var(--warm-900);">Hủy</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
