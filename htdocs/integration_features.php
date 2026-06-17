<?php
// Integration Features from Apple Website
// This file contains enhanced features to be integrated

// ===== BANNER FEATURE =====
function displayBanner($imagePath = "img/banner_1.jpg", $altText = "WingsBooks Banner") {
    echo '<div class="banner-container">';
    echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($altText) . '" loading="lazy">';
    echo '</div>';
}

// ===== PRODUCT CLICK HANDLER =====
function getProductLink($type, $id) {
    if ($type === 'sale') {
        return "index.php?go=chitietsales&id=" . intval($id);
    } else {
        return "index.php?go=chitiet&id=" . intval($id);
    }
}

// ===== ENHANCED PRODUCT CARD =====
function displayProductCard($product, $type = 'normal') {
    $discount = 0;
    
    if ($type === 'sale') {
        // For sales products
        $id = $product['id_tt'];
        $name = $product['ten_tt'];
        $image = $product['anh_tt'];
        $author = $product['tacgia_tt'];
        $price = $product['saugiamgia_tt'];
        $oldPrice = $product['giasp_tt'] ?? 0;
        $discount = $product['giamgia_tt'] ?? 0;
        $link = getProductLink('sale', $id);
    } else {
        // For normal products
        $id = $product['id_sp'];
        $name = $product['ten_sp'];
        $image = $product['anh_sp'];
        $author = $product['tacgia_sp'];
        $price = $product['gia_sp'];
        $oldPrice = $product['gia_cu'] ?? 0;
        
        if ($oldPrice > $price) {
            $discount = round((($oldPrice - $price) / $oldPrice) * 100);
        }
        $link = getProductLink('normal', $id);
    }
    
    ?>
    <div class="conten enhanced-product">
        <div class="manga-card">
            <div class="image-wrapper">
                <a href="<?php echo $link; ?>" class="product-link">
                    <img src="<?php echo htmlspecialchars($image); ?>" 
                         alt="<?php echo htmlspecialchars($name); ?>"
                         loading="lazy"
                         onerror="this.src='img/placeholder.jpg'">
                </a>
                <?php if ($discount > 0): ?>
                    <span class="discount-badge animated">-<?php echo $discount; ?>%</span>
                <?php endif; ?>
                <?php if ($type === 'sale'): ?>
                    <span class="sale-indicator">SALE</span>
                <?php endif; ?>
            </div>
            
            <div class="manga-infor">
                <div class="thongso">
                    <a href="<?php echo $link; ?>" class="product-title-link">
                        <p class="tensp"><?php echo htmlspecialchars($name); ?></p>
                    </a>
                    <p class="product-author">Tác giả: <?php echo htmlspecialchars($author); ?></p>
                    <?php if (isset($product['danh_muc']) && !empty($product['danh_muc'])): ?>
                        <p class="product-category">Danh mục: <?php echo htmlspecialchars($product['danh_muc']); ?></p>
                    <?php endif; ?>
                    <div class="price-wraper">
                        <span class="new-price"><?php echo number_format($price, 0, ',', '.'); ?>đ</span>
                        <?php if ($discount > 0 && $oldPrice > 0): ?>
                            <span class="old-price"><?php echo number_format($oldPrice, 0, ',', '.'); ?>đ</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick actions -->
                    <div class="product-quick-actions">
                        <button class="quick-btn add-to-cart-btn" 
                                onclick="addToCart(<?php echo $id; ?>, '<?php echo $type; ?>')">
                            <i data-lucide="shopping-cart"></i>
                        </button>
                        <button class="quick-btn wishlist-btn"
                                onclick="toggleWishlist(<?php echo $id; ?>, '<?php echo $type; ?>')">
                            <i data-lucide="heart"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// ===== RATING DISPLAY =====
function displayRating($productId, $type = 'normal') {
    global $ketnoi;
    
    $table = ($type === 'sale') ? 'reviews_sales' : 'reviews';
    $idField = ($type === 'sale') ? 'id_tt' : 'id_sp';
    
    $query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
              FROM $table WHERE $idField = ?";
    $stmt = $ketnoi->prepare($query);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rating = $result->fetch_assoc();
    
    $avgRating = round($rating['avg_rating'] ?? 0, 1);
    $totalReviews = $rating['total_reviews'] ?? 0;
    
    ?>
    <div class="product-rating">
        <div class="stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?php echo $i <= $avgRating ? 'filled' : ''; ?>">★</span>
            <?php endfor; ?>
        </div>
        <span class="rating-text">(<?php echo $totalReviews; ?> đánh giá)</span>
    </div>
    <?php
}

// ===== RELATED PRODUCTS =====
function displayRelatedProducts($currentId, $type = 'normal', $limit = 6) {
    global $ketnoi;
    
    echo '<div class="related-products-section">';
    echo '<h3 class="section-heading">Sản phẩm tương tự</h3>';
    echo '<div class="related-products-grid">';
    
    // Get normal products
    $query = "SELECT * FROM sanpham WHERE id_sp != ? ORDER BY RAND() LIMIT ?";
    $stmt = $ketnoi->prepare($query);
    $stmt->bind_param("ii", $currentId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($product = $result->fetch_assoc()) {
        displayProductCard($product, 'normal');
    }
    
    // Get sale products
    $querySale = "SELECT * FROM sales ORDER BY RAND() LIMIT ?";
    $stmtSale = $ketnoi->prepare($querySale);
    $remainingLimit = $limit;
    $stmtSale->bind_param("i", $remainingLimit);
    $stmtSale->execute();
    $resultSale = $stmtSale->get_result();
    
    while ($sale = $resultSale->fetch_assoc()) {
        displayProductCard($sale, 'sale');
    }
    
    echo '</div>';
    echo '</div>';
}

// ===== WISHLIST FEATURE =====
function toggleWishlist($productId, $type) {
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = array();
    }
    
    $key = $type . '_' . $productId;
    
    if (in_array($key, $_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = array_diff($_SESSION['wishlist'], [$key]);
        return false; // Removed
    } else {
        $_SESSION['wishlist'][] = $key;
        return true; // Added
    }
}

// ===== ENHANCED CART =====
function addToCartEnhanced($productId, $type, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    $key = $type . '_' . $productId;
    
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key] += $quantity;
    } else {
        $_SESSION['cart'][$key] = $quantity;
    }
    
    return $_SESSION['cart'][$key];
}

// ===== SEARCH ENHANCEMENT =====
function searchProducts($keyword, $category = 'all') {
    global $ketnoi;
    $results = array();
    $keyword = '%' . $keyword . '%';
    
    // Search in normal products
    if ($category === 'all' || $category === 'normal') {
        $query = "SELECT *, 'normal' as product_type FROM sanpham 
                 WHERE ten_sp LIKE ? OR tacgia_sp LIKE ? OR chitiet_sp LIKE ?";
        $stmt = $ketnoi->prepare($query);
        $stmt->bind_param("sss", $keyword, $keyword, $keyword);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    }
    
    // Search in sale products
    if ($category === 'all' || $category === 'sale') {
        $querySale = "SELECT *, 'sale' as product_type FROM sales 
                     WHERE ten_tt LIKE ? OR tacgia_tt LIKE ? OR chitietsp_tt LIKE ?";
        $stmtSale = $ketnoi->prepare($querySale);
        $stmtSale->bind_param("sss", $keyword, $keyword, $keyword);
        $stmtSale->execute();
        $resultSale = $stmtSale->get_result();
        
        while ($row = $resultSale->fetch_assoc()) {
            $results[] = $row;
        }
    }
    
    return $results;
}

// ===== FILTER & SORT =====
function filterAndSortProducts($filters = array()) {
    global $ketnoi;
    
    $where = array();
    $params = array();
    $types = "";
    
    // Price filter
    if (isset($filters['min_price'])) {
        $where[] = "gia_sp >= ?";
        $params[] = $filters['min_price'];
        $types .= "i";
    }
    
    if (isset($filters['max_price'])) {
        $where[] = "gia_sp <= ?";
        $params[] = $filters['max_price'];
        $types .= "i";
    }
    
    // Author filter
    if (isset($filters['author']) && !empty($filters['author'])) {
        $where[] = "tacgia_sp LIKE ?";
        $params[] = '%' . $filters['author'] . '%';
        $types .= "s";
    }
    
    // Build query
    $query = "SELECT * FROM sanpham";
    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }
    
    // Sorting
    if (isset($filters['sort'])) {
        switch ($filters['sort']) {
            case 'price_asc':
                $query .= " ORDER BY gia_sp ASC";
                break;
            case 'price_desc':
                $query .= " ORDER BY gia_sp DESC";
                break;
            case 'name_asc':
                $query .= " ORDER BY ten_sp ASC";
                break;
            case 'newest':
                $query .= " ORDER BY id_sp DESC";
                break;
            default:
                $query .= " ORDER BY id_sp DESC";
        }
    }
    
    $stmt = $ketnoi->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    
    return $stmt->get_result();
}
?>
