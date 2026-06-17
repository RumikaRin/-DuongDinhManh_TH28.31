<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình VNPAY Sandbox (Môi trường thử nghiệm)
$vnp_TmnCode = "1WIV1E54"; // Website ID của Sandbox
$vnp_HashSecret = "TPTH6AB56X56OK3ZD402MFUCFL65I5Q8"; // Chuỗi bí mật
$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
$vnp_Returnurl = "http://127.0.0.1:8099/vnpay_return.php";
$vnp_apiUrl = "http://sandbox.vnpayment.vn/merchant_webapi/merchant.html";
// Config input format
// Expire
$startTime = date("YmdHis");
$expire = date('YmdHis',strtotime('+15 minutes',strtotime($startTime)));
?>
