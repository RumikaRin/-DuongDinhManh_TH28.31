<?php
session_start();

// Bao gồm tệp kết nối cơ sở dữ liệu
require_once 'dbconnect.php';
require_once __DIR__ . '/includes/BlockchainAuditService.php';
app_require_post_csrf();

// Nhận dữ liệu từ form và loại bỏ khoảng trắng thừa
$ten_tv = isset($_POST['ten_tv']) ? trim($_POST['ten_tv']) : '';
$sdt_tv = isset($_POST['sdt_tv']) ? trim($_POST['sdt_tv']) : '';
$email_tv = isset($_POST['email_tv']) ? trim($_POST['email_tv']) : '';
$diachi_tv = isset($_POST['diachi_tv']) ? trim($_POST['diachi_tv']) : '';
$mk_tv = isset($_POST['mk_tv']) ? trim($_POST['mk_tv']) : '';

// 1. Kiểm tra rỗng (Validation phía máy chủ)
if (empty($ten_tv) || empty($sdt_tv) || empty($email_tv) || empty($mk_tv) || empty($diachi_tv)) {
    header("Location: index.php?go=dangky&message=" . urlencode("Vui lòng điền đầy đủ thông tin."));
    exit();
}

// 2. Xác thực định dạng email cơ bản
if (!filter_var($email_tv, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.php?go=dangky&message=" . urlencode("Địa chỉ email không hợp lệ."));
    exit();
}

// 3. Kiểm tra số điện thoại (tùy chọn - có thể thêm validation)
if (!preg_match('/^[0-9]{9,11}$/', $sdt_tv)) {
    header("Location: index.php?go=dangky&message=" . urlencode("Số điện thoại không hợp lệ (9-11 chữ số)."));
    exit();
}

// 4. Kiểm tra tên tài khoản đã tồn tại chưa
$sql_check_user = "SELECT id_tv FROM users WHERE ten_tv = ?";
$stmt_check = $ketnoi->prepare($sql_check_user);

if ($stmt_check === false) {
    error_log("Lỗi chuẩn bị truy vấn (kiểm tra tài khoản) trong xuly_dangky.php: " . $ketnoi->error);
    header("Location: index.php?go=dangky&message=" . urlencode("Lỗi hệ thống. Vui lòng thử lại sau."));
    exit();
}

$stmt_check->bind_param("s", $ten_tv);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    $stmt_check->close();
    $ketnoi->close();
    header("Location: index.php?go=dangky&message=" . urlencode("Tên tài khoản đã tồn tại. Vui lòng chọn tên khác."));
    exit();
}
$stmt_check->close();

// 5. Kiểm tra email đã tồn tại chưa
$sql_check_email = "SELECT id_tv FROM users WHERE email_tv = ?";
$stmt_check_email = $ketnoi->prepare($sql_check_email);

if ($stmt_check_email === false) {
    error_log("Lỗi chuẩn bị truy vấn (kiểm tra email) trong xuly_dangky.php: " . $ketnoi->error);
    header("Location: index.php?go=dangky&message=" . urlencode("Lỗi hệ thống. Vui lòng thử lại sau."));
    exit();
}

$stmt_check_email->bind_param("s", $email_tv);
$stmt_check_email->execute();
$stmt_check_email->store_result();

if ($stmt_check_email->num_rows > 0) {
    $stmt_check_email->close();
    $ketnoi->close();
    header("Location: index.php?go=dangky&message=" . urlencode("Email đã được sử dụng. Vui lòng dùng email khác."));
    exit();
}
$stmt_check_email->close();

// 6. Mã hóa mật khẩu
$hashed_password = password_hash($mk_tv, PASSWORD_DEFAULT);

// 7. Chèn vào DB bằng Prepared Statement (ĐÚNG: 5 trường, 5 placeholder)
$sql_insert = "INSERT INTO users (ten_tv, sdt_tv, email_tv, diachi_tv, mk_tv) VALUES (?, ?, ?, ?, ?)";
$stmt_insert = $ketnoi->prepare($sql_insert);

if ($stmt_insert === false) {
    error_log("Lỗi chuẩn bị truy vấn (chèn dữ liệu) trong xuly_dangky.php: " . $ketnoi->error);
    header("Location: index.php?go=dangky&message=" . urlencode("Lỗi hệ thống. Vui lòng thử lại sau."));
    exit();
}

// ĐÚNG: 5 tham số "sssss" tương ứng với 5 giá trị
$stmt_insert->bind_param("sssss", $ten_tv, $sdt_tv, $email_tv, $diachi_tv, $hashed_password);

if ($stmt_insert->execute()) {
    $userId = (int)$stmt_insert->insert_id;
    blockchain_audit_record($ketnoi, 'user', $userId, 'user_registered', [
        'user_id' => $userId,
        'source' => 'registration_page',
    ], ['type' => 'public', 'id' => $userId, 'name' => $ten_tv]);

    // Đăng ký thành công
    $stmt_insert->close();
    $ketnoi->close();
    header("Location: index.php?go=dangnhap&message=" . urlencode("Đăng ký thành công! Vui lòng đăng nhập."));
    exit();
} else {
    // Lỗi khi thực thi
    $error_message = "Lỗi khi đăng ký: " . $stmt_insert->error;
    error_log("Lỗi thực thi đăng ký trong xuly_dangky.php: " . $stmt_insert->error);
    $stmt_insert->close();
    $ketnoi->close();
    header("Location: index.php?go=dangky&message=" . urlencode("Không thể đăng ký. Vui lòng thử lại."));
    exit();
}
?>
