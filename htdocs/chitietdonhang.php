<?php
session_start();
require_once "dbconnect.php";
require_once __DIR__ . "/includes/BlockchainAuditService.php";

// kiểm tra đăng nhập
if (!isset($_SESSION['id_tv'])) {
    header("Location: dangnhap.php");
    exit();
}
$id_tv = $_SESSION['id_tv'];

// lấy id đơn
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id <= 0) {
    die("Đơn hàng không hợp lệ.");
}

// lấy thông tin đơn hàng, lọc theo id_tv
$sql_dh = "SELECT * FROM donhang WHERE id_dh = ? AND id_tv = ?";
$stmt_dh = $ketnoi->prepare($sql_dh);
$stmt_dh->bind_param("ii", $order_id, $id_tv);
$stmt_dh->execute();
$result_dh = $stmt_dh->get_result();
$order = $result_dh->fetch_assoc();
$stmt_dh->close();

if (!$order) {
    die("Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này.");
}

// lấy chi tiết đơn hàng (hỗ trợ cả sản phẩm thường và sale)
$sql_ct = "SELECT dhct.*,
                  COALESCE(s.ten_tt, sp.ten_sp)                                  AS ten,
                  COALESCE(s.anh_tt, sp.anh_sp)                                  AS anh,
                  COALESCE(s.saugiamgia_tt, s.giasp_tt, sp.gia_sp, 0)            AS gia
           FROM donhang_chitiet dhct
           LEFT JOIN sanpham sp ON dhct.id_sp = sp.id_sp
           LEFT JOIN sales   s  ON dhct.id_sp = s.id_tt
           WHERE dhct.id_dh = ?";
$stmt_ct = $ketnoi->prepare($sql_ct);
$stmt_ct->bind_param("i", $order_id);
$stmt_ct->execute();
$result_ct = $stmt_ct->get_result();
$items = $result_ct->fetch_all(MYSQLI_ASSOC);
$stmt_ct->close();
// Tính tạm tính và phí giao hàng (vì cột tongtien đã gồm phí ship)
$subtotal = 0;
foreach ($items as $it) {
    $gia = isset($it['gia']) ? (int)$it['gia'] : 0;
    $sl  = isset($it['soluong']) ? (int)$it['soluong'] : 0;
    $subtotal += $gia * $sl;
}
$shipping_fee = max((int)$order['tongtien'] - (int)$subtotal, 0);
$blockchainAuditEvent = blockchain_audit_latest_for_entity($ketnoi, 'order', $order_id);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chi tiết đơn hàng #<?= str_pad($order_id,6,'0',STR_PAD_LEFT) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="script.js"></script>
<style>
/* Import CSS variables from main stylesheets */
@import url('style.css');
@import url('redesign.css');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Hide scrollbars */
* {
    scrollbar-width: none;
    -ms-overflow-style: none;
}

*::-webkit-scrollbar {
    display: none;
}

body {
    font-family: var(--font-main), 'Inter', -apple-system, sans-serif;
    background: var(--bg-primary);
    min-height: 100vh;
    color: var(--text-primary);
    line-height: 1.6;
    padding: 30px 20px;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
    position: relative;
}

.container {
    max-width: 1100px;
    width: 96%;
    margin: 0 auto;
}

/* Main Card */
.main-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: var(--shadow-lg);
    margin-bottom: 20px;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
    position: relative;
}

.main-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    transition: left 0.7s ease;
    pointer-events: none;
    display: none;
}

.main-card:hover::before {
    left: 100%;
    display: none;
}

.main-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

/* Header */
.card-header {
    padding: 18px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg-secondary);
}

.card-header h2 {
    margin: 0;
    font-size: 22px;
    color: var(--text-primary);
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.order-badge {
    display: inline-block;
    background: var(--primary);
    color: var(--text-white);
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    animation: pulse 2s ease infinite;
}

.blockchain-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid var(--border);
    background: var(--bg-secondary);
}

.blockchain-badge-confirmed {
    color: #047857;
    border-color: #86efac;
    background: #dcfce7;
}

.blockchain-badge-pending {
    color: #1d4ed8;
    border-color: #bfdbfe;
    background: #dbeafe;
}

.blockchain-badge-failed {
    color: #b91c1c;
    border-color: #fecaca;
    background: #fee2e2;
}

.blockchain-badge-disabled,
.blockchain-badge-empty {
    color: #6b7280;
    border-color: #d1d5db;
    background: #f3f4f6;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.header-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
}

.header-link:hover {
    transform: translateX(-3px);
    color: var(--primary-hover);
}

/* Info Section */
.info-section {
    padding: 20px;
    border-bottom: 1px solid var(--border);
    background: var(--bg-card);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.info-item {
    padding: 16px;
    background: var(--bg-secondary);
    border-radius: 8px;
    border-left: 3px solid var(--primary);
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
    position: relative;
    overflow: hidden;
}

.info-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 0;
    height: 100%;
    background: linear-gradient(90deg, rgba(var(--primary-rgb), 0.05), transparent);
    transition: width 0.3s ease;
}

.info-item:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.info-item:hover::before {
    width: 100%;
}

.info-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.info-label i {
    color: var(--primary);
}

.info-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    word-break: break-word;
}

/* Products Table */
.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border);
}

thead tr {
    color: var(--text-secondary);
}

thead th {
    padding: 12px 16px;
    font-weight: 700;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

thead th:first-child {
    text-align: left;
}

thead th:nth-child(2),
thead th:nth-child(3) {
    text-align: center;
}

thead th:nth-child(4) {
    text-align: right;
}

tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
}

tbody tr:hover {
    background: var(--bg-secondary);
    transform: scale(1.005);
}

tbody td {
    padding: 12px 16px;
}

.product-cell {
    display: flex;
    gap: 12px;
    align-items: center;
}

.product-img {
    width: 64px;
    height: 80px;
    object-fit: cover;
    border: 1px solid var(--border);
    border-radius: 6px;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
    cursor: pointer;
}

.product-img:hover {
    transform: scale(1.1) rotate(2deg);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    border-color: var(--primary);
}

.product-info {
    flex: 1;
}

.product-name {
    font-weight: 600;
    color: var(--text-primary);
    max-width: 420px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}

.product-type {
    font-size: 12px;
    color: var(--text-light);
    display: inline-block;
    background: var(--bg-secondary);
    padding: 2px 8px;
    border-radius: 4px;
    border: 1px solid var(--border);
}

.price-cell {
    text-align: center;
    color: var(--primary);
    font-weight: 700;
}

.qty-cell {
    text-align: center;
    font-weight: 600;
    color: var(--text-primary);
}

.total-cell {
    text-align: right;
    font-weight: 700;
    color: var(--text-primary);
    padding-right: 16px;
}

/* Footer Section */
.card-footer {
    padding: 20px;
    background: var(--bg-card);
}

.footer-content {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.summary-section {
    width: 100%;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
}

.summary-row:last-child {
    border-bottom: none;
    border-top: 2px solid var(--border);
    padding-top: 16px;
    margin-top: 8px;
}

.summary-label {
    font-size: 15px;
    color: var(--text-secondary);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.summary-value {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary);
}

.summary-row:last-child .summary-label {
    font-size: 18px;
    color: var(--text-primary);
}

.summary-row:last-child .summary-value {
    font-size: 24px;
    color: var(--primary);
}

/* Actions */
.actions-section {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
    width: 100%;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 700;
    font-size: 15px;
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s ease, height 0.6s ease;
}

.btn:hover::before {
    width: 300px;
    height: 300px;
}

.btn span,
.btn i {
    position: relative;
    z-index: 1;
}

.btn-primary {
    background: var(--primary);
    color: var(--text-white);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.4);
}

.btn-secondary {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    color: var(--text-primary);
}

.btn-secondary:hover {
    background: var(--bg-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 40px;
}

.empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 25px;
    background: var(--bg-secondary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    animation: float 3s ease infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.empty-state h3 {
    color: var(--text-primary);
    margin-bottom: 12px;
    font-size: 20px;
    font-weight: 600;
}

.empty-state p {
    font-size: 15px;
    color: var(--text-secondary);
    margin-bottom: 30px;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
    body {
        padding: 20px 10px;
    }
    
    .container {
        width: 100%;
    }
    
    .card-header {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .product-cell {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .product-name {
        max-width: 100%;
        white-space: normal;
    }
    
    .footer-content {
        flex-direction: column;
    }
    
    .summary-section {
        width: 100%;
    }
    
    .actions-section {
        width: 100%;
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}

/* Print Styles */
@media print {
    body {
        background: white;
        padding: 0;
    }
    
    .main-card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
    
    .actions-section {
        display: none;
    }
}
</style>
<link rel="stylesheet" href="redesign.css?v=<?php echo filemtime(__DIR__ . '/redesign.css'); ?>">
<link rel="stylesheet" href="css/flof-layout.css?v=<?php echo filemtime(__DIR__ . '/css/flof-layout.css'); ?>">
</head>
<body class="order-detail-page">
<?php include("header.php"); ?>
<main class="main-content" id="main-content" style="padding-top: 24px; padding-bottom: 24px;">
    <div class="flof-container fade-in-up">
        <div class="flof-grid-12">
            <!-- Sidebar -->
            <?php 
            $activeTab = 'donhang';
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
            include __DIR__ . '/views/partials/profile_sidebar.php'; 
            ?>

            <!-- Content -->
            <div class="flof-col-8">
                <div class="flof-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--warm-100); padding-bottom: 16px;">
                        <h3 class="flof-section-title" style="margin-bottom: 0; border-bottom: none; padding-bottom: 0;">
                            Chi tiết đơn hàng
                            <span style="color: var(--warm-500); font-size: 1rem; margin-left: 8px;">#<?= str_pad($order_id,6,'0',STR_PAD_LEFT) ?></span>
                        </h3>
                        <a href="index.php?go=donhang" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.875rem; font-weight: bold; color: var(--warm-500); text-decoration: none;">
                            <i data-lucide="chevron-left" style="width: 16px; height: 16px;"></i> Quay lại
                        </a>
                    </div>

                    <!-- Info Section -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; padding: 16px; background: var(--warm-50); border-radius: var(--radius-xl); border: 1px solid var(--warm-100);">
                        <div>
                            <div style="font-size: 0.625rem; font-weight: bold; color: var(--warm-500); text-transform: uppercase; margin-bottom: 4px;">Người nhận</div>
                            <div style="font-size: 0.875rem; font-weight: bold; color: var(--warm-900);"><?= htmlspecialchars($order['hoten']) ?></div>
                            <div style="font-size: 0.875rem; color: var(--warm-700);"><?= htmlspecialchars($order['sdt']) ?></div>
                        </div>
                        <div>
                            <div style="font-size: 0.625rem; font-weight: bold; color: var(--warm-500); text-transform: uppercase; margin-bottom: 4px;">Ngày đặt hàng</div>
                            <div style="font-size: 0.875rem; font-weight: bold; color: var(--warm-900);"><?= date("d/m/Y H:i", strtotime($order['ngaydat'])) ?></div>
                        </div>
                        <div style="grid-column: span 2;">
                            <div style="font-size: 0.625rem; font-weight: bold; color: var(--warm-500); text-transform: uppercase; margin-bottom: 4px;">Địa chỉ giao hàng</div>
                            <div style="font-size: 0.875rem; color: var(--warm-900);"><?= htmlspecialchars($order['diachi']) ?></div>
                        </div>
                        <?php if ($blockchainAuditEvent): ?>
                        <div style="grid-column: span 2; padding-top: 12px; border-top: 1px solid var(--warm-200);">
                            <div style="font-size: 0.625rem; font-weight: bold; color: var(--warm-500); text-transform: uppercase; margin-bottom: 4px;">Blockchain Proof</div>
                            <div><?= blockchain_audit_status_badge($blockchainAuditEvent) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Products Table -->
                    <h4 style="font-family: var(--font-display); font-size: 1rem; font-weight: bold; margin-bottom: 16px; color: var(--warm-900);">Sản phẩm đã đặt</h4>
                    
                    <?php if (empty($items)): ?>
                        <div style="text-align: center; padding: 40px; color: var(--warm-500);">Không có sản phẩm nào.</div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px;">
                            <?php foreach($items as $item): ?>
                                <div style="display: flex; gap: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--warm-100);">
                                    <img src="<?= htmlspecialchars($item['anh']) ?>" alt="<?= htmlspecialchars($item['ten']) ?>" style="width: 64px; height: 64px; object-fit: cover; border-radius: 8px; border: 1px solid var(--warm-100);" onerror="this.src='/placeholder.svg?height=64&width=64'">
                                    
                                    <div style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
                                        <div style="font-size: 0.625rem; font-weight: bold; color: var(--warm-400); text-transform: uppercase; margin-bottom: 4px;">
                                            <?= ($item['loai'] === 'sale') ? 'Khuyến mãi' : 'Sản phẩm' ?>
                                        </div>
                                        <div style="font-size: 0.875rem; font-weight: bold; color: var(--warm-900); margin-bottom: 4px;">
                                            <?= htmlspecialchars($item['ten']) ?>
                                        </div>
                                        <div style="font-size: 0.875rem; color: var(--warm-500);">
                                            <?= number_format($item['gia'],0,',','.') ?> đ x <?= intval($item['soluong']) ?>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; align-items: center; font-family: monospace; font-weight: bold; color: var(--warm-900); font-size: 1rem;">
                                        <?= number_format($item['gia'] * $item['soluong'],0,',','.') ?> đ
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Footer Summary -->
                    <div style="background: var(--warm-50); padding: 20px; border-radius: var(--radius-xl); border: 1px solid var(--warm-100);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.875rem; color: var(--warm-700);">
                            <span>Tạm tính:</span>
                            <span style="font-family: monospace; font-weight: bold;"><?= number_format($subtotal,0,',','.') ?> đ</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 0.875rem; color: var(--warm-700);">
                            <span>Phí giao hàng:</span>
                            <span style="font-family: monospace; font-weight: bold;"><?= number_format($shipping_fee,0,',','.') ?> đ</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-top: 16px; border-top: 1px solid var(--warm-200); align-items: flex-end;">
                            <span style="font-family: var(--font-display); font-size: 1rem; font-weight: bold; color: var(--warm-900);">Tổng cộng:</span>
                            <span style="font-family: monospace; font-size: 1.5rem; font-weight: bold; color: #88734C; line-height: 1;">
                                <?= number_format($order['tongtien'],0,',','.') ?> đ
                            </span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                        <button onclick="window.print()" class="flof-btn" style="background-color: var(--warm-200); color: var(--warm-900);">
                            <i data-lucide="printer" style="width: 16px; height: 16px; margin-right: 8px;"></i> In đơn hàng
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include("sidenavbutton.php"); ?>
<?php include("footer.php"); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Load theme from localStorage
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.body.setAttribute('data-theme', savedTheme);
    
    // Add loading animation for images
    const images = document.querySelectorAll('.product-img');
    images.forEach(img => {
        img.style.opacity = '0';
        img.addEventListener('load', function() {
            this.style.transition = 'opacity 0.5s ease';
            this.style.opacity = '1';
        });
    });
    
    // Add click to zoom for product images
    images.forEach(img => {
        img.addEventListener('click', function() {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                cursor: pointer;
                animation: fadeIn 0.3s ease;
            `;
            
            const modalImg = document.createElement('img');
            modalImg.src = this.src;
            modalImg.style.cssText = `
                max-width: 90%;
                max-height: 90%;
                border-radius: 8px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.5);
                animation: zoomIn 0.3s ease;
            `;
            
            modal.appendChild(modalImg);
            document.body.appendChild(modal);
            
            modal.addEventListener('click', () => {
                modal.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => document.body.removeChild(modal), 300);
            });
        });
    });
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    @keyframes zoomIn {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
`;
document.head.appendChild(style);
</script>
</body>
</html>
