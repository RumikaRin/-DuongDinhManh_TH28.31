<?php
// FILE: admin/template/edit_product.php
if (!isset($conn)) { die('Lỗi: Không tìm thấy kết nối database.'); }
require_once dirname(__DIR__, 2) . '/includes/BlockchainAuditService.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo '<div class="breadcrumb"><a href="index.php?route=dashboard">Home</a> / Sản phẩm</div><div class="panel"><div class="panel-body">Thiếu ID sản phẩm.</div></div>'; return; }

$cats = $conn->query("SELECT id_dm, ten_dm FROM danhmuc ORDER BY ten_dm ASC");

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    app_require_post_csrf();
    $ten_sp = trim($_POST['ten_sp'] ?? '');
    $gia_sp = (int)($_POST['gia_sp'] ?? 0);
    $tacgia_sp = trim($_POST['tacgia_sp'] ?? '');
    $anh_sp = trim($_POST['anh_sp'] ?? '');
    $chitiet_sp = trim($_POST['chitiet_sp'] ?? '');
    // Lấy danh sách danh mục được chọn
    $danh_muc_ids = isset($_POST['danh_muc']) ? array_map('intval', $_POST['danh_muc']) : [];

    // Handle image upload if provided
    if (!empty($_FILES['anh_file']['name']) && $_FILES['anh_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $type = mime_content_type($_FILES['anh_file']['tmp_name']);
        if (isset($allowed[$type])) {
            $ext = $allowed[$type];
            $base = 'product_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
            // __DIR__ = admin/template, save to root img/sp
            $destDir = dirname(__DIR__) . '/../img/sp/';
            if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
            $destPath = $destDir . $base;
            if (move_uploaded_file($_FILES['anh_file']['tmp_name'], $destPath)) {
                $anh_sp = 'img/sp/' . $base; // relative web path
            }
        }
    }

    if ($ten_sp === '' || $gia_sp <= 0) {
        $error = 'Vui lòng nhập đầy đủ Tên và Giá sản phẩm hợp lệ.';
    } else {
        $stmt = $conn->prepare('UPDATE sanpham SET ten_sp=?, gia_sp=?, tacgia_sp=?, anh_sp=?, chitiet_sp=? WHERE id_sp=?');
        $stmt->bind_param('sisssi', $ten_sp, $gia_sp, $tacgia_sp, $anh_sp, $chitiet_sp, $id);
        if ($stmt && $stmt->execute()) {
            // Cập nhật danh mục
            // Xóa danh mục cũ
            $stmt2 = $conn->prepare('DELETE FROM sanpham_danhmuc WHERE id_sp = ?');
            $stmt2->bind_param('i', $id);
            $stmt2->execute();
            $stmt2->close();
            
            // Thêm danh mục mới
            if (!empty($danh_muc_ids)) {
                $stmt3 = $conn->prepare("INSERT INTO sanpham_danhmuc (id_sp, id_dm) VALUES (?, ?)");
                foreach ($danh_muc_ids as $id_dm) {
                    $stmt3->bind_param("ii", $id, $id_dm);
                    $stmt3->execute();
                }
                $stmt3->close();
            }
            
            $success = 'Cập nhật sản phẩm thành công!';
            blockchain_audit_record($conn, 'product', (int)$id, 'product_updated', [
                'product_id' => (int)$id,
                'price' => (int)$gia_sp,
                'category_ids' => $danh_muc_ids,
                'image_path' => $anh_sp,
            ], blockchain_audit_current_actor('admin'));
            app_cache_clear();
        } else {
            $error = 'Không thể cập nhật sản phẩm.';
        }
        if ($stmt) $stmt->close();
    }
}

// Fetch product
$stmt2 = $conn->prepare('SELECT id_sp, ten_sp, gia_sp, tacgia_sp, anh_sp, chitiet_sp FROM sanpham WHERE id_sp=?');
$stmt2->bind_param('i', $id);
$stmt2->execute();
$prod = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

// Lấy danh mục hiện tại của sản phẩm
$current_categories = [];
if ($prod) {
    $cat_query = "SELECT id_dm FROM sanpham_danhmuc WHERE id_sp = " . (int)$prod['id_sp'];
    $cat_result = $conn->query($cat_query);
    if ($cat_result && $cat_result->num_rows > 0) {
        while ($cat = $cat_result->fetch_assoc()) {
            $current_categories[] = (int)$cat['id_dm'];
        }
    }
}

?>
<div class="breadcrumb">
    <a href="index.php?route=dashboard">Home</a> / Catalog / <a href="index.php?route=manage_products">Sản phẩm</a> / Sửa
</div>
<div class="page-header">
    <h1 class="page-title">Sửa sản phẩm #<?php echo (int)$id; ?></h1>
</div>

<?php if ($error): ?>
<div style="background:#fde2e2;color:#b42318;border:1px solid #fca5a5;padding:10px;border-radius:6px;margin-bottom:12px;"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div style="background:#dcfce7;color:#166534;border:1px solid #86efac;padding:10px;border-radius:6px;margin-bottom:12px;"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($prod): ?>
<div class="panel">
    <div class="panel-header"><h3 class="panel-title">Thông tin sản phẩm</h3></div>
    <div class="panel-body">
        <form method="post" action="index.php?route=edit_product&id=<?php echo (int)$id; ?>" enctype="multipart/form-data">
            <?php echo app_csrf_field(); ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label>Tên sản phẩm</label>
                    <input type="text" name="ten_sp" value="<?php echo htmlspecialchars($prod['ten_sp']); ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;" required />
                </div>
                <div>
                    <label>Giá (VNĐ)</label>
                    <input type="number" name="gia_sp" value="<?php echo (int)$prod['gia_sp']; ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;" required />
                </div>
                <div>
                    <label>Nhà sản xuất / Tác giả</label>
                    <input type="text" name="tacgia_sp" value="<?php echo htmlspecialchars($prod['tacgia_sp']); ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;" />
                </div>
                <div style="grid-column:1 / -1;">
                    <label>Danh mục</label>
                    <div class="category-checkboxes" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px; margin-top: 8px;">
                        <?php if ($cats): while ($c = $cats->fetch_assoc()): ?>
                            <label style="display: flex; align-items: center; gap: 8px; padding: 8px; background: rgba(255, 255, 255, 0.1); border-radius: 4px; cursor: pointer;">
                                <input type="checkbox" name="danh_muc[]" value="<?php echo (int)$c['id_dm']; ?>" <?php echo in_array((int)$c['id_dm'], $current_categories) ? 'checked' : ''; ?> style="margin: 0;">
                                <span><?php echo htmlspecialchars($c['ten_dm']); ?></span>
                            </label>
                        <?php endwhile; endif; ?>
                    </div>
                    <div class="muted" style="color: #7f8c8d; font-size: 12px; margin-top: 6px;">Có thể chọn nhiều danh mục</div>
                </div>
                <div style="grid-column:1 / -1;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <label>Đường dẫn ảnh (vd: img/sp/6.jpg)</label>
                        <input type="text" name="anh_sp" value="<?php echo htmlspecialchars($prod['anh_sp']); ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;" />
                    </div>
                    <div>
                        <div style="margin-top:8px;">
                            <?php if (!empty($prod['anh_sp'])): ?>
                                <img src="../<?php echo htmlspecialchars($prod['anh_sp']); ?>" alt="preview" style="width:120px;height:120px;object-fit:cover;border-radius:6px;border:1px solid #eee;" />
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div style="grid-column:1 / -1;">
                    <label>Chi tiết</label>
                    <textarea name="chitiet_sp" rows="5" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;"><?php echo htmlspecialchars($prod['chitiet_sp']); ?></textarea>
                </div>
            </div>
            <div style="margin-top:16px;display:flex;gap:10px;">
                <button class="btn btn-success" type="submit"><i class="fas fa-save"></i> Lưu thay đổi</button>
                <a class="btn btn-danger" href="index.php?route=manage_products"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<div class="panel"><div class="panel-body">Không tìm thấy sản phẩm.</div></div>
<?php endif; ?>
