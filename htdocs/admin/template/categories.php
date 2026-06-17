<?php
// FILE: admin/template/categories.php
if (!isset($conn)) { die('Lỗi: Không tìm thấy kết nối database.'); }
require_once dirname(__DIR__, 2) . '/includes/BlockchainAuditService.php';

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    app_require_post_csrf();
    if ($action === 'delete' && $id > 0) {
        $stmt = $conn->prepare('DELETE FROM danhmuc WHERE id_dm = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            blockchain_audit_record($conn, 'category', (int)$id, 'category_deleted', [
                'category_id' => (int)$id,
            ], blockchain_audit_current_actor('admin'));
            app_cache_clear();
        }
        $stmt->close();
        $msg = 'Đã xóa danh mục.';
    } else {
        $ten = trim($_POST['ten_dm'] ?? '');
        $mota = trim($_POST['mota'] ?? '');
        if ($ten === '') {
            $msg = 'Tên danh mục không được để trống';
        } else {
            if ($action === 'edit' && $id > 0) {
                $stmt = $conn->prepare('UPDATE danhmuc SET ten_dm=?, mota=? WHERE id_dm=?');
                $stmt->bind_param('ssi', $ten, $mota, $id);
                if ($stmt->execute()) {
                    blockchain_audit_record($conn, 'category', (int)$id, 'category_updated', [
                        'category_id' => (int)$id,
                        'category_label' => $ten,
                    ], blockchain_audit_current_actor('admin'));
                    app_cache_clear();
                }
                $stmt->close();
                $msg = 'Cập nhật danh mục thành công!';
            } else {
                $stmt = $conn->prepare('INSERT INTO danhmuc (ten_dm, mota) VALUES (?, ?)');
                $stmt->bind_param('ss', $ten, $mota);
                if ($stmt->execute()) {
                    $categoryId = (int)$stmt->insert_id;
                    blockchain_audit_record($conn, 'category', $categoryId, 'category_created', [
                        'category_id' => $categoryId,
                        'category_label' => $ten,
                    ], blockchain_audit_current_actor('admin'));
                    app_cache_clear();
                }
                $stmt->close();
                $msg = 'Thêm danh mục thành công!';
            }
        }
    }
}

// If editing, fetch record
$edit = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $conn->prepare('SELECT id_dm, ten_dm, mota FROM danhmuc WHERE id_dm=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// List
$list = $conn->query('SELECT id_dm, ten_dm, mota FROM danhmuc ORDER BY id_dm DESC');
?>

<div class="breadcrumb">
    <a href="index.php?route=dashboard">Home</a> / Catalog / Danh mục
</div>
<div class="page-header">
    <h1 class="page-title">Quản lý Danh mục</h1>
</div>

<?php if ($msg): ?>
<div style="background:#dcfce7;color:#166534;border:1px solid #86efac;padding:10px;border-radius:6px;margin-bottom:12px;">{<?php echo htmlspecialchars($msg); ?>}</div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title"><?php echo $edit ? 'Sửa danh mục' : 'Thêm danh mục'; ?></h3>
    </div>
    <div class="panel-body">
        <form method="post" action="index.php?route=categories<?php echo $edit ? '&action=edit&id='.(int)$edit['id_dm'] : ''; ?>">
            <?php echo app_csrf_field(); ?>
            <div style="display:grid;grid-template-columns:1fr;gap:12px;max-width:620px;">
                <div>
                    <label>Tên danh mục</label>
                    <input type="text" name="ten_dm" value="<?php echo htmlspecialchars($edit['ten_dm'] ?? ''); ?>" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;" required />
                </div>
                <div>
                    <label>Mô tả</label>
                    <textarea name="mota" rows="3" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;"><?php echo htmlspecialchars($edit['mota'] ?? ''); ?></textarea>
                </div>
            </div>
            <div style="margin-top:12px;display:flex;gap:10px;">
                <button class="btn btn-success" type="submit"><i class="fas fa-save"></i> Lưu</button>
                <?php if ($edit): ?>
                    <a class="btn btn-danger" href="index.php?route=categories"><i class="fas fa-times"></i> Hủy</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3 class="panel-title">Danh sách Danh mục (<?php echo $list ? $list->num_rows : 0; ?>)</h3></div>
    <div class="panel-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên</th>
                        <th>Mô tả</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($list && $list->num_rows > 0): while ($row = $list->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo (int)$row['id_dm']; ?></td>
                        <td><?php echo htmlspecialchars($row['ten_dm']); ?></td>
                        <td><?php echo htmlspecialchars($row['mota']); ?></td>
                        <td>
                            <div class="action-btns">
                                <a class="btn btn-success btn-sm" href="index.php?route=categories&action=edit&id=<?php echo (int)$row['id_dm']; ?>"><i class="fas fa-edit"></i></a>
                                <a class="btn btn-danger btn-sm" href="javascript:void(0)" onclick="deleteCategory(<?php echo (int)$row['id_dm']; ?>)"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4">Chưa có danh mục.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function deleteCategory(id) {
    if (confirm('Xóa danh mục #' + id + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?route=categories&action=delete&id=' + id;
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?php echo app_csrf_token(); ?>';
        form.appendChild(csrfInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
