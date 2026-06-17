<?php
// FILE: template/manufacturers.php
if (!isset($conn)) { die('Lỗi: Không tìm thấy kết nối database.'); }

// Lấy danh sách nhà sản xuất từ sanpham và sales
$sql_manu = "
    SELECT tacgia AS ten_nsx, SUM(soluong) AS so_sp FROM (
        SELECT tacgia_sp AS tacgia, COUNT(*) AS soluong FROM sanpham GROUP BY tacgia_sp
        UNION ALL
        SELECT tacgia_tt AS tacgia, COUNT(*) AS soluong FROM sales GROUP BY tacgia_tt
    ) t
    GROUP BY tacgia
    ORDER BY ten_nsx ASC
";
$result_manu = $conn->query($sql_manu);
?>

<div class="breadcrumb">
    <a href="index.php?route=dashboard">Home</a> / Catalog / Nhà sản xuất
</div>
<div class="page-header">
    <h1 class="page-title">Nhà sản xuất</h1>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">Danh sách Nhà sản xuất (<?php echo $result_manu ? $result_manu->num_rows : 0; ?>)</h3>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tên Nhà sản xuất</th>
                        <th>Số lượng sản phẩm</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_manu && $result_manu->num_rows > 0): ?>
                        <?php $i=1; while ($row = $result_manu->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($row['ten_nsx']); ?></td>
                            <td><?php echo (int)$row['so_sp']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">Chưa có dữ liệu nhà sản xuất.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
