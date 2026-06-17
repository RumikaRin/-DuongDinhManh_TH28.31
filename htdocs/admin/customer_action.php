<?php

session_start();
require_once __DIR__ . '/../dbconnect.php';
require_once __DIR__ . '/../includes/BlockchainAuditService.php';

app_require_admin();
app_require_post_csrf();

$id = (int)($_POST['id'] ?? 0);
if (($_POST['action'] ?? '') === 'delete' && $id > 0) {
    $stmt = $ketnoi->prepare('DELETE FROM customers WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        blockchain_audit_record($ketnoi, 'customer', (int)$id, 'customer_deleted', [
            'customer_id' => (int)$id,
        ], blockchain_audit_current_actor('admin'));
    }
    $stmt->close();
}

header('Location: index.php?route=customers');
exit;
