<?php
// FILE: admin/template/account.php
if (!isset($conn)) { die('Lỗi: Không tìm thấy kết nối database.'); }
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once dirname(__DIR__, 2) . '/includes/BlockchainAuditService.php';
$user_id = isset($_SESSION['id_tv']) ? (int)$_SESSION['id_tv'] : 0;

$success = '';
$error = '';

if ($user_id > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        app_require_post_csrf();
        $ten_tv = trim($_POST['ten_tv'] ?? '');
        $email  = trim($_POST['email_tv'] ?? '');
        $sdt    = trim($_POST['sdt_tv'] ?? '');
        $diachi = trim($_POST['diachi_tv'] ?? '');
        $pass   = trim($_POST['mk_tv'] ?? '');

        if ($ten_tv === '' || $email === '') {
            $error = 'Vui lòng nhập đầy đủ Tên và Email';
        } else {
            // Update cơ bản
            $stmt = $conn->prepare('UPDATE users SET ten_tv = ?, email_tv = ?, sdt_tv = ?, diachi_tv = ? WHERE id_tv = ?');
            $stmt->bind_param('ssssi', $ten_tv, $email, $sdt, $diachi, $user_id);
            $stmt->execute();
            $stmt->close();

            // Đổi mật khẩu nếu có nhập
            if ($pass !== '') {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $stmt2 = $conn->prepare('UPDATE users SET mk_tv = ? WHERE id_tv = ?');
                $stmt2->bind_param('si', $hash, $user_id);
                $stmt2->execute();
                $stmt2->close();
            }

            $_SESSION['ten_tv'] = $ten_tv;
            $success = 'Cập nhật tài khoản thành công!';
            blockchain_audit_record($conn, 'user', (int)$user_id, 'profile_updated', [
                'user_id' => (int)$user_id,
                'source' => 'admin_account_page',
                'updated_fields' => $pass !== ''
                    ? ['ten_tv', 'email_tv', 'sdt_tv', 'diachi_tv', 'mk_tv']
                    : ['ten_tv', 'email_tv', 'sdt_tv', 'diachi_tv'],
            ], blockchain_audit_current_actor('admin'));
        }
    }

    // Lấy lại thông tin
    $stmt3 = $conn->prepare('SELECT ten_tv, email_tv, sdt_tv, diachi_tv FROM users WHERE id_tv = ?');
    $stmt3->bind_param('i', $user_id);
    $stmt3->execute();
    $info = $stmt3->get_result()->fetch_assoc();
    $stmt3->close();
} else {
    $error = 'Bạn chưa đăng nhập.';
}
?>

<div class="breadcrumb">
    <a href="index.php?route=dashboard">Home</a> / Tài khoản
</div>
<div class="page-header">
    <h1 class="page-title">Thông tin tài khoản</h1>
</div>

<?php if ($error): ?>
<div style="background:#fde2e2;color:#b42318;border:1px solid #fca5a5;padding:10px;border-radius:6px;margin-bottom:12px;"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div style="background:#dcfce7;color:#166534;border:1px solid #86efac;padding:10px;border-radius:6px;margin-bottom:12px;"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($user_id > 0): ?>
<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">Chỉnh sửa thông tin</h3>
    </div>
    <div class="panel-body">
        <form method="post" action="index.php?route=account">
            <?php echo app_csrf_field(); ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label>Tên hiển thị</label>
                    <input type="text" name="ten_tv" value="<?php echo htmlspecialchars($info['ten_tv'] ?? ''); ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;" />
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="email_tv" value="<?php echo htmlspecialchars($info['email_tv'] ?? ''); ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;" />
                </div>
                <div>
                    <label>Số điện thoại</label>
                    <input type="text" name="sdt_tv" value="<?php echo htmlspecialchars($info['sdt_tv'] ?? ''); ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;" />
                </div>
                <div>
                    <label>Địa chỉ</label>
                    <input type="text" name="diachi_tv" value="<?php echo htmlspecialchars($info['diachi_tv'] ?? ''); ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;" />
                </div>
                <div>
                    <label>Mật khẩu mới (để trống nếu không đổi)</label>
                    <input type="password" name="mk_tv" value="" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;" />
                </div>
            </div>
            <div style="margin-top:16px;display:flex;gap:10px;">
                <button class="btn btn-success" type="submit"><i class="fas fa-save"></i> Lưu thay đổi</button>
                <button class="btn btn-danger" type="submit" formaction="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
