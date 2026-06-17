<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php?go=home");
    exit();
}
// File này chỉ chứa NỘI DUNG trang chủ
// Header và footer được xử lý bởi index.php
// Session đã được start ở index.php rồi

if (!isset($ketnoi)) {
    include(__DIR__ . "/../dbconnect.php");
}

require_once __DIR__ . "/../includes/ProductRepository.php";
$productRepo = new ProductRepository($ketnoi);
?>

<!-- Slideshow -->
<?php 
if (file_exists(__DIR__ . "/slideshow.php")) {
    include(__DIR__ . "/slideshow.php"); 
}
?>

<section class="home-intro-strip" data-reveal>
    <a href="index.php?go=danhmuc">
        <strong>28 dòng sách</strong>
        <span>Chọn theo thế giới đọc</span>
    </a>
    <a href="index.php?go=sales">
        <strong>Ưu đãi đang chạy</strong>
        <span>Giá tốt cho tủ sách mới</span>
    </a>
    <a href="index.php?go=tintuc">
        <strong>Tin sách</strong>
        <span>Sự kiện và điểm sách mới</span>
    </a>
</section>

<!-- Phần sản phẩm mới -->
<section class="products-section home-showcase">
    <div class="section-header section-header-split">
        <div>
            <span class="section-kicker">Sách mới</span>
            <h1 class="section-title">Những câu chuyện vừa lên kệ</h1>
            <p class="section-subtitle">Bìa sách là tín hiệu đầu tiên. Hover để nhìn gần hơn, chọn nhanh cuốn bạn muốn đọc tiếp.</p>
        </div>
        <a class="section-link" href="index.php?go=sanpham">Xem tất cả</a>
    </div>
    
    <div class="container premium-grid">
        <?php
        $newProducts = $productRepo->getNewProducts(8);
        if (!empty($newProducts)) {
            foreach ($newProducts as $card) {
                include __DIR__ . "/partials/product_card.php";
            }
        } else {
            echo '<div class="no-products">Không có sản phẩm nào.</div>';
        }
        ?>
    </div>
</section>

<section class="story-band" data-reveal>
    <div class="story-band-media">
        <img src="img/banner_1.jpg" alt="Không gian sách Kim Đồng">
    </div>
    <div class="story-band-copy">
        <span class="section-kicker">Không gian đọc</span>
        <h2>Để mỗi bìa sách mở ra một khung cảnh riêng.</h2>
        <p>Trang chủ mới đặt ảnh và bìa sách ở trung tâm, nhưng vẫn giữ đường mua hàng rõ ràng cho độc giả.</p>
        <a class="button button-primary" href="index.php?go=video">Xem chuyển động</a>
    </div>
</section>

<!-- Phần Sales -->
<section class="products-section home-showcase home-showcase-sale">
    <div class="section-header section-header-split">
        <div>
            <span class="section-kicker">Campaign</span>
            <h1 class="section-title">Ưu đãi nổi bật</h1>
            <p class="section-subtitle">Cách đọc mới không cần chờ dịp đặc biệt. Các đầu sách sale được giữ nổi bật và dễ quét giá.</p>
        </div>
        <a class="section-link" href="index.php?go=sales">Xem khuyến mãi</a>
    </div>
    
    <div class="container premium-grid">
        <?php
        $newSales = $productRepo->getNewSales(8);
        if (!empty($newSales)) {
            foreach ($newSales as $card) {
                include __DIR__ . "/partials/product_card.php";
            }
        } else {
            echo '<div class="no-products">Chưa có sản phẩm khuyến mãi.</div>';
        }
        ?>
    </div>
</section>

<!-- Divider line between sections -->
<div class="section-divider"></div>

<!-- Phần Tin tức -->
<?php 
if (file_exists(__DIR__ . "/tintuc_content.php")) {
    include(__DIR__ . "/tintuc_content.php"); 
} elseif (file_exists(__DIR__ . "/tintuc.php")) {
    include(__DIR__ . "/tintuc.php");
}
?>
