<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php?go=" . basename(__FILE__, ".php"));
    exit();
}
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php?go=dangnhap");
    exit();
}

// Nhận thông báo từ session (nếu có)
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';

// Xóa thông báo sau khi hiển thị
unset($_SESSION['message']);
unset($_SESSION['message_type']);
?>
<div class="flof-container fade-in-up">
    <div class="flof-grid-12">
        <!-- Sidebar -->
        <?php 
        $activeTab = 'caidat';
        $ten_tv = $_SESSION['ten_tv'] ?? '';
        $email_tv = '';
        if ($ten_tv) {
            $sql_user = "SELECT email_tv FROM users WHERE ten_tv = ?";
            $stmt_user = $ketnoi->prepare($sql_user);
            $stmt_user->bind_param("s", $ten_tv);
            $stmt_user->execute();
            $res_user = $stmt_user->get_result();
            if ($row_user = $res_user->fetch_assoc()) {
                $email_tv = $row_user['email_tv'];
            }
            $stmt_user->close();
        }
        include __DIR__ . '/partials/profile_sidebar.php'; 
        ?>

        <!-- Content -->
        <div class="flof-col-8">
            <div class="flof-card">
                <h3 class="flof-section-title">Đổi mật khẩu</h3>

                <?php if (!empty($message)): ?>
                    <div class="message-box <?php echo $message_type; ?>" style="margin-bottom: 24px; padding: 12px; border-radius: var(--radius-xl); font-size: 0.875rem; <?php echo $message_type === 'error' ? 'background: rgba(239, 68, 68, 0.1); color: #ef4444;' : 'background: rgba(16, 185, 129, 0.1); color: #10b981;'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form action="xuly_caidat.php" method="POST">
                    <?php echo app_csrf_field(); ?>
                    
                    <div class="flof-form-group">
                        <label class="flof-label" for="old_password">Mật khẩu cũ</label>
                        <input type="password" id="old_password" name="oldpass" class="flof-input" required>
                    </div>
                    
                    <div class="flof-form-group">
                        <label class="flof-label" for="new_password">Mật khẩu mới</label>
                        <input type="password" id="new_password" name="newpass" class="flof-input" required>
                    </div>
                    
                    <div class="flof-form-group">
                        <label class="flof-label" for="confirm_new_password">Xác nhận mật khẩu mới</label>
                        <input type="password" id="confirm_new_password" name="confirm_newpass" class="flof-input" required>
                    </div>
                    
                    <div style="margin-top: 24px; display: flex; gap: 12px;">
                        <button type="submit" class="flof-btn flof-btn-primary">Lưu thay đổi</button>
                        <a href="index.php?go=hoso" class="flof-btn" style="background-color: var(--warm-200); color: var(--warm-900);">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
