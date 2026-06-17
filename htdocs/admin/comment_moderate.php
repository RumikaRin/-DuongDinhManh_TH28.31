<?php

session_start();
require_once __DIR__ . '/../dbconnect.php';
require_once __DIR__ . '/../includes/BlockchainAuditService.php';

app_require_admin();
app_require_post_csrf();

$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($action === 'reply' && $id > 0) {
    $reply = trim((string)($_POST['reply'] ?? ''));
    $admin = (string)$_SESSION['ten_tv'];
    $stmt = $ketnoi->prepare('UPDATE comments SET reply = ?, reply_by = ?, reply_date = NOW() WHERE id = ?');
    $stmt->bind_param('ssi', $reply, $admin, $id);
    $stmt->execute();
    if ($stmt->affected_rows >= 0) {
        blockchain_audit_record($ketnoi, 'comment', (int)$id, 'comment_replied', [
            'comment_id' => (int)$id,
        ], blockchain_audit_current_actor('admin'));
    }
    $stmt->close();
} elseif ($action === 'delete' && $id > 0) {
    $stmt = $ketnoi->prepare('DELETE FROM comments WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        blockchain_audit_record($ketnoi, 'comment', (int)$id, 'comment_deleted_by_admin', [
            'comment_id' => (int)$id,
        ], blockchain_audit_current_actor('admin'));
    }
    $stmt->close();
}

header('Location: index.php?route=comments');
exit;
