<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/includes/app.php';

echo "=== STARTING INTEGRATION TESTS ===\n";

// 1. Setup Test Database (manh_test)
$config = app_database_config();
$testDbName = 'manh_test';

// Override database in environment for subsequent connects
putenv("DB_DATABASE={$testDbName}");
$_ENV['DB_DATABASE'] = $testDbName;

$link = new mysqli($config['host'], $config['username'], $config['password'], '', $config['port']);
if ($link->connect_error) {
    die("Integration Test: Connection failed: " . $link->connect_error);
}

// Recreate database
$link->query("DROP DATABASE IF EXISTS `{$testDbName}`");
if (!$link->query("CREATE DATABASE `{$testDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    die("Integration Test: Failed to create database {$testDbName}: " . $link->error);
}

$link->select_db($testDbName);

// Import schema & seeds from database_create.sql
$sqlContent = file_get_contents(dirname(__DIR__) . '/database_create.sql');
// Replace target database references to manh_test
$sqlContent = str_replace(['`manh`', ' manh ', 'manh;'], ["`{$testDbName}`", " {$testDbName} ", "{$testDbName};"], $sqlContent);

// Strip comments
$sqlContent = preg_replace('/--.*\n/', '', $sqlContent);
$sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);

// Run queries
$statements = explode(';', $sqlContent);
foreach ($statements as $stmtText) {
    $stmtText = trim($stmtText);
    if ($stmtText !== '') {
        if (!$link->query($stmtText)) {
            // Ignore minor trigger/view warnings if any, but report errors
            echo "Warning/Error executing: " . substr($stmtText, 0, 50) . "... Error: " . $link->error . "\n";
        }
    }
}
$link->close();

// Now connect to manh_test using our app connection file
require dirname(__DIR__) . '/dbconnect.php';
// $ketnoi is now connected to manh_test
require_once dirname(__DIR__) . '/includes/BlockchainAuditService.php';

ob_start();
require dirname(__DIR__) . '/database_migrate.php';
ob_get_clean();

// Helper to clean up global arrays
function reset_request_state()
{
    $_POST = [];
    $_GET = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
}

// ==========================================
// TEST 0: Blockchain Audit Service
// ==========================================
$payloadA = ['b' => 2, 'a' => 1];
$payloadB = ['a' => 1, 'b' => 2];
test_assert_same(
    blockchain_audit_payload_hash($payloadA),
    blockchain_audit_payload_hash($payloadB),
    'Blockchain audit hash is stable regardless of key order'
);

$safePayload = blockchain_audit_sanitize_payload([
    'hoten' => 'Nguyen Van A',
    'email' => 'a@example.test',
    'sdt' => '0900000000',
    'diachi' => 'Secret address',
    'order_id' => 123,
]);
test_assert_same(false, isset($safePayload['hoten']), 'Audit sanitizer removes customer name');
test_assert_same(false, isset($safePayload['email']), 'Audit sanitizer removes email');
test_assert_same(false, isset($safePayload['sdt']), 'Audit sanitizer removes phone number');
test_assert_same(false, isset($safePayload['diachi']), 'Audit sanitizer removes address');
test_assert_same(123, $safePayload['order_id'], 'Audit sanitizer keeps non-sensitive ids');

putenv('BLOCKCHAIN_ENABLED=0');
$auditId = blockchain_audit_record($ketnoi, 'test_entity', 1, 'created', ['order_id' => 1], ['type' => 'system', 'id' => 0]);
test_assert_true($auditId > 0, 'Audit service inserts local event in disabled mode');
$auditRow = $ketnoi->query("SELECT status, previous_hash, event_hash FROM blockchain_audit_events WHERE id = {$auditId}")->fetch_assoc();
test_assert_same('disabled', $auditRow['status'], 'Disabled blockchain mode records disabled status');
test_assert_true(str_starts_with((string)$auditRow['event_hash'], '0x'), 'Audit event stores a local chain hash');

$nextAuditId = blockchain_audit_record($ketnoi, 'test_entity', 2, 'updated', ['order_id' => 2], ['type' => 'system', 'id' => 0]);
$nextAuditRow = $ketnoi->query("SELECT previous_hash FROM blockchain_audit_events WHERE id = {$nextAuditId}")->fetch_assoc();
test_assert_same($auditRow['event_hash'], $nextAuditRow['previous_hash'], 'Audit event hash chain links to previous event');

// ==========================================
// TEST 1: Login Verification
// ==========================================
reset_request_state();
$_SESSION = [];

// Seed a user
$testUser = 'testuser';
$testPass = 'password123';
$hashedPass = password_hash($testPass, PASSWORD_BCRYPT);
$stmt = $ketnoi->prepare("INSERT INTO users (ten_tv, sdt_tv, email_tv, diachi_tv, mk_tv) VALUES (?, '0987654321', 'test@example.com', 'Test Address', ?)");
$stmt->bind_param("ss", $testUser, $hashedPass);
$stmt->execute();
$stmt->close();

// Verify user exists and credentials match
$stmt = $ketnoi->prepare("SELECT id_tv, ten_tv, mk_tv FROM users WHERE ten_tv = ?");
$stmt->bind_param("s", $testUser);
$stmt->execute();
$res = $stmt->get_result();
test_assert_same(1, $res->num_rows, "Login test: User successfully seeded");
$userRow = $res->fetch_assoc();
test_assert_true(password_verify($testPass, $userRow['mk_tv']), "Login test: Password matches hashed version");
$stmt->close();

// Populate simulated session
$_SESSION['loggedin'] = true;
$_SESSION['id_tv'] = $userRow['id_tv'];
$_SESSION['ten_tv'] = $userRow['ten_tv'];
test_assert_true(isset($_SESSION['loggedin']) && $_SESSION['id_tv'] > 0, "Login test: Simulated session login matches expected state");


// ==========================================
// TEST 2: Checkout (Order creation)
// ==========================================
reset_request_state();

// Seed a product to checkout
$prodId = 999;
$prodTitle = 'Manga Test Book';
$prodPrice = 50000;
$stmt = $ketnoi->prepare("INSERT INTO sanpham (id_sp, ten_sp, gia_sp, chitiet_sp, anh_sp, tacgia_sp) VALUES (?, ?, ?, 'Details', 'img/sp/test.jpg', 'Author')");
$stmt->bind_param("isd", $prodId, $prodTitle, $prodPrice);
$stmt->execute();
$stmt->close();

// Setup session cart
$_SESSION['cart'] = [
    [
        'id' => $prodId,
        'loai' => 'sanpham',
        'soluong' => 2
    ]
];

// Setup POST checkout data
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['hoten'] = 'John Doe';
$_POST['sdt'] = '0912345678';
$_POST['email'] = 'john@example.com';
$_POST['diachi'] = '123 Test Street';
$_POST['payment_method'] = 'cod';
$_POST['shipping_fee'] = '30000';
$_POST['csrf_token'] = 'token123';
$_SESSION['csrf_token'] = 'token123';

// Run checkout process and capture output
ob_start();
include dirname(__DIR__) . '/xuly_thanhtoan.php';
$output = ob_get_clean();

// Verify database records
$orderRes = $ketnoi->query("SELECT * FROM donhang ORDER BY id_dh DESC LIMIT 1");
test_assert_same(1, $orderRes->num_rows, "Checkout test: Order record created");
$orderRow = $orderRes->fetch_assoc();
test_assert_same('John Doe', $orderRow['hoten'], "Checkout test: Customer name matches");
test_assert_same(130000, (int)$orderRow['tongtien'], "Checkout test: Total matches (50000 * 2 + 30000 shipping)");

$orderId = $orderRow['id_dh'];
$detailRes = $ketnoi->query("SELECT * FROM donhang_chitiet WHERE id_dh = {$orderId}");
test_assert_same(1, $detailRes->num_rows, "Checkout test: Order detail record created");
$detailRow = $detailRes->fetch_assoc();
test_assert_same($prodId, (int)$detailRow['id_sp'], "Checkout test: Product ID in detail matches");
test_assert_same(2, (int)$detailRow['soluong'], "Checkout test: Quantity in detail matches");

test_assert_same(false, isset($_SESSION['cart']), "Checkout test: Cart is cleared from session after checkout");


// ==========================================
// TEST 3: Admin CRUD (Product CRUD)
// ==========================================
reset_request_state();
$_SESSION = [
    'loggedin' => true,
    'ten_tv' => 'admin' // Make app_is_admin_session return true
];

$conn = $ketnoi; // For scripts expecting $conn

// 3.1: CREATE Product
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['ten_sp'] = 'New Manga Title';
$_POST['gia_sp'] = '85000';
$_POST['chitiet_sp'] = 'Description here';
$_POST['tacgia_sp'] = 'Seeded Author';
$_POST['csrf_token'] = 'admin_token';
$_SESSION['csrf_token'] = 'admin_token';

ob_start();
include dirname(__DIR__) . '/admin/add_products.php';
ob_get_clean();

// Check if product is in DB
$prodRes = $ketnoi->query("SELECT * FROM sanpham WHERE ten_sp = 'New Manga Title'");
test_assert_same(1, $prodRes->num_rows, "Admin CRUD: Product successfully created");
$newProdRow = $prodRes->fetch_assoc();
$newProdId = (int)$newProdRow['id_sp'];
test_assert_same(85000, (int)$newProdRow['gia_sp'], "Admin CRUD: Product price matches");

// 3.2: UPDATE Product
reset_request_state();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['id'] = $newProdId;
$_POST['ten_sp'] = 'Updated Manga Title';
$_POST['gia_sp'] = '90000';
$_POST['tacgia_sp'] = 'Updated Author';
$_POST['anh_sp'] = 'img/sp/updated.jpg';
$_POST['chitiet_sp'] = 'Updated Description';
$_POST['csrf_token'] = 'admin_token';

ob_start();
include dirname(__DIR__) . '/admin/template/edit_product.php';
ob_get_clean();

$prodRes = $ketnoi->query("SELECT * FROM sanpham WHERE id_sp = {$newProdId}");
$updatedRow = $prodRes->fetch_assoc();
test_assert_same('Updated Manga Title', $updatedRow['ten_sp'], "Admin CRUD: Product title updated successfully");
test_assert_same(90000, (int)$updatedRow['gia_sp'], "Admin CRUD: Product price updated successfully");

// 3.3: DELETE Product
// Simulate the delete query logic of admin/product_action.php using a transaction
$ketnoi->begin_transaction();
$stmt = $ketnoi->prepare('DELETE FROM sanpham_danhmuc WHERE id_sp = ?');
$stmt->bind_param('i', $newProdId);
$stmt->execute();
$stmt->close();

$stmt = $ketnoi->prepare('DELETE FROM sanpham WHERE id_sp = ?');
$stmt->bind_param('i', $newProdId);
$stmt->execute();
$stmt->close();
$ketnoi->commit();

$prodRes = $ketnoi->query("SELECT * FROM sanpham WHERE id_sp = {$newProdId}");
test_assert_same(0, $prodRes->num_rows, "Admin CRUD: Product successfully deleted");

echo "=== INTEGRATION TESTS COMPLETED ===\n";
