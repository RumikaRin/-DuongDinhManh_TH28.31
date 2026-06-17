<?php
// FILE: add_products.php
// Xử lý thêm sản phẩm mới
if (!isset($conn)) {
    require_once __DIR__ . '/header.php';
}
require_once __DIR__ . '/../includes/BlockchainAuditService.php';

// Kiểm tra nếu form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    app_require_post_csrf();
    $ten_sp = trim($_POST['ten_sp']);
    $gia_sp = floatval($_POST['gia_sp']);
    $chitiet_sp = trim($_POST['chitiet_sp']);
    $tacgia_sp = trim($_POST['tacgia_sp']);
    // Lấy danh sách danh mục được chọn
    $danh_muc_ids = isset($_POST['danh_muc']) ? array_map('intval', $_POST['danh_muc']) : [];
    
    // Xử lý upload ảnh
    $anh_sp = '';
    if (isset($_FILES['anh_sp']) && $_FILES['anh_sp']['error'] === UPLOAD_ERR_OK) {
        // Thư mục upload thực tế (ngoài admin/)
        $upload_dir_disk = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'sp' . DIRECTORY_SEPARATOR;
        if (!is_dir($upload_dir_disk)) {
            @mkdir($upload_dir_disk, 0775, true);
        }

        $file_name = basename($_FILES['anh_sp']['name']);
        // Loại bỏ ký tự không hợp lệ
        $file_name = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $file_name);
        $target_file_disk = $upload_dir_disk . $file_name;

        // Đường dẫn lưu trong DB để frontend truy cập
        $web_path = 'img/sp/' . $file_name;

        // Kiểm tra loại file (MIME + phần mở rộng)
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowed_exts = ['jpg','jpeg','png','gif','webp'];
        $file_type = function_exists('mime_content_type') ? mime_content_type($_FILES['anh_sp']['tmp_name']) : ($_FILES['anh_sp']['type'] ?? '');
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_type, $allowed_types, true) && in_array($ext, $allowed_exts, true)) {
            if (move_uploaded_file($_FILES['anh_sp']['tmp_name'], $target_file_disk)) {
                $anh_sp = $web_path;
            } else {
                $error = "Lỗi khi upload ảnh.";
            }
        } else {
            $error = "Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP).";
        }
    }
    
    // Thêm vào database
    if (empty($error)) {
        try {
            // Tạo id_sp tiếp theo nếu PK không AUTO_INCREMENT
            $nextId = 1;
            $rs = $conn->query("SELECT IFNULL(MAX(id_sp),0)+1 AS next FROM sanpham");
            if ($rs) {
                $row = $rs->fetch_assoc();
                if ($row && isset($row['next'])) { $nextId = (int)$row['next']; }
                $rs->free();
            }

            $sql = "INSERT INTO sanpham (id_sp, ten_sp, gia_sp, chitiet_sp, anh_sp, tacgia_sp) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) { throw new Exception('Lỗi chuẩn bị truy vấn: ' . $conn->error); }
            $stmt->bind_param("isdsss", $nextId, $ten_sp, $gia_sp, $chitiet_sp, $anh_sp, $tacgia_sp);
            $stmt->execute();
            $stmt->close();

            // Lưu danh mục vào bảng trung gian
            if (!empty($danh_muc_ids)) {
                $stmt2 = $conn->prepare("INSERT INTO sanpham_danhmuc (id_sp, id_dm) VALUES (?, ?)");
                if ($stmt2 === false) { throw new Exception('Lỗi chuẩn bị truy vấn danh mục: ' . $conn->error); }
                
                foreach ($danh_muc_ids as $id_dm) {
                    $stmt2->bind_param("ii", $nextId, $id_dm);
                    $stmt2->execute();
                }
                $stmt2->close();
            }

            $success = "Thêm sản phẩm thành công!";
            blockchain_audit_record($conn, 'product', (int)$nextId, 'product_created', [
                'product_id' => (int)$nextId,
                'price' => (int)$gia_sp,
                'category_ids' => $danh_muc_ids,
                'image_path' => $anh_sp,
            ], blockchain_audit_current_actor('admin'));
            app_cache_clear();
            // Dùng JS redirect để tránh 'headers already sent'
            echo '<script>setTimeout(function(){ window.location.href = "index.php?route=manage_products"; }, 1500);</script>';
        } catch (Throwable $e) {
            $error = "Lỗi khi thêm sản phẩm: " . $e->getMessage();
        }
    }
}

// Lấy danh sách danh mục (nếu có)
$categories = [];
$sql = "SELECT id_dm, ten_dm FROM danhmuc ORDER BY ten_dm";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<style>
  /* Scoped styles for this page to match admin theme */
  .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
  .form-field { display: flex; flex-direction: column; }
  .form-field label { font-weight: 600; color: white; margin-bottom: 6px; }
  .form-field input[type="text"],
  .form-field input[type="number"],
  .form-field select,
  .form-field textarea {
      padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 4px; outline: none; background: rgba(255, 255, 255, 0.15); color: white;
  }
  .form-field textarea { min-height: 140px; resize: vertical; }
  .muted { color: #7f8c8d; font-size: 12px; margin-top: 6px; }
  .panel .actions { display: flex; gap: 10px; }
  .panel .actions .btn-secondary { 
    background: rgba(255, 255, 255, 0.2); 
    color: #ffffff; 
    border: 2px solid rgba(255, 255, 255, 0.3);
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  }
  .panel .actions .btn-secondary:hover { 
    background: rgba(255, 255, 255, 0.3); 
    transform: translateY(-1px);
  }
  .alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 16px; }
  .alert-success { background: #d4edda; color: #155724; }
  .alert-danger { background: #f8d7da; color: #721c24; }
  .close { background: transparent; border: 0; font-size: 18px; cursor: pointer; float: right; }
  .required { color: #e74c3c; }
</style>

<div class="panel">
  <div class="panel-header">
    <div class="panel-title">Thêm Sản Phẩm Mới</div>
  </div>
  <div class="panel-body">
    <?php if (isset($success)): ?>
      <div class="alert alert-success">
        <button type="button" class="close" onclick="this.parentElement.remove()">&times;</button>
        <?php echo $success; ?>
      </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
      <div class="alert alert-danger">
        <button type="button" class="close" onclick="this.parentElement.remove()">&times;</button>
        <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <?php echo app_csrf_field(); ?>
      <div class="form-grid">
        <div class="form-field">
          <label for="ten_sp">Tên Sản Phẩm <span class="required">*</span></label>
          <input type="text" id="ten_sp" name="ten_sp" required>
        </div>
        <div class="form-field">
          <label for="gia_sp">Giá (VNĐ) <span class="required">*</span></label>
          <input type="number" id="gia_sp" name="gia_sp" step="1000" min="0" required>
        </div>
        <div class="form-field">
          <label for="tacgia_sp">Tác Giả <span class="required">*</span></label>
          <input type="text" id="tacgia_sp" name="tacgia_sp" required>
        </div>
        <div class="form-field" style="grid-column: 1 / -1;">
          <label>Danh Mục</label>
          <div class="category-checkboxes" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px; margin-top: 8px;">
            <?php foreach ($categories as $cat): ?>
              <label style="display: flex; align-items: center; gap: 8px; padding: 8px; background: rgba(255, 255, 255, 0.1); border-radius: 4px; cursor: pointer;">
                  <input type="checkbox" name="danh_muc[]" value="<?php echo $cat['id_dm']; ?>" style="margin: 0;">
                  <span><?php echo htmlspecialchars($cat['ten_dm']); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="muted">Có thể chọn nhiều danh mục</div>
        </div>
        <div class="form-field" style="grid-column: 1 / -1;">
          <label for="anh_sp">Ảnh Sản Phẩm</label>
          <input type="file" id="anh_sp" name="anh_sp" accept="image/*">
          <div class="muted">Chấp nhận: JPG, PNG, GIF, WEBP</div>
        </div>
        <div class="form-field" style="grid-column: 1 / -1;">
          <label for="chitiet_sp">Chi Tiết Sản Phẩm <span class="required">*</span></label>
          <textarea id="chitiet_sp" name="chitiet_sp" required></textarea>
        </div>
      </div>
      <div class="actions" style="margin-top: 10px;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Thêm Sản Phẩm</button>
        <a href="index.php?route=manage_products" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy</a>
      </div>
    </form>
  </div>
</div>
