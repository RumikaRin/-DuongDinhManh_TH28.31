<?php
// FILE: manage_products.php

if (!isset($conn)) {
    die("Lỗi: Không tìm thấy kết nối database.");
}

// Search and pagination params (products only)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$per_page = 20; // paginate products 20 per page
$offset = ($page - 1) * $per_page;

// Fetch products from sanpham with optional search
$where = '';
if ($q !== '') {
    $safe = '%' . $conn->real_escape_string($q) . '%';
    $where = "WHERE ten_sp LIKE '$safe' OR tacgia_sp LIKE '$safe'";
}

// Count total for pagination
$count_sql = "SELECT COUNT(*) AS total FROM sanpham $where";
$total_products = (int)$conn->query($count_sql)->fetch_assoc()['total'];
$total_pages = max(1, (int)ceil($total_products / $per_page));

$sql_products = "SELECT DISTINCT s.id_sp, s.ten_sp, s.gia_sp, s.tacgia_sp, s.anh_sp, GROUP_CONCAT(d.ten_dm SEPARATOR ', ') as danh_muc FROM sanpham s LEFT JOIN sanpham_danhmuc sd ON s.id_sp = sd.id_sp LEFT JOIN danhmuc d ON sd.id_dm = d.id_dm $where GROUP BY s.id_sp ORDER BY s.id_sp DESC LIMIT $per_page OFFSET $offset";
$result_products = $conn->query($sql_products);

// Optionally, fetch promotional products from `sales` with same search
$where_sales = '';
if ($q !== '') {
    $safe = '%' . $conn->real_escape_string($q) . '%';
    $where_sales = "WHERE ten_tt LIKE '$safe' OR tacgia_tt LIKE '$safe'";
}
$sql_sales = "SELECT s.id_tt, s.ten_tt, s.giasp_tt, s.tacgia_tt, s.anh_tt, GROUP_CONCAT(d.ten_dm SEPARATOR ', ') as danh_muc FROM sales s LEFT JOIN sales_danhmuc sd ON s.id_tt = sd.id_tt LEFT JOIN danhmuc d ON sd.id_dm = d.id_dm $where_sales GROUP BY s.id_tt ORDER BY s.id_tt DESC";
$result_sales = $conn->query($sql_sales);
?>

<div class="breadcrumb">
    <a href="index.php?route=dashboard">Home</a> / Catalog / Sản phẩm
</div>
<div class="page-header">
    <h1 class="page-title">Quản lý Sản phẩm</h1>
    <div class="actions">
        <a href="index.php?route=addproducts" class="btn btn-success"><i class="fas fa-plus"></i> Thêm Sản phẩm</a>
    </div>
    </div>
<div class="panel">
    <div class="panel-body">
        <form method="get" action="index.php" style="display:flex; gap:10px; align-items:center;">
            <input type="hidden" name="route" value="manage_products" />
            <input type="text" name="q" placeholder="Tìm theo tên hoặc nhà sản xuất" value="<?php echo htmlspecialchars($q); ?>" style="padding:8px; width:280px; border:1px solid #ddd; border-radius:4px;" />
            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Tìm</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">Danh sách Sản phẩm (<?php echo $result_products ? $result_products->num_rows : 0; ?>)</h3>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ảnh</th>
                        <th>Tên</th>
                        <th>Tác giả/NCC</th>
                        <th>Danh mục</th>
                        <th>Giá</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_products && $result_products->num_rows > 0): ?>
                        <?php while ($row = $result_products->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id_sp']; ?></td>
                            <td>
                                <?php if (!empty($row['anh_sp'])): ?>
                                    <img src="../<?php echo htmlspecialchars($row['anh_sp']); ?>" alt="<?php echo htmlspecialchars($row['ten_sp']); ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <div style="width:48px;height:48px;background:#ecf0f1;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#7f8c8d;">N/A</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['ten_sp']); ?></td>
                            <td><?php echo htmlspecialchars($row['tacgia_sp']); ?></td>
                            <td><?php echo !empty($row['danh_muc']) ? htmlspecialchars($row['danh_muc']) : 'Chưa phân loại'; ?></td>
                            <td><?php echo number_format((int)$row['gia_sp']); ?>đ</td>
                            <td>
                                <div class="action-btns">
                                    <a href="index.php?route=edit_product&id=<?php echo (int)$row['id_sp']; ?>" class="btn btn-success btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                                    <form method="post" action="product_action.php" style="display:inline" onsubmit="return confirm('Xóa sản phẩm #<?php echo (int)$row['id_sp']; ?>?');">
                                        <?php echo app_csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="type" value="sanpham">
                                        <input type="hidden" name="id" value="<?php echo (int)$row['id_sp']; ?>">
                                        <button class="btn btn-danger btn-sm" type="submit" title="Xóa"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Chưa có sản phẩm nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:10px; display:flex; gap:8px; align-items:center;">
            <?php if ($page > 1): ?>
                <a class="btn btn-sm btn-primary" href="index.php?route=manage_products&page=<?php echo $page-1; ?>&q=<?php echo urlencode($q); ?>">« Trước</a>
            <?php endif; ?>
            <span>Trang <?php echo $page; ?> / <?php echo $total_pages; ?></span>
            <?php if ($page < $total_pages): ?>
                <a class="btn btn-sm btn-primary" href="index.php?route=manage_products&page=<?php echo $page+1; ?>&q=<?php echo urlencode($q); ?>">Sau »</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">Sản phẩm Khuyến mãi (<?php echo $result_sales ? $result_sales->num_rows : 0; ?>)</h3>
        <a href="index.php?route=add_sale" class="btn btn-success"><i class="fas fa-plus"></i> Thêm Sale</a>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ảnh</th>
                        <th>Tên</th>
                        <th>Tác giả/NCC</th>
                        <th>Danh mục</th>
                        <th>Giá</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_sales && $result_sales->num_rows > 0): ?>
                        <?php while ($row = $result_sales->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id_tt']; ?></td>
                            <td>
                                <?php if (!empty($row['anh_tt'])): ?>
                                    <img src="../<?php echo htmlspecialchars($row['anh_tt']); ?>" alt="<?php echo htmlspecialchars($row['ten_tt']); ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <div style="width:48px;height:48px;background:#ecf0f1;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#7f8c8d;">N/A</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['ten_tt']); ?></td>
                            <td><?php echo htmlspecialchars($row['tacgia_tt']); ?></td>
                            <td><?php echo !empty($row['danh_muc']) ? htmlspecialchars($row['danh_muc']) : 'Chưa phân loại'; ?></td>
                            <td><?php echo number_format((int)$row['giasp_tt']); ?>đ</td>
                            <td>
                                <div class="action-btns">
                                    <a href="index.php?route=edit_sale&id=<?php echo (int)$row['id_tt']; ?>" class="btn btn-success btn-sm" title="Sửa"><i class="fas fa-edit"></i></a>
                                    <form method="post" action="sale_action.php" style="display:inline" onsubmit="return confirm('Xóa khuyến mãi #<?php echo (int)$row['id_tt']; ?>?');">
                                        <?php echo app_csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$row['id_tt']; ?>">
                                        <button class="btn btn-danger btn-sm" type="submit" title="Xóa"><i class="fas fa-trash"></i></button>
                                    </form>
                                    <form method="post" action="sale_action.php" style="display:inline" onsubmit="return confirm('Chuyển khuyến mãi #<?php echo (int)$row['id_tt']; ?> thành sản phẩm thường?');">
                                        <?php echo app_csrf_field(); ?>
                                        <input type="hidden" name="action" value="convert">
                                        <input type="hidden" name="id" value="<?php echo (int)$row['id_tt']; ?>">
                                        <button class="btn btn-primary btn-sm" type="submit" title="Chuyển thành SP thường"><i class="fas fa-exchange-alt"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">Chưa có sản phẩm khuyến mãi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
