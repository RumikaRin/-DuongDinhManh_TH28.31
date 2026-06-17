  <?php
  // FILE: admin/template/comments.php
  if (!isset($conn)) { die('Lỗi: Không tìm thấy kết nối database.'); }

  $status = isset($_GET['status']) && in_array($_GET['status'], ['pending','approved','rejected'], true)
    ? $_GET['status'] : '';
  $q = isset($_GET['q']) ? trim($_GET['q']) : '';
  $where = 'WHERE 1=1';
  if ($status !== '') { $where .= " AND status='".$conn->real_escape_string($status)."'"; }
  if ($q !== '') {
    $safe = '%'.$conn->real_escape_string($q).'%';
    $where .= " AND (username LIKE '$safe' OR content LIKE '$safe')";
  }
  $sql = "SELECT * FROM comments $where ORDER BY id DESC LIMIT 200";
  $rows = $conn->query($sql);
  ?>
  <style>
  .modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
  }
  .modal-overlay.active {
    display: flex;
  }
  .modal-content {
    background: white;
    border-radius: 8px;
    max-width: 900px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    position: relative;
    display: flex;
    flex-direction: column;
  }
  .modal-header {
    padding: 20px 25px;
    border-bottom: 2px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    flex-shrink: 0;
  }
  .modal-title {
    font-size: 20px;
    color: #333;
    font-weight: 600;
    margin: 0;
  }
  .modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: 0.3s;
    line-height: 1;
  }
  .modal-close:hover {
    background: #f5f5f5;
    color: #333;
  }
  .modal-body {
    padding: 25px;
    overflow-y: auto;
    flex: 1;
  }
  .info-row {
    display: flex;
    margin-bottom: 20px;
    align-items: flex-start;
  }
  .info-row:last-child {
    margin-bottom: 0;
  }
  .info-label {
    min-width: 140px;
    font-weight: 500;
    color: #555;
    flex-shrink: 0;
    font-size: 15px;
    padding-top: 2px;
  }
  .info-label i {
    margin-right: 8px;
    width: 20px;
    text-align: center;
    color: #666;
  }
  .info-value {
    flex: 1;
    color: #333;
    font-size: 15px;
  }
  .content-box {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 6px;
    white-space: pre-wrap;
    word-wrap: break-word;
    line-height: 1.6;
    border: 1px solid #e8e8e8;
    color: #333;
  }
  .rating-stars {
    color: #ffa500;
    font-size: 18px;
  }
  .modal-footer {
    padding: 20px 25px;
    border-top: 2px solid #e0e0e0;
    background: white;
    flex-shrink: 0;
  }
  .form-group {
    margin-bottom: 15px;
  }
  .form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 500;
    color: #555;
    font-size: 15px;
  }
  .form-group label i {
    margin-right: 8px;
    color: #666;
  }
  .form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-family: inherit;
    font-size: 14px;
    transition: 0.3s;
    color: #000;
    background: white;
  }
  .form-control:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
  }
  .form-control::placeholder {
    color: #999;
  }
  .btn-group {
    display: flex;
    gap: 10px;
  }
  .btn-secondary {
    background: #6c757d;
    color: white;
  }
  .btn-secondary:hover {
    background: #5a6268;
  }
  .btn i {
    font-size: 13px;
  }
  </style>

  <div class="breadcrumb">
    <a href="index.php?route=dashboard">Home</a> / Bình luận
  </div>

  <div class="page-header">
    <h1 class="page-title">Quản lý bình luận</h1>
  </div>

  <div class="panel">
    <div class="panel-body">
      <form method="get" action="index.php" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <input type="hidden" name="route" value="comments" />
        <select name="status" class="form-control" style="width:auto;padding:8px;border:1px solid #ddd;border-radius:4px">
          <option value="">Tất cả trạng thái</option>
          <option value="pending" <?php echo $status==='pending'?'selected':''; ?>>Chờ duyệt</option>
          <option value="approved" <?php echo $status==='approved'?'selected':''; ?>>Đã duyệt</option>
          <option value="rejected" <?php echo $status==='rejected'?'selected':''; ?>>Từ chối</option>
        </select>
        <input type="text" name="q" placeholder="Tìm theo tên hoặc nội dung" value="<?php echo htmlspecialchars($q); ?>" style="padding:8px;width:280px;border:1px solid #ddd;border-radius:4px" />
        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Lọc</button>
      </form>
    </div>
  </div>

  <div class="panel">
    <div class="panel-header">
      <h3 class="panel-title">Danh sách bình luận (tối đa 200 gần nhất)</h3>
    </div>
    <div class="panel-body">
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th style="width:60px">ID</th>
              <th style="width:120px">Sản phẩm</th>
              <th style="width:150px">User</th>
              <th style="width:100px">Đánh giá</th>
              <th style="width:120px">Thời gian</th>
              <th style="width:100px;text-align:center">Phản hồi</th>
              <th style="width:100px;text-align:center">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($rows && $rows->num_rows>0): ?>
              <?php while ($r = $rows->fetch_assoc()): ?>
                <tr>
                  <td>#<?php echo (int)$r['id']; ?></td>
                  <td><?php echo htmlspecialchars($r['product_type']).' #'.(int)$r['product_id']; ?></td>
                  <td><?php echo htmlspecialchars($r['username']); ?></td>
                  <td>
                    <span style="color:#f39c12">
                      <?php $s=intval($r['rating']); echo str_repeat('★',$s).str_repeat('☆',5-$s); ?>
                    </span>
                  </td>
                  <td><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></td>
                  <td style="text-align:center">
                    <button class="btn btn-primary btn-sm" onclick="openCommentModal(
                      <?php echo (int)$r['id']; ?>, 
                      <?php echo htmlspecialchars(json_encode($r['username']), ENT_QUOTES); ?>, 
                      <?php echo htmlspecialchars(json_encode($r['content']), ENT_QUOTES); ?>, 
                      <?php echo htmlspecialchars(json_encode($r['reply'] ?? ''), ENT_QUOTES); ?>, 
                      <?php echo (int)$r['rating']; ?>,
                      <?php echo htmlspecialchars(json_encode($r['product_type'].' #'.$r['product_id']), ENT_QUOTES); ?>,
                      <?php echo htmlspecialchars(json_encode($r['created_at']), ENT_QUOTES); ?>
                    )">
                      <i class="fas fa-reply"></i> Phản hồi
                    </button>
                  </td>
                  <td style="text-align:center">
                    <a class="btn btn-danger btn-sm" href="javascript:void(0)" onclick="deleteComment(<?php echo (int)$r['id']; ?>)">
                      <i class="fas fa-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="7" style="text-align:center;padding:30px;color:#7f8c8d">
                <i class="fas fa-comments" style="font-size:48px;margin-bottom:10px;display:block;opacity:0.3"></i>
                Chưa có bình luận nào
              </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal-overlay" id="commentModal" onclick="closeCommentModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
      <div class="modal-header">
        <h3 class="modal-title">
          Chi tiết bình luận #<span id="modalCommentId"></span>
        </h3>
        <button class="modal-close" onclick="closeCommentModal()">&times;</button>
      </div>
      
      <div class="modal-body">
        <div class="info-row">
          <div class="info-label"><i class="fas fa-shopping-bag"></i> Sản phẩm:</div>
          <div class="info-value" id="modalProduct"></div>
        </div>
        
        <div class="info-row">
          <div class="info-label"><i class="fas fa-user"></i> Người dùng:</div>
          <div class="info-value" id="modalUsername"></div>
        </div>
        
        <div class="info-row">
          <div class="info-label"><i class="fas fa-star"></i> Đánh giá:</div>
          <div class="info-value rating-stars" id="modalRating"></div>
        </div>
        
        <div class="info-row">
          <div class="info-label"><i class="fas fa-clock"></i> Thời gian:</div>
          <div class="info-value" id="modalTime"></div>
        </div>
        
        <div class="info-row">
          <div class="info-label"><i class="fas fa-comment"></i> Nội dung:</div>
          <div class="info-value">
            <div class="content-box" id="modalContent"></div>
          </div>
        </div>
        
        <div class="info-row" style="display:none" id="currentReplyRow">
          <div class="info-label"><i class="fas fa-reply"></i> Phản hồi hiện tại:</div>
          <div class="info-value">
            <div class="content-box" style="background:#e8f4f8;border-color:#b3dae8" id="modalCurrentReply"></div>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <form method="post" action="comment_moderate.php">
          <?php echo app_csrf_field(); ?>
          <input type="hidden" name="action" value="reply" />
          <input type="hidden" name="id" id="modalFormId" />
          
          <div class="form-group">
            <label for="modalReplyInput">
              <i class="fas fa-pen"></i> Phản hồi của bạn:
            </label>
            <textarea name="reply" id="modalReplyInput" rows="5" class="form-control" placeholder="Nhập nội dung phản hồi..." style="resize:vertical;min-height:120px"></textarea>
          </div>
          
          <div class="btn-group">
            <button class="btn btn-success" type="submit" style="background:#00bfa5;border:none;padding:10px 20px">
              <i class="fas fa-save"></i> Lưu phản hồi
            </button>
            <button class="btn btn-secondary" type="button" onclick="closeCommentModal()" style="padding:10px 20px">
              <i class="fas fa-times"></i> Đóng
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
  function openCommentModal(id, username, content, reply, rating, product, time) {
    document.getElementById('modalCommentId').textContent = id;
    document.getElementById('modalUsername').textContent = username;
    document.getElementById('modalProduct').textContent = product;
    document.getElementById('modalContent').textContent = content;
    document.getElementById('modalTime').textContent = time;
    document.getElementById('modalFormId').value = id;
    document.getElementById('modalReplyInput').value = reply || '';
    
    // Display current reply if exists
    const currentReplyRow = document.getElementById('currentReplyRow');
    const currentReplyDiv = document.getElementById('modalCurrentReply');
    if (reply && reply.trim() !== '') {
      currentReplyDiv.textContent = reply;
      currentReplyRow.style.display = 'flex';
    } else {
      currentReplyRow.style.display = 'none';
    }
    
    // Display rating stars
    let stars = '';
    for (let i = 0; i < rating; i++) stars += '★';
    for (let i = rating; i < 5; i++) stars += '☆';
    document.getElementById('modalRating').innerHTML = stars + ' <span style="color:#7f8c8d;font-size:14px">(' + rating + '/5)</span>';
    
    document.getElementById('commentModal').classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeCommentModal(event) {
    if (!event || event.target === event.currentTarget) {
      document.getElementById('commentModal').classList.remove('active');
      document.body.style.overflow = 'auto';
    }
  }

  // Close modal with ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeCommentModal();
    }
  });

  function deleteComment(id) {
    if (confirm('Xóa bình luận #' + id + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'comment_moderate.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        form.appendChild(idInput);
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?php echo app_csrf_token(); ?>';
        form.appendChild(csrfInput);
        
        document.body.appendChild(form);
        form.submit();
    }
  }
  </script>
