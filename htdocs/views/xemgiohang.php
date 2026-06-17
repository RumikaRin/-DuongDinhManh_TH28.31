<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php?go=" . basename(__FILE__, ".php"));
    exit();
}
require_once __DIR__ . "/../dbconnect.php"; // kết nối DB

// Nếu giỏ hàng trống
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    ?>
    <div class="cart-container account-surface cart-surface" data-reveal>
        <div class="cart-header">
            <h2>Giỏ hàng của bạn</h2>
        </div>
        
        <div class="empty-cart cart-empty-state">
            <p class="empty-eyebrow">Giỏ hàng</p>
            <h3>Giỏ hàng của bạn đang trống</h3>
            <p>Hãy tiếp tục mua sắm để chọn thêm sản phẩm yêu thích của bạn.</p>
            <a href="index.php?go=sanpham" class="btn-back">Tiếp tục mua sắm</a>
        </div>
    </div>
    <?php
    return;
}

// Lấy thông tin chi tiết từng item trong giỏ
$items = [];
$tongTien = 0;

foreach ($_SESSION['cart'] as $key => $it) {
    $loai = $it['loai'];
    $id   = (int)$it['id'];
    $qty  = (int)$it['soluong'];
    if ($qty <= 0) continue;

    if ($loai === 'sanpham') {
        $stmt = $ketnoi->prepare("SELECT id_sp AS id, ten_sp AS ten, anh_sp AS anh, gia_sp AS gia FROM sanpham WHERE id_sp=?");
        $stmt->bind_param('i', $id);
    } else { // sale
        $stmt = $ketnoi->prepare("SELECT id_tt AS id, ten_tt AS ten, anh_tt AS anh, saugiamgia_tt AS gia FROM sales WHERE id_tt=?");
        $stmt->bind_param('i', $id);
    }
    if ($stmt && $stmt->execute()) {
        $rs = $stmt->get_result();
        if ($row = $rs->fetch_assoc()) {
            $row['loai'] = $loai;
            $row['key']  = $key;
            $row['qty']  = $qty;
            $row['thanhtien'] = (int)$row['gia'] * $qty;
            $tongTien += $row['thanhtien'];
            $items[] = $row;
        }
    }
}
?>
<link rel="stylesheet" href="css/cart.css?v=<?php echo filemtime(__DIR__ . '/../css/cart.css'); ?>">

<div class="flof-container fade-in-up">
    <div class="flof-grid-12">
        <!-- ── LEFT: Cart Items ── -->
        <div class="flof-col-8">
            <h1 style="font-family: var(--font-display); font-size: 1.5rem; font-weight: bold; margin-bottom: 24px; color: var(--warm-900);">
                Giỏ hàng <span style="font-size: 1rem; color: var(--warm-500); font-weight: normal;">(<?php echo count($items); ?> sản phẩm)</span>
            </h1>

            <form method="POST" action="update_giohang.php" id="cart-form">
                <?php echo app_csrf_field(); ?>
                
                <div class="flof-card" style="padding: 0; overflow: hidden;">
                    <?php foreach ($items as $row): ?>
                        <div class="flof-cart-item" id="cart-item-<?php echo htmlspecialchars($row['key']); ?>">
                            <img src="<?php echo htmlspecialchars($row['anh']); ?>" alt="<?php echo htmlspecialchars($row['ten']); ?>" class="flof-cart-img">
                            
                            <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <div style="font-size: 0.625rem; font-weight: bold; color: var(--warm-400); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">
                                            <?php echo ($row['loai']==='sale') ? 'Khuyến mãi' : 'Sản phẩm'; ?>
                                        </div>
                                        <a href="index.php?go=chitietsanpham&id=<?php echo $row['id']; ?>" style="font-weight: bold; color: var(--warm-900); font-size: 1rem; display: block; margin-bottom: 4px; text-decoration: none;">
                                            <?php echo htmlspecialchars($row['ten']); ?>
                                        </a>
                                    </div>
                                    <a href="javascript:void(0)" onclick="removeCartItem('<?php echo htmlspecialchars($row['key']); ?>')" style="color: var(--warm-400); padding: 8px;">
                                        <i data-lucide="trash-2" style="width: 18px; height: 18px;"></i>
                                    </a>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 12px;">
                                    <div style="font-weight: bold; color: var(--warm-900);">
                                        <?php echo number_format((int)$row['gia'],0,',','.'); ?> đ
                                    </div>
                                    
                                    <div style="display: flex; align-items: center; gap: 16px;">
                                        <div class="flof-qty-ctrl">
                                            <button type="button" class="flof-qty-btn" onclick="updateCartQuantityInline('<?php echo htmlspecialchars($row['key']); ?>', -1)">-</button>
                                            <input type="number" min="1" max="99" name="soluong[<?php echo htmlspecialchars($row['key']); ?>]" id="qty-input-<?php echo htmlspecialchars($row['key']); ?>" value="<?php echo (int)$row['qty']; ?>" onchange="updateCartQuantity('<?php echo htmlspecialchars($row['key']); ?>', this.value)" class="flof-qty-val" style="border: none; background: transparent; padding: 0; margin: 0; outline: none; width: 30px;">
                                            <button type="button" class="flof-qty-btn" onclick="updateCartQuantityInline('<?php echo htmlspecialchars($row['key']); ?>', 1)">+</button>
                                        </div>
                                        <div id="subtotal-<?php echo htmlspecialchars($row['key']); ?>" style="font-family: monospace; font-weight: bold; color: var(--warm-900); min-width: 90px; text-align: right;">
                                            <?php echo number_format((int)$row['thanhtien'],0,',','.'); ?> đ
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>

        <!-- ── RIGHT: Summary ── -->
        <div class="flof-col-4">
            <div class="flof-card" style="position: sticky; top: 100px;">
                <h3 class="flof-section-title" style="margin-bottom: 16px; border-bottom: none; padding-bottom: 0;">Tổng đơn hàng</h3>
                
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.875rem; color: var(--warm-700); margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Tạm tính</span>
                        <span style="font-family: monospace; font-weight: bold; color: var(--warm-900);" id="cart-total-price-summary">
                            <?php echo number_format((int)$tongTien,0,',','.'); ?> đ
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Phí vận chuyển</span>
                        <span style="font-family: monospace; font-weight: bold; color: #10b981;">Miễn phí</span>
                    </div>
                </div>
                
                <div style="border-top: 1px solid var(--warm-100); padding-top: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end;">
                    <span style="font-family: var(--font-display); font-weight: bold; font-size: 1rem; color: var(--warm-900);">Tổng cộng</span>
                    <div style="text-align: right;">
                        <span style="font-family: monospace; font-size: 1.5rem; font-weight: bold; color: var(--warm-900); display: block; line-height: 1;" id="cart-total-price">
                            <?php echo number_format((int)$tongTien,0,',','.'); ?> đ
                        </span>
                        <span style="font-size: 0.625rem; color: var(--warm-400);">Đã bao gồm VAT</span>
                    </div>
                </div>

                <a href="index.php?go=thanhtoan" class="flof-btn" style="background-color: #88734C; color: white; width: 100%; justify-content: center; padding: 16px; font-size: 1rem;">
                    Tiến hành thanh toán <span style="margin-left: 8px;">→</span>
                </a>
                
                <div style="margin-top: 16px; background-color: var(--warm-50); padding: 12px; border-radius: var(--radius-xl); border: 1px solid var(--warm-100); font-size: 0.625rem; color: var(--warm-500); line-height: 1.5; text-align: center;">
                    ✓ Sách chính hãng từ nhà xuất bản<br>✓ Đổi trả trong vòng 7 ngày
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateCartQuantityInline(key, change) {
    const input = document.getElementById('qty-input-' + key);
    if (input) {
        let newQty = parseInt(input.value) + change;
        if (newQty >= 1 && newQty <= 99) {
            input.value = newQty;
            updateCartQuantity(key, newQty);
        }
    }
}
</script>

<script>
// Prevent form submit when pressing Enter inside inputs, update automatically instead
document.querySelector('form[action="update_giohang.php"]')?.addEventListener('submit', function(e) {
    e.preventDefault();
});

let updateTimeout = null;

function updateCartQuantity(key, qty) {
    qty = parseInt(qty);
    if (isNaN(qty) || qty <= 0) return;
    
    // Debounce database updates to prevent query overload when typing
    clearTimeout(updateTimeout);
    updateTimeout = setTimeout(() => {
        const formData = new FormData();
        formData.append('soluong[' + key + ']', qty);
        formData.append('csrf_token', '<?php echo app_csrf_token(); ?>');
        
        fetch('update_giohang.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            if (res && res.ok) {
                // Update item subtotal
                const subtotalEl = document.getElementById('subtotal-' + key);
                if (subtotalEl && res.item_subtotals && res.item_subtotals[key]) {
                    subtotalEl.textContent = res.item_subtotals[key];
                }
                
                // Update total price
                const totalEl = document.getElementById('cart-total-price');
                const totalSummaryEl = document.getElementById('cart-total-price-summary');
                if (totalEl) {
                    totalEl.textContent = res.total_price;
                }
                if (totalSummaryEl) {
                    totalSummaryEl.textContent = res.total_price;
                }
                
                // Update header cart badge
                const badge = document.getElementById('cart-badge') || document.querySelector('.cart-count');
                if (badge) {
                    badge.textContent = res.cart_count;
                    badge.style.display = res.cart_count > 0 ? 'inline-flex' : 'none';
                }
                
                if (typeof modernWebsite !== 'undefined' && modernWebsite.showNotification) {
                    modernWebsite.showNotification('Đã cập nhật số lượng', 'success');
                }
            }
        })
        .catch(err => console.error('Error updating cart:', err));
    }, 300);
}

function removeCartItem(key) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) return;
    
    const formData = new FormData();
    formData.append('key', key);
    formData.append('csrf_token', '<?php echo app_csrf_token(); ?>');
    
    fetch('remove_giohang.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (res && res.ok) {
            const row = document.getElementById('cart-item-' + key);
            if (row) {
                // Fade out row
                row.style.transition = 'opacity 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    
                    // If cart is empty, reload page to show empty state
                    if (res.empty) {
                        location.reload();
                    }
                }, 300);
            }
            
            // Update total price
            const totalEl = document.getElementById('cart-total-price');
            const totalSummaryEl = document.getElementById('cart-total-price-summary');
            if (totalEl) {
                totalEl.textContent = res.total_price;
            }
            if (totalSummaryEl) {
                totalSummaryEl.textContent = res.total_price;
            }
            
            // Update header cart badge
            const badge = document.getElementById('cart-badge') || document.querySelector('.cart-count');
            if (badge) {
                badge.textContent = res.cart_count;
                badge.style.display = res.cart_count > 0 ? 'inline-flex' : 'none';
            }
            
            if (typeof modernWebsite !== 'undefined' && modernWebsite.showNotification) {
                modernWebsite.showNotification('Đã xóa sản phẩm khỏi giỏ hàng', 'info');
            }
        }
    })
    .catch(err => console.error('Error removing item:', err));
}
</script>
