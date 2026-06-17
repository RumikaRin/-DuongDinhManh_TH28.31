<?php

session_start();
require_once __DIR__ . '/../dbconnect.php';
require_once __DIR__ . '/../includes/BlockchainAuditService.php';

app_require_admin();
app_require_post_csrf();

$action = $_POST['action'] ?? '';
$type = app_normalize_product_type($_POST['type'] ?? '');
$id = (int)($_POST['id'] ?? 0);

if ($action === 'delete' && $type === 'sanpham' && $id > 0) {
    $ketnoi->begin_transaction();
    try {
        $stmt = $ketnoi->prepare('DELETE FROM sanpham_danhmuc WHERE id_sp = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $ketnoi->prepare('DELETE FROM sanpham WHERE id_sp = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $ketnoi->commit();
        blockchain_audit_record($ketnoi, 'product', (int)$id, 'product_deleted', [
            'product_id' => (int)$id,
            'source' => 'admin_product_action',
        ], blockchain_audit_current_actor('admin'));
        app_cache_clear();
    } catch (Throwable $exception) {
        $ketnoi->rollback();
        error_log('Delete product failed: ' . $exception->getMessage());
    }
}

header('Location: index.php?route=manage_products');
exit;
