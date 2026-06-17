<?php

session_start();
require_once __DIR__ . '/../dbconnect.php';
require_once __DIR__ . '/../includes/BlockchainAuditService.php';

app_require_admin();
app_require_post_csrf();

$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0 || !in_array($action, ['delete', 'convert'], true)) {
    header('Location: index.php?route=manage_products');
    exit;
}

$ketnoi->begin_transaction();
$convertedProductId = 0;
try {
    if ($action === 'delete') {
        $stmt = $ketnoi->prepare('DELETE FROM sales_danhmuc WHERE id_tt = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $ketnoi->prepare('DELETE FROM sales WHERE id_tt = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $ketnoi->prepare('SELECT ten_tt, giasp_tt, chitietsp_tt, anh_tt, tacgia_tt FROM sales WHERE id_tt = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $sale = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($sale) {
            $nextResult = $ketnoi->query('SELECT COALESCE(MAX(id_sp), 0) + 1 AS next_id FROM sanpham');
            $nextId = (int)$nextResult->fetch_assoc()['next_id'];

            $stmt = $ketnoi->prepare('INSERT INTO sanpham (id_sp, ten_sp, gia_sp, chitiet_sp, anh_sp, tacgia_sp) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('isisss', $nextId, $sale['ten_tt'], $sale['giasp_tt'], $sale['chitietsp_tt'], $sale['anh_tt'], $sale['tacgia_tt']);
            $stmt->execute();
            $stmt->close();

            $stmt = $ketnoi->prepare('INSERT IGNORE INTO sanpham_danhmuc (id_sp, id_dm) SELECT ?, id_dm FROM sales_danhmuc WHERE id_tt = ?');
            $stmt->bind_param('ii', $nextId, $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $ketnoi->prepare('DELETE FROM sales_danhmuc WHERE id_tt = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $ketnoi->prepare('DELETE FROM sales WHERE id_tt = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $convertedProductId = $nextId;
        }
    }
    $ketnoi->commit();
    if ($action === 'delete') {
        blockchain_audit_record($ketnoi, 'sale', (int)$id, 'sale_deleted', [
            'sale_id' => (int)$id,
            'source' => 'admin_sale_action',
        ], blockchain_audit_current_actor('admin'));
    } elseif ($convertedProductId > 0) {
        blockchain_audit_record($ketnoi, 'sale', (int)$id, 'sale_converted_to_product', [
            'sale_id' => (int)$id,
            'product_id' => (int)$convertedProductId,
        ], blockchain_audit_current_actor('admin'));
        blockchain_audit_record($ketnoi, 'product', (int)$convertedProductId, 'product_created_from_sale', [
            'product_id' => (int)$convertedProductId,
            'sale_id' => (int)$id,
        ], blockchain_audit_current_actor('admin'));
    }
    app_cache_clear();
} catch (Throwable $exception) {
    $ketnoi->rollback();
    error_log('Sale action failed: ' . $exception->getMessage());
}

header('Location: index.php?route=manage_products');
exit;
