<?php
require_once __DIR__ . '/includes/app.php';

$databaseConfig = app_database_config();
mysqli_report(MYSQLI_REPORT_OFF);

$ketnoi = new mysqli(
    $databaseConfig['host'],
    $databaseConfig['username'],
    $databaseConfig['password'],
    $databaseConfig['database'],
    $databaseConfig['port']
);

if ($ketnoi->connect_error) {
    error_log("Lỗi kết nối CSDL: " . $ketnoi->connect_error);
    die("Kết nối cơ sở dữ liệu thất bại. Vui lòng thử lại sau.");
}

$ketnoi->set_charset("utf8mb4");
?>
