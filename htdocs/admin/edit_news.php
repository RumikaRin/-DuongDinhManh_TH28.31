<?php
// FILE: edit_news.php
// Xử lý sửa tin tức
if (!isset($conn)) {
    require_once __DIR__ . '/header.php';
}
require_once __DIR__ . '/../includes/BlockchainAuditService.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php?route=manage_news');
    exit;
}

// Lấy thông tin tin tức hiện tại
$stmt = $ketnoi->prepare("SELECT * FROM tintuc WHERE id_sp = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$news = $result->fetch_assoc();

if (!$news) {
    header('Location: index.php?route=manage_news');
    exit;
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    app_require_post_csrf();
    $title_tintuc = trim($_POST['title_tintuc']);
    $noidung_tintuc = trim($_POST['noidung_tintuc']);
    $date_tintuc = $_POST['date_tintuc'] ?? date('Y-m-d');
    
    // Xử lý upload ảnh mới (nếu có)
    $anh_tintuc = $news['anh_tintuc']; // Giữ ảnh cũ
    if (isset($_FILES['anh_tintuc']) && $_FILES['anh_tintuc']['error'] === UPLOAD_ERR_OK) {
        // Xóa ảnh cũ nếu có
        if (!empty($news['anh_tintuc'])) {
            $old_image_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . $news['anh_tintuc'];
            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }
        }
        
        // Upload ảnh mới
        $upload_dir_disk = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'sp' . DIRECTORY_SEPARATOR;
        if (!is_dir($upload_dir_disk)) {
            @mkdir($upload_dir_disk, 0775, true);
        }

        $file_name = basename($_FILES['anh_tintuc']['name']);
        $file_name = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $file_name);
        $target_file_disk = $upload_dir_disk . $file_name;
        $web_path = 'img/sp/' . $file_name;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowed_exts = ['jpg','jpeg','png','gif','webp'];
        $file_type = function_exists('mime_content_type') ? mime_content_type($_FILES['anh_tintuc']['tmp_name']) : ($_FILES['anh_tintuc']['type'] ?? '');
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_type, $allowed_types, true) && in_array($ext, $allowed_exts, true)) {
            if (move_uploaded_file($_FILES['anh_tintuc']['tmp_name'], $target_file_disk)) {
                $anh_tintuc = $web_path;
            } else {
                $error = "Lỗi khi upload ảnh.";
            }
        } else {
            $error = "Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP).";
        }
    }
    
    // Cập nhật database
    if (empty($error)) {
        try {
            $sql = "UPDATE tintuc SET title_tintuc = ?, noidung_tintuc = ?, anh_tintuc = ?, date_tintuc = ? WHERE id_sp = ?";
            $stmt = $ketnoi->prepare($sql);
            if ($stmt === false) { throw new Exception('Lỗi chuẩn bị truy vấn: ' . $ketnoi->error); }
            $stmt->bind_param("ssssi", $title_tintuc, $noidung_tintuc, $anh_tintuc, $date_tintuc, $id);
            $stmt->execute();
            $stmt->close();

            $success = "Cập nhật tin tức thành công!";
            blockchain_audit_record($ketnoi, 'news', (int)$id, 'news_updated', [
                'news_id' => (int)$id,
                'title' => $title_tintuc,
                'published_date' => $date_tintuc,
                'image_path' => $anh_tintuc,
            ], blockchain_audit_current_actor('admin'));
            // Redirect sau 1.5 giây
            echo '<script>setTimeout(function(){ window.location.href = "index.php?route=manage_news"; }, 1500);</script>';
        } catch (Throwable $e) {
            $error = "Lỗi khi cập nhật tin tức: " . $e->getMessage();
        }
    }
}
?>

<style>
  /* Scoped styles for this page to match admin theme */
  .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
  .form-field { display: flex; flex-direction: column; }
  .form-field label { font-weight: 600; color: white; margin-bottom: 6px; }
  .form-field input[type="text"],
  .form-field input[type="date"],
  .form-field select,
  .form-field textarea {
      padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 4px; outline: none; background: rgba(255, 255, 255, 0.15); color: white;
  }
  .form-field textarea { min-height: 140px; resize: vertical; }
  .muted { color: #7f8c8d; font-size: 12px; margin-top: 6px; }
  .panel .actions { display: flex; gap: 10px; }
  .current-image { max-width: 200px; max-height: 150px; border-radius: 8px; margin-top: 10px; }
</style>

<div class="breadcrumb">
    <a href="index.php?route=dashboard">Home</a> / <a href="index.php?route=manage_news">Tin tức</a> / Sửa tin tức
</div>

<div class="page-header">
    <h1 class="page-title">Sửa tin tức</h1>
    <div class="actions">
        <a href="index.php?route=manage_news" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>
</div>

<div class="panel">
    <div class="panel-body">
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-grid">
            <?php echo app_csrf_field(); ?>
            <div class="form-field">
                <label for="title_tintuc">Tiêu đề tin tức *</label>
                <input type="text" id="title_tintuc" name="title_tintuc" required 
                       value="<?php echo htmlspecialchars($news['title_tintuc']); ?>"
                       placeholder="Nhập tiêu đề tin tức">
                <div class="muted">Tiêu đề sẽ hiển thị trên trang tin tức</div>
            </div>

            <div class="form-field">
                <label for="date_tintuc">Ngày đăng</label>
                <input type="date" id="date_tintuc" name="date_tintuc" 
                       value="<?php echo htmlspecialchars($news['date_tintuc']); ?>">
                <div class="muted">Ngày hiển thị tin tức</div>
            </div>

            <div class="form-field" style="grid-column: 1 / -1;">
                <label for="noidung_tintuc">Nội dung tin tức *</label>
                <textarea id="noidung_tintuc" name="noidung_tintuc" required 
                          placeholder="Nhập nội dung chi tiết của tin tức"><?php echo htmlspecialchars($news['noidung_tintuc']); ?></textarea>
                <div class="muted">Nội dung chi tiết của tin tức</div>
            </div>

            <div class="form-field" style="grid-column: 1 / -1;">
                <label for="anh_tintuc">Ảnh tin tức</label>
                <?php if (!empty($news['anh_tintuc'])): ?>
                    <div>
                        <strong>Ảnh hiện tại:</strong><br>
                        <img src="../<?php echo htmlspecialchars($news['anh_tintuc']); ?>" 
                             alt="Ảnh hiện tại" class="current-image">
                    </div>
                <?php endif; ?>
                <input type="file" id="anh_tintuc" name="anh_tintuc" accept="image/*">
                <div class="muted">Chọn ảnh mới cho tin tức (JPG, PNG, GIF, WEBP) - Để trống nếu không muốn thay đổi</div>
            </div>

            <div class="form-field" style="grid-column: 1 / -1;">
                <div class="panel .actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Cập nhật tin tức
                    </button>
                    <a href="index.php?route=manage_news" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const titleInput = document.getElementById('title_tintuc');
    const contentInput = document.getElementById('noidung_tintuc');
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Clear previous errors
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        
        // Validate title
        if (!titleInput.value.trim()) {
            showError(titleInput, 'Vui lòng nhập tiêu đề tin tức');
            isValid = false;
        }
        
        // Validate content
        if (!contentInput.value.trim()) {
            showError(contentInput, 'Vui lòng nhập nội dung tin tức');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    function showError(input, message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.style.color = '#fd5d93';
        errorDiv.style.fontSize = '12px';
        errorDiv.style.marginTop = '4px';
        errorDiv.textContent = message;
        input.parentNode.appendChild(errorDiv);
    }
});
</script>
