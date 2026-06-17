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
$minDiscount = (int)($_GET['min_discount'] ?? 0);
$sortBy = isset($_GET['sort']) ? trim((string)$_GET['sort']) : 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$resultData = $productRepo->getPaginatedSales($page, $perPage, $q, $author, $minDiscount, $sortBy);
$total = $resultData['total'];
$sales = $resultData['items'];
$totalPages = (int)ceil($total / $perPage);
?>

<section class="products-section catalog-page catalog-sales">
    <div class="section-header section-header-split">
        <div>
            <span class="section-kicker">Ưu đãi</span>
            <h1 class="section-title">Sản phẩm khuyến mãi</h1>
            <p class="section-subtitle">Lọc nhanh theo mức giảm, tác giả và giá để tìm cuốn đáng đưa vào giỏ.</p>
        </div>
        <span class="catalog-count"><?php echo number_format($total); ?> ưu đãi</span>
    </div>

    <!-- Filter & Search -->
    <div class="filter-section catalog-toolbar">
        <div class="search-box">
            <input type="text" placeholder="Tìm kiếm sản phẩm khuyến mãi..." id="searchInput" value="<?php echo htmlspecialchars($q); ?>">
        </div>
        
        <div class="search-box">
            <input type="text" placeholder="Tìm theo tác giả..." id="authorInput" value="<?php echo htmlspecialchars($author); ?>">
        </div>
        
        <div class="filter-tabs">
            <button class="filter-tab <?php echo ($minDiscount === 0) ? 'active' : ''; ?>" data-min-discount="0">Tất cả</button>
            <button class="filter-tab <?php echo ($minDiscount === 10) ? 'active' : ''; ?>" data-min-discount="10">≥10%</button>
            <button class="filter-tab <?php echo ($minDiscount === 20) ? 'active' : ''; ?>" data-min-discount="20">≥20%</button>
            <button class="filter-tab <?php echo ($minDiscount === 30) ? 'active' : ''; ?>" data-min-discount="30">≥30%</button>
            <button class="filter-tab <?php echo ($minDiscount === 40) ? 'active' : ''; ?>" data-min-discount="40">≥40%</button>
        </div>

        <div class="sort-dropdown">
            <select id="sortSelect">
                <option value="newest" <?php echo ($sortBy === 'newest') ? 'selected' : ''; ?>>Mới nhất</option>
                <option value="price-low" <?php echo ($sortBy === 'price-low') ? 'selected' : ''; ?>>Giá thấp đến cao</option>
                <option value="price-high" <?php echo ($sortBy === 'price-high') ? 'selected' : ''; ?>>Giá cao đến thấp</option>
                <option value="discount-high" <?php echo ($sortBy === 'discount-high') ? 'selected' : ''; ?>>Giảm nhiều nhất</option>
            </select>
        </div>
    </div>

    <div class="container">
        <div class="premium-grid">
            <?php
            if (!empty($sales)) {
                foreach ($sales as $card) {
                    include __DIR__ . '/partials/product_card.php';
                }
            } else {
                echo '<div class="no-products" style="grid-column: 1 / -1; text-align: center; padding: 40px 20px; color: var(--text-secondary);">
                        <i data-lucide="package-x" style="width: 48px; height: 48px; margin-bottom: 12px; color: var(--text-light);"></i>
                        <h3>Không có sản phẩm khuyến mãi nào</h3>
                        <p>Không tìm thấy ưu đãi nào phù hợp với bộ lọc hiện tại.</p>
                      </div>';
            }
            ?>
        </div>

        <?php if ($totalPages > 1): 
            $queryParams = $_GET;
            $queryParams['go'] = 'sales';
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
