<?php
session_start();
require_once "dbconnect.php";
require_once "vnpay_config.php";

$vnp_SecureHash = $_GET['vnp_SecureHash'];
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

unset($inputData['vnp_SecureHash']);
ksort($inputData);
$i = 0;
$hashData = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$vnp_HashSecret = trim($vnp_HashSecret);
$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

$order_id = $_GET['vnp_TxnRef'];
$vnp_Amount = $_GET['vnp_Amount'] / 100; // Số tiền thực tế
$vnp_ResponseCode = $_GET['vnp_ResponseCode'];
$vnp_TransactionNo = $_GET['vnp_TransactionNo'];

$success = false;

if ($secureHash == $vnp_SecureHash) {
    if ($vnp_ResponseCode == '00') {
        // Giao dịch thành công
        $success = true;
        // Cập nhật trạng thái đơn hàng và lưu vnpay_transaction_no
        $sql = "UPDATE donhang SET trangthai = 'Đã thanh toán (VNPAY)', vnpay_transaction_no = ? WHERE id_dh = ?";
        $stmt = $ketnoi->prepare($sql);
        $stmt->bind_param("si", $vnp_TransactionNo, $order_id);
        $stmt->execute();
        $stmt->close();
        
        // Ghi log Blockchain
        require_once "includes/BlockchainAuditService.php";
        blockchain_audit_record($ketnoi, 'order', (int)$order_id, 'vnpay_payment_success', [
            'vnp_TransactionNo' => $vnp_TransactionNo,
            'vnp_Amount' => $vnp_Amount,
            'vnp_BankCode' => $_GET['vnp_BankCode'] ?? '',
        ], blockchain_audit_current_actor('system'));

    } else {
        // Giao dịch thất bại
        $sql = "UPDATE donhang SET trangthai = 'Thanh toán thất bại' WHERE id_dh = ?";
        $stmt = $ketnoi->prepare($sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        
        // Ghi log Blockchain
        require_once "includes/BlockchainAuditService.php";
        blockchain_audit_record($ketnoi, 'order', (int)$order_id, 'vnpay_payment_failed', [
            'vnp_ResponseCode' => $vnp_ResponseCode
        ], blockchain_audit_current_actor('system'));
    }
} else {
    // Chữ ký không hợp lệ
    $success = false;
    
    // Ghi log Blockchain
    require_once "includes/BlockchainAuditService.php";
    blockchain_audit_record($ketnoi, 'security', (int)$order_id, 'vnpay_invalid_signature', [
        'vnp_ResponseCode' => $vnp_ResponseCode ?? 'unknown'
    ], blockchain_audit_current_actor('system'));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Kết quả thanh toán VNPAY</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="redesign.css">
  <style>
    body { font-family: var(--font-main); background: var(--bg-secondary); padding: 40px; text-align: center; }
    .result-container { background: var(--bg-card); max-width: 600px; margin: 0 auto; padding: 40px; border-radius: 12px; box-shadow: var(--shadow); }
    .icon { font-size: 64px; margin-bottom: 20px; }
    .success { color: #4CAF50; }
    .error { color: #F44336; }
    .btn { display: inline-block; padding: 10px 20px; background: var(--primary); color: white; text-decoration: none; border-radius: 8px; margin-top: 20px; font-weight: bold; }
  </style>
</head>
<body>
  <div class="result-container">
    <?php if ($success): ?>
        <div class="icon success">✓</div>
        <h2>Thanh toán thành công!</h2>
        <p>Cảm ơn bạn đã thanh toán qua VNPAY. Đơn hàng <strong>#<?php echo htmlspecialchars($order_id); ?></strong> của bạn đã được ghi nhận.</p>
        <p>Mã giao dịch VNPAY: <strong><?php echo htmlspecialchars($vnp_TransactionNo); ?></strong></p>
        <p style="color: #666; font-size: 14px; margin-top: 20px;"><em>Giao dịch này sẽ sớm được Admin xác thực và đẩy lên mạng lưới Blockchain để đảm bảo tính minh bạch.</em></p>
    <?php else: ?>
        <div class="icon error">✕</div>
        <h2>Thanh toán không thành công!</h2>
        <p>Giao dịch VNPAY thất bại hoặc đã bị hủy.</p>
        <p>Vui lòng kiểm tra lại số dư hoặc thử thanh toán bằng phương thức khác.</p>
    <?php endif; ?>
    <a href="index.php" class="btn">Trở về Trang chủ</a>
  </div>
</body>
</html>
