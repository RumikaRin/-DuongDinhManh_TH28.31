<?php
// FILE: manage_orders.php

if (!isset($conn)) {
    die("Lỗi: Không tìm thấy kết nối database.");
}

// Search & pagination
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = '';
if ($q !== '') {
    $safe = '%' . $conn->real_escape_string($q) . '%';
    $where = "WHERE hoten LIKE '$safe' OR sdt LIKE '$safe' OR trangthai LIKE '$safe'";
}

$count_sql = "SELECT COUNT(*) AS total FROM donhang $where";
$total_orders = (int)$conn->query($count_sql)->fetch_assoc()['total'];
$total_pages = max(1, (int)ceil($total_orders / $per_page));

// Lấy tất cả đơn hàng (có lọc & phân trang)
$sql_all_orders = "SELECT id_dh, hoten, sdt, tongtien, ngaydat, trangthai, vnpay_transaction_no, blockchain_tx_hash FROM donhang $where ORDER BY ngaydat DESC LIMIT $per_page OFFSET $offset";
$result_all_orders = $conn->query($sql_all_orders);

// Hàm hiển thị badge trạng thái (Định nghĩa lại để file này độc lập)
function get_status_badge($status) {
    switch ($status) {
        case 'Chờ xử lý':
            return '<span class="badge badge-warning">Chờ xử lý</span>';
        case 'Đã xác nhận':
            return '<span class="badge badge-warning">Đã xác nhận</span>';
        case 'Đang vận chuyển':
            return '<span class="badge badge-warning">Đang vận chuyển</span>';
        case 'Vận chuyển thành công':
            return '<span class="badge badge-success">Vận chuyển thành công</span>';
        case 'Đã hủy':
            return '<span class="badge badge-danger">Đã hủy</span>';
        case 'Hoàn thành': // legacy mapping if any
            return '<span class="badge badge-success">Hoàn thành</span>';
        case 'Đang xử lý': // legacy mapping if any
            return '<span class="badge badge-warning">Đang xử lý</span>';
        default:
            return '<span class="badge badge-warning">' . htmlspecialchars($status) . '</span>';
    }
}
?>

<div class="breadcrumb">
    <a href="index.php?route=dashboard">Home</a> / Sales / Đơn hàng
</div>
<div class="page-header">
    <h1 class="page-title">Quản lý Đơn hàng</h1>
    <div class="actions">
        <button class="btn btn-primary"><i class="fas fa-print"></i> In</button>
        <button class="btn btn-success"><i class="fas fa-download"></i> Export</button>
    </div>
</div>

<div class="panel">
    <div class="panel-body">
        <form method="get" action="index.php" style="display:flex; gap:10px; align-items:center;">
            <input type="hidden" name="route" value="manage_orders" />
            <input type="text" name="q" placeholder="Tìm theo tên, SĐT, trạng thái" value="<?php echo htmlspecialchars($q); ?>" style="padding:8px; width:280px; border:1px solid #ddd; border-radius:4px;" />
            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Tìm</button>
        </form>
    </div>
    <div class="panel-body" style="padding-top:0">
        <div style="display:flex; gap:8px; align-items:center;">
            <?php if ($page > 1): ?>
                <a class="btn btn-sm btn-primary" href="index.php?route=manage_orders&page=<?php echo $page-1; ?>&q=<?php echo urlencode($q); ?>">« Trước</a>
            <?php endif; ?>
            <span>Trang <?php echo $page; ?> / <?php echo $total_pages; ?></span>
            <?php if ($page < $total_pages): ?>
                <a class="btn btn-sm btn-primary" href="index.php?route=manage_orders&page=<?php echo $page+1; ?>&q=<?php echo urlencode($q); ?>">Sau »</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">Danh sách Đơn hàng (<?php echo $result_all_orders->num_rows; ?>)</h3>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>SĐT</th>
                        <th>Trạng thái</th>
                        <th>Tổng tiền</th>
                        <th>Ngày đặt</th>
                        <th>Blockchain</th>
                        <th>Cập nhật trạng thái</th>
                    </tr>
                <tbody>
                    <?php if ($result_all_orders->num_rows > 0): ?>
                        <?php while ($row = $result_all_orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo (int)$row['id_dh']; ?></td>
                            <td><?php echo htmlspecialchars($row['hoten']); ?></td>
                            <td><?php echo htmlspecialchars($row['sdt']); ?></td>
                            <td><?php echo get_status_badge($row['trangthai']); ?></td>
                            <td><?php echo number_format($row['tongtien']); ?>đ</td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['ngaydat'])); ?></td>
                            <td>
                                <?php if (!empty($row['vnpay_transaction_no'])): ?>
                                    <?php if (!empty($row['blockchain_tx_hash'])): ?>
                                        <a href="https://sepolia.etherscan.io/tx/<?php echo $row['blockchain_tx_hash']; ?>" target="_blank" class="badge badge-success" style="text-decoration:none;"><i class="fas fa-link"></i> Đã lưu chuỗi</a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-info btn-sync-blockchain" data-order="<?php echo $row['id_dh']; ?>" data-amount="<?php echo $row['tongtien']; ?>" data-vnpay="<?php echo $row['vnpay_transaction_no']; ?>"><i class="fas fa-sync"></i> Đồng bộ BC</button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#aaa; font-size: 0.85em;">Không áp dụng</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="order_action.php" style="display:flex; gap:8px; align-items:center;">
                                    <?php echo app_csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int)$row['id_dh']; ?>" />
                                    <select name="status" style="padding:6px 8px; border:1px solid #ddd; border-radius:4px;">
                                        <?php
                                            $statuses = ['Chờ xử lý','Đã xác nhận','Đang vận chuyển','Vận chuyển thành công','Đã hủy'];
                                            $current = $row['trangthai'];
                                            $delivered = in_array($current, ['Vận chuyển thành công','Hoàn thành'], true);
                                            foreach ($statuses as $st) {
                                                $sel = ($current === $st) ? 'selected' : '';
                                                // Gợi ý 1: khóa hủy khi đã giao thành công/hoàn thành
                                                // Gợi ý 2: không cho quay về 'Đã xác nhận' khi đã giao thành công
                                                $disabled = '';
                                                if ($delivered && in_array($st, ['Đã hủy','Đã xác nhận'], true)) {
                                                    $disabled = 'disabled';
                                                }
                                                echo '<option value="'.htmlspecialchars($st).'" '.$sel.' '.$disabled.'>'.htmlspecialchars($st).'</option>';
                                            }
                                        ?>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-primary btn-sm">Cập nhật</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">Không có đơn hàng nào được tìm thấy.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/5.7.2/ethers.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Dummy contract address cho mục đích demo (đã deploy trên Sepolia)
    const CONTRACT_ADDRESS = '0xD804473D547F538964E1D05105EDa20EEcd2Eea5'; 
    const CONTRACT_ABI = [
        "function recordPayment(string memory _orderId, uint256 _amountVND, string memory _vnpayTxNo) public"
    ];

    document.querySelectorAll('.btn-sync-blockchain').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const orderId = btn.getAttribute('data-order');
            const amount = btn.getAttribute('data-amount');
            const vnpayTx = btn.getAttribute('data-vnpay');

            if (typeof window.ethereum === 'undefined') {
                alert('Vui lòng cài đặt MetaMask trên trình duyệt (Chrome/Edge) để ghi nhận giao dịch lên Blockchain!');
                return;
            }

            try {
                // Yêu cầu kết nối ví MetaMask
                await window.ethereum.request({ method: 'eth_requestAccounts' });
                const provider = new ethers.providers.Web3Provider(window.ethereum);
                const signer = provider.getSigner();

                const contract = new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signer);

                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang ký...';
                btn.disabled = true;

                // Gửi giao dịch
                const tx = await contract.recordPayment(String(orderId), parseInt(amount), String(vnpayTx));
                
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xác nhận chuỗi...';
                
                // Đợi xác nhận block
                await tx.wait();

                // Lưu tx.hash vào cơ sở dữ liệu qua AJAX
                const fd = new FormData();
                fd.append('order_id', orderId);
                fd.append('tx_hash', tx.hash);

                const res = await fetch('update_tx_hash.php', {
                    method: 'POST',
                    body: fd
                });
                
                const data = await res.json();

                if (data.success) {
                    alert('Ghi nhận lên Blockchain thành công! Bằng chứng đã được tạo.');
                    window.location.reload();
                } else {
                    alert('Đã ghi lên mạng lưới Blockchain nhưng lỗi lưu vào CSDL: ' + data.message);
                    btn.innerHTML = '<i class="fas fa-sync"></i> Đồng bộ BC';
                    btn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                alert('Có lỗi xảy ra hoặc bạn đã từ chối giao dịch trong MetaMask.');
                btn.innerHTML = '<i class="fas fa-sync"></i> Đồng bộ BC';
                btn.disabled = false;
            }
        });
    });
});
</script>
