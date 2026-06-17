<?php
// FILE: admin/template/reports.php
if (!isset($conn)) { die('Lỗi: Không tìm thấy kết nối database.'); }

// Top customers by number of orders (VIP ranking, all time)
$top_sql = "SELECT hoten, sdt, COUNT(*) AS so_don, SUM(tongtien) AS tong_chi
            FROM donhang
            GROUP BY hoten, sdt
            ORDER BY tong_chi DESC, so_don DESC
            LIMIT 10";
$top_customers = $conn->query($top_sql);

// 30-day revenue (completed or delivered)
$rev30_sql = "SELECT DATE(ngaydat) AS ngay, SUM(tongtien) AS doanh_thu
              FROM donhang
              WHERE trangthai IN ('Hoàn thành','Vận chuyển thành công')
                AND DATE(ngaydat) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              GROUP BY DATE(ngaydat)
              ORDER BY ngay ASC";
$rev30 = $conn->query($rev30_sql);
$rev30_labels = [];
$rev30_data = [];
// Fill 30 days to show zero days as 0
for ($i=29; $i>=0; $i--) {
    $rev30_labels[] = date('d/m', strtotime("-{$i} day"));
    $rev30_data[date('Y-m-d', strtotime("-{$i} day"))] = 0;
}
if ($rev30 && $rev30->num_rows > 0) {
    while ($r = $rev30->fetch_assoc()) {
        $key = date('Y-m-d', strtotime($r['ngay']));
        $rev30_data[$key] = (int)$r['doanh_thu'];
    }
}
$rev30_values = array_values($rev30_data);

// Top products sold in last 30 days (by quantity)
$prod_sql = "SELECT COALESCE(sp.ten_sp, s.ten_tt) AS ten, SUM(ct.soluong) AS sl
             FROM donhang_chitiet ct
             JOIN donhang d ON d.id_dh = ct.id_dh
             LEFT JOIN sanpham sp ON ct.loai='sanpham' AND sp.id_sp = ct.id_sp
             LEFT JOIN sales s ON ct.loai='sales' AND s.id_tt = ct.id_sp
             WHERE DATE(d.ngaydat) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
               AND d.trangthai IN ('Hoàn thành','Vận chuyển thành công')
             GROUP BY ten
             ORDER BY sl DESC
             LIMIT 8";
$prod = $conn->query($prod_sql);
$prod_labels = [];
$prod_data = [];
if ($prod && $prod->num_rows > 0) {
    while ($p = $prod->fetch_assoc()) {
        $prod_labels[] = $p['ten'] !== null ? $p['ten'] : 'Không rõ';
        $prod_data[] = (int)$p['sl'];
    }
}

// Loyal customers (top spend last 30 days)
$vip30_sql = "SELECT hoten, sdt, SUM(tongtien) AS tong_chi
              FROM donhang
              WHERE DATE(ngaydat) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                AND trangthai IN ('Hoàn thành','Vận chuyển thành công')
              GROUP BY hoten, sdt
              ORDER BY tong_chi DESC
              LIMIT 8";
$vip30 = $conn->query($vip30_sql);
$vip30_labels = [];
$vip30_data = [];
if ($vip30 && $vip30->num_rows > 0) {
    while ($v = $vip30->fetch_assoc()) {
        $label = trim($v['hoten']) !== '' ? $v['hoten'] : 'Không tên';
        if (!empty($v['sdt'])) { $label .= ' ('.substr($v['sdt'], -4).')'; }
        $vip30_labels[] = $label;
        $vip30_data[] = (int)$v['tong_chi'];
    }
}
?>

<div class="breadcrumb">
    <a href="index.php?route=dashboard">Home</a> / Reports
</div>
<div class="page-header">
    <h1 class="page-title">Báo cáo bán hàng</h1>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">Doanh thu 30 ngày gần nhất</h3>
    </div>
    <div class="panel-body">
        <div style="height:360px; position:relative;">
            <canvas id="rev30Chart"></canvas>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h3 class="panel-title">Khách hàng VIP (chi tiêu cao nhất)</h3>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Họ tên</th>
                        <th>SĐT</th>
                        <th>Số đơn</th>
                        <th>Tổng chi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($top_customers && $top_customers->num_rows > 0): $i=1; ?>
                    <?php while ($row = $top_customers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($row['hoten']); ?></td>
                        <td><?php echo htmlspecialchars($row['sdt']); ?></td>
                        <td><?php echo (int)$row['so_don']; ?></td>
                        <td><?php echo number_format((int)$row['tong_chi']); ?>đ</td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">Chưa có dữ liệu.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const rev30Ctx = document.getElementById('rev30Chart').getContext('2d');
const rev30Chart = new Chart(rev30Ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($rev30_labels, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{
            label: 'Doanh thu (đ)',
            data: <?php echo json_encode(array_values($rev30_values), JSON_UNESCAPED_UNICODE); ?>,
            borderColor: '#1e91cf',
            backgroundColor: 'rgba(30,145,207,0.15)',
            borderWidth: 2,
            tension: 0.25,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true } },
        scales: {
            y: {
                beginAtZero: true,
                suggestedMax: (function(){ const d = <?php echo json_encode(array_values($rev30_values), JSON_UNESCAPED_UNICODE); ?>; const m = Math.max.apply(null, d.concat([0])); return m*1.6; })()
            }
        }
    }
});

// Top products in 30 days (fixed-height container)
const prodWrap = document.createElement('div');
prodWrap.style.height = '360px';
prodWrap.style.position = 'relative';
const prodCtx = document.createElement('canvas');
prodCtx.id = 'prod30Chart';
prodCtx.style.height = '360px';
prodCtx.style.width = '100%';
prodWrap.appendChild(prodCtx);
document.querySelectorAll('.panel')[1].querySelector('.panel-body').appendChild(prodWrap);
new Chart(prodCtx.getContext('2d'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($prod_labels, JSON_UNESCAPED_UNICODE); ?>,
    datasets: [{
      label: 'Số lượng bán',
      data: <?php echo json_encode($prod_data, JSON_UNESCAPED_UNICODE); ?>,
      backgroundColor: '#4e73df',
      borderRadius: 6,
      barPercentage: 8,
      categoryPercentage: 8 ,
      maxBarThickness: 26
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins:{ legend:{ display:true } },
    scales:{
      x:{ ticks:{ autoSkip: false, maxRotation: 0, minRotation: 0 } },
      y:{ beginAtZero:true, suggestedMax: (function(){ const d = <?php echo json_encode($prod_data, JSON_UNESCAPED_UNICODE); ?>; const m = Math.max.apply(null, d.concat([0])); return m*1.4; })() }
    }
  }
});

// Loyal customers in 30 days (spending) (fixed-height container)
const vipWrap = document.createElement('div');
vipWrap.style.height = '360px';
vipWrap.style.position = 'relative';
const vipCtx = document.createElement('canvas');
vipCtx.id = 'vip30Chart';
vipCtx.style.height = '360px';
vipCtx.style.width = '100%';
vipWrap.appendChild(vipCtx);
document.querySelectorAll('.panel')[2].querySelector('.panel-body').appendChild(vipWrap);
new Chart(vipCtx.getContext('2d'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($vip30_labels, JSON_UNESCAPED_UNICODE); ?>,
    datasets: [{
      label: 'Tổng chi (đ)',
      data: <?php echo json_encode($vip30_data, JSON_UNESCAPED_UNICODE); ?>,
      backgroundColor: '#1cc88a',
      borderRadius: 6,
      barPercentage: 0.6,
      categoryPercentage: 0.6,
      maxBarThickness: 36
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins:{ legend:{ display:true } },
    scales:{
      x:{ ticks:{ autoSkip: false, maxRotation: 0, minRotation: 0 } },
      y:{ beginAtZero:true }
    }
  }
});
</script>
