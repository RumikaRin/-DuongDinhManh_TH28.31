<?php

declare(strict_types=1);

require_once __DIR__ . '/dbconnect.php';

if (PHP_SAPI !== 'cli') {
    app_require_admin();
    app_require_post_csrf();
}

$messages = [];

function migration_log(string $message): void
{
    global $messages;
    $messages[] = $message;
}

function migration_query(mysqli $db, string $sql, string $label): void
{
    if (!$db->query($sql)) {
        throw new RuntimeException($label . ': ' . $db->error);
    }
    migration_log($label);
}

function migration_table_exists(mysqli $db, string $table): bool
{
    $safe = $db->real_escape_string($table);
    $result = $db->query("SHOW TABLES LIKE '{$safe}'");

    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function migration_column_exists(mysqli $db, string $table, string $column): bool
{
    $safeColumn = $db->real_escape_string($column);
    $result = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$safeColumn}'");

    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function migration_count(mysqli $db, string $sql): int
{
    $result = $db->query($sql);
    if (!$result) {
        throw new RuntimeException($db->error);
    }

    return (int)$result->fetch_row()[0];
}

try {
    migration_query($ketnoi, "CREATE TABLE IF NOT EXISTS danhmuc (
        id_dm INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        ten_dm VARCHAR(100) NOT NULL,
        mota TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", 'Đã kiểm tra bảng danhmuc');

    migration_query($ketnoi, "CREATE TABLE IF NOT EXISTS sanpham_danhmuc (
        id_sp INT NOT NULL,
        id_dm INT NOT NULL,
        PRIMARY KEY (id_sp, id_dm),
        KEY idx_sp_dm_category (id_dm)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", 'Đã kiểm tra bảng sanpham_danhmuc');

    migration_query($ketnoi, "CREATE TABLE IF NOT EXISTS sales_danhmuc (
        id_tt INT NOT NULL,
        id_dm INT NOT NULL,
        PRIMARY KEY (id_tt, id_dm),
        KEY idx_sales_dm_category (id_dm)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", 'Đã kiểm tra bảng sales_danhmuc');

    migration_query($ketnoi, "INSERT IGNORE INTO danhmuc (id_dm, ten_dm, mota) VALUES
        (1, 'Sách văn học', 'Các tác phẩm văn học trong và ngoài nước'),
        (2, 'Sách khoa học', 'Sách về khoa học công nghệ'),
        (3, 'Sách thiếu nhi', 'Sách dành cho trẻ em'),
        (4, 'Sách kỹ năng sống', 'Sách phát triển bản thân'),
        (5, 'Sách giáo khoa', 'Sách giáo khoa các cấp'),
        (6, 'Tiểu thuyết', 'Tiểu thuyết và light novel'),
        (7, 'Truyện tranh', 'Manga, comic và truyện tranh'),
        (8, 'Sách ngoại ngữ', 'Sách học ngoại ngữ')", 'Đã bổ sung danh mục mặc định còn thiếu');

    $productCount = migration_count($ketnoi, 'SELECT COUNT(*) FROM sanpham');
    $productCategoryCount = migration_count($ketnoi, 'SELECT COUNT(*) FROM sanpham_danhmuc');
    $legacyAutoSeedCount = migration_count(
        $ketnoi,
        "SELECT COUNT(*) FROM (
            SELECT id_sp
            FROM sanpham_danhmuc
            GROUP BY id_sp
            HAVING COUNT(*) = 2 AND MIN(id_dm) = 1 AND MAX(id_dm) = 6
        ) seeded"
    );

    if ($productCount > 0 && $productCategoryCount === $productCount * 2 && $legacyAutoSeedCount === $productCount) {
        migration_query($ketnoi, 'DELETE FROM sanpham_danhmuc WHERE id_dm = 1', 'Đã loại bỏ liên kết danh mục gán cứng cũ');
        $productCategoryCount = migration_count($ketnoi, 'SELECT COUNT(*) FROM sanpham_danhmuc');
    }

    if ($productCount > 0 && $productCategoryCount === 0) {
        migration_query(
            $ketnoi,
            'INSERT IGNORE INTO sanpham_danhmuc (id_sp, id_dm) SELECT id_sp, 6 FROM sanpham',
            'Đã phân loại dữ liệu sản phẩm hiện có vào Tiểu thuyết'
        );
    }

    $saleCount = migration_count($ketnoi, 'SELECT COUNT(*) FROM sales');
    $saleCategoryCount = migration_count($ketnoi, 'SELECT COUNT(*) FROM sales_danhmuc');
    if ($saleCount > 0 && $saleCategoryCount === 0) {
        migration_query(
            $ketnoi,
            'INSERT IGNORE INTO sales_danhmuc (id_tt, id_dm) SELECT id_tt, 7 FROM sales',
            'Đã phân loại dữ liệu khuyến mãi hiện có vào Truyện tranh'
        );
    }

    if (!migration_table_exists($ketnoi, 'comments')) {
        migration_query($ketnoi, "CREATE TABLE comments (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            product_type ENUM('sanpham','sale') NOT NULL,
            product_id INT NOT NULL,
            user_id INT NULL,
            username VARCHAR(100) NOT NULL DEFAULT 'Ẩn danh',
            rating TINYINT NOT NULL,
            content TEXT NOT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
            reply TEXT NULL,
            reply_by VARCHAR(100) NULL,
            reply_date TIMESTAMP NULL,
            helpful_count INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_comments_product (product_type, product_id),
            KEY idx_comments_user (user_id),
            KEY idx_comments_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", 'Đã tạo bảng comments');
    } else {
        migration_query($ketnoi, "ALTER TABLE comments MODIFY product_type ENUM('sanpham','sale','sales') NOT NULL", 'Đã mở rộng kiểu dữ liệu comments tạm thời');
        migration_query($ketnoi, "UPDATE comments SET product_type='sale' WHERE product_type='sales'", 'Đã chuẩn hóa loại bình luận sale');
        migration_query($ketnoi, "ALTER TABLE comments MODIFY product_type ENUM('sanpham','sale') NOT NULL", 'Đã chuẩn hóa kiểu dữ liệu comments');

        if (!migration_column_exists($ketnoi, 'comments', 'username')) {
            migration_query($ketnoi, "ALTER TABLE comments ADD username VARCHAR(100) NOT NULL DEFAULT 'Ẩn danh' AFTER user_id", 'Đã thêm comments.username');
        }
        if (!migration_column_exists($ketnoi, 'comments', 'content')) {
            migration_query($ketnoi, 'ALTER TABLE comments ADD content TEXT NULL AFTER rating', 'Đã thêm comments.content');
            if (migration_column_exists($ketnoi, 'comments', 'comment')) {
                migration_query($ketnoi, "UPDATE comments SET content=COALESCE(comment, '')", 'Đã chuyển dữ liệu comments.comment');
            }
            migration_query($ketnoi, "UPDATE comments SET content='' WHERE content IS NULL", 'Đã chuẩn hóa comments.content');
            migration_query($ketnoi, 'ALTER TABLE comments MODIFY content TEXT NOT NULL', 'Đã hoàn tất comments.content');
        }
        if (!migration_column_exists($ketnoi, 'comments', 'reply')) {
            migration_query($ketnoi, 'ALTER TABLE comments ADD reply TEXT NULL', 'Đã thêm comments.reply');
        }
        if (!migration_column_exists($ketnoi, 'comments', 'reply_by')) {
            migration_query($ketnoi, 'ALTER TABLE comments ADD reply_by VARCHAR(100) NULL', 'Đã thêm comments.reply_by');
        }
        if (!migration_column_exists($ketnoi, 'comments', 'reply_date')) {
            migration_query($ketnoi, 'ALTER TABLE comments ADD reply_date TIMESTAMP NULL', 'Đã thêm comments.reply_date');
        }
        if (!migration_column_exists($ketnoi, 'comments', 'helpful_count')) {
            migration_query($ketnoi, 'ALTER TABLE comments ADD helpful_count INT NOT NULL DEFAULT 0', 'Đã thêm comments.helpful_count');
        }
    }

    migration_query($ketnoi, "CREATE TABLE IF NOT EXISTS comment_helpful (
        user_id INT NOT NULL,
        comment_id INT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, comment_id),
        KEY idx_helpful_comment (comment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", 'Đã kiểm tra bảng comment_helpful');

    if (!migration_column_exists($ketnoi, 'donhang', 'email')) {
        migration_query($ketnoi, 'ALTER TABLE donhang ADD email VARCHAR(255) NULL AFTER diachi', 'Đã thêm donhang.email');
    }

    migration_query($ketnoi, "CREATE TABLE IF NOT EXISTS blockchain_audit_events (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        entity_type VARCHAR(80) NOT NULL,
        entity_id INT NOT NULL,
        action VARCHAR(120) NOT NULL,
        actor_type VARCHAR(50) NOT NULL DEFAULT 'system',
        actor_id INT NOT NULL DEFAULT 0,
        payload_hash CHAR(66) NOT NULL,
        previous_hash CHAR(66) NULL,
        event_hash CHAR(66) NOT NULL,
        payload_json LONGTEXT NOT NULL,
        pii_policy VARCHAR(255) NOT NULL,
        status ENUM('disabled','pending','confirmed','failed') NOT NULL DEFAULT 'disabled',
        error_message VARCHAR(500) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_blockchain_audit_entity (entity_type, entity_id),
        KEY idx_blockchain_audit_action (action),
        KEY idx_blockchain_audit_status (status),
        KEY idx_blockchain_audit_hash (payload_hash),
        KEY idx_blockchain_audit_event_hash (event_hash)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", 'Đã kiểm tra bảng blockchain_audit_events');

    if (!migration_column_exists($ketnoi, 'blockchain_audit_events', 'previous_hash')) {
        migration_query($ketnoi, 'ALTER TABLE blockchain_audit_events ADD previous_hash CHAR(66) NULL AFTER payload_hash', 'Đã thêm blockchain_audit_events.previous_hash');
    }
    if (!migration_column_exists($ketnoi, 'blockchain_audit_events', 'event_hash')) {
        migration_query($ketnoi, 'ALTER TABLE blockchain_audit_events ADD event_hash CHAR(66) NULL AFTER previous_hash', 'Đã thêm blockchain_audit_events.event_hash');
    }
    migration_query(
        $ketnoi,
        "UPDATE blockchain_audit_events SET event_hash = payload_hash WHERE event_hash IS NULL OR event_hash = ''",
        'Đã chuẩn hóa event_hash cho audit cũ'
    );
    migration_query($ketnoi, 'ALTER TABLE blockchain_audit_events MODIFY event_hash CHAR(66) NOT NULL', 'Đã kiểm tra ràng buộc blockchain_audit_events.event_hash');

    migration_query($ketnoi, "CREATE TABLE IF NOT EXISTS blockchain_receipts (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        audit_event_id INT NOT NULL,
        network VARCHAR(80) NOT NULL,
        chain_id INT NOT NULL DEFAULT 0,
        contract_address VARCHAR(120) NOT NULL,
        tx_hash VARCHAR(120) NULL,
        block_number BIGINT NULL,
        block_hash VARCHAR(120) NULL,
        confirmed_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_blockchain_receipts_event (audit_event_id),
        KEY idx_blockchain_receipts_tx (tx_hash),
        CONSTRAINT fk_blockchain_receipt_event FOREIGN KEY (audit_event_id)
            REFERENCES blockchain_audit_events (id)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", 'Đã kiểm tra bảng blockchain_receipts');

    migration_query($ketnoi, "CREATE OR REPLACE VIEW v_bestsellers AS
        SELECT
            COALESCE(sp.ten_sp, s.ten_tt) AS product_name,
            COALESCE(sp.id_sp, s.id_tt) AS product_id,
            dc.loai AS product_type,
            SUM(dc.soluong) AS total_sold,
            COUNT(DISTINCT dc.id_dh) AS order_count
        FROM donhang_chitiet dc
        LEFT JOIN sanpham sp ON dc.loai = 'sanpham' AND dc.id_sp = sp.id_sp
        LEFT JOIN sales s ON dc.loai = 'sale' AND dc.id_sp = s.id_tt
        GROUP BY dc.id_sp, dc.loai", 'Đã kiểm tra view v_bestsellers');

    migration_query($ketnoi, "CREATE OR REPLACE VIEW v_revenue_by_month AS
        SELECT
            DATE_FORMAT(ngaydat, '%Y-%m') AS month,
            COUNT(DISTINCT id_dh) AS total_orders,
            SUM(tongtien) AS total_revenue,
            AVG(tongtien) AS avg_order_value
        FROM donhang
        WHERE trangthai IN ('Hoàn thành', 'Vận chuyển thành công', 'Đang xử lý')
        GROUP BY DATE_FORMAT(ngaydat, '%Y-%m')", 'Đã kiểm tra view v_revenue_by_month');

    migration_query($ketnoi, "CREATE OR REPLACE VIEW v_all_products AS
        SELECT id_sp AS id, ten_sp AS name, gia_sp AS price, anh_sp AS image,
               tacgia_sp AS author, 'sanpham' AS type, NULL AS original_price,
               NULL AS discount
        FROM sanpham
        UNION ALL
        SELECT id_tt AS id, ten_tt AS name, saugiamgia_tt AS price, anh_tt AS image,
               tacgia_tt AS author, 'sale' AS type, giasp_tt AS original_price,
               giamgia_tt AS discount
        FROM sales", 'Đã kiểm tra view v_all_products');

    migration_log('Migration hoàn tất.');
} catch (Throwable $exception) {
    error_log('Migration failed: ' . $exception->getMessage());
    $messages[] = 'Lỗi migration: ' . $exception->getMessage();
    http_response_code(500);
}

if (PHP_SAPI === 'cli') {
    echo implode(PHP_EOL, $messages) . PHP_EOL;
} else {
    echo '<pre>' . htmlspecialchars(implode(PHP_EOL, $messages), ENT_QUOTES, 'UTF-8') . '</pre>';
}
