<?php
// FILE: add_sale.php
// Xử lý thêm sản phẩm khuyến mãi
if (!isset($conn)) {
    require_once __DIR__ . '/header.php';
}
require_once __DIR__ . '/../includes/BlockchainAuditService.php';

// Lấy danh sách danh mục
$categories = [];
$cat_query = "SELECT * FROM danhmuc ORDER BY id_dm";
$cat_result = $conn->query($cat_query);
if ($cat_result && $cat_result->num_rows > 0) {
    while ($cat = $cat_result->fetch_assoc()) {
        $categories[] = $cat;
    }
}

// Kiểm tra nếu form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    app_require_post_csrf();
    $ten_tt = trim($_POST['ten_tt']);
    // Bảng sales dùng INT cho giá => dùng số nguyên
    $giasp_tt = intval($_POST['giasp_tt']);
    $giamgia_tt = intval($_POST['giamgia_tt']);
    $chitietsp_tt = trim($_POST['chitietsp_tt']);
    $tacgia_tt = trim($_POST['tacgia_tt']);
    // Lấy danh sách danh mục được chọn
    $danh_muc_ids = isset($_POST['danh_muc']) ? array_map('intval', $_POST['danh_muc']) : [];
    
    // Tính giá sau giảm (int)
    $saugiamgia_tt = (int) round($giasp_tt - ($giasp_tt * $giamgia_tt / 100));
    
    // Xử lý upload ảnh
    $anh_tt = '';
    if (isset($_FILES['anh_tt']) && $_FILES['anh_tt']['error'] === UPLOAD_ERR_OK) {
        // Thư mục upload thực tế (ngoài admin/)
        $upload_dir_disk = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'sp' . DIRECTORY_SEPARATOR;
        if (!is_dir($upload_dir_disk)) {
            @mkdir($upload_dir_disk, 0775, true);
        }

        $file_name = basename($_FILES['anh_tt']['name']);
        // Loại bỏ ký tự không hợp lệ
        $file_name = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $file_name);
        $target_file_disk = $upload_dir_disk . $file_name;

        // Đường dẫn lưu trong DB để frontend truy cập
        $web_path = 'img/sp/' . $file_name;

        // Kiểm tra loại file (MIME + phần mở rộng)
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowed_exts = ['jpg','jpeg','png','gif','webp'];
        $file_type = function_exists('mime_content_type') ? mime_content_type($_FILES['anh_tt']['tmp_name']) : ($_FILES['anh_tt']['type'] ?? '');
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_type, $allowed_types, true) && in_array($ext, $allowed_exts, true)) {
            if (move_uploaded_file($_FILES['anh_tt']['tmp_name'], $target_file_disk)) {
                $anh_tt = $web_path;
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
            // Tạo id_tt tiếp theo nếu PK không AUTO_INCREMENT
            $nextId = 1;
            $rs = $conn->query("SELECT IFNULL(MAX(id_tt),0)+1 AS next FROM sales");
            if ($rs) {
                $row = $rs->fetch_assoc();
                if ($row && isset($row['next'])) { $nextId = (int)$row['next']; }
                $rs->free();
            }

            $sql = "INSERT INTO sales (id_tt, ten_tt, anh_tt, giasp_tt, chitietsp_tt, giamgia_tt, saugiamgia_tt, tacgia_tt) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Lỗi chuẩn bị truy vấn: ' . $conn->error);
            }
            // Types: i,s,s,i,s,i,i,s
            $stmt->bind_param("issisiis", $nextId, $ten_tt, $anh_tt, $giasp_tt, $chitietsp_tt, $giamgia_tt, $saugiamgia_tt, $tacgia_tt);
            $stmt->execute();
            $stmt->close();

            // Lưu danh mục vào bảng trung gian
            if (!empty($danh_muc_ids)) {
                $stmt2 = $conn->prepare("INSERT INTO sales_danhmuc (id_tt, id_dm) VALUES (?, ?)");
                if ($stmt2 === false) { throw new Exception('Lỗi chuẩn bị truy vấn danh mục: ' . $conn->error); }
                
                foreach ($danh_muc_ids as $id_dm) {
                    $stmt2->bind_param("ii", $nextId, $id_dm);
                    $stmt2->execute();
                }
                $stmt2->close();
            }
            $success = "Thêm sản phẩm khuyến mãi thành công!";
            blockchain_audit_record($conn, 'sale', (int)$nextId, 'sale_created', [
                'sale_id' => (int)$nextId,
                'price' => (int)$giasp_tt,
                'discount_percent' => (int)$giamgia_tt,
                'discounted_price' => (int)$saugiamgia_tt,
                'category_ids' => $danh_muc_ids,
                'image_path' => $anh_tt,
            ], blockchain_audit_current_actor('admin'));
            app_cache_clear();
            // Dùng JS redirect để tránh 'headers already sent'
            echo '<script>setTimeout(function(){ window.location.href = "index.php?route=manage_products"; }, 1500);</script>';
        } catch (Throwable $e) {
            $error = "Lỗi khi thêm sản phẩm khuyến mãi: " . $e->getMessage();
            if (isset($stmt) && $stmt instanceof mysqli_stmt) { $stmt->close(); }
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
  .form-field input[type="number"],
  .form-field select,
  .form-field textarea { padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 4px; outline: none; background: rgba(255, 255, 255, 0.15); color: white; }
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
    <div class="panel-title">Thêm Sản Phẩm Khuyến Mãi</div>
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

    <form method="POST" enctype="multipart/form-data" id="saleForm">
      <?php echo app_csrf_field(); ?>
      <div class="form-grid">
        <div class="form-field">
          <label for="ten_tt">Tên Sản Phẩm <span class="required">*</span></label>
          <input type="text" id="ten_tt" name="ten_tt" required>
        </div>
        <div class="form-field">
          <label for="giasp_tt">Giá Gốc (VNĐ) <span class="required">*</span></label>
          <input type="number" id="giasp_tt" name="giasp_tt" step="1000" min="0" required>
        </div>
        <div class="form-field">
          <label for="giamgia_tt">Giảm Giá (%) <span class="required">*</span></label>
          <input type="number" id="giamgia_tt" name="giamgia_tt" min="1" max="99" required>
        </div>
        <div class="form-field">
          <label for="tacgia_tt">Tác Giả <span class="required">*</span></label>
          <input type="text" id="tacgia_tt" name="tacgia_tt" required>
        </div>
        <div class="form-field" style="grid-column: 1 / -1;">
          <label>Giá Sau Giảm (VNĐ)</label>
          <input type="text" id="saugiamgia_display" readonly style="background-color: rgba(255, 255, 255, 0.15);">
          <div class="muted">Tự động tính dựa trên giá gốc và % giảm</div>
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
          <label for="anh_tt">Ảnh Sản Phẩm</label>
          <input type="file" id="anh_tt" name="anh_tt" accept="image/*">
          <div class="muted">Chấp nhận: JPG, PNG, GIF, WEBP</div>
        </div>
        <div class="form-field" style="grid-column: 1 / -1;">
          <label for="chitietsp_tt">Chi Tiết Sản Phẩm <span class="required">*</span></label>
          <textarea id="chitietsp_tt" name="chitietsp_tt" rows="6" required></textarea>
        </div>
      </div>
      <div class="actions" style="margin-top: 10px;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Thêm Sản Phẩm Khuyến Mãi</button>
        <a href="index.php?route=manage_products" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy</a>
      </div>
    </form>
  </div>
</div>

<script>
// Tự động tính giá sau giảm
function calculateDiscountedPrice() {
    const giaGoc = parseFloat(document.getElementById('giasp_tt').value) || 0;
    const giamGia = parseFloat(document.getElementById('giamgia_tt').value) || 0;
    
    if (giaGoc > 0 && giamGia > 0 && giamGia <= 100) {
        const giaSauGiam = giaGoc - (giaGoc * giamGia / 100);
        document.getElementById('saugiamgia_display').value = giaSauGiam.toLocaleString('vi-VN') + ' VNĐ';
    } else {
        document.getElementById('saugiamgia_display').value = '';
    }
}

document.getElementById('giasp_tt').addEventListener('input', calculateDiscountedPrice);
document.getElementById('giamgia_tt').addEventListener('input', calculateDiscountedPrice);
</script>
