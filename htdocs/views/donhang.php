<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php?go=" . basename(__FILE__, ".php"));
    exit();
}
require_once __DIR__ . '/../dbconnect.php';

if (!isset($_SESSION['id_tv'])) {
    header("Location: index.php?go=dangnhap");
    exit();
}
$id_tv = $_SESSION['id_tv'];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

$sql = "SELECT id_dh, hoten, sdt, diachi, ngaydat, tongtien, trangthai, blockchain_tx_hash
        FROM donhang
        WHERE id_tv = ?
        ORDER BY ngaydat DESC";
$stmt = $ketnoi->prepare($sql);
$stmt->bind_param("i", $id_tv);
$stmt->execute();
$result = $stmt->get_result();
?>
<div class="flof-container fade-in-up">
    <div class="flof-grid-12">
        <!-- Sidebar -->
        <?php 
        $activeTab = 'donhang';
        include __DIR__ . '/partials/profile_sidebar.php'; 
        ?>

        <!-- Content -->
        <div class="flof-col-8">
            <div class="flof-card">
                <h3 class="flof-section-title">Lịch sử mua hàng</h3>

                <?php if ($result->num_rows === 0): ?>
                    <div style="text-align: center; padding: 40px; color: var(--warm-500);">
                        Bạn chưa có đơn hàng nào.
                        <br><br>
                        <a href="index.php?go=sanpham" class="flof-btn flof-btn-primary">Tiếp tục mua sắm</a>
                    </div>
                <?php else: ?>
                    <div class="flof-order-list">
                        <?php while ($row = $result->fetch_assoc()): 
                            $status = strtolower($row['trangthai']);
                            $badgeClass = 'pending';
                            
                            if (strpos($status, 'hủy') !== false || strpos($status, 'huỷ') !== false || strpos($status, 'cancel') !== false) {
                                $badgeClass = 'cancelled';
                            } elseif ((strpos($status, 'vận chuyển') !== false && strpos($status, 'thành công') !== false)
                                   || strpos($status, 'hoàn thành') !== false || strpos($status, 'completed') !== false) {
                                $badgeClass = 'completed';
                            } elseif (strpos($status, 'xử lý') !== false || strpos($status, 'processing') !== false) {
                                $badgeClass = 'processing';
                            }
                        ?>
                            <div class="flof-order-card flof-card-hover">
                                <div class="flof-order-info">
                                    <div class="flof-order-header">
                                        <span class="flof-order-id">KD-<?php echo str_pad($row['id_dh'], 6, '0', STR_PAD_LEFT); ?></span>
                                        <span class="flof-badge <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($row['trangthai']); ?>
                                        </span>
                                    </div>
                                    <p class="flof-order-items">
                                        Giao đến: <?php echo htmlspecialchars($row['hoten']) . ' - ' . htmlspecialchars($row['sdt']); ?>
                                        <br>
                                        <span style="font-weight: normal; color: var(--warm-500);"><?php echo htmlspecialchars($row['diachi']); ?></span>
                                    </p>
                                    <span class="flof-order-date">
                                        <?php echo date('Y-m-d H:i', strtotime($row['ngaydat'])); ?>
                                    </span>
                                    <?php if (!empty($row['blockchain_tx_hash'])): ?>
                                        <div style="margin-top: 8px;">
                                            <a href="https://sepolia.etherscan.io/tx/<?php echo $row['blockchain_tx_hash']; ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; background: #e0f2fe; color: #0284c7; border-radius: 4px; font-size: 0.75rem; text-decoration: none; font-weight: 600;">
                                                <i data-lucide="link" style="width: 12px; height: 12px;"></i> Đã xác thực Blockchain
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flof-order-total" style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                                    <div style="text-align: right;">
                                        <span>Tổng tiền</span>
                                        <span><?php echo number_format($row['tongtien'], 0, ',', '.'); ?> đ</span>
                                    </div>
                                    <a href="chitietdonhang.php?id=<?php echo $row['id_dh']; ?>" class="flof-btn" style="background: white; border: 1px solid var(--warm-200); padding: 6px 12px; font-size: 0.75rem;">Chi tiết</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Load user preferences (including theme)
    if (typeof modernWebsite !== 'undefined' && modernWebsite.loadUserPreferences) {
        modernWebsite.loadUserPreferences();
    } else {
        // Fallback: load theme manually
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.body.setAttribute('data-theme', savedTheme);
    }
    
    // Update dark mode icon based on current theme
    const updateDarkModeIcon = () => {
        const currentTheme = document.body.getAttribute('data-theme');
        const icon = document.querySelector('.dark-mode-toggle i');
        if (icon) {
            icon.setAttribute('data-lucide', currentTheme === 'dark' ? 'sun' : 'moon');
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    };
    
    // Initial icon update
    updateDarkModeIcon();
    
    // Listen for theme changes
    const observer = new MutationObserver(updateDarkModeIcon);
    observer.observe(document.body, { attributes: true, attributeFilter: ['data-theme'] });
    
    // Handle responsive layout
    function handleResponsiveLayout() {
        const isMobile = window.innerWidth <= 768;
        const table = document.querySelector('table');
        const mobileCards = document.querySelector('.orders-list');
        
        if (table && mobileCards) {
            if (isMobile) {
                table.parentElement.style.display = 'none';
                mobileCards.style.display = 'block';
            } else {
                table.parentElement.style.display = 'block';
                mobileCards.style.display = 'none';
            }
        }
    }
    
    handleResponsiveLayout();
    window.addEventListener('resize', handleResponsiveLayout);
});
</script>
