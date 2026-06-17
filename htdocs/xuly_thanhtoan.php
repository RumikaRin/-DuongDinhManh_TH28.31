<?php
if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}
require_once "dbconnect.php";
require_once __DIR__ . "/includes/BlockchainAuditService.php";
app_require_post_csrf();

// Kiểm tra đăng nhập trước
if (!isset($_SESSION['id_tv'])) {
    header("Location: index.php?go=dangnhap");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    header("Location: index.php?go=xemgiohang");
    exit();
}

$id_tv = $_SESSION['id_tv'];
$hoten = trim((string)($_POST['hoten'] ?? ''));
$sdt = trim((string)($_POST['sdt'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$diachi = trim((string)($_POST['diachi'] ?? ''));
$ghichu = isset($_POST['ghichu']) ? trim($_POST['ghichu']) : '';
$payment_method = isset($_POST['payment_method']) && in_array($_POST['payment_method'], ['cod','bank','vnpay'], true) ? $_POST['payment_method'] : 'cod';
$shipping_fee = app_shipping_fee($_POST['shipping_fee'] ?? 30000);
$tongtien = 0;
$tongtien_final = 0;

// Recompute subtotal and accepted cart items on the server.
$subtotal_server = 0;
$valid_items = [];
foreach ($_SESSION['cart'] as $ci) {
    $id = isset($ci['id']) ? (int)$ci['id'] : 0;
    $loai = app_normalize_product_type($ci['loai'] ?? null);
    $soluong = app_normalize_quantity($ci['soluong'] ?? null);
    if ($id <= 0 || $soluong === 0 || $loai === '') { continue; }

    if ($loai === 'sanpham') {
        $sqlp = "SELECT gia_sp AS gia FROM sanpham WHERE id_sp = ?";
    } else {
        $sqlp = "SELECT saugiamgia_tt AS gia FROM sales WHERE id_tt = ?";
    }
    if ($stmtp = $ketnoi->prepare($sqlp)) {
        $stmtp->bind_param("i", $id);
        $stmtp->execute();
        $rsp = $stmtp->get_result();
        if ($rowp = $rsp->fetch_assoc()) {
            $subtotal_server += ((int)$rowp['gia']) * $soluong;
            $valid_items[] = ['id' => $id, 'loai' => $loai, 'soluong' => $soluong];
        }
        $stmtp->close();
    }
}
$tongtien = $subtotal_server;
$tongtien_final = $tongtien + $shipping_fee;

$success = false;
$error = "";
$order_id = 0;

// Kiểm tra thông tin
if ($hoten === '' || $diachi === '' || !preg_match('/^[0-9]{9,11}$/', $sdt)) {
    $error = "Vui lòng điền đầy đủ thông tin.";
} elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Địa chỉ email không hợp lệ.";
} elseif (empty($valid_items) || $tongtien <= 0) {
    $error = "Giỏ hàng không có sản phẩm hợp lệ.";
} else {
    try {
        $ketnoi->begin_transaction();

        // 1. Thêm đơn hàng (lưu tổng tiền đã gồm phí giao hàng)
        $sql = "INSERT INTO donhang (id_tv, hoten, sdt, email, diachi, tongtien, ngaydat, trangthai) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Chờ xử lý')";
        $stmt = $ketnoi->prepare($sql);
        if (!$stmt) { throw new RuntimeException('Không thể tạo đơn hàng.'); }
        $stmt->bind_param("issssi", $id_tv, $hoten, $sdt, $email, $diachi, $tongtien_final);
        if (!$stmt->execute()) { throw new RuntimeException('Không thể lưu đơn hàng.'); }
        $order_id = $stmt->insert_id;
        $stmt->close();

        // 2. Thêm chi tiết đơn hàng
        foreach ($valid_items as $item) {
            $id = $item['id'];
            $loai = $item['loai'];
            $soluong = $item['soluong'];

            $sql_ct = "INSERT INTO donhang_chitiet (id_dh, id_sp, loai, soluong) VALUES (?, ?, ?, ?)";
            $stmt_ct = $ketnoi->prepare($sql_ct);
            if (!$stmt_ct) { throw new RuntimeException('Không thể tạo chi tiết đơn hàng.'); }
            $stmt_ct->bind_param("iisi", $order_id, $id, $loai, $soluong);
            if (!$stmt_ct->execute()) { throw new RuntimeException('Không thể lưu chi tiết đơn hàng.'); }
            $stmt_ct->close();
        }

        if (isset($_POST['update_user_info']) && $_POST['update_user_info'] === '1') {
            $update_stmt = $ketnoi->prepare("UPDATE users SET ten_tv = ?, sdt_tv = ?, email_tv = ?, diachi_tv = ? WHERE id_tv = ?");
            if (!$update_stmt) { throw new RuntimeException('Không thể cập nhật hồ sơ.'); }
            $update_stmt->bind_param("ssssi", $hoten, $sdt, $email, $diachi, $id_tv);
            if (!$update_stmt->execute()) { throw new RuntimeException('Không thể cập nhật hồ sơ.'); }
            $update_stmt->close();
        }

        $ketnoi->commit();

        blockchain_audit_record($ketnoi, 'order', (int)$order_id, 'order_created', [
            'order_id' => (int)$order_id,
            'user_id' => (int)$id_tv,
            'items' => $valid_items,
            'subtotal' => (int)$tongtien,
            'shipping_fee' => (int)$shipping_fee,
            'total' => (int)$tongtien_final,
            'payment_method' => $payment_method,
        ], ['type' => 'user', 'id' => (int)$id_tv]);

        if (isset($_POST['update_user_info']) && $_POST['update_user_info'] === '1') {
            blockchain_audit_record($ketnoi, 'user', (int)$id_tv, 'profile_updated', [
                'user_id' => (int)$id_tv,
                'source' => 'checkout',
                'updated_fields' => ['hoten', 'sdt', 'email', 'diachi'],
            ], ['type' => 'user', 'id' => (int)$id_tv]);
        }

        // 3. Xóa giỏ hàng
        unset($_SESSION['cart']);
        
        if ($payment_method === 'vnpay') {
            require_once __DIR__ . "/vnpay_config.php";
            $vnp_TxnRef = $order_id; // Mã đơn hàng
            $vnp_OrderInfo = "ThanhToanDonHang_" . $order_id;
            $vnp_OrderType = "billpayment";
            $vnp_Amount = $tongtien_final * 100;
            $vnp_Locale = "vn";
            $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
            
            $inputData = array(
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => $vnp_OrderType,
                "vnp_ReturnUrl" => $vnp_Returnurl,
                "vnp_TxnRef" => $vnp_TxnRef
            );
            
            ksort($inputData);
            $query = "";
            $i = 0;
            $hashdata = "";
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }
            
            $vnp_Url = $vnp_Url . "?" . $query;
            if (isset($vnp_HashSecret)) {
                $vnp_HashSecret = trim($vnp_HashSecret);
                $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret);
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }
            header('Location: ' . $vnp_Url);
            exit();
        }

        $success = true;

    } catch (Throwable $e) {
        $ketnoi->rollback();
        error_log("Order processing failed: " . $e->getMessage());
        $error = "Có lỗi xảy ra khi xử lý đơn hàng. Vui lòng thử lại.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $success ? 'Đặt hàng thành công' : 'Lỗi đặt hàng' ?></title>
  <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
  <link rel="stylesheet" href="redesign.css?v=<?php echo filemtime(__DIR__ . '/redesign.css'); ?>">
  <style>
    body { 
      font-family: var(--font-main); 
      background: var(--bg-secondary); 
      color: var(--text-primary);
      padding: 20px; 
      transition: background-color 0.3s ease, color 0.3s ease;
    }
    
    .result-container { 
      background: var(--bg-card); 
      max-width: 560px; 
      margin: 0 auto; 
      border-radius: 10px; 
      padding: 28px; 
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      transition: background-color 0.3s ease, border-color 0.3s ease;
    }
    
    .icon { 
      width: 64px; 
      height: 64px; 
      border-radius: 50%; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      font-size: 32px; 
      margin: 0 auto 14px; 
    }
    
    .success .icon { 
      background: rgba(76, 175, 80, 0.1); 
      color: var(--success); 
    }
    
    .error .icon { 
      background: rgba(244, 67, 54, 0.1); 
      color: var(--error); 
    }
    
    h1 { 
      text-align: center; 
      margin: 8px 0 12px; 
      font-size: 22px; 
      color: var(--text-primary);
    }
    
    .message { 
      text-align: center; 
      color: var(--text-secondary); 
      margin-bottom: 16px; 
    }
    
    .order-info { 
      background: var(--bg-secondary); 
      border: 1px solid var(--border); 
      border-radius: 8px; 
      padding: 14px; 
      margin: 16px 0; 
      transition: background-color 0.3s ease, border-color 0.3s ease;
    }
    
    .row { 
      display: flex; 
      justify-content: space-between; 
      padding: 6px 0; 
      border-bottom: 1px dashed var(--border); 
    }
    
    .row:last-child { 
      border-bottom: none; 
    }
    
    .label { 
      color: var(--text-secondary); 
    }
    
    .value { 
      font-weight: 600; 
      color: var(--text-primary); 
    }
    
    .actions { 
      display: flex; 
      gap: 10px; 
      justify-content: center; 
      margin-top: 12px; 
    }
    
    .btn { 
      padding: 10px 16px; 
      border-radius: 6px; 
      text-decoration: none; 
      font-weight: 600; 
      transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
    }
    
    .btn-primary { 
      background: var(--primary); 
      color: #fff; 
    }
    
    .btn-primary:hover {
      background: #b91c1c;
      transform: translateY(-1px);
    }
    
    .btn-secondary { 
      background: var(--bg-card); 
      color: var(--text-primary); 
      border: 1px solid var(--border); 
    }
    
    .btn-secondary:hover {
      background: var(--bg-secondary);
      transform: translateY(-1px);
    }

    .dark-mode-toggle {
      position: fixed;
      top: 20px;
      right: 20px;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 50%;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
      z-index: 1000;
    }

    .dark-mode-toggle:hover {
      transform: scale(1.1);
    }
  </style>
  </head>
<body>
  <!-- Dark Mode Toggle -->
  <div class="dark-mode-toggle" onclick="toggleDarkMode()">
    <i data-lucide="moon" class="dark-icon" style="display: none;"></i>
    <i data-lucide="sun" class="light-icon"></i>
  </div>

  <div class="result-container <?= $success ? 'success' : 'error' ?>">
    <?php if ($success): ?>
      <div class="icon">✓</div>
      <h1>Đặt hàng thành công!</h1>
      <p class="message">Cảm ơn bạn đã đặt hàng. Đơn hàng đang được xử lý.</p>

      <div class="order-info">
        <div class="row"><span class="label">Mã đơn hàng</span><span class="value">#<?php echo (int)$order_id; ?></span></div>
        <div class="row"><span class="label">Khách hàng</span><span class="value"><?php echo htmlspecialchars($hoten); ?></span></div>
        <div class="row"><span class="label">Số điện thoại</span><span class="value"><?php echo htmlspecialchars($sdt); ?></span></div>
        <div class="row"><span class="label">Địa chỉ</span><span class="value"><?php echo htmlspecialchars($diachi); ?></span></div>
        <?php if (!empty($ghichu)): ?><div class="row"><span class="label">Ghi chú</span><span class="value"><?php echo htmlspecialchars($ghichu); ?></span></div><?php endif; ?>
        <div class="row"><span class="label">Phương thức</span><span class="value"><?php echo ($payment_method === 'bank') ? 'Chuyển khoản' : 'COD'; ?></span></div>
        <div class="row"><span class="label">Phí giao hàng</span><span class="value"><?php echo number_format($shipping_fee, 0, ',', '.'); ?>đ</span></div>
        <div class="row"><span class="label">Tổng thanh toán</span><span class="value" style="color:#c62828;"><?php echo number_format($tongtien_final, 0, ',', '.'); ?>đ</span></div>
      </div>

      <div class="actions">
        <a class="btn btn-primary" href="index.php">Tiếp tục mua sắm</a>
        <a class="btn btn-secondary" href="index.php?go=donhang">Xem đơn hàng</a>
      </div>
    <?php else: ?>
      <div class="icon">✕</div>
      <h1>Đặt hàng thất bại!</h1>
      <p class="message"><?php echo htmlspecialchars($error); ?></p>
      <div class="actions">
        <a class="btn btn-primary" href="index.php?go=thanhtoan">Thử lại</a>
        <a class="btn btn-secondary" href="index.php">Về trang chủ</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  
  <script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Dark mode functionality
    function toggleDarkMode() {
      const currentTheme = document.body.getAttribute('data-theme');
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      
      document.body.setAttribute('data-theme', newTheme);
      localStorage.setItem('theme', newTheme);
      
      const darkIcon = document.querySelector('.dark-icon');
      const lightIcon = document.querySelector('.light-icon');
      
      if (newTheme === 'dark') {
        if (darkIcon) darkIcon.style.display = 'none';
        if (lightIcon) lightIcon.style.display = 'block';
      } else {
        if (darkIcon) darkIcon.style.display = 'block';
        if (lightIcon) lightIcon.style.display = 'none';
      }
    }

    // Load saved theme
    document.addEventListener('DOMContentLoaded', function() {
      const savedTheme = localStorage.getItem('theme') || 'light';
      document.body.setAttribute('data-theme', savedTheme);
      
      const darkIcon = document.querySelector('.dark-icon');
      const lightIcon = document.querySelector('.light-icon');
      
      if (savedTheme === 'dark') {
        if (darkIcon) darkIcon.style.display = 'none';
        if (lightIcon) lightIcon.style.display = 'block';
      } else {
        if (darkIcon) darkIcon.style.display = 'block';
        if (lightIcon) lightIcon.style.display = 'none';
      }
    });
  </script>
</body>
</html>
