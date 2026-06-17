<?php
// FILE: admin/template/customers.php
if (!isset($ketnoi)) { die('Lỗi: Không tìm thấy kết nối database.'); }
require_once dirname(__DIR__, 2) . '/includes/BlockchainAuditService.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    app_require_post_csrf();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_customers'])) {
    $ok = $ketnoi->query(
        "INSERT INTO customers (name, phone, address, email)
         SELECT d.hoten, d.sdt, d.diachi, COALESCE(d.email, '') AS email
         FROM donhang d
         WHERE IFNULL(TRIM(d.sdt), '') <> ''
         GROUP BY d.sdt, d.hoten, d.diachi, COALESCE(d.email, '')
         ON DUPLICATE KEY UPDATE name=VALUES(name), address=VALUES(address), email=VALUES(email)"
    );
    $msg = $ok
        ? '<div style="background:#dcfce7;color:#166534;border:1px solid #86efac;padding:10px;border-radius:6px;margin-bottom:12px;">Đã đồng bộ khách hàng từ đơn hàng.</div>'
        : '<div style="background:#fde2e2;color:#b42318;border:1px solid #fca5a5;padding:10px;border-radius:6px;margin-bottom:12px;">Không thể đồng bộ khách hàng.</div>';
    if ($ok) {
        blockchain_audit_record($ketnoi, 'customer_batch', 1, 'customers_refreshed_from_orders', [
            'affected_rows' => (int)$ketnoi->affected_rows,
        ], blockchain_audit_current_actor('admin'));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['refresh_customers'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        $msg = '<div style="background:#fde2e2;color:#b42318;border:1px solid #fca5a5;padding:10px;border-radius:6px;margin-bottom:12px;">Vui lòng nhập tên khách hàng.</div>';
    } else {
        $stmt = $ketnoi->prepare('INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $name, $email, $phone, $address);
        if ($stmt && $stmt->execute()) {
            $customerId = (int)$stmt->insert_id;
            blockchain_audit_record($ketnoi, 'customer', $customerId, 'customer_created', [
                'customer_id' => $customerId,
                'source' => 'admin_customers_page',
            ], blockchain_audit_current_actor('admin'));
            $msg = '<div style="background:#dcfce7;color:#166534;border:1px solid #86efac;padding:10px;border-radius:6px;margin-bottom:12px;">Thêm khách hàng thành công!</div>';
        } else {
            $err = $stmt ? $stmt->error : $ketnoi->error;
            $msg = '<div style="background:#fde2e2;color:#b42318;border:1px solid #fca5a5;padding:10px;border-radius:6px;margin-bottom:12px;">Không thể thêm: ' . htmlspecialchars($err) . '</div>';
        }
        if ($stmt) $stmt->close();
    }
}

// Fetch list
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '';
if ($q !== '') {
    $safe = '%' . $ketnoi->real_escape_string($q) . '%';
    $where = "WHERE name LIKE '$safe' OR email LIKE '$safe' OR phone LIKE '$safe'";
}
$res = $ketnoi->query("SELECT * FROM customers $where ORDER BY id DESC");
?>
<div class="breadcrumb">
    <a href="index.php?route=dashboard">Home</a> / Khách hàng
</div>
<div class="page-header">
    <h1 class="page-title">Khách hàng</h1>
</div>
<?php echo $msg; ?>
<div class="panel">
    <div class="panel-header"><h3 class="panel-title">Thêm khách hàng</h3></div>
    <div class="panel-body">
        <form method="post" action="index.php?route=customers" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <?php echo app_csrf_field(); ?>
            <div>
                <label>Tên</label>
                <input type="text" name="name" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;" />
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;" />
            </div>
            <div>
                <label>Số điện thoại</label>
                <input type="text" name="phone" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;" />
            </div>
            <div>
                <label>Địa chỉ</label>
                <input type="text" name="address" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;" />
            </div>
            <div style="grid-column:1 / -1;display:flex;gap:10px;">
                <button class="btn btn-success" type="submit"><i class="fas fa-save"></i> Lưu</button>
                <a class="btn btn-danger" href="index.php?route=dashboard"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header"><h3 class="panel-title">Danh sách khách hàng</h3></div>
    <div class="panel-body">
        <div style="margin-bottom:15px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <form method="get" action="index.php" style="display:flex;gap:8px;align-items:center;flex:1;min-width:300px;">
                <input type="hidden" name="route" value="customers" />
                <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Tìm tên/email/sđt" style="padding:8px;border:1px solid #ddd;border-radius:6px;flex:1;" />
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Tìm</button>
            </form>
            <form method="post" action="index.php?route=customers" style="display:inline;">
                <?php echo app_csrf_field(); ?>
                <button type="submit" name="refresh_customers" value="1" class="btn btn-success" style="white-space:nowrap;">
                    <i class="fas fa-sync-alt"></i> Đồng bộ từ đơn hàng
                </button>
            </form>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên</th>
                        <th>Email</th>
                        <th>SĐT</th>
                        <th>Địa chỉ</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res && $res->num_rows > 0): ?>
                        <?php while ($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo (int)$row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                                <td>
                                    <?php if (!empty($row['email'])): ?>
                                        <span style="color: #28a745; font-weight: 500;"><?php echo htmlspecialchars($row['email']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #dc3545; font-style: italic;">Chưa có email</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['address'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at'] ?? ''); ?></td>
                                <td>
                                    <form method="post" action="customer_action.php" style="display:inline" onsubmit="return confirm('Xóa khách hàng #<?php echo (int)$row['id']; ?>?');">
                                        <?php echo app_csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: #666;">
                                <i class="fas fa-users" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                                Chưa có khách hàng nào.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
