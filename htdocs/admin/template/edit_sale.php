<?php
// FILE: admin/template/edit_sale.php
if (!isset($conn)) { die('Lỗi: Không tìm thấy kết nối database.'); }
require_once dirname(__DIR__, 2) . '/includes/BlockchainAuditService.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo '<div class="breadcrumb"><a href="index.php?route=dashboard">Home</a> / Khuyến mãi</div><div class="panel"><div class="panel-body">Thiếu ID sản phẩm khuyến mãi.</div></div>'; return; }

// Lấy danh sách danh mục
$categories = [];
$cat_query = "SELECT * FROM danhmuc ORDER BY id_dm";
$cat_result = $conn->query($cat_query);
if ($cat_result && $cat_result->num_rows > 0) {
    while ($cat = $cat_result->fetch_assoc()) {
        $categories[] = $cat;
    }
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    app_require_post_csrf();
    $ten = trim($_POST['ten_tt'] ?? '');
    $gia = (int)($_POST['giasp_tt'] ?? 0);
    $tacgia = trim($_POST['tacgia_tt'] ?? '');
    $anh = trim($_POST['anh_tt'] ?? '');
    $chitiet = trim($_POST['chitietsp_tt'] ?? '');
    $giamgia = (int)($_POST['giamgia_tt'] ?? 0);
    $saugiam = (int)($_POST['saugiamgia_tt'] ?? 0);
    // Lấy danh sách danh mục được chọn
    $danh_muc_ids = isset($_POST['danh_muc']) ? array_map('intval', $_POST['danh_muc']) : [];

    // Handle image upload if provided
    if (!empty($_FILES['anh_file']['name']) && $_FILES['anh_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $type = mime_content_type($_FILES['anh_file']['tmp_name']);
        if (isset($allowed[$type])) {
            $ext = $allowed[$type];
            $base = 'sale_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
            // __DIR__ = admin/template, so root img/sp is one level above admin
            $destDir = dirname(__DIR__) . '/../img/sp/';
            if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
            $destPath = $destDir . $base;
            if (move_uploaded_file($_FILES['anh_file']['tmp_name'], $destPath)) {
                // Save relative web path
                $anh = 'img/sp/' . $base;
            }
        }
    }

    if ($ten === '' || $gia <= 0) {
        $error = 'Vui lòng nhập đầy đủ Tên và Giá hợp lệ.';
    } else {
        $stmt = $conn->prepare('UPDATE sales SET ten_tt=?, giasp_tt=?, tacgia_tt=?, anh_tt=?, chitietsp_tt=?, giamgia_tt=?, saugiamgia_tt=? WHERE id_tt=?');
        $stmt->bind_param('sisssiii', $ten, $gia, $tacgia, $anh, $chitiet, $giamgia, $saugiam, $id);
        if ($stmt && $stmt->execute()) {
            // Cập nhật danh mục
            // Xóa danh mục cũ
            $stmt2 = $conn->prepare('DELETE FROM sales_danhmuc WHERE id_tt = ?');
            $stmt2->bind_param('i', $id);
            $stmt2->execute();
            $stmt2->close();
            
            // Thêm danh mục mới
            if (!empty($danh_muc_ids)) {
                $stmt3 = $conn->prepare("INSERT INTO sales_danhmuc (id_tt, id_dm) VALUES (?, ?)");
                foreach ($danh_muc_ids as $id_dm) {
                    $stmt3->bind_param("ii", $id, $id_dm);
                    $stmt3->execute();
                }
                $stmt3->close();
            }
            
            $success = 'Cập nhật khuyến mãi thành công!';
            blockchain_audit_record($conn, 'sale', (int)$id, 'sale_updated', [
                'sale_id' => (int)$id,
                'price' => (int)$gia,
                'discount_percent' => (int)$giamgia,
                'discounted_price' => (int)$saugiam,
                'category_ids' => $danh_muc_ids,
                'image_path' => $anh,
            ], blockchain_audit_current_actor('admin'));
            app_cache_clear();
        } else {
            $error = 'Không thể cập nhật.';
        }
        if ($stmt) $stmt->close();
    }
}

$stmt2 = $conn->prepare('SELECT * FROM sales WHERE id_tt=?');
$stmt2->bind_param('i', $id);
$stmt2->execute();
$item = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

?>
<div class="breadcrumb">
    <a href="index.php?route=dashboard">Home</a> / Catalog / <a href="index.php?route=manage_products">Khuyến mãi</a> / Sửa
</div>
<div class="page-header">
    <h1 class="page-title">Sửa sản phẩm khuyến mãi #<?php echo (int)$id; ?></h1>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
    <button type="button" class="close" onclick="this.parentElement.remove()">&times;</button>
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success">
    <button type="button" class="close" onclick="this.parentElement.remove()">&times;</button>
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>
<?php if ($item): ?>
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
    <div class="panel-header"><h3 class="panel-title">Thông tin khuyến mãi</h3></div>
    <div class="panel-body">
        <form method="post" action="index.php?route=edit_sale&id=<?php echo (int)$id; ?>" enctype="multipart/form-data">
            <?php echo app_csrf_field(); ?>
            <div class="form-grid">
                <div class="form-field">
                    <label for="ten_tt">Tên <span class="required">*</span></label>
                    <input type="text" id="ten_tt" name="ten_tt" value="<?php echo htmlspecialchars($item['ten_tt']); ?>" required />
                </div>
                <div class="form-field">
                    <label for="giasp_tt">Giá (VNĐ) <span class="required">*</span></label>
                    <input type="number" id="giasp_tt" name="giasp_tt" value="<?php echo (int)$item['giasp_tt']; ?>" required />
                </div>
                <div class="form-field">
                    <label for="giamgia_tt">Giảm (%)</label>
                    <input type="number" id="giamgia_tt" name="giamgia_tt" value="<?php echo (int)$item['giamgia_tt']; ?>" />
                </div>
                <div class="form-field">
                    <label for="saugiamgia_tt">Sau giảm (VNĐ)</label>
                    <input type="number" id="saugiamgia_tt" name="saugiamgia_tt" value="<?php echo (int)$item['saugiamgia_tt']; ?>" />
                </div>
                <div class="form-field">
                    <label for="tacgia_tt">Nhà sản xuất / Tác giả</label>
                    <input type="text" id="tacgia_tt" name="tacgia_tt" value="<?php echo htmlspecialchars($item['tacgia_tt']); ?>" />
                </div>
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label>Danh mục</label>
                    <div class="category-checkboxes" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px; margin-top: 8px;">
                        <?php 
                        // Lấy danh mục hiện tại của sản phẩm
                        $current_categories = [];
                        if (isset($item['id_tt'])) {
                            $cat_query = "SELECT id_dm FROM sales_danhmuc WHERE id_tt = " . (int)$item['id_tt'];
                            $cat_result = $conn->query($cat_query);
                            if ($cat_result && $cat_result->num_rows > 0) {
                                while ($cat = $cat_result->fetch_assoc()) {
                                    $current_categories[] = (int)$cat['id_dm'];
                                }
                            }
                        }
                        ?>
                        <?php foreach ($categories as $cat): ?>
                            <label style="display: flex; align-items: center; gap: 8px; padding: 8px; background: rgba(255, 255, 255, 0.1); border-radius: 4px; cursor: pointer;">
                                <input type="checkbox" name="danh_muc[]" value="<?php echo $cat['id_dm']; ?>" <?php echo in_array($cat['id_dm'], $current_categories) ? 'checked' : ''; ?> style="margin: 0;">
                                <span><?php echo htmlspecialchars($cat['ten_dm']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="muted">Có thể chọn nhiều danh mục</div>
                </div>
                <div class="form-field" style="grid-column:1 / -1;">
                    <label for="chitietsp_tt">Chi tiết</label>
                    <textarea id="chitietsp_tt" name="chitietsp_tt" rows="5"><?php echo htmlspecialchars($item['chitietsp_tt']); ?></textarea>
                </div>
                <div class="form-field" style="grid-column:1 / -1;">
                    <label for="anh_tt">Đường dẫn ảnh (giữ nguyên nếu dùng file upload)</label>
                    <input type="text" id="anh_tt" name="anh_tt" value="<?php echo htmlspecialchars($item['anh_tt']); ?>" />
                    <div class="muted">Hoặc upload file ảnh mới:</div>
                    <input type="file" id="anh_file" name="anh_file" accept="image/*" style="margin-top: 8px;" />
                    <div class="muted">Chấp nhận: JPG, PNG, WEBP</div>
                </div>
                <?php if (!empty($item['anh_tt'])): ?>
                <div class="form-field" style="grid-column:1 / -1;">
                    <label>Ảnh hiện tại:</label>
                    <img src="../<?php echo htmlspecialchars($item['anh_tt']); ?>" alt="preview" style="width:120px;height:120px;object-fit:cover;border-radius:6px;border:1px solid #eee;" />
                </div>
                <?php endif; ?>
            </div>
            <div class="actions">
                <button class="btn btn-success" type="submit"><i class="fas fa-save"></i> Lưu thay đổi</button>
                <a class="btn btn-secondary" href="index.php?route=manage_products"><i class="fas fa-arrow-left"></i> Quay lại</a>
                <button class="btn btn-primary" type="submit" formaction="sale_action.php" name="action" value="convert" onclick="return confirm('Chuyển sản phẩm sale này thành sản phẩm thường?');"><i class="fas fa-exchange-alt"></i> Chuyển thành SP thường</button>
                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
            </div>
        </form>
    </div>
</div>
<script>
function recalc() {
    var gia = parseInt(document.getElementById('giasp_tt').value || '0', 10);
    var pct = parseInt(document.getElementById('giamgia_tt').value || '0', 10);
    if (gia >= 0 && pct >= 0) {
        var after = Math.round(gia * (100 - pct) / 100);
        document.getElementById('saugiamgia_tt').value = after;
    }
}
document.getElementById('giasp_tt').addEventListener('input', recalc);
document.getElementById('giamgia_tt').addEventListener('input', recalc);
</script>
<?php else: ?>
<div class="panel"><div class="panel-body">Không tìm thấy bản ghi.</div></div>
<?php endif; ?>
