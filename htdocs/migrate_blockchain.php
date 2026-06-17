<?php
require_once __DIR__ . '/dbconnect.php';

$sql = "ALTER TABLE donhang ADD COLUMN blockchain_tx_hash VARCHAR(255) NULL DEFAULT NULL AFTER id_dh";
if ($ketnoi->query($sql) === TRUE) {
    echo "Thêm cột blockchain_tx_hash thành công!<br>";
} else {
    echo "Lỗi hoặc cột đã tồn tại: " . $ketnoi->error . "<br>";
}

$sql2 = "ALTER TABLE donhang ADD COLUMN block_timestamp DATETIME NULL DEFAULT NULL AFTER blockchain_tx_hash";
if ($ketnoi->query($sql2) === TRUE) {
    echo "Thêm cột block_timestamp thành công!<br>";
} else {
    echo "Lỗi hoặc cột đã tồn tại: " . $ketnoi->error . "<br>";
}

$sql3 = "ALTER TABLE donhang ADD COLUMN vnpay_transaction_no VARCHAR(255) NULL DEFAULT NULL AFTER block_timestamp";
if ($ketnoi->query($sql3) === TRUE) {
    echo "Thêm cột vnpay_transaction_no thành công!<br>";
} else {
    echo "Lỗi hoặc cột đã tồn tại: " . $ketnoi->error . "<br>";
}

echo "Hoàn thành cập nhật cấu trúc bảng!";
?>
