<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php");
    exit();
}
?>
<section class="editorial-hero kinetic-hero" aria-labelledby="hero-title">
    <div class="hero-copy">
        <span class="eyebrow">Nhà xuất bản Kim Đồng</span>
        <h1 id="hero-title"><span>Mở sách.</span><span>Gặp thế giới.</span><span>Chọn câu chuyện của bạn.</span></h1>
        <p>Truyện tranh, văn học, sách thiếu nhi và những ấn phẩm mới được sắp xếp như một chuyến phiêu lưu đọc mỗi ngày.</p>
        <div class="hero-actions">
            <a class="button button-primary" href="index.php?go=sanpham">Khám phá tủ sách</a>
            <a class="button button-quiet" href="index.php?go=danhmuc">Xem danh mục</a>
        </div>
        <div class="hero-facts" aria-label="Thông tin nổi bật">
            <span><strong>65+</strong> năm đồng hành</span>
            <span><strong>28</strong> dòng sách</span>
            <span><strong>Toàn quốc</strong> giao hàng</span>
        </div>
    </div>
    <div class="hero-stage">
        <div class="hero-book-stack">
            <span class="stack-card stack-card-one" aria-hidden="true">Manga</span>
            <span class="stack-card stack-card-two" aria-hidden="true">Văn học</span>
            <span class="stack-card stack-card-three" aria-hidden="true">Thiếu nhi</span>
        </div>
        <div class="hero-visual" data-hero-slider aria-label="Ấn phẩm nổi bật">
            <a class="hero-slide is-active" href="index.php?go=sanpham" aria-hidden="false">
                <img src="img/sp/banner1.webp" alt="Ấn phẩm nổi bật của Nhà xuất bản Kim Đồng" fetchpriority="high">
            </a>
            <a class="hero-slide" href="index.php?go=sanpham" aria-hidden="true">
                <img src="img/sp/banner2.jpg" alt="Tủ sách mới dành cho độc giả trẻ" loading="lazy">
            </a>
            <a class="hero-slide" href="index.php?go=sanpham" aria-hidden="true">
                <img src="img/sp/banner3.webp" alt="Những câu chuyện giàu trí tưởng tượng" loading="lazy">
            </a>
            <a class="hero-slide" href="index.php?go=sales" aria-hidden="true">
                <img src="img/sp/banner4.webp" alt="Ấn phẩm Kim Đồng đang được giới thiệu" loading="lazy">
            </a>
            <a class="hero-slide" href="index.php?go=danhmuc" aria-hidden="true">
                <img src="img/sp/banner5.webp" alt="Khám phá các dòng sách Kim Đồng" loading="lazy">
            </a>

            <span class="hero-stamp">Đọc để lớn lên</span>
            <div class="hero-slider-controls">
                <button class="hero-slider-arrow" type="button" data-slider-prev aria-label="Ảnh trước">
                    <span aria-hidden="true">‹</span>
                </button>
                <div class="hero-slider-dots" aria-label="Chọn ảnh trình chiếu">
                    <?php for ($slide = 0; $slide < 5; $slide++): ?>
                        <button type="button" data-slider-dot="<?php echo $slide; ?>" class="<?php echo $slide === 0 ? 'is-active' : ''; ?>" aria-label="Xem ảnh <?php echo $slide + 1; ?>"></button>
                    <?php endfor; ?>
                </div>
                <button class="hero-slider-arrow" type="button" data-slider-next aria-label="Ảnh tiếp theo">
                    <span aria-hidden="true">›</span>
                </button>
            </div>
        </div>
        <div class="hero-reading-rail" aria-label="Lối đọc nhanh">
            <a href="index.php?go=sales">Ưu đãi mới</a>
            <a href="index.php?go=tintuc">Tin sách</a>
            <a href="index.php?go=video">Không gian đọc</a>
        </div>
    </div>
</section>
