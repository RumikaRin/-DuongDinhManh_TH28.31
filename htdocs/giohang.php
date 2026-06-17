<?php
session_start();
require_once __DIR__ . '/includes/app.php';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
app_require_post_csrf($isAjax);

// ✅ Kiểm tra đăng nhập: nếu chưa đăng nhập thì chuyển về trang đăng nhập
if (!isset($_SESSION['ten_tv'])) {
    header("Location: index.php?go=dangnhap&message=" . urlencode("Bạn phải đăng nhập trước khi mua hàng"));
    exit();
}

// Lấy dữ liệu từ form
$id      = isset($_POST['id_sp']) ? intval($_POST['id_sp']) : 0;
$loai    = isset($_POST['loai']) ? $_POST['loai'] : '';
$soluong = app_normalize_quantity($_POST['soluong'] ?? null);
$action  = isset($_POST['action']) ? $_POST['action'] : '';

// Kiểm tra dữ liệu hợp lệ
if ($id <= 0 || $soluong === 0 ||
    !in_array($action, ['add','buy']) || 
    !in_array($loai, ['sanpham','sale'])) {
    header("Location: index.php");
    exit();
}

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Tạo key duy nhất (vd: sanpham_5, sale_3)
$key = "{$loai}_{$id}";

if ($action === 'add') {
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['soluong'] = min(99, (int)$_SESSION['cart'][$key]['soluong'] + $soluong);
    } else {
        $_SESSION['cart'][$key] = [
            'id' => $id,
            'loai' => $loai,
            'soluong' => $soluong
        ];
    }
    // If AJAX request, return JSON instead of redirecting
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        $count = 0;
        foreach ($_SESSION['cart'] as $it) { $count += (int)$it['soluong']; }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'cart_count' => $count]);
        exit();
    }
    // Non-AJAX: go back to previous page with flag
    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
    $sep = (strpos($ref, '?') !== false) ? '&' : '?';
    header("Location: {$ref}{$sep}added=1");
    exit();
}

if ($action === 'buy') {
    // Reset giỏ hàng, chỉ giữ sản phẩm này để thanh toán
    $_SESSION['cart'] = [
        $key => [
            'id' => $id,
            'loai' => $loai,
            'soluong' => $soluong
        ]
    ];
    
    // If AJAX request, return JSON instead of redirecting
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'redirect' => 'index.php?go=thanhtoan']);
        exit();
    }
    
    // Non-AJAX: redirect to payment page
    header("Location: index.php?go=thanhtoan");
    exit();
}
?>
