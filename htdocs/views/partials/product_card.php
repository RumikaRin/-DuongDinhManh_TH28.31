<?php
// FILE: views/partials/product_card.php
// Expected variable: $card (array)

$detailUrl = $card['type'] === 'sale' 
    ? 'index.php?go=chitietsales&id=' . $card['id'] 
    : 'index.php?go=chitiet&id=' . $card['id'];
$cardImage = (string)($card['image'] ?? '');
$isExternalImage = (bool)preg_match('/^(https?:)?\/\//i', $cardImage) || str_starts_with($cardImage, 'data:');
if ($cardImage === '' || (!$isExternalImage && !is_file(__DIR__ . '/../../' . $cardImage))) {
    $cardImage = 'img/sp/logo.webp';
}
?>
<div class="premium-card fade-in-up" 
     data-category="<?php echo htmlspecialchars(strtolower($card['category'] ?? '')); ?>" 
     data-id="<?php echo $card['id']; ?>" 
     data-title="<?php echo htmlspecialchars(strtolower($card['title'])); ?>" 
     data-author="<?php echo htmlspecialchars(strtolower($card['author'])); ?>" 
     data-price="<?php echo $card['price']; ?>" 
     data-discount="<?php echo $card['discount']; ?>" 
     data-reveal
     onclick="window.location.href='<?php echo $detailUrl; ?>'" 
     onkeydown="if(event.key === 'Enter'){ window.location.href='<?php echo $detailUrl; ?>'; }"
     tabindex="0"
     role="link"
     aria-label="<?php echo htmlspecialchars('Xem chi tiết ' . $card['title'], ENT_QUOTES, 'UTF-8'); ?>"
     style="cursor:pointer">
    <div>
        <div class="image-wrapper">
            <a href="<?php echo $detailUrl; ?>">
                <img src="<?php echo htmlspecialchars($cardImage); ?>" 
                     alt="<?php echo htmlspecialchars($card['title']); ?>" 
                     class="product-image"
                     loading="lazy">
            </a>
            <?php if ($card['discount'] > 0): ?>
                <div class="discount-badge">-<?php echo $card['discount']; ?>%</div>
            <?php endif; ?>
        </div>

        <div class="product-content">
            <h3 class="tensp">
                <a href="<?php echo $detailUrl; ?>">
                    <?php echo htmlspecialchars($card['title']); ?>
                </a>
            </h3>

            <div class="product-rating">
                <span class="rating-text"><?php echo number_format((float)$card['avg_rating'], 1, ',', '.'); ?>/5</span>
                <span class="review-count"><?php echo (int)$card['review_count']; ?> đánh giá</span>
            </div>

            <div class="product-author">
                <span>Tác giả: <?php echo htmlspecialchars($card['author']); ?></span>
            </div>

            <div class="price-wraper">
                <span class="new-price"><?php echo number_format($card['price'], 0, ',', '.'); ?>đ</span>
                <?php if ($card['discount'] > 0 && $card['original_price'] > 0): ?>
                    <span class="old-price"><?php echo number_format($card['original_price'], 0, ',', '.'); ?>đ</span>
                <?php endif; ?>
            </div>
            <div class="book-card-footer">
                <span><?php echo htmlspecialchars($card['category'] ?: 'Kim Đồng', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </div>
    </div>
</div>
