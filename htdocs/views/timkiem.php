<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php?go=" . basename(__FILE__, ".php") . (isset($_GET['q']) ? "&q=" . urlencode($_GET['q']) : ""));
    exit();
}
// Trang tìm kiếm: gộp sản phẩm thường và sản phẩm sale, có phân trang
if (!isset($ketnoi)) { require_once __DIR__ . '/../dbconnect.php'; }

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24;
$offset = ($page - 1) * $perPage;

$whereConditions = [];
if ($q !== '') {
    $safe = '%' . $ketnoi->real_escape_string($q) . '%';
    $whereConditions[] = "(ten LIKE '$safe' OR tacgia LIKE '$safe')";
}

if ($category !== '') {
    $categorySafe = $ketnoi->real_escape_string($category);
    $whereConditions[] = "category = '$categorySafe'";
}

$whereLike = '';
if (!empty($whereConditions)) {
    $whereLike = "WHERE " . implode(' AND ', $whereConditions);
}

// Dùng UNION để gộp 2 bảng về chung schema với category
$sqlBase = "(
    SELECT 
        s.id_sp AS id,
        s.ten_sp AS ten,
        s.tacgia_sp AS tacgia,
        s.gia_sp AS gia,
        NULL AS gia_cu,
        s.anh_sp AS anh,
        'sanpham' AS loai,
        GROUP_CONCAT(d.ten_dm SEPARATOR ', ') AS category
    FROM sanpham s
    LEFT JOIN sanpham_danhmuc sd ON s.id_sp = sd.id_sp
    LEFT JOIN danhmuc d ON sd.id_dm = d.id_dm
    GROUP BY s.id_sp
) UNION ALL (
    SELECT 
        sl.id_tt AS id,
        sl.ten_tt AS ten,
        sl.tacgia_tt AS tacgia,
        COALESCE(sl.saugiamgia_tt, sl.giasp_tt, 0) AS gia,
        sl.giasp_tt AS gia_cu,
        sl.anh_tt AS anh,
        'sale' AS loai,
        GROUP_CONCAT(d.ten_dm SEPARATOR ', ') AS category
    FROM sales sl
    LEFT JOIN sales_danhmuc sld ON sl.id_tt = sld.id_tt
    LEFT JOIN danhmuc d ON sld.id_dm = d.id_dm
    GROUP BY sl.id_tt
)
";

// Bọc lại để thêm điều kiện tìm kiếm trên alias
$sqlCount = "SELECT COUNT(*) AS total FROM (SELECT * FROM ( $sqlBase ) AS t $whereLike) AS u";
$total = (int)($ketnoi->query($sqlCount)->fetch_assoc()['total'] ?? 0);
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT * FROM (SELECT * FROM ( $sqlBase ) AS t $whereLike) AS u ORDER BY id DESC LIMIT $perPage OFFSET $offset";
$result = $ketnoi->query($sql);
?>



<div class="search-page catalog-page" data-reveal>
    <div class="search-header section-header-split">
        <div>
            <span class="section-kicker">Tìm kiếm</span>
            <div class="search-title">Kết quả tìm kiếm</div>
            <div class="muted">Từ khóa: "<?php echo htmlspecialchars($q); ?>" • <?php echo number_format($total); ?> kết quả</div>
        </div>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $href = ($row['loai'] === 'sale')
                        ? 'index.php?go=chitietsales&id=' . (int)$row['id']
                        : 'index.php?go=chitiet&id=' . (int)$row['id'];
                ?>
                <a class="card" href="<?php echo $href; ?>">
                    <img class="thumb" src="<?php echo htmlspecialchars($row['anh']); ?>" alt="<?php echo htmlspecialchars($row['ten']); ?>">
                    <?php 
                    $discount = 0;
                    if ($row['loai'] === 'sale' && !empty($row['gia_cu']) && (int)$row['gia_cu'] > (int)$row['gia']) {
                        $discount = round((($row['gia_cu'] - $row['gia']) / $row['gia_cu']) * 100);
                    }
                    if ($discount > 0): ?>
                        <div class="discount-badge">-<?php echo $discount; ?>%</div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="row-top">
                            <div class="badge"><?php echo ($row['loai'] === 'sale') ? 'Sale' : 'Sản phẩm'; ?></div>
                        </div>
                        <div class="name"><?php echo htmlspecialchars($row['ten']); ?></div>
                        <div class="author"><?php echo htmlspecialchars($row['tacgia'] ?? ''); ?></div>
                        <div class="price">
                            <?php if ($row['loai'] === 'sale' && !empty($row['gia_cu']) && (int)$row['gia_cu'] > (int)$row['gia']): ?>
                                <span class="new"><?php echo number_format((int)$row['gia'],0,',','.'); ?>đ</span>
                                <span class="old"><?php echo number_format((int)$row['gia_cu'],0,',','.'); ?>đ</span>
                            <?php else: ?>
                                <span class="new"><?php echo number_format((int)$row['gia'],0,',','.'); ?>đ</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a class="page-link <?php echo ($p === $page) ? 'active' : ''; ?>" href="index.php?go=timkiem&q=<?php echo urlencode($q); ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="muted">Không tìm thấy kết quả phù hợp.</div>
    <?php endif; ?>
</div>

