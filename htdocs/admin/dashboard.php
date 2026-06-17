<?php
// FILE: dashboard.php

// Kiểm tra kết nối database đã được thiết lập trong header.php
if (!isset($conn)) {
    die("Lỗi: Không tìm thấy kết nối database.");
}

// Lấy dữ liệu thống kê
// Tổng đơn hàng
$sql_orders = "SELECT COUNT(id_dh) as total_orders FROM donhang";
$result_orders = $conn->query($sql_orders);
$total_orders = $result_orders->fetch_assoc()['total_orders'];

// Tổng doanh thu (đã giao): Hoàn thành + Vận chuyển thành công
$sql_revenue = "SELECT COALESCE(SUM(tongtien),0) as total_revenue FROM donhang WHERE trangthai IN ('Hoàn thành','Vận chuyển thành công')";
$result_revenue = $conn->query($sql_revenue);
$revenue_value = $result_revenue->fetch_assoc()['total_revenue'];

// Format doanh thu theo triệu VNĐ
if ($revenue_value >= 1000000) {
    $total_revenue = number_format($revenue_value / 1000000, 1) . 'M';
} elseif ($revenue_value >= 1000) {
    $total_revenue = number_format($revenue_value / 1000, 1) . 'K';
} else {
    $total_revenue = number_format($revenue_value);
}

// Tổng sản phẩm (Tính cả sanpham và sales)
$sql_products = "SELECT (SELECT COUNT(id_sp) FROM sanpham) + (SELECT COUNT(id_tt) FROM sales) AS total_products";
$result_products = $conn->query($sql_products);
$total_products = $result_products->fetch_assoc()['total_products'];

// Tổng khách hàng (Từ bảng users)
$sql_users = "SELECT COUNT(id_tv) as total_users FROM users";
$result_users = $conn->query($sql_users);
$total_users = $result_users->fetch_assoc()['total_users'];


// Lấy 10 đơn hàng gần đây
$sql_recent_orders = "SELECT id_dh, hoten, trangthai, tongtien, ngaydat FROM donhang ORDER BY ngaydat DESC LIMIT 10";
$result_recent_orders = $conn->query($sql_recent_orders);

// Hàm hiển thị badge trạng thái
function get_status_badge($status) {
    switch ($status) {
        case 'Hoàn thành':
            return '<span class="badge badge-success">Hoàn thành</span>';
        case 'Vận chuyển thành công':
            return '<span class="badge badge-success">Vận chuyển thành công</span>';
        case 'Đã xác nhận':
            return '<span class="badge badge-warning">Đã xác nhận</span>';
        case 'Đang vận chuyển':
            return '<span class="badge badge-warning">Đang vận chuyển</span>';
        case 'Đang xử lý':
            return '<span class="badge badge-warning">Đang xử lý</span>';
        case 'Chờ xử lý':
            return '<span class="badge badge-warning">Chờ xử lý</span>';
        case 'Đã hủy':
            return '<span class="badge badge-danger">Đã hủy</span>';
        default:
            return '<span class="badge badge-warning">' . $status . '</span>';
    }
}
?>

<div class="breadcrumb">
    <a href="index.php?route=dashboard">Home</a> / Dashboard
</div>
<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h3><?php echo number_format($total_orders); ?></h3>
            <p>Tổng đơn hàng</p>
        </div>
        <div class="stat-icon blue">
            <i class="fas fa-shopping-cart"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3><?php echo $total_revenue; ?></h3>
            <p>Doanh thu (Triệu VNĐ)</p>
        </div>
        <div class="stat-icon green">
            <i class="fas fa-dollar-sign"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3><?php echo number_format($total_users); ?></h3>
            <p>Khách hàng</p>
        </div>
        <div class="stat-icon orange">
            <i class="fas fa-users"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3><?php echo number_format($total_products); ?></h3>
            <p>Sản phẩm</p>
        </div>
        <div class="stat-icon red">
            <i class="fas fa-box"></i>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">Đơn hàng gần đây</h3>
        <a href="index.php?route=manage_orders" class="btn btn-primary btn-sm">
            <i class="fas fa-eye"></i> Xem tất cả
        </a>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th>Trạng thái</th>
                        <th>Tổng tiền</th>
                        <th>Ngày đặt</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_recent_orders->num_rows > 0): ?>
                        <?php while ($row = $result_recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id_dh']; ?></td>
                            <td><?php echo htmlspecialchars($row['hoten']); ?></td>
                            <td><?php echo get_status_badge($row['trangthai']); ?></td>
                            <td><?php echo number_format($row['tongtien']); ?>đ</td>
                            <td><?php echo date('d/m/Y', strtotime($row['ngaydat'])); ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="index.php?route=manage_orders&action=view&id=<?php echo $row['id_dh']; ?>" class="btn btn-primary btn-sm" title="Xem"><i class="fas fa-eye"></i></a>
                                    <a href="index.php?route=manage_orders&action=edit&id=<?php echo $row['id_dh']; ?>" class="btn btn-success btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Không có đơn hàng nào gần đây.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>