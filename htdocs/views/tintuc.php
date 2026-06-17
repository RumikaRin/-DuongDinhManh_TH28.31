<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header("Location: ../index.php?go=" . basename(__FILE__, ".php"));
    exit();
}
// Đảm bảo dbconnect.php đã được include và $ketnoi đã có sẵn
if (!isset($ketnoi)) {
    require_once __DIR__ . '/../dbconnect.php';
}

// Xử lý tìm kiếm và phân trang
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6; // Số tin tức mỗi trang
$offset = ($page - 1) * $limit;

// Xây dựng query với tìm kiếm
$where_clause = '';
$params = [];
if (!empty($search)) {
    $where_clause = "WHERE title_tintuc LIKE ? OR noidung_tintuc LIKE ?";
    $search_term = "%$search%";
    $params = [$search_term, $search_term];
}

// Đếm tổng số tin tức
$count_query = "SELECT COUNT(*) as total FROM tintuc $where_clause";
$count_stmt = $ketnoi->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param("ss", ...$params);
}
$count_stmt->execute();
$total_news = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_news / $limit);

// Lấy tin tức cho trang hiện tại
$query = "SELECT * FROM tintuc $where_clause ORDER BY date_tintuc DESC LIMIT ? OFFSET ?";
$stmt = $ketnoi->prepare($query);
if (!empty($params)) {
    // Thêm $limit và $offset vào cuối mảng params
    $all_params = array_merge($params, [$limit, $offset]);
    $stmt->bind_param("ssii", ...$all_params);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="news-section">
    <!-- Header Section -->
    <div class="section-header section-header-split">
        <div>
            <span class="section-kicker">Tin sách</span>
            <h1 class="section-title">Tin tức & sự kiện</h1>
            <p class="section-subtitle">Cập nhật phát hành mới, hoạt động đọc sách và những câu chuyện quanh tủ sách Kim Đồng.</p>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="news-controls">

        <div class="news-stats">
        </div>
    </div>
    
    <!-- Separator line -->
    <div class="news-separator"></div>

    <!-- News Grid -->
    <div class="news-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php $isFirstNews = true; ?>
            <?php while ($news = $result->fetch_assoc()): ?>
                <article class="news-card <?php echo $isFirstNews ? 'news-card-featured' : ''; ?>" data-reveal>
                    <div class="news-image">
                        <img src="<?php echo htmlspecialchars($news['anh_tintuc']); ?>" 
                             alt="<?php echo htmlspecialchars($news['title_tintuc']); ?>"
                             loading="lazy">
                        <div class="news-date-badge">
                            <span class="day"><?php echo date('d', strtotime($news['date_tintuc'])); ?></span>
                            <span class="month"><?php echo date('M', strtotime($news['date_tintuc'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="news-content">
                        <div class="news-meta">
                            <span class="news-date">
                                <i data-lucide="calendar"></i>
                                <?php echo date('d/m/Y', strtotime($news['date_tintuc'])); ?>
                            </span>
                            <span class="news-category">
                                <i data-lucide="tag"></i>
                                Tin tức
                            </span>
                        </div>
                        
                        <h3 class="news-title">
                            <a href="#" onclick="showNewsDetail(<?php echo $news['id_sp']; ?>)">
                                <?php echo htmlspecialchars($news['title_tintuc']); ?>
                            </a>
                        </h3>
                        
                        <p class="news-excerpt">
                            <?php 
                            $excerpt = strip_tags($news['noidung_tintuc']);
                            echo htmlspecialchars(mb_substr($excerpt, 0, 150)) . (mb_strlen($excerpt) > 150 ? '...' : '');
                            ?>
                        </p>
                        
                        <div class="news-actions">
                            <button class="btn-read-more" onclick="showNewsDetail(<?php echo $news['id_sp']; ?>)">
                                <i data-lucide="arrow-right"></i>
                                Đọc thêm
                            </button>
                        </div>
                    </div>
                </article>
                <?php $isFirstNews = false; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-news">
                <div class="no-news-icon">
                    <i data-lucide="newspaper"></i>
                </div>
                <h3>Không tìm thấy tin tức</h3>
                <p><?php echo !empty($search) ? 'Không có tin tức nào phù hợp với từ khóa "' . htmlspecialchars($search) . '"' : 'Chưa có tin tức nào được đăng.'; ?></p>
                <?php if (!empty($search)): ?>
                    <a href="?go=tintuc" class="btn-clear-search">
                        <i data-lucide="x"></i>
                        Xóa bộ lọc
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?go=tintuc&page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                   class="pagination-btn">
                    <i data-lucide="chevron-left"></i>
                    Trước
                </a>
            <?php endif; ?>
            
            <div class="pagination-numbers">
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <a href="?go=tintuc&page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="pagination-number <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            
            <?php if ($page < $total_pages): ?>
                <a href="?go=tintuc&page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                   class="pagination-btn">
                    Sau
                    <i data-lucide="chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- News Detail Modal -->
<div id="newsModal" class="news-modal">
    <div class="news-modal-content">
        <button class="news-modal-close" onclick="closeNewsModal()">
            <i data-lucide="x"></i>
        </button>
        <div id="newsModalBody">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<script>
// News detail functionality
function showNewsDetail(newsId) {
    const modal = document.getElementById('newsModal');
    const modalBody = document.getElementById('newsModalBody');
    
    modalBody.innerHTML = `
        <div class="news-detail-loading">
            <i data-lucide="loader-2"></i>
            <p>Đang tải tin tức...</p>
        </div>
    `;
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Re-initialize icons
    lucide.createIcons();
    
    // Load news detail via AJAX
    fetch(`news_detail.php?id=${newsId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                modalBody.innerHTML = `
                    <div class="news-detail-error">
                        <i data-lucide="alert-circle"></i>
                        <h3>Lỗi tải tin tức</h3>
                        <p>${data.error}</p>
                        <button onclick="closeNewsModal()" class="btn-close-error">
                            <i data-lucide="x"></i>
                            Đóng
                        </button>
                    </div>
                `;
            } else {
                modalBody.innerHTML = `
                    <div class="news-detail">
                        <div class="news-detail-header">
                            <div class="news-detail-meta">
                                <span class="news-detail-date">
                                    <i data-lucide="calendar"></i>
                                    ${data.date_full}
                                </span>
                                <span class="news-detail-author">
                                    <i data-lucide="user"></i>
                                    ${data.author}
                                </span>
                            </div>
                        </div>
                        
                        <div class="news-detail-image">
                            <img src="${data.image}" alt="${data.title}" loading="lazy">
                        </div>
                        
                        <h1 class="news-detail-title">${data.title}</h1>
                        
                        <div class="news-detail-content">
                            ${data.content}
                        </div>
                        
                        <div class="news-detail-footer">
                            <button onclick="closeNewsModal()" class="btn-close-detail">
                                <i data-lucide="x"></i>
                                Đóng
                            </button>
                        </div>
                    </div>
                `;
            }
            lucide.createIcons();
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = `
                <div class="news-detail-error">
                    <i data-lucide="wifi-off"></i>
                    <h3>Lỗi kết nối</h3>
                    <p>Không thể tải tin tức. Vui lòng kiểm tra kết nối mạng và thử lại.</p>
                    <button onclick="closeNewsModal()" class="btn-close-error">
                        <i data-lucide="x"></i>
                        Đóng
                    </button>
        </div>
            `;
            lucide.createIcons();
        });
}

function closeNewsModal() {
    const modal = document.getElementById('newsModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.getElementById('newsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeNewsModal();
    }
});

// Auto-submit search form on input
const newsSearchBox = document.querySelector('.search-box input');
if (newsSearchBox) {
    newsSearchBox.addEventListener('input', function(e) {
        if (e.target.value.length > 2 || e.target.value.length === 0) {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                e.target.form.submit();
            }, 500);
        }
    });
}
</script>
