<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php?go=" . basename(__FILE__, ".php") . (isset($_GET['id']) ? "&id=" . urlencode($_GET['id']) : ""));
    exit();
}
// Lấy ID tin tức / sản phẩm sale từ URL
$id_tt = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_tt <= 0) {
    die("ID tin tức không hợp lệ.");
}

// Truy vấn chi tiết sale
$sql = "SELECT id_tt, anh_tt, ten_tt, giasp_tt, giamgia_tt, saugiamgia_tt, tacgia_tt, chitietsp_tt 
        FROM sales WHERE id_tt = ?";
$stmt = $ketnoi->prepare($sql);

if ($stmt === false) {
    error_log("Lỗi chuẩn bị truy vấn trong chitietsales.php: " . $ketnoi->error);
    die("Lỗi hệ thống khi tải chi tiết sản phẩm. Vui lòng thử lại sau.");
}

$stmt->bind_param("i", $id_tt);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Lỗi khi truy vấn sản phẩm: " . $ketnoi->error);
}
?>

<?php
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $imgPath = htmlspecialchars($row["anh_tt"]);
    if (!file_exists($imgPath) || empty($imgPath)) {
        $imgPath = "default.png";
    }
?>
<link rel="stylesheet" href="css/product-detail.css?v=<?php echo filemtime(__DIR__ . '/../css/product-detail.css'); ?>">
<link rel="stylesheet" href="css/comments.css?v=<?php echo filemtime(__DIR__ . '/../css/comments.css'); ?>">
<?php
    $discountPercent = 0;
    if ($row["giasp_tt"] > 0) {
        $discountPercent = round((($row["giasp_tt"] - $row["saugiamgia_tt"]) / $row["giasp_tt"]) * 100);
    }
?>
<div class="flof-container fade-in-up">
    <div style="margin-bottom: 24px;">
        <a href="index.php?go=sales" class="flof-btn" style="background: white; border: 1px solid var(--warm-200); padding: 8px 16px; text-decoration: none; color: var(--warm-700);">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg> Quay lại danh sách Khuyến mãi
        </a>
    </div>

    <div class="flof-grid-12" style="margin-bottom: 40px;">
        <!-- Cột Ảnh Sản Phẩm -->
        <div class="flof-col-5">
            <div class="flof-card" style="padding: 0; overflow: hidden; position: sticky; top: 100px; background: white; text-align: center; border-radius: var(--radius-xl);">
                <?php if ($discountPercent > 0): ?>
                <div style="position: absolute; top: 16px; right: 16px; background: #e74c3c; color: white; padding: 6px 12px; border-radius: var(--radius-md); font-weight: 800; font-size: 1rem; z-index: 2; box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);">
                    -<?php echo $discountPercent; ?>%
                </div>
                <?php endif; ?>
                <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($row["ten_tt"]); ?>" style="width: 100%; max-height: 600px; object-fit: contain; display: block; margin: 0 auto;">
            </div>
        </div>

        <!-- Cột Thông Tin Sản Phẩm -->
        <div class="flof-col-7">
            <div class="flof-card">
                <h1 style="font-size: 2rem; color: var(--warm-900); margin-bottom: 16px; line-height: 1.3; font-weight: 700;">
                    <?php echo htmlspecialchars($row["ten_tt"]); ?>
                </h1>

                <div style="display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; color: var(--warm-600); font-size: 0.95rem;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <strong>Tác giả:</strong> <?php echo htmlspecialchars($row["tacgia_tt"]); ?>
                    </div>
                    <?php
                    $cat_query = "SELECT d.ten_dm FROM danhmuc d 
                                   INNER JOIN sales_danhmuc sd ON d.id_dm = sd.id_dm 
                                   WHERE sd.id_tt = " . intval($row['id_tt']);
                    $cat_result = $ketnoi->query($cat_query);
                    if ($cat_result && $cat_result->num_rows > 0) {
                        $categories = [];
                        while ($cat_row = $cat_result->fetch_assoc()) {
                            $categories[] = $cat_row['ten_dm'];
                        }
                        echo '<div style="display: flex; align-items: center; gap: 6px;">';
                        echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>';
                        echo '<strong>Danh mục:</strong> ' . implode(', ', $categories);
                        echo '</div>';
                    }
                    
                    // Rating
                    $avg = 0; $cnt = 0;
                    $rs = $ketnoi->query("SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM comments WHERE product_type='sale' AND product_id=".(int)$row['id_tt']." AND status='approved'");
                    if ($rs && $rs->num_rows) { 
                        $d = $rs->fetch_assoc(); 
                        $avg = (float)$d['avg_rating']; 
                        $cnt = (int)$d['cnt']; 
                    }
                    $starsHtml = '';
                    for ($i=1; $i<=5; $i++) { 
                        $starsHtml .= ($i <= round($avg)) ? "★" : "☆"; 
                    }
                    echo '<div style="display: flex; align-items: center; gap: 6px;">';
                    echo '<strong>Đánh giá:</strong> <span style="color:#f39c12; letter-spacing: 2px;">'.$starsHtml.'</span> <span style="color:var(--warm-500);">('.number_format($avg,1).'/5 từ '.$cnt.' đánh giá)</span>';
                    echo '</div>';
                    ?>
                </div>

                <div style="background: var(--warm-50); border-radius: var(--radius-xl); padding: 24px; margin-bottom: 32px; border-left: 4px solid #e74c3c; display: flex; flex-direction: column; gap: 8px;">
                    <?php if ($row["giasp_tt"] > 0): ?>
                    <div style="color: var(--warm-500); font-size: 1.1rem; text-decoration: line-through; display: flex; align-items: center; gap: 8px;">
                        Giá gốc: <?php echo number_format($row["giasp_tt"], 0, ',', '.'); ?> đ
                    </div>
                    <?php endif; ?>
                    
                    <div style="display: flex; align-items: baseline; gap: 16px;">
                        <span style="font-size: 2.5rem; font-weight: 800; color: #e74c3c; line-height: 1;">
                            <?php echo number_format($row["saugiamgia_tt"], 0, ',', '.'); ?> đ
                        </span>
                        <?php if ($discountPercent > 0): ?>
                        <span style="background: rgba(231, 76, 60, 0.1); color: #e74c3c; padding: 4px 12px; border-radius: var(--radius-full); font-weight: 700; font-size: 0.9rem;">
                            Tiết kiệm <?php echo number_format($row["giasp_tt"] - $row["saugiamgia_tt"], 0, ',', '.'); ?> đ
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($_SESSION['ten_tv'])): ?>
                    <form method="POST" action="giohang.php">
                        <?php echo app_csrf_field(); ?>
                        <input type="hidden" name="id_sp" value="<?php echo $row['id_tt']; ?>">
                        <input type="hidden" name="loai" value="sale">
                        
                        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 32px;">
                            <span style="font-weight: 600; color: var(--warm-900);">Số lượng:</span>
                            <div style="display: flex; align-items: center; border: 1px solid var(--warm-200); border-radius: var(--radius-md); overflow: hidden;">
                                <button type="button" onclick="decreaseQty()" style="width: 40px; height: 40px; background: white; border: none; cursor: pointer; font-size: 1.2rem; color: var(--warm-600); hover: background-color: var(--warm-50);">−</button>
                                <input type="number" name="soluong" id="qty-input" value="1" min="1" max="99" readonly style="width: 50px; height: 40px; text-align: center; border: none; border-left: 1px solid var(--warm-200); border-right: 1px solid var(--warm-200); font-weight: 600; color: var(--warm-900);">
                                <button type="button" onclick="increaseQty()" style="width: 40px; height: 40px; background: white; border: none; cursor: pointer; font-size: 1.2rem; color: var(--warm-600); hover: background-color: var(--warm-50);">+</button>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <button type="button" name="action" value="add" class="flof-btn" style="background: white; border: 2px solid var(--primary); color: var(--primary); justify-content: center; font-size: 1.1rem; height: 54px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg> Thêm vào giỏ
                            </button>
                            <button type="submit" name="action" value="buy" class="flof-btn flof-btn-primary" style="justify-content: center; font-size: 1.1rem; height: 54px; box-shadow: 0 4px 12px rgba(136, 115, 76, 0.3);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg> Mua ngay
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div style="padding: 20px; background: var(--warm-50); border: 1px dashed var(--warm-300); border-radius: var(--radius-xl); text-align: center; color: var(--warm-700);">
                        Bạn cần <a href="index.php?go=dangnhap" style="color: var(--primary); font-weight: 600; text-decoration: underline;">đăng nhập</a> để mua hoặc thêm sản phẩm vào giỏ hàng.
                    </div>
                <?php endif; ?>

                <div style="margin-top: 40px;">
                    <h3 class="flof-section-title">Mô tả chi tiết</h3>
                    <div style="color: var(--warm-700); line-height: 1.8; font-size: 1rem;">
                        <?php echo nl2br(htmlspecialchars($row["chitietsp_tt"])); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Comments section
    echo "<div class='flof-comments-section' style='margin-bottom: 40px;'>";
    echo "<h3 class='flof-section-title'>Đánh giá & Bình luận</h3>";
    
    // Star counts for filter
    $cnts = [1=>0,2=>0,3=>0,4=>0,5=>0];
    $qc = $ketnoi->query("SELECT rating, COUNT(*) AS c FROM comments WHERE product_type='sale' AND product_id=".(int)$row['id_tt']." AND status='approved' GROUP BY rating");
    if ($qc) { 
        while($rr=$qc->fetch_assoc()){ 
            $r=(int)$rr['rating']; 
            if($r>=1&&$r<=5){ 
                $cnts[$r]=(int)$rr['c']; 
            } 
        } 
    }
    $totalCnt = array_sum($cnts);
    
    echo "<div id='cmt-filter'>";
    echo "<button class='cmt-chip active' data-star='all'>Tất cả (".$totalCnt.")</button>";
    for($s=5;$s>=1;$s--){
        echo "<button class='cmt-chip' data-star='".$s."'>".$s." Sao (".$cnts[$s].")</button>";
    }
    echo "</div>";
    
    // List comments
    $cmt = $ketnoi->query("SELECT username, rating, content, created_at, reply FROM comments WHERE product_type='sale' AND product_id=".(int)$row['id_tt']." AND status='approved' ORDER BY id DESC LIMIT 50");
    if ($cmt && $cmt->num_rows>0) {
        echo "<div style='display:flex;flex-direction:column;gap:12px'>";
        while ($c = $cmt->fetch_assoc()) {
            $sh = '';
            for ($i=1;$i<=5;$i++){ 
                $sh .= ($i <= (int)$c['rating']) ? '★' : '☆'; 
            }
            echo "<div class='cmt-item' data-rating='".(int)$c['rating']."'>";
            echo "<div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:6px'>";
            echo "<strong style='color:var(--text-primary)'>".htmlspecialchars($c['username'])."</strong>";
            echo "<span style='color:#f39c12;font-size:16px'>".$sh."</span>";
            echo "</div>";
            echo "<div style='color:var(--text-secondary);font-size:14px'>".nl2br(htmlspecialchars($c['content']))."</div>";
            echo "<div style='color:var(--text-light);font-size:12px;margin-top:6px'>".htmlspecialchars($c['created_at'])."</div>";
            if (!empty($c['reply'])) {
                echo "<div style='margin-top:10px;padding:12px;background:var(--bg-secondary);border-left:3px solid var(--primary);border-radius:6px'>";
                echo "<div style='font-weight:600;color:var(--text-primary);margin-bottom:4px;font-size:13px'>Phản hồi của quản trị</div>";
                echo "<div style='color:var(--text-secondary);font-size:14px'>".nl2br(htmlspecialchars($c['reply']))."</div>";
                echo "</div>";
            }
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<p style='color:var(--text-secondary);font-size:14px'>Chưa có bình luận nào.</p>";
    }
    
    // Form comment
    if (isset($_SESSION['ten_tv'])) {
        echo "<form id='comment-form' style='margin-top:20px'>";
        echo app_csrf_field();
        echo "<input type='hidden' name='product_type' value='sale'>";
        echo "<input type='hidden' name='product_id' value='".(int)$row['id_tt']."'>";
        echo "<div style='display:flex;gap:10px;align-items:center;margin-bottom:12px'>";
        echo "<label style='color:var(--text-primary);font-weight:600;font-size:14px'>Đánh giá:</label>";
        echo "<div id='star-input' data-value='5' style='color:#f39c12;font-size:22px;cursor:pointer'>★★★★★</div>";
        echo "<input type='hidden' name='rating' id='rating' value='5'>";
        echo "</div>";
        echo "<textarea name='content' rows='4' placeholder='Chia sẻ cảm nhận của bạn...' style='width:100%;padding:12px;border:1px solid var(--border);border-radius:8px;background:var(--bg-card);color:var(--text-primary);font-family:inherit;font-size:14px' required></textarea>";
        echo "<button type='submit' style='margin-top:12px;padding:10px 18px;background:var(--primary);color:var(--text-white);border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:14px'>Gửi bình luận</button>";
        echo "<div id='cmt-msg' style='margin-top:10px;font-size:14px'></div>";
        echo "</form>";
    } else {
        echo "<div class='login-alert' style='margin-top:16px'>Bạn cần <a href='index.php?go=dangnhap'>đăng nhập</a> để bình luận.</div>";
    }
    echo "</div>"; // end comments section

    // Related products
    echo "<div class='flof-related-section'>";
    echo "<h3 class='flof-section-title' style='margin-bottom: 24px;'>Sản phẩm tương tự</h3>";
    
    // Lấy tất cả sản phẩm và trộn lẫn
    $randomSeed = time() + rand(1, 1000);
    $allProducts = array();
    
    // Lấy sản phẩm sale (không trùng với sản phẩm hiện tại)
    $rel1 = $ketnoi->query("SELECT id_tt as id_sp, ten_tt as ten_sp, anh_tt as anh_sp, saugiamgia_tt as gia_sp, giasp_tt, giamgia_tt, 'sale' as type FROM sales WHERE id_tt<>".(int)$row['id_tt']." ORDER BY RAND($randomSeed) LIMIT 6");
    if ($rel1) {
        while ($r1 = $rel1->fetch_assoc()) {
            $allProducts[] = $r1;
        }
    }
    
    // Lấy sản phẩm thường
    $rel2 = $ketnoi->query("SELECT id_sp, ten_sp, anh_sp, gia_sp, 'normal' as type FROM sanpham ORDER BY RAND($randomSeed) LIMIT 6");
    if ($rel2) {
        while ($r2 = $rel2->fetch_assoc()) {
            $allProducts[] = $r2;
        }
    }
    
    // Trộn lẫn mảng
    shuffle($allProducts);
    
    echo "<div class='premium-grid' style='margin-top: 24px; width: 100%; grid-template-columns: repeat(3, 1fr);'>";
    $displayCount = 0;
    foreach ($allProducts as $product) {
        if ($displayCount >= 3) break;
        
        $card = [
            'id' => $product['id_sp'],
            'type' => $product['type'],
            'title' => $product['ten_sp'],
            'image' => $product['anh_sp'],
            'price' => $product['gia_sp'],
            'original_price' => isset($product['giasp_tt']) ? $product['giasp_tt'] : 0,
            'discount' => isset($product['giamgia_tt']) ? $product['giamgia_tt'] : 0,
            'category' => '', // Mặc định rỗng ở trang chi tiết
            'author' => ''
        ];
        
        include __DIR__ . '/partials/product_card.php';
        
        $displayCount++;
    }
    echo "</div>"; // end premium-grid
    echo "</div>"; // end flof-related-section

    echo "</div>"; // end product-container
} else {
    echo "<p>Không tìm thấy sản phẩm này.</p>";
}

$stmt->close();

?>

<script>
function increaseQty() {
    const input = document.getElementById('qty-input');
    input.value = Math.min(99, parseInt(input.value) + 1);
}

function decreaseQty() {
    const input = document.getElementById('qty-input');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

// AJAX add-to-cart: stay on page and show toast
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector("form[action='giohang.php']");
    if (!form) return;

    const addBtn = form.querySelector("button[name='action'][value='add']");
    if (!addBtn) return;

    function updateCartBadge(count){
        const badge = document.getElementById('cart-badge');
        if (badge) { 
            badge.textContent = count; 
            badge.style.display = count>0 ? 'inline-flex' : 'none'; 
        }
    }

    addBtn.addEventListener('click', function (e) {
        e.preventDefault();
        const fd = new FormData(form);
        fd.set('action','add');

        fetch('giohang.php', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            if (res && res.ok) {
                updateCartBadge(res.cart_count);
                const t = document.createElement('div');
                t.textContent = 'Đã thêm vào giỏ hàng!';
                t.style.cssText = 'position:fixed;top:20px;right:20px;background:#28a745;color:#fff;padding:12px 18px;border-radius:8px;box-shadow:0 6px 16px rgba(0,0,0,.15);z-index:9999;font-weight:600;font-size:14px';
                document.body.appendChild(t);
                setTimeout(()=>t.remove(), 1800);
            } else {
                alert('Không thể thêm vào giỏ. Vui lòng thử lại.');
            }
        })
        .catch(()=> alert('Lỗi mạng. Vui lòng thử lại.'));
    });

    // Xử lý nút "Mua ngay"
    const buyBtn = form.querySelector("button[name='action'][value='buy']");
    if (buyBtn) {
        buyBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const fd = new FormData(form);
            fd.set('action','buy');

            fetch('giohang.php', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(res => {
                if (res && res.ok) {
                    window.location.href = res.redirect || 'index.php?go=thanhtoan';
                } else {
                    alert('Lỗi khi thêm sản phẩm vào giỏ hàng. Vui lòng thử lại.');
                }
            })
            .catch(() => {
                alert('Lỗi khi thêm sản phẩm vào giỏ hàng. Vui lòng thử lại.');
            });
        });
    }
});

// Star input and comment submit
document.addEventListener('DOMContentLoaded', function(){
    const star = document.getElementById('star-input');
    const ratingHidden = document.getElementById('rating');
    if (star && ratingHidden) {
        star.addEventListener('mousemove', function(e){
            const rect = star.getBoundingClientRect();
            const pct = Math.min(1, Math.max(0, (e.clientX - rect.left) / rect.width));
            const val = Math.max(1, Math.min(5, Math.ceil(pct * 5)));
            star.dataset.value = String(val);
            star.textContent = '★★★★★'.slice(0, val) + '☆☆☆☆☆'.slice(0, 5-val);
        });
        star.addEventListener('mouseleave', function(){
            const val = parseInt(ratingHidden.value||'5');
            star.textContent = '★★★★★'.slice(0, val) + '☆☆☆☆☆'.slice(0, 5-val);
        });
        star.addEventListener('click', function(){
            const current = parseInt(star.dataset.value||'5');
            ratingHidden.value = String(current);
        });
    }

    const cmtForm = document.getElementById('comment-form');
    if (cmtForm) {
        cmtForm.addEventListener('submit', async function(e){
            e.preventDefault();
            const msg = document.getElementById('cmt-msg');
            msg.textContent = 'Đang gửi...';
            msg.style.color = 'var(--text-secondary)';
            try {
                const fd = new FormData(cmtForm);
                const res = await fetch('comment_action.php', { 
                    method:'POST', 
                    body: fd, 
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.ok) {
                    msg.style.color = '#27ae60';
                    msg.textContent = 'Gửi thành công! Bình luận đang chờ duyệt.';
                    cmtForm.reset();
                    ratingHidden.value = '5';
                    if (star) { star.textContent = '★★★★★'; }
                } else {
                    msg.style.color = '#e74c3c';
                    msg.textContent = data.message || 'Gửi thất bại';
                }
            } catch(err){
                msg.style.color = '#e74c3c';
                msg.textContent = 'Lỗi mạng, vui lòng thử lại.';
            }
        });
    }
});

// Comment filter
document.addEventListener('DOMContentLoaded', function(){
    const filter = document.getElementById('cmt-filter');
    if (!filter) return;
    filter.addEventListener('click', function(e){
        const btn = e.target.closest('.cmt-chip');
        if (!btn) return;
        const star = btn.dataset.star;
        
        // Set active style
        [...filter.querySelectorAll('.cmt-chip')].forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        // Filter comments
        const items = document.querySelectorAll('.cmt-item');
        items.forEach(it => {
            const r = it.getAttribute('data-rating');
            it.style.display = (star === 'all' || r === star) ? '' : 'none';
        });
    });
});
</script>
