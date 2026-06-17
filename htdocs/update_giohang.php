<?php
session_start();
require_once "dbconnect.php"; // kết nối DB

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
app_require_post_csrf($isAjax);

if (isset($_POST['soluong']) && is_array($_POST['soluong'])) {
    foreach ($_POST['soluong'] as $key => $soluong) {
        $soluong = app_normalize_quantity($soluong);
        if ($soluong > 0 && isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['soluong'] = $soluong;
        } else {
            unset($_SESSION['cart'][$key]);
        }
    }
}

// Check if AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    // Calculate new totals
    $cart_count = 0;
    $total_price = 0;
    $item_subtotals = [];
    
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $it) {
            $loai = $it['loai'];
            $id   = (int)$it['id'];
            $qty  = (int)$it['soluong'];
            $cart_count += $qty;
            
            $price = 0;
            if ($loai === 'sanpham') {
                $stmt = $ketnoi->prepare("SELECT gia_sp AS gia FROM sanpham WHERE id_sp=?");
            } else {
                $stmt = $ketnoi->prepare("SELECT saugiamgia_tt AS gia FROM sales WHERE id_tt=?");
            }
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $rs = $stmt->get_result();
                    if ($row = $rs->fetch_assoc()) {
                        $price = (int)$row['gia'];
                    }
                }
                $stmt->close();
            }
            
            $subtotal = $price * $qty;
            $total_price += $subtotal;
            $item_subtotals[$key] = number_format($subtotal, 0, ',', '.') . ' đ';
        }
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'cart_count' => $cart_count,
        'total_price' => number_format($total_price, 0, ',', '.') . ' đ',
        'item_subtotals' => $item_subtotals,
        'empty' => empty($_SESSION['cart'])
    ]);
    exit();
}

header("Location: index.php?go=xemgiohang");
exit();
?>
