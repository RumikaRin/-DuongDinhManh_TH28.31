<?php
require_once __DIR__ . '/includes/app.php';

$activePage = isset($_GET['go']) ? (string)$_GET['go'] : 'home';
$categories = app_cache_get('header_categories_list');
if ($categories === null) {
    $categories = [];
    if (isset($ketnoi)) {
        $result = $ketnoi->query(
            "SELECT d.id_dm, d.ten_dm, d.mota,
                    (SELECT COUNT(*) FROM sanpham_danhmuc sd WHERE sd.id_dm = d.id_dm) +
                    (SELECT COUNT(*) FROM sales_danhmuc saled WHERE saled.id_dm = d.id_dm) AS item_count
             FROM danhmuc d
             ORDER BY d.ten_dm"
        );
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
    }
    app_cache_set('header_categories_list', $categories);
}
?>
<header id="header">
    <div class="site-header">
        <a class="brand" href="index.php" aria-label="Nhà xuất bản Kim Đồng">
            <img src="img/sp/logo.webp" alt="" width="112" height="46">
            <span class="brand-copy">
                <strong>Kim Đồng</strong>
                <small>Sách mở ra thế giới</small>
            </span>
        </a>

        <form class="header-search" action="index.php" method="get" role="search">
            <input type="hidden" name="go" value="timkiem">
            <input type="search" name="q" placeholder="Tìm sách, truyện, tác giả..." value="<?php echo htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" aria-label="Tìm kiếm">
            <button type="submit">Tìm</button>
        </form>

        <div class="header-actions">
            <a href="index.php?go=xemgiohang" class="icon-button cart-toggle" aria-label="Giỏ hàng" style="position: relative; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: inherit; margin-right: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <?php
                $cart_count = 0;
                if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as $item) {
                        $cart_count += (int)$item['soluong'];
                    }
                }
                ?>
                <span id="cart-badge" class="cart-count" style="display: <?php echo $cart_count > 0 ? 'inline-flex' : 'none'; ?>; position: absolute; top: -5px; right: -5px; background-color: var(--primary); color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; font-weight: bold; align-items: center; justify-content: center;">
                    <?php echo $cart_count; ?>
                </span>
            </a>

            <?php include __DIR__ . '/dropdown_user.php'; ?>
        </div>
    </div>

    <div class="nav-shell">
        <nav class="primary-nav" aria-label="Điều hướng chính">
            <a href="index.php" class="<?php echo $activePage === 'home' ? 'active' : ''; ?>">Trang chủ</a>
            <a href="index.php?go=sanpham" class="<?php echo $activePage === 'sanpham' ? 'active' : ''; ?>">Truyện</a>
            <a href="index.php?go=sales" class="<?php echo $activePage === 'sales' ? 'active' : ''; ?>">Khuyến mãi</a>
            <a href="index.php?go=tintuc" class="<?php echo $activePage === 'tintuc' ? 'active' : ''; ?>">Tin tức</a>

            <details class="category-menu">
                <summary class="<?php echo $activePage === 'danhmuc' ? 'active' : ''; ?>">
                    Danh mục
                </summary>
                <div class="category-dropdown-menu">
                    <div class="category-dropdown-head">
                        <div>
                            <span>Thư viện Kim Đồng</span>
                            <strong>Chọn thế giới bạn muốn khám phá</strong>
                        </div>
                        <a href="index.php?go=danhmuc">Xem tất cả</a>
                    </div>
                    <div class="category-grid">
                        <?php foreach ($categories as $category): ?>
                            <a class="category-item" href="index.php?go=danhmuc&amp;category=<?php echo urlencode(app_slugify($category['ten_dm'])); ?>">
                                <span class="category-letter"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($category['ten_dm'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                                <span>
                                    <strong><?php echo htmlspecialchars($category['ten_dm'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo (int)$category['item_count']; ?> ấn phẩm</small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                        <?php if (!$categories): ?>
                            <p class="category-empty-state">Chưa có danh mục. Hãy chạy công cụ migration trong trang quản trị.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </details>
        </nav>
    </div>
</header>
