<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php?go=" . basename(__FILE__, ".php") . (isset($_GET['category']) ? "&category=" . urlencode($_GET['category']) : ""));
    exit();
}
if (!isset($ketnoi)) {
    require_once __DIR__ . '/../dbconnect.php';
}
require_once __DIR__ . '/../includes/app.php';
require_once __DIR__ . '/../includes/ProductRepository.php';

$category_slug = isset($_GET['category']) ? trim((string)$_GET['category']) : '';
$current_category = null;

// Tải tất cả danh mục (có cache)
$categories_list_cached = app_cache_get('danhmuc_general_list');
if ($categories_list_cached === null) {
    // Nếu chưa có cache, lấy từ DB
    $danhmuc_query = "SELECT * FROM danhmuc ORDER BY id_dm";
    $danhmuc_result = $ketnoi->query($danhmuc_query);
    $categories_list_cached = [];
    if ($danhmuc_result && $danhmuc_result->num_rows > 0) {
        while ($dm = $danhmuc_result->fetch_assoc()) {
            $categories_list_cached[] = $dm;
        }
    }
    app_cache_set('danhmuc_general_list', $categories_list_cached);
}

// Nếu có category slug, tìm danh mục hiện tại trong list
if ($category_slug !== '') {
    foreach ($categories_list_cached as $dm) {
        if (app_slugify($dm['ten_dm']) === $category_slug) {
            $current_category = $dm;
            break;
        }
    }
}

// Nếu không tìm thấy danh mục, redirect về trang danh mục chung
if ($category_slug !== '' && !$current_category) {
    header('Location: index.php?go=danhmuc');
    exit;
}
?>

<section class="products-section catalog-page category-index-page">
    <?php if ($current_category): 
        $productRepo = new ProductRepository($ketnoi);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 12;
        $categoryId = (int)$current_category['id_dm'];
        
        $resultData = $productRepo->getProductsByCategoryId($categoryId, $page, $perPage);
        $total = $resultData['total'];
        $products = $resultData['items'];
        $totalPages = (int)ceil($total / $perPage);
        ?>
        <!-- Trang danh mục cụ thể -->
        <div class="section-header section-header-split">
            <div>
            <span class="section-kicker">Danh mục</span>
            <h1 class="section-title"><?php echo htmlspecialchars($current_category['ten_dm']); ?></h1>
            <p class="section-subtitle">
                <?php echo htmlspecialchars($current_category['mota']); ?>
            </p>
            </div>
            <a class="section-link" href="index.php?go=danhmuc">Tất cả danh mục</a>
        </div>

        <!-- Sản phẩm trong danh mục -->
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
                            <p>Hiện tại chưa có sản phẩm nào trong danh mục này.</p>
                          </div>';
                }
                ?>
            </div>

            <?php if ($totalPages > 1): 
                $queryParams = $_GET;
                $queryParams['go'] = 'danhmuc';
                $queryParams['category'] = $category_slug;
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

    <?php else: ?>
        <!-- Trang danh mục chung -->
        <div class="section-header section-header-split">
            <div>
            <span class="section-kicker">Thế giới đọc</span>
            <h1 class="section-title">Danh mục sản phẩm</h1>
            <p class="section-subtitle">
                Chọn dòng sách theo độ tuổi, thể loại và nhịp đọc yêu thích.
            </p>
            </div>
        </div>

        <div class="categories-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; padding: 24px 0;">
            <?php
            // Lấy danh mục kèm đếm sản phẩm có cache
            $general_categories_with_count = app_cache_get('danhmuc_general_list_with_count');
            if ($general_categories_with_count === null) {
                $danhmuc_query = "SELECT d.*, 
                                 (SELECT COUNT(*) FROM sanpham_danhmuc sd WHERE sd.id_dm = d.id_dm) as so_san_pham 
                                 FROM danhmuc d 
                                 ORDER BY d.id_dm";
                $danhmuc_result = $ketnoi->query($danhmuc_query);
                $general_categories_with_count = [];
                if ($danhmuc_result && $danhmuc_result->num_rows > 0) {
                    while ($dm = $danhmuc_result->fetch_assoc()) {
                        $general_categories_with_count[] = $dm;
                    }
                }
                app_cache_set('danhmuc_general_list_with_count', $general_categories_with_count);
            }
            
            if (!empty($general_categories_with_count)) {
                foreach ($general_categories_with_count as $dm) {
                    $so_san_pham = (int)$dm['so_san_pham'];
                    $category_slug = app_slugify($dm['ten_dm']);
                    ?>
                    <div class="category-card" data-reveal style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 24px; transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease; cursor: pointer;" 
                         onclick="window.location.href='index.php?go=danhmuc&category=<?php echo urlencode($category_slug); ?>'">
                        
                        <div class="category-header" style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                            <div class="category-icon" style="width: 48px; height: 48px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; font-weight: bold;">
                                <?php echo htmlspecialchars(mb_strtoupper(mb_substr($dm['ten_dm'], 0, 1))); ?>
                            </div>
                            <div class="category-info">
                                <h3 class="category-name" style="margin: 0; font-size: 18px; font-weight: 600; color: var(--text-primary);">
                                    <?php echo htmlspecialchars($dm['ten_dm']); ?>
                                </h3>
                                <p class="category-count" style="margin: 4px 0 0 0; font-size: 14px; color: var(--text-secondary);">
                                    <?php echo $so_san_pham; ?> sản phẩm
                                </p>
                            </div>
                        </div>
                        
                        <div class="category-description" style="color: var(--text-secondary); font-size: 14px; line-height: 1.5; margin-bottom: 16px;">
                            <?php echo htmlspecialchars($dm['mota']); ?>
                        </div>
                        
                        <div class="category-action" style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="color: var(--primary); font-size: 14px; font-weight: 500;">
                                Xem sản phẩm
                            </span>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="no-categories" style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                        <i data-lucide="folder-x" style="width: 64px; height: 64px; margin-bottom: 16px; color: var(--text-light);"></i>
                        <h3>Chưa có danh mục nào</h3>
                        <p>Hiện tại chưa có danh mục sản phẩm nào.</p>
                      </div>';
            }
            ?>
        </div>
    <?php endif; ?>
</section>



<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
