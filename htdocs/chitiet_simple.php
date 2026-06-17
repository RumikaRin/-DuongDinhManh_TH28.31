<?php
// Trang chi tiết sản phẩm đơn giản
if (!isset($ketnoi)) {
    require_once 'dbconnect.php';
}

$id_sp = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_sp <= 0) {
    echo '<div style="text-align: center; padding: 2rem; color: var(--accent-color);">Sản phẩm không tồn tại!</div>';
    return;
}

$query = "SELECT * FROM sanpham WHERE id_sp = ?";
$stmt = $ketnoi->prepare($query);
$stmt->bind_param("i", $id_sp);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div style="text-align: center; padding: 2rem; color: var(--accent-color);">Sản phẩm không tồn tại!</div>';
    return;
}

$sp = $result->fetch_assoc();
$discount = 0;
if (isset($sp['gia_cu']) && $sp['gia_cu'] > $sp['gia_sp']) {
    $discount = round((($sp['gia_cu'] - $sp['gia_sp']) / $sp['gia_cu']) * 100);
}
?>

<div class="simple-product-detail">
    <div class="product-main">
        <div class="product-image">
            <img src="<?php echo htmlspecialchars($sp['anh_sp']); ?>" 
                 alt="<?php echo htmlspecialchars($sp['ten_sp']); ?>">
            <?php if ($discount > 0): ?>
                <div class="discount-badge">-<?php echo $discount; ?>%</div>
            <?php endif; ?>
        </div>

        <div class="product-info">
            <h1><?php echo htmlspecialchars($sp['ten_sp']); ?></h1>
            
            <div class="product-rating">
                <?php 
                $rating = rand(35, 50) / 10;
                $fullStars = floor($rating);
                for ($i = 1; $i <= 5; $i++) {
                    echo $i <= $fullStars ? '⭐' : '☆';
                }
                ?>
                <span>(<?php echo rand(50, 500); ?> đánh giá)</span>
            </div>

            <div class="price-section">
                <span class="current-price"><?php echo number_format($sp['gia_sp'], 0, ',', '.'); ?>đ</span>
                <?php if ($discount > 0): ?>
                    <span class="old-price"><?php echo number_format($sp['gia_cu'], 0, ',', '.'); ?>đ</span>
                <?php endif; ?>
            </div>

            <div class="product-meta">
                <p><strong>Tác giả:</strong> <?php echo htmlspecialchars($sp['tacgia_sp']); ?></p>
                <p><strong>Tình trạng:</strong> <span style="color: var(--success-color);">Còn hàng</span></p>
            </div>

            <div class="quantity-section">
                <label>Số lượng:</label>
                <div class="qty-controls">
                    <button onclick="changeQty(-1)">-</button>
                    <input type="number" id="qty" value="1" min="1">
                    <button onclick="changeQty(1)">+</button>
                </div>
            </div>

            <div class="action-buttons">
                <button class="add-cart-btn" onclick="addToCartDetail(<?php echo $sp['id_sp']; ?>)">
                    🛒 Thêm vào giỏ
                </button>
                <button class="buy-now-btn" onclick="buyNowDetail(<?php echo $sp['id_sp']; ?>)">
                    ⚡ Mua ngay
                </button>
            </div>
        </div>
    </div>

    <div class="product-description">
        <h3>Mô tả sản phẩm</h3>
        <p><?php echo isset($sp['mota_sp']) ? nl2br(htmlspecialchars($sp['mota_sp'])) : 'Đây là một cuốn sách tuyệt vời từ Nhà xuất bản Kim Đồng với nội dung hấp dẫn và chất lượng cao.'; ?></p>
    </div>
</div>

<style>
.simple-product-detail {
    max-width: 1000px;
    margin: 0 auto;
    padding: var(--space-xl);
}

.product-main {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-2xl);
    margin-bottom: var(--space-2xl);
}

.product-image {
    position: relative;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
}

.product-image img {
    width: 100%;
    height: 500px;
    object-fit: cover;
}

.discount-badge {
    position: absolute;
    top: var(--space-md);
    right: var(--space-md);
    background: var(--accent-color);
    color: white;
    padding: var(--space-sm) var(--space-md);
    border-radius: var(--radius-md);
    font-weight: 700;
}

.product-info {
    display: flex;
    flex-direction: column;
    gap: var(--space-lg);
}

.product-info h1 {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-primary);
}

.product-rating {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    font-size: 1.2rem;
}

.price-section {
    display: flex;
    align-items: center;
    gap: var(--space-md);
}

.current-price {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--primary-color);
}

.old-price {
    font-size: 1.2rem;
    color: var(--text-light);
    text-decoration: line-through;
}

.product-meta {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.quantity-section {
    display: flex;
    align-items: center;
    gap: var(--space-md);
}

.qty-controls {
    display: flex;
    align-items: center;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    overflow: hidden;
}

.qty-controls button {
    width: 40px;
    height: 40px;
    border: none;
    background: var(--bg-secondary);
    cursor: pointer;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--text-primary);
}

.qty-controls button:hover {
    background: var(--primary-color);
    color: white;
}

.qty-controls input {
    width: 60px;
    height: 40px;
    border: none;
    text-align: center;
    font-weight: 600;
    background: var(--bg-card);
    color: var(--text-primary);
}

.action-buttons {
    display: flex;
    gap: var(--space-md);
}

.add-cart-btn,
.buy-now-btn {
    flex: 1;
    padding: var(--space-lg);
    border: none;
    border-radius: var(--radius-md);
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color var(--transition-normal), color var(--transition-normal), border-color var(--transition-normal), transform var(--transition-normal), box-shadow var(--transition-normal), opacity var(--transition-normal);
}

.add-cart-btn {
    background: var(--bg-secondary);
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
}

.add-cart-btn:hover {
    background: var(--primary-color);
    color: white;
}

.buy-now-btn {
    background: var(--primary-color);
    color: white;
}

.buy-now-btn:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
}

.product-description {
    background: var(--bg-card);
    padding: var(--space-xl);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
}

.product-description h3 {
    font-size: 1.3rem;
    margin-bottom: var(--space-md);
    color: var(--text-primary);
}

/* Dark mode */
body.dark-mode .qty-controls button {
    background: var(--bg-card);
    color: var(--text-primary);
}

body.dark-mode .qty-controls input {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

body.dark-mode .add-cart-btn {
    background: var(--bg-card);
}

/* Responsive */
@media (max-width: 768px) {
    .product-main {
        grid-template-columns: 1fr;
        gap: var(--space-lg);
    }
    
    .simple-product-detail {
        padding: var(--space-md);
    }
    
    .product-info h1 {
        font-size: 1.5rem;
    }
    
    .current-price {
        font-size: 1.5rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<script>
function changeQty(change) {
    const qtyInput = document.getElementById('qty');
    const currentQty = parseInt(qtyInput.value);
    const newQty = currentQty + change;
    
    if (newQty >= 1 && newQty <= 99) {
        qtyInput.value = newQty;
    }
}

function addToCartDetail(productId) {
    const qty = parseInt(document.getElementById('qty').value);
    const button = document.querySelector('.add-cart-btn');
    const originalText = button.textContent;
    
    button.textContent = 'Đang thêm...';
    button.disabled = true;
    
    setTimeout(() => {
        let cartItems = JSON.parse(localStorage.getItem('cartItems')) || [];
        const existingItem = cartItems.find(item => item.id === productId);
        
        if (existingItem) {
            existingItem.quantity += qty;
        } else {
            cartItems.push({
                id: productId,
                quantity: qty,
                addedAt: new Date().toISOString()
            });
        }
        
        localStorage.setItem('cartItems', JSON.stringify(cartItems));
        
        button.textContent = '✓ Đã thêm';
        button.style.background = 'var(--success-color)';
        button.style.color = 'white';
        button.style.borderColor = 'var(--success-color)';
        
        if (typeof showNotification !== 'undefined') {
            showNotification(`Đã thêm ${qty} sản phẩm vào giỏ hàng`, 'success');
        }
        
        if (typeof updateCartCount !== 'undefined') {
            updateCartCount();
        }
        
        setTimeout(() => {
            button.textContent = originalText;
            button.disabled = false;
            button.style.background = '';
            button.style.color = '';
            button.style.borderColor = '';
        }, 2000);
    }, 1000);
}

function buyNowDetail(productId) {
    const qty = parseInt(document.getElementById('qty').value);
    
    let cartItems = JSON.parse(localStorage.getItem('cartItems')) || [];
    const existingItem = cartItems.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity += qty;
    } else {
        cartItems.push({
            id: productId,
            quantity: qty,
            addedAt: new Date().toISOString()
        });
    }
    
    localStorage.setItem('cartItems', JSON.stringify(cartItems));
    window.location.href = `index.php?go=thanhtoan&id=${productId}&qty=${qty}`;
}
</script>


