<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php?go=" . basename(__FILE__, ".php"));
    exit();
}
if (!isset($ketnoi)) {
    require_once __DIR__ . '/../dbconnect.php';
}

require_once __DIR__ . '/../includes/ProductRepository.php';
$productRepo = new ProductRepository($ketnoi);

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$author = isset($_GET['author']) ? trim((string)$_GET['author']) : '';
$sortBy = isset($_GET['sort']) ? trim((string)$_GET['sort']) : 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$resultData = $productRepo->getPaginatedProducts($page, $perPage, $q, $author, $sortBy);
$total = $resultData['total'];
$products = $resultData['items'];
$totalPages = (int)ceil($total / $perPage);
?>

<section class="products-section catalog-page catalog-products">
    <div class="section-header section-header-split">
        <div>
        <span class="section-kicker">Tủ sách</span>
        <h1 class="section-title">Sản phẩm nổi bật</h1>
        <p class="section-subtitle">
            Tìm theo tên sách, tác giả hoặc sắp xếp theo cách bạn muốn đọc.
        </p>
        </div>
        <span class="catalog-count"><?php echo number_format($total); ?> ấn phẩm</span>
    </div>

    <div class="filter-section catalog-toolbar">
        <div class="search-box">
            <input type="text" placeholder="Tìm kiếm sản phẩm..." id="searchInput" value="<?php echo htmlspecialchars($q); ?>">
        </div>
        
        <div class="search-box">
            <input type="text" placeholder="Tìm theo tác giả..." id="authorInput" value="<?php echo htmlspecialchars($author); ?>">
        </div>

        <div class="sort-dropdown">
            <select id="sortSelect">
                <option value="newest" <?php echo ($sortBy === 'newest') ? 'selected' : ''; ?>>Mới nhất</option>
                <option value="price-low" <?php echo ($sortBy === 'price-low') ? 'selected' : ''; ?>>Giá thấp đến cao</option>
                <option value="price-high" <?php echo ($sortBy === 'price-high') ? 'selected' : ''; ?>>Giá cao đến thấp</option>
                <option value="popular" <?php echo ($sortBy === 'popular') ? 'selected' : ''; ?>>Phổ biến</option>
            </select>
        </div>
    </div>

    <div class="container">
        <div class="premium-grid">
            <?php
            if (!empty($products)) {
                foreach ($products as $card) {
                    include __DIR__ . '/partials/product_card.php';
                }
            } else {
                echo '<div class="no-products" style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; color: var(--text-secondary);">
                        <i data-lucide="package-x" style="width: 48px; height: 48px; margin-bottom: 12px; color: var(--text-light);"></i>
                        <h3>Không có sản phẩm nào</h3>
                        <p>Không tìm thấy sản phẩm phù hợp với bộ lọc hiện tại.</p>
                      </div>';
            }
            ?>
        </div>

        <?php if ($totalPages > 1): 
            $queryParams = $_GET;
            unset($queryParams['go']); // Remove router page parameter if needed or preserve it
            // Preserve go parameter for router
            $queryParams['go'] = 'sanpham';
            ?>
            <div class="pagination">
                <?php if ($page > 1): 
                    $queryParams['page'] = $page - 1;
                    $prevUrl = 'index.php?' . http_build_query($queryParams);
                    ?>
                    <a href="<?php echo htmlspecialchars($prevUrl); ?>" class="pagination-btn">
                        <i data-lucide="chevron-left"></i> Trước
                    </a>
                <?php endif; ?>
                
                <div class="pagination-numbers">
                    <?php for ($i = 1; $i <= $totalPages; $i++): 
                        $queryParams['page'] = $i;
                        $pageUrl = 'index.php?' . http_build_query($queryParams);
                        $activeClass = ($i === $page) ? 'active' : '';
                        ?>
                        <a href="<?php echo htmlspecialchars($pageUrl); ?>" class="pagination-number <?php echo $activeClass; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>

                <?php if ($page < $totalPages): 
                    $queryParams['page'] = $page + 1;
                    $nextUrl = 'index.php?' . http_build_query($queryParams);
                    ?>
                    <a href="<?php echo htmlspecialchars($nextUrl); ?>" class="pagination-btn">
                        Sau <i data-lucide="chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script src="js/product-common.js?v=<?php echo filemtime(__DIR__ . '/../js/product-common.js'); ?>"></script>
