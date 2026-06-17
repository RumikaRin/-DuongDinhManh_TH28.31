<?php

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app.php';

test_assert_true(app_is_allowed_route('danhmuc'), 'Known public route is allowed');
test_assert_same(false, app_is_allowed_route('../dbconnect'), 'Path traversal route is rejected');
test_assert_same(false, app_is_allowed_route('missing-page'), 'Unknown route is rejected');

test_assert_true(app_is_admin_session(['loggedin' => true, 'ten_tv' => 'admin']), 'Admin account is authorized');
test_assert_same(false, app_is_admin_session(['loggedin' => true, 'ten_tv' => 'manh']), 'Regular account is not an admin');

test_assert_same(1, app_normalize_quantity(1), 'Minimum quantity is accepted');
test_assert_same(99, app_normalize_quantity(99), 'Maximum quantity is accepted');
test_assert_same(0, app_normalize_quantity(0), 'Zero quantity is rejected');
test_assert_same(0, app_normalize_quantity(100), 'Excessive quantity is rejected');

test_assert_same(30000, app_shipping_fee(30000), 'Standard shipping fee is accepted');
test_assert_same(50000, app_shipping_fee(50000), 'Express shipping fee is accepted');
test_assert_same(30000, app_shipping_fee(1), 'Tampered shipping fee falls back to standard');

test_assert_true(app_verify_csrf('known-token', 'known-token'), 'Matching CSRF token is accepted');
test_assert_same(false, app_verify_csrf('known-token', 'different-token'), 'Mismatched CSRF token is rejected');

$runtimeDdlFiles = [
    'header.php',
    'comment_handler.php',
    'comment_action.php',
    'admin/template/comments.php',
    'admin/comment_moderate.php',
    'admin/template/categories.php',
    'admin/template/customers.php',
    'admin/template/edit_product.php',
    'xuly_thanhtoan.php',
];
foreach ($runtimeDdlFiles as $file) {
    $source = file_get_contents(dirname(__DIR__) . '/' . $file);
    test_assert_same(false, str_contains($source, 'CREATE TABLE'), "{$file} has no runtime CREATE TABLE");
    test_assert_same(false, str_contains($source, 'ALTER TABLE'), "{$file} has no runtime ALTER TABLE");
}

$homeSource = file_get_contents(dirname(__DIR__) . '/views/home.php');
test_assert_same(false, str_contains($homeSource, "product_type = 'sales'"), 'Home uses the canonical sale comment type');

$urlSource = file_get_contents(dirname(__DIR__) . '/url.php');
test_assert_true(str_contains($urlSource, 'app_is_allowed_route'), 'Public routing uses the shared allowlist');
test_assert_true(str_contains($urlSource, "'views'"), 'Public routing resolves pages from the views directory');
test_assert_same(false, str_contains($urlSource, 'xlylienhe'), 'Public routing has no dead legacy routes');

$adminActionFiles = [
    'admin/product_action.php',
    'admin/sale_action.php',
    'admin/order_action.php',
    'admin/customer_action.php',
    'admin/comment_moderate.php',
];
foreach ($adminActionFiles as $file) {
    $source = file_get_contents(dirname(__DIR__) . '/' . $file);
    test_assert_true(str_contains($source, 'app_require_admin'), "{$file} requires admin authorization");
    test_assert_true(str_contains($source, 'app_require_post_csrf'), "{$file} requires POST and CSRF");
    test_assert_same(false, str_contains($source, "DB_PASSWORD"), "{$file} uses centralized database configuration");
}

$publicMutationFiles = [
    'giohang.php',
    'update_giohang.php',
    'remove_giohang.php',
    'comment_action.php',
    'xuly_thanhtoan.php',
    'xuly_dangnhap.php',
    'xuly_dangky.php',
    'xuly_dangxuat.php',
    'xuly_caidat.php',
];
foreach ($publicMutationFiles as $file) {
    $source = file_get_contents(dirname(__DIR__) . '/' . $file);
    test_assert_true(str_contains($source, 'app_require_post_csrf'), "{$file} requires POST and CSRF");
}

$routedViews = ['home', 'sanpham', 'sales', 'tintuc', 'video', 'dangnhap', 'dangky', 'donhang', 'thanhtoan'];
foreach ($routedViews as $view) {
    $source = file_get_contents(dirname(__DIR__) . '/views/' . $view . '.php');
    test_assert_same(false, str_contains($source, '<!DOCTYPE'), "views/{$view}.php is an embeddable view");
    test_assert_same(false, str_contains($source, 'handlers/'), "views/{$view}.php uses existing endpoints");
}

$slideshowSource = file_get_contents(dirname(__DIR__) . '/views/slideshow.php');
test_assert_same(5, substr_count($slideshowSource, '<a class="hero-slide'), 'Hero contains five banner slides');
test_assert_true(str_contains($slideshowSource, 'data-hero-slider'), 'Hero exposes a slider hook');
test_assert_true(str_contains($slideshowSource, 'aria-label="Ảnh tiếp theo"'), 'Hero has accessible next control');
test_assert_true(str_contains($slideshowSource, 'kinetic-hero'), 'Hero uses the kinetic bookstore layout');
test_assert_true(str_contains($slideshowSource, 'hero-book-stack'), 'Hero includes a layered book visual stack');
test_assert_same(false, str_contains($slideshowSource, 'arrow-up-right'), 'Hero avoids decorative link icons');

$productCardSource = file_get_contents(dirname(__DIR__) . '/views/partials/product_card.php');
test_assert_true(str_contains($productCardSource, 'book-card-shell'), 'Product card exposes the new kinetic card shell');
test_assert_true(str_contains($productCardSource, 'data-reveal'), 'Product cards opt into scroll reveal animation');
test_assert_true(str_contains($productCardSource, 'kimdong-compact-card'), 'Product cards use the compact Kim Dong inspired layout');
test_assert_same(false, str_contains($productCardSource, 'data-lucide="star'), 'Product cards avoid decorative star icons');
test_assert_same(false, str_contains($productCardSource, 'arrow-up-right'), 'Product cards avoid decorative arrow icons');

$homeSource = file_get_contents(dirname(__DIR__) . '/views/home.php');
test_assert_true(str_contains($homeSource, 'home-curated-grid'), 'Home page uses curated mixed product grids');
test_assert_true(str_contains($homeSource, 'story-band'), 'Home page includes an editorial story band');
test_assert_same(false, str_contains($homeSource, 'arrow-up-right'), 'Home section links avoid decorative icons');

$catalogSource = file_get_contents(dirname(__DIR__) . '/views/sanpham.php') . file_get_contents(dirname(__DIR__) . '/views/sales.php');
test_assert_true(str_contains($catalogSource, 'catalog-toolbar'), 'Catalog pages share the redesigned filter toolbar');

$newsSource = file_get_contents(dirname(__DIR__) . '/views/tintuc.php');
test_assert_true(str_contains($newsSource, 'news-card-featured'), 'News page marks a featured editorial article');

$scriptSource = file_get_contents(dirname(__DIR__) . '/script.js');
test_assert_true(str_contains($scriptSource, 'initializeHeroSlider'), 'Public script initializes the hero slider');
test_assert_true(str_contains($scriptSource, 'initializeDismissibleDetails'), 'Public script closes dropdowns from outside interactions');
test_assert_true(str_contains($scriptSource, 'initializeScrollReveals'), 'Public script initializes scroll reveal animation');
test_assert_true(str_contains($scriptSource, 'initializeKineticPointer'), 'Public script initializes pointer-based media polish');

$indexSource = file_get_contents(dirname(__DIR__) . '/index.php');
test_assert_true(str_contains($indexSource, "filemtime(__DIR__ . '/style.css')"), 'Public shell versions style.css using an absolute filesystem path');
test_assert_true(str_contains($indexSource, "filemtime(__DIR__ . '/redesign.css')"), 'Public shell versions redesign.css using an absolute filesystem path');

$headerSource = file_get_contents(dirname(__DIR__) . '/header.php');
$dropdownSource = file_get_contents(dirname(__DIR__) . '/dropdown_user.php');
$quickDockSource = file_get_contents(dirname(__DIR__) . '/sidenavbutton.php');
test_assert_same(false, str_contains($headerSource, 'data-lucide="search"'), 'Header search avoids decorative icon markup');
test_assert_same(false, str_contains($headerSource, 'data-lucide="moon"'), 'Theme toggle uses text instead of icon markup');
test_assert_same(false, str_contains($headerSource, 'data-lucide="chevron-down"'), 'Header category menu avoids decorative chevron icon');
test_assert_same(false, str_contains($dropdownSource, 'data-lucide'), 'User dropdown avoids decorative icon markup');
test_assert_same(false, str_contains($quickDockSource, 'data-lucide'), 'Quick dock uses text-only shortcuts');

$checkoutResultSource = file_get_contents(dirname(__DIR__) . '/xuly_thanhtoan.php');
test_assert_true(str_contains($checkoutResultSource, 'redesign.css'), 'Standalone checkout result page loads the redesign stylesheet');

$auditServicePath = dirname(__DIR__) . '/includes/BlockchainAuditService.php';
test_assert_true(is_file($auditServicePath), 'Blockchain audit service exists');
$auditServiceSource = is_file($auditServicePath) ? file_get_contents($auditServicePath) : '';
test_assert_true(str_contains($auditServiceSource, 'function blockchain_audit_record'), 'Audit service exposes blockchain_audit_record');

$migrationSource = file_get_contents(dirname(__DIR__) . '/database_migrate.php');
test_assert_true(str_contains($migrationSource, 'blockchain_audit_events'), 'Migration creates blockchain audit events table');
test_assert_true(str_contains($migrationSource, 'blockchain_receipts'), 'Migration creates blockchain receipts table');

$adminIndexSource = file_get_contents(dirname(__DIR__) . '/admin/index.php');
test_assert_true(str_contains($adminIndexSource, "case 'blockchain_audit'"), 'Admin router exposes blockchain audit page');

$checkoutSource = file_get_contents(dirname(__DIR__) . '/xuly_thanhtoan.php');
test_assert_true(str_contains($checkoutSource, 'blockchain_audit_record'), 'Checkout records blockchain audit event');

$orderActionSource = file_get_contents(dirname(__DIR__) . '/admin/order_action.php');
test_assert_true(str_contains($orderActionSource, 'blockchain_audit_record'), 'Order status updates record blockchain audit events');

test_assert_true(is_file(dirname(__DIR__, 2) . '/contracts/BookstoreAuditTrail.sol'), 'Bookstore audit smart contract exists');

$redesignSource = file_get_contents(dirname(__DIR__) . '/redesign.css');
test_assert_true(str_contains($redesignSource, '"Be Vietnam Pro"'), 'Redesign uses a Vietnamese-friendly interface font');
test_assert_true(str_contains($redesignSource, '"Noto Serif"'), 'Redesign uses an editorial display font');
test_assert_true(str_contains($redesignSource, '.account-surface'), 'Redesign provides shared account surface styling');
test_assert_true((bool)preg_match('/\\.conten-1,\\s*\\.compact-grid \\.conten-1\\s*\\{[^}]*display:\\s*grid/s', $redesignSource), 'Product grid explicitly enables CSS grid layout');
test_assert_true((bool)preg_match('/\\.image-wrapper img,\\s*\\.product-image-wrapper img,\\s*\\.product-image\\s*\\{[^}]*width:\\s*100%[^}]*height:\\s*100%/s', $redesignSource), 'Product images are constrained inside card frames');
test_assert_true(str_contains($redesignSource, '--motion-fast'), 'Redesign defines shared motion duration tokens');
test_assert_true(str_contains($redesignSource, '--ease-out'), 'Redesign defines shared easing tokens');
test_assert_true(str_contains($redesignSource, '--zoom-ease'), 'Redesign defines a shared image zoom easing token');
test_assert_true(str_contains($redesignSource, '.kinetic-hero'), 'Redesign styles the kinetic bookstore hero');
test_assert_true(str_contains($redesignSource, '.book-card-shell'), 'Redesign styles the new product card shell');
test_assert_true(str_contains($redesignSource, '.home-curated-grid'), 'Redesign styles mixed home product grids');
test_assert_true(str_contains($redesignSource, '.catalog-toolbar'), 'Redesign styles the catalog filter toolbar');
test_assert_true(str_contains($redesignSource, '@media (prefers-reduced-motion: reduce)'), 'Redesign respects reduced motion preference');
test_assert_true((bool)preg_match('/\\.kinetic-hero\\s+\\.hero-slide\\s+img\\s*\\{[^}]*object-fit:\\s*contain/s', $redesignSource), 'Hero slideshow keeps the full banner visible');
test_assert_same(false, str_contains($redesignSource, 'grid-row: span 2'), 'Home product grids avoid oversized masonry cards');
test_assert_true(str_contains($redesignSource, '.kimdong-compact-card'), 'Redesign styles compact product cards');
test_assert_true((bool)preg_match('/\\.conten-1,\\s*\\.compact-grid \\.conten-1,\\s*\\.home-curated-grid,\\s*\\.compact-grid \\.home-curated-grid\\s*\\{[^}]*grid-template-columns:\\s*repeat\\(4,\\s*minmax\\(0,\\s*1fr\\)\\);/s', $redesignSource), 'Product grids keep four desktop columns');
test_assert_true(str_contains($redesignSource, '.cart-empty-state'), 'Cart empty state has a dedicated centered layout');
test_assert_true((bool)preg_match('/\\.cart-empty-state\\s*\\{[^}]*place-items:\\s*center/s', $redesignSource), 'Cart empty state centers its content');

$cartSource = file_get_contents(dirname(__DIR__) . '/views/xemgiohang.php');
$ordersSource = file_get_contents(dirname(__DIR__) . '/views/donhang.php');
$settingsSource = file_get_contents(dirname(__DIR__) . '/views/caidat.php');
$orderDetailSource = file_get_contents(dirname(__DIR__, 2) . '/htdocs/chitietdonhang.php');
test_assert_true(str_contains($cartSource, 'cart-empty-state'), 'Cart empty markup exposes the centered layout hook');
test_assert_same(false, str_contains($cartSource, '🛒'), 'Cart empty state avoids emoji icons');
test_assert_same(false, str_contains($cartSource, '✖'), 'Cart remove action uses text instead of icon-only glyphs');
test_assert_same(false, str_contains($ordersSource, '📋'), 'Orders empty state avoids emoji icons');
test_assert_same(false, str_contains($settingsSource, 'boxicons'), 'Settings page avoids loading icon fonts');
test_assert_same(false, str_contains($orderDetailSource, 'font-awesome'), 'Order detail avoids loading icon fonts');
test_assert_same(false, str_contains($orderDetailSource, 'fas fa-'), 'Order detail avoids Font Awesome glyphs in labels');
test_assert_true(str_contains($orderDetailSource, 'include("header.php")'), 'Order detail uses the shared public header');
test_assert_true(str_contains($orderDetailSource, 'include("footer.php")'), 'Order detail uses the shared public footer');

$cssFiles = array_merge(
    glob(dirname(__DIR__) . '/*.css') ?: [],
    glob(dirname(__DIR__) . '/css/*.css') ?: [],
    glob(dirname(__DIR__) . '/admin/*.css') ?: []
);
foreach ($cssFiles as $cssFile) {
    $cssSource = file_get_contents($cssFile);
    $relativeCss = str_replace(dirname(__DIR__) . DIRECTORY_SEPARATOR, '', $cssFile);
    test_assert_same(false, (bool)preg_match('/transition\s*:\s*all\b/i', $cssSource), "{$relativeCss} avoids transition: all");
}

$adminHeaderSource = file_get_contents(dirname(__DIR__) . '/admin/header.php');
test_assert_same(false, (bool)preg_match('/transition\s*:\s*all\b/i', $adminHeaderSource), 'Admin header avoids transition: all');
