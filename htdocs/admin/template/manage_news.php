<?php
// FILE: admin/template/manage_news.php
// Quản lý danh sách và xóa tin tức. Thêm/sửa dùng các trang chuyên biệt.
require_once dirname(__DIR__, 2) . '/includes/BlockchainAuditService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    app_require_post_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $stmt = $ketnoi->prepare('DELETE FROM tintuc WHERE id_sp = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            blockchain_audit_record($ketnoi, 'news', (int)$id, 'news_deleted', [
                'news_id' => (int)$id,
            ], blockchain_audit_current_actor('admin'));
            $success_message = 'Xóa tin tức thành công!';
        } else {
            $error_message = 'Không thể xóa tin tức.';
        }
        $stmt->close();
    } else {
        $error_message = 'Yêu cầu không hợp lệ.';
    }
}

// Lấy danh sách tin tức
$news_query = "SELECT * FROM tintuc ORDER BY date_tintuc DESC";
$news_result = $ketnoi->query($news_query);
?>

<style>
/* Đồng bộ styles với add_news.php */
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

/* Breadcrumb */
.breadcrumb {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    color: rgba(255, 255, 255, 0.8);
    font-size: 14px;
}

.breadcrumb a {
    color: #ffe491;
    text-decoration: none;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
}

.breadcrumb a:hover {
    color: #ffffff;
}

/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    padding: 20px 24px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.page-title {
    color: #ffffff;
    font-size: 28px;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Panel */
.panel {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.panel-body {
    padding: 24px;
}

/* Alert Messages */
.alert {
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
}

.alert-success {
    background: rgba(0, 242, 195, 0.2);
    color: #00f2c3;
    border: 1px solid rgba(0, 242, 195, 0.3);
}

.alert-error {
    background: rgba(253, 93, 147, 0.2);
    color: #fd5d93;
    border: 1px solid rgba(253, 93, 147, 0.3);
}

/* Table Styles */
.data-table {
    width: 100%;
    border-collapse: collapse;
    background: transparent;
}

.data-table thead {
    background: rgba(255, 255, 255, 0.15);
}

.data-table th {
    color: #ffffff;
    padding: 16px;
    text-align: left;
    font-weight: 600;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    border-bottom: 2px solid rgba(255, 255, 255, 0.2);
}

.data-table td {
    padding: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: #ffffff;
}

.data-table tbody tr {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
}

.data-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

/* News Thumbnail */
.news-thumbnail {
    width: 60px;
    height: 40px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.no-image {
    color: rgba(255, 255, 255, 0.6);
    font-style: italic;
    font-size: 12px;
}

.news-title {
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 4px;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}

.news-excerpt {
    color: rgba(255, 255, 255, 0.8);
    font-size: 12px;
    line-height: 1.4;
}

/* Action Buttons */
.actions {
    display: flex;
    gap: 12px;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn {
    padding: 12px 24px;
    border: none;
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

.btn-primary {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    border: 1px solid #e0e0e0;
}

.btn-primary:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 255, 255, 0.2);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
}

.btn-sm {
    padding: 8px 16px;
    font-size: 12px;
}

.btn-info {
    background: linear-gradient(135deg, rgba(100, 150, 200, 0.6), rgba(80, 120, 160, 0.6));
    color: #ffffff;
    border: 1px solid rgba(100, 150, 200, 0.7);
}

.btn-info:hover {
    background: linear-gradient(135deg, rgba(100, 150, 200, 0.8), rgba(80, 120, 160, 0.8));
    box-shadow: 0 4px 12px rgba(100, 150, 200, 0.3);
}

.btn-danger {
    background: linear-gradient(135deg, rgba(200, 100, 100, 0.6), rgba(160, 80, 80, 0.6));
    color: #ffffff;
    border: 1px solid rgba(200, 100, 100, 0.7);
}

.btn-danger:hover {
    background: linear-gradient(135deg, rgba(200, 100, 100, 0.8), rgba(160, 80, 80, 0.8));
    box-shadow: 0 4px 12px rgba(200, 100, 100, 0.3);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.7);
    overflow-y: auto;
}

.modal-content {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    margin: 5% auto;
    padding: 0;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.modal-header {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    padding: 20px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.close {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}

.close:hover {
    opacity: 0.7;
    transform: scale(1.1);
}

.modal-body {
    padding: 30px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 20px 30px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

/* Form Group */
.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    color: #ffffff;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 15px;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    color: #ffffff;
    font-size: 15px;
    font-weight: 500;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    box-sizing: border-box;
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: rgba(255, 255, 255, 0.5);
    font-weight: 400;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: rgb(255, 228, 145);
    background: rgba(255, 255, 255, 0.25);
    box-shadow: 0 4px 12px rgba(255, 228, 145, 0.3);
}

.form-group textarea {
    min-height: 150px;
    resize: vertical;
    font-family: 'Segoe UI', Tahoma, sans-serif;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.image-preview {
    margin-top: 10px;
    text-align: center;
}

.image-preview img {
    max-width: 200px;
    max-height: 150px;
    border-radius: 8px;
    border: 2px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.warning-text {
    color: #fd5d93;
    font-weight: 600;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}

.text-center {
    text-align: center;
    color: rgba(255, 255, 255, 0.6);
    font-style: italic;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
    
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
    
    .modal-header, .modal-body {
        padding: 15px;
    }
}
</style>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="index.php?route=dashboard">Home</a> / Quản lý Tin tức
</div>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class='bx bx-news'></i> Quản lý Tin tức
    </h1>
    <div class="actions">
        <a href="index.php?route=add_news" class="btn btn-primary">
            <i class='bx bx-plus'></i> Thêm tin tức mới
        </a>
    </div>
</div>

<!-- Alert Messages -->
<?php if (isset($success_message)): ?>
    <div class="alert alert-success">
        <i class='bx bx-check-circle'></i> <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-error">
        <i class='bx bx-error-circle'></i> <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<!-- Main Panel -->
<div class="panel">
    <div class="panel-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ảnh</th>
                    <th>Tiêu đề</th>
                    <th>Ngày đăng</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($news_result && $news_result->num_rows > 0): ?>
                    <?php while ($news = $news_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $news['id_sp']; ?></td>
                            <td>
                                <?php if (!empty($news['anh_tintuc'])): ?>
                                    <img src="../<?php echo htmlspecialchars($news['anh_tintuc']); ?>" 
                                         alt="Ảnh tin tức" class="news-thumbnail">
                                <?php else: ?>
                                    <span class="no-image">Không có ảnh</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="news-title">
                                    <?php echo htmlspecialchars($news['title_tintuc']); ?>
                                </div>
                                <div class="news-excerpt">
                                    <?php echo htmlspecialchars(mb_substr(strip_tags($news['noidung_tintuc']), 0, 100)) . '...'; ?>
                                </div>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($news['date_tintuc'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="index.php?route=edit_news&id=<?php echo $news['id_sp']; ?>" class="btn btn-sm btn-info">
                                        <i class='bx bx-edit'></i> Sửa
                                    </a>
                                    <button class="btn btn-sm btn-danger" onclick="deleteNews(<?php echo $news['id_sp']; ?>)">
                                        <i class='bx bx-trash'></i> Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Chưa có tin tức nào</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Xác nhận Xóa -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Xác nhận xóa</h2>
            <span class="close" onclick="closeDeleteModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Bạn có chắc chắn muốn xóa tin tức này không?</p>
            <p class="warning-text">Hành động này không thể hoàn tác!</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Hủy</button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                <i class='bx bx-trash'></i> Xóa
            </button>
        </div>
    </div>
</div>

<script>
// Biến toàn cục
let currentNewsId = null;

// Xóa tin tức
function deleteNews(id) {
    currentNewsId = id;
    document.getElementById('deleteModal').style.display = 'block';
}

// Xác nhận xóa
function confirmDelete() {
    if (currentNewsId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${currentNewsId}">
            <input type="hidden" name="csrf_token" value="<?php echo app_csrf_token(); ?>">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Đóng modal
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    currentNewsId = null;
}

// Đóng modal khi click outside
window.onclick = function(event) {
    const deleteModal = document.getElementById('deleteModal');
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
}
</script>
