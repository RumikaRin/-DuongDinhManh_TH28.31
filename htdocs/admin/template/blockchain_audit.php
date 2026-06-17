<?php
// FILE: admin/template/blockchain_audit.php
if (!isset($conn)) { die('Lỗi: Không tìm thấy kết nối database.'); }

$status = isset($_GET['status']) && in_array($_GET['status'], ['disabled', 'pending', 'confirmed', 'failed'], true)
    ? $_GET['status']
    : '';
$entity = isset($_GET['entity_type']) ? trim((string)$_GET['entity_type']) : '';
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = 'WHERE 1=1';
if ($status !== '') {
    $where .= " AND e.status='" . $conn->real_escape_string($status) . "'";
}
if ($entity !== '') {
    $where .= " AND e.entity_type='" . $conn->real_escape_string($entity) . "'";
}

$totalResult = $conn->query("SELECT COUNT(*) AS total FROM blockchain_audit_events e $where");
$total = $totalResult ? (int)$totalResult->fetch_assoc()['total'] : 0;
$totalPages = max(1, (int)ceil($total / $perPage));

$events = $conn->query(
    "SELECT e.*, r.network, r.chain_id, r.contract_address, r.tx_hash, r.block_number
     FROM blockchain_audit_events e
     LEFT JOIN blockchain_receipts r ON r.audit_event_id = e.id
     $where
     ORDER BY e.id DESC
     LIMIT $perPage OFFSET $offset"
);

function blockchain_audit_admin_badge(string $status): string
{
    $class = 'badge-warning';
    if ($status === 'confirmed') {
        $class = 'badge-success';
    } elseif ($status === 'failed') {
        $class = 'badge-danger';
    }
    return '<span class="badge ' . $class . '">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>';
}
?>

<div class="breadcrumb">
    <a href="index.php?route=dashboard">Home</a> / Blockchain Audit
</div>

<div class="page-header">
    <h1 class="page-title">Blockchain Audit</h1>
</div>

<div class="panel">
    <div class="panel-body">
        <form method="get" action="index.php" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <input type="hidden" name="route" value="blockchain_audit" />
            <input type="text" name="entity_type" placeholder="order, product, sale..." value="<?php echo htmlspecialchars($entity, ENT_QUOTES, 'UTF-8'); ?>" style="padding:8px;width:220px;border:1px solid #ddd;border-radius:4px" />
            <select name="status" style="padding:8px;border:1px solid #ddd;border-radius:4px">
                <option value="">Tất cả trạng thái</option>
                <?php foreach (['disabled', 'pending', 'confirmed', 'failed'] as $st): ?>
                    <option value="<?php echo $st; ?>" <?php echo $status === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Lọc</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">Audit events (<?php echo $total; ?>)</h3>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Entity</th>
                        <th>Action</th>
                        <th>Actor</th>
                        <th>Status</th>
                        <th>Payload hash</th>
                        <th>Event hash</th>
                        <th>Tx / Block</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($events && $events->num_rows > 0): ?>
                    <?php while ($row = $events->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo (int)$row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['entity_type'], ENT_QUOTES, 'UTF-8'); ?> #<?php echo (int)$row['entity_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['action'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['actor_type'], ENT_QUOTES, 'UTF-8'); ?> #<?php echo (int)$row['actor_id']; ?></td>
                            <td><?php echo blockchain_audit_admin_badge((string)$row['status']); ?></td>
                            <td style="font-family:monospace;font-size:12px;max-width:260px;word-break:break-all"><?php echo htmlspecialchars($row['payload_hash'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="font-family:monospace;font-size:12px;max-width:260px;word-break:break-all"><?php echo htmlspecialchars((string)$row['event_hash'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="font-family:monospace;font-size:12px">
                                <?php if (!empty($row['tx_hash'])): ?>
                                    <?php echo htmlspecialchars($row['tx_hash'], ENT_QUOTES, 'UTF-8'); ?><br>
                                    block <?php echo htmlspecialchars((string)$row['block_number'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php else: ?>
                                    local proof
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center;padding:30px">Chưa có audit event nào.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="display:flex;gap:8px;margin-top:12px;align-items:center">
            <?php if ($page > 1): ?>
                <a class="btn btn-sm btn-primary" href="index.php?route=blockchain_audit&page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status); ?>&entity_type=<?php echo urlencode($entity); ?>">« Trước</a>
            <?php endif; ?>
            <span>Trang <?php echo $page; ?> / <?php echo $totalPages; ?></span>
            <?php if ($page < $totalPages): ?>
                <a class="btn btn-sm btn-primary" href="index.php?route=blockchain_audit&page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status); ?>&entity_type=<?php echo urlencode($entity); ?>">Sau »</a>
            <?php endif; ?>
        </div>
    </div>
</div>
