<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php?go=" . basename(__FILE__, ".php"));
    exit();
}
require_once __DIR__ . "/../dbconnect.php";

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: index.php?go=xemgiohang");
    exit();
}

// Lấy thông tin người dùng đã đăng nhập
$user_info = [
    'hoten' => '',
    'sdt' => '',
    'diachi' => '',
    'email' => ''
];

if (isset($_SESSION['id_tv'])) {
    $user_id = $_SESSION['id_tv'];
    $sql = "SELECT ten_tv as hoten, sdt_tv as sdt, email_tv as email, diachi_tv as diachi FROM users WHERE id_tv = ?";
    $stmt = $ketnoi->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user_info = [
            'hoten' => $row['hoten'] ?? '',
            'sdt' => $row['sdt'] ?? '',
            'diachi' => $row['diachi'] ?? '',
            'email' => $row['email'] ?? ''
        ];
    }
    $stmt->close();
    
}

// Tính tổng tiền
$tongtien = 0;
$items = [];

foreach ($_SESSION['cart'] as $key => $item) {
    $id = intval($item['id']);
    $loai = $item['loai'];
    $soluong = $item['soluong'];

    if ($loai === "sanpham") {
        $sql = "SELECT ten_sp AS ten, gia_sp AS gia, anh_sp AS anh FROM sanpham WHERE id_sp = ?";
    } else {
        $sql = "SELECT ten_tt AS ten, saugiamgia_tt AS gia, anh_tt AS anh FROM sales WHERE id_tt = ?";
    }

    $stmt = $ketnoi->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $thanhtien = $row['gia'] * $soluong;
        $tongtien += $thanhtien;

        // fallback ảnh
        $imgPath = htmlspecialchars($row['anh']);
        if (empty($imgPath) || !file_exists($imgPath)) {
            $imgPath = "default.png";
        }

        $items[] = [
            "ten" => $row['ten'],
            "gia" => $row['gia'],
            "anh" => $imgPath,
            "soluong" => $soluong,
            "thanhtien" => $thanhtien
        ];
    }
    $stmt->close();
}
?>
<link rel="stylesheet" href="css/checkout.css?v=<?php echo filemtime(__DIR__ . '/../css/checkout.css'); ?>">

<div class="flof-container fade-in-up">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
        <a href="index.php?go=xemgiohang" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.875rem; font-weight: bold; color: var(--warm-500); text-decoration: none;">
            <i data-lucide="chevron-left" style="width: 16px; height: 16px;"></i> Quay lại giỏ hàng
        </a>
        <span style="font-size: 0.75rem; color: var(--warm-500); font-weight: bold;"><?php echo count($items); ?> sản phẩm</span>
    </div>

    <form method="POST" action="xuly_thanhtoan.php" id="checkoutForm">
        <?php echo app_csrf_field(); ?>
        
        <div class="flof-grid-12">
            <!-- ── LEFT: Checkout Form ── -->
            <div class="flof-col-8">
                <div class="flof-card">
                    <h3 class="flof-section-title">Thông tin giao hàng</h3>

                    <div class="flof-grid-12" style="gap: 16px;">
                        <div class="flof-col-6 flof-form-group" style="margin-bottom: 0;">
                            <label class="flof-label">Họ và tên</label>
                            <input type="text" name="hoten" value="<?= htmlspecialchars($user_info['hoten']) ?>" class="flof-input" required>
                        </div>
                        <div class="flof-col-6 flof-form-group" style="margin-bottom: 0;">
                            <label class="flof-label">Số điện thoại</label>
                            <input type="tel" name="sdt" value="<?= htmlspecialchars($user_info['sdt']) ?>" class="flof-input" required pattern="[0-9]{9,11}" title="Số điện thoại phải là 9-11 chữ số">
                        </div>
                    </div>

                    <div class="flof-form-group" style="margin-top: 16px;">
                        <label class="flof-label">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user_info['email']) ?>" class="flof-input" required>
                    </div>

                    <div class="flof-form-group">
                        <label class="flof-label">Địa chỉ nhận hàng</label>
                        <textarea name="diachi" class="flof-input" required rows="3"><?= htmlspecialchars($user_info['diachi']) ?></textarea>
                    </div>

                    <div class="flof-form-group">
                        <label class="flof-label">Ghi chú (Tùy chọn)</label>
                        <textarea name="ghichu" class="flof-input" placeholder="Nhập ghi chú cho đơn hàng..." rows="2"></textarea>
                    </div>

                    <h3 class="flof-section-title" style="margin-top: 32px;">Phương thức thanh toán</h3>
                    
                    <div class="flof-form-group" style="display: flex; flex-direction: column; gap: 12px;">
                        <label style="display: flex; align-items: center; gap: 12px; padding: 16px; border: 1px solid var(--warm-200); border-radius: var(--radius-xl); cursor: pointer;" class="flof-card-hover">
                            <input type="radio" name="payment_method" value="cod" checked style="accent-color: #88734C; width: 18px; height: 18px;">
                            <span style="font-weight: bold; color: var(--warm-900);">Thanh toán khi nhận hàng (COD)</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 12px; padding: 16px; border: 1px solid var(--warm-200); border-radius: var(--radius-xl); cursor: pointer;" class="flof-card-hover">
                            <input type="radio" name="payment_method" value="vnpay" style="accent-color: #88734C; width: 18px; height: 18px;">
                            <span style="font-weight: bold; color: var(--warm-900);">Thanh toán VNPAY (ATM/QR Code)</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 12px; padding: 16px; border: 1px solid var(--warm-200); border-radius: var(--radius-xl); cursor: pointer;" class="flof-card-hover">
                            <input type="radio" name="payment_method" value="bank" style="accent-color: #88734C; width: 18px; height: 18px;">
                            <span style="font-weight: bold; color: var(--warm-900);">Chuyển khoản ngân hàng</span>
                        </label>
                        <div id="bankInfo" style="display:none; padding: 12px; background-color: var(--warm-50); border-radius: var(--radius-xl); font-size: 0.875rem; color: var(--warm-700);">
                            Vui lòng chuyển khoản theo hướng dẫn hiển thị sau khi đặt hàng.
                        </div>
                    </div>

                    <div class="flof-form-group" style="margin-top: 24px;">
                        <label class="flof-label">Tùy chọn giao hàng</label>
                        <select id="shippingSelect" name="shipping_fee" class="flof-input" style="appearance: auto;">
                            <option value="30000">Giao tiêu chuẩn (30.000đ)</option>
                            <option value="50000">Giao nhanh (50.000đ)</option>
                            <option value="0">Miễn phí (áp dụng theo chương trình)</option>
                        </select>
                    </div>
                    
                    <?php if (isset($_SESSION['id_tv'])): ?>
                    <input type="hidden" name="update_user_info" value="1">
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── RIGHT: Order Summary ── -->
            <div class="flof-col-4">
                <div style="position: sticky; top: 100px; display: flex; flex-direction: column; gap: 24px;">
                    <!-- Items Summary -->
                    <div class="flof-card">
                        <h3 class="flof-section-title" style="margin-bottom: 16px;">Sản phẩm trong đơn</h3>
                        <div style="display: flex; flex-direction: column; gap: 16px; max-height: 300px; overflow-y: auto; padding-right: 8px;">
                            <?php foreach ($items as $item): ?>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <div style="position: relative;">
                                    <img src="<?= $item['anh'] ?>" alt="<?= htmlspecialchars($item['ten']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--warm-100);">
                                    <span style="position: absolute; top: -6px; right: -6px; background: var(--warm-500); color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 0.625rem; font-weight: bold;">
                                        <?= $item['soluong'] ?>
                                    </span>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 0.875rem; font-weight: bold; color: var(--warm-900); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($item['ten']) ?>
                                    </div>
                                </div>
                                <div style="font-family: monospace; font-weight: bold; color: var(--warm-900); font-size: 0.875rem;">
                                    <?= number_format($item['thanhtien'], 0, ',', '.') ?>đ
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Total Summary -->
                    <div class="flof-card">
                        <h3 class="flof-section-title" style="margin-bottom: 16px; border-bottom: none; padding-bottom: 0;">Tổng đơn hàng</h3>
                        
                        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.875rem; color: var(--warm-700); margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span>Tạm tính</span>
                                <span style="font-family: monospace; font-weight: bold; color: var(--warm-900);">
                                    <?= number_format($tongtien, 0, ',', '.') ?>đ
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Phí vận chuyển</span>
                                <span style="font-family: monospace; font-weight: bold; color: var(--warm-900);" id="shippingText">
                                    30.000đ
                                </span>
                            </div>
                        </div>
                        
                        <div style="border-top: 1px solid var(--warm-100); padding-top: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end;">
                            <span style="font-family: var(--font-display); font-weight: bold; font-size: 1rem; color: var(--warm-900);">Tổng cộng</span>
                            <div style="text-align: right;">
                                <span style="font-family: monospace; font-size: 1.5rem; font-weight: bold; color: var(--warm-900); display: block; line-height: 1;" id="totalText">
                                    <?= number_format($tongtien + 30000, 0, ',', '.') ?>đ
                                </span>
                            </div>
                        </div>

                        <input type="hidden" name="tongtien" id="subtotalInput" value="<?= $tongtien ?>">
                        <input type="hidden" name="tongtien_final" id="totalInput" value="<?= $tongtien + 30000 ?>">

                        <button type="submit" class="flof-btn" style="background-color: #88734C; color: white; width: 100%; justify-content: center; padding: 16px; font-size: 1rem;">
                            Xác nhận đặt hàng
                        </button>
                        
                        <div style="margin-top: 16px; background-color: var(--warm-50); padding: 12px; border-radius: var(--radius-xl); border: 1px solid var(--warm-100); font-size: 0.625rem; color: var(--warm-500); line-height: 1.5; text-align: center;">
                            Bằng việc đặt hàng, bạn đồng ý với Điều khoản sử dụng và Chính sách bảo mật của chúng tôi.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
  (function(){
    const shippingSelect = document.getElementById('shippingSelect');
    const subtotal = parseInt(document.getElementById('subtotalInput').value || '0', 10);
    const shippingText = document.getElementById('shippingText');
    const totalText = document.getElementById('totalText');
    const totalInput = document.getElementById('totalInput');

    function formatVND(n){
      return (n||0).toLocaleString('vi-VN') + 'đ';
    }

    function updateTotal(){
      const fee = parseInt(shippingSelect.value || '0', 10);
      shippingText.textContent = formatVND(fee);
      const total = subtotal + fee;
      totalText.textContent = formatVND(total);
      totalInput.value = total;
    }

    if (shippingSelect){
      shippingSelect.addEventListener('change', updateTotal);
      updateTotal();
    }

    const bankInfo = document.getElementById('bankInfo');
    document.querySelectorAll('input[name="payment_method"]').forEach(r => {
      r.addEventListener('change', () => {
        bankInfo.style.display = r.value === 'bank' && r.checked ? 'block' : 'none';
      });
    });
  })();
</script>
