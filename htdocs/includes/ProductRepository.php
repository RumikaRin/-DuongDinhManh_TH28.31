<?php

declare(strict_types=1);

class ProductRepository
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Map a database row of sanpham to standardized product card format
     */
    public function mapProduct(array $row): array
    {
        $price = (int)$row['gia_sp'];
        $original_price = isset($row['gia_cu']) ? (int)$row['gia_cu'] : 0;
        $discount = 0;
        if ($original_price > $price) {
            $discount = (int)round((($original_price - $price) / $original_price) * 100);
        }

        return [
            'id' => (int)$row['id_sp'],
            'type' => 'sanpham',
            'title' => (string)$row['ten_sp'],
            'author' => (string)($row['tacgia_sp'] ?? ''),
            'image' => (string)($row['anh_sp'] ?? ''),
            'price' => $price,
            'original_price' => $original_price,
            'discount' => $discount,
            'category' => (string)($row['category'] ?? ''),
            'avg_rating' => isset($row['avg_rating']) ? (float)$row['avg_rating'] : 0.0,
            'review_count' => isset($row['review_count']) ? (int)$row['review_count'] : 0
        ];
    }

    /**
     * Map a database row of sales to standardized product card format
     */
    public function mapSale(array $row): array
    {
        $original_price = (int)$row['giasp_tt'];
        $discount = isset($row['giamgia_tt']) ? (int)$row['giamgia_tt'] : 0;
        $price = (int)$row['saugiamgia_tt'];
        if ($price === 0 && $original_price > 0) {
            $price = (int)($original_price * (1 - $discount / 100));
        }

        return [
            'id' => (int)$row['id_tt'],
            'type' => 'sale',
            'title' => (string)$row['ten_tt'],
            'author' => (string)($row['tacgia_tt'] ?? ''),
            'image' => (string)($row['anh_tt'] ?? ''),
            'price' => $price,
            'original_price' => $original_price,
            'discount' => $discount,
            'category' => (string)($row['category'] ?? ''),
            'avg_rating' => isset($row['avg_rating']) ? (float)$row['avg_rating'] : 0.0,
            'review_count' => isset($row['review_count']) ? (int)$row['review_count'] : 0
        ];
    }

    /**
     * Get new products (WINGBOOKS) for homepage
     */
    public function getNewProducts(int $limit = 8): array
    {
        $query = "SELECT DISTINCT s.*, GROUP_CONCAT(d.ten_dm SEPARATOR ', ') as category,
                         MAX(c.avg_rating) as avg_rating, MAX(c.review_count) as review_count
                  FROM sanpham s 
                  LEFT JOIN sanpham_danhmuc sd ON s.id_sp = sd.id_sp 
                  LEFT JOIN danhmuc d ON sd.id_dm = d.id_dm 
                  LEFT JOIN (
                      SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count
                      FROM comments
                      WHERE product_type = 'sanpham' AND status = 'approved'
                      GROUP BY product_id
                  ) c ON s.id_sp = c.product_id
                  GROUP BY s.id_sp
                  ORDER BY s.id_sp DESC LIMIT ?";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $this->mapProduct($row);
        }
        $stmt->close();
        return $products;
    }

    /**
     * Get new sales (KHUYẾN MÃI NỔI BẬT) for homepage
     */
    public function getNewSales(int $limit = 8): array
    {
        $query = "SELECT DISTINCT s.*, GROUP_CONCAT(d.ten_dm SEPARATOR ', ') as category,
                         MAX(c.avg_rating) as avg_rating, MAX(c.review_count) as review_count
                  FROM sales s 
                  LEFT JOIN sales_danhmuc sd ON s.id_tt = sd.id_tt 
                  LEFT JOIN danhmuc d ON sd.id_dm = d.id_dm 
                  LEFT JOIN (
                      SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count
                      FROM comments
                      WHERE product_type = 'sale' AND status = 'approved'
                      GROUP BY product_id
                  ) c ON s.id_tt = c.product_id
                  GROUP BY s.id_tt
                  ORDER BY s.id_tt DESC LIMIT ?";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sales = [];
        while ($row = $result->fetch_assoc()) {
            $sales[] = $this->mapSale($row);
        }
        $stmt->close();
        return $sales;
    }

    /**
     * Get paginated products for sanpham page
     */
    public function getPaginatedProducts(int $page, int $perPage, string $q, string $author, string $sortBy): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];
        $types = "";

        if ($q !== '') {
            $whereConditions[] = "(s.ten_sp LIKE ? OR s.tacgia_sp LIKE ?)";
            $search = "%" . $q . "%";
            $params[] = $search;
            $params[] = $search;
            $types .= "ss";
        }

        if ($author !== '') {
            $whereConditions[] = "s.tacgia_sp LIKE ?";
            $params[] = "%" . $author . "%";
            $types .= "s";
        }

        $whereClause = "";
        if (!empty($whereConditions)) {
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
        }

        // Determine sort order
        $orderBy = "s.id_sp DESC";
        if ($sortBy === "price-low") {
            $orderBy = "s.gia_sp ASC";
        } elseif ($sortBy === "price-high") {
            $orderBy = "s.gia_sp DESC";
        } elseif ($sortBy === "popular") {
            $orderBy = "review_count DESC, avg_rating DESC, s.id_sp DESC";
        }

        // Count query
        $countQuery = "SELECT COUNT(DISTINCT s.id_sp) as total FROM sanpham s $whereClause";
        $stmtCount = $this->db->prepare($countQuery);
        if (!empty($params) && $stmtCount) {
            $stmtCount->bind_param($types, ...$params);
        }
        $total = 0;
        if ($stmtCount && $stmtCount->execute()) {
            $total = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
            $stmtCount->close();
        }

        // Items query
        $query = "SELECT DISTINCT s.*, GROUP_CONCAT(d.ten_dm SEPARATOR ', ') as category,
                         MAX(c.avg_rating) as avg_rating, MAX(c.review_count) as review_count
                  FROM sanpham s 
                  LEFT JOIN sanpham_danhmuc sd ON s.id_sp = sd.id_sp 
                  LEFT JOIN danhmuc d ON sd.id_dm = d.id_dm 
                  LEFT JOIN (
                      SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count
                      FROM comments
                      WHERE product_type = 'sanpham' AND status = 'approved'
                      GROUP BY product_id
                  ) c ON s.id_sp = c.product_id
                  $whereClause
                  GROUP BY s.id_sp
                  ORDER BY $orderBy
                  LIMIT ? OFFSET ?";
        
        $stmtItems = $this->db->prepare($query);
        if ($stmtItems) {
            $typesWithLimit = $types . "ii";
            $paramsWithLimit = array_merge($params, [$perPage, $offset]);
            $stmtItems->bind_param($typesWithLimit, ...$paramsWithLimit);
            $stmtItems->execute();
            $result = $stmtItems->get_result();
            
            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = $this->mapProduct($row);
            }
            $stmtItems->close();
            
            return [
                'total' => $total,
                'items' => $items
            ];
        }

        return ['total' => 0, 'items' => []];
    }

    /**
     * Get paginated sales for sales page
     */
    public function getPaginatedSales(int $page, int $perPage, string $q, string $author, int $minDiscount, string $sortBy): array
    {
        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];
        $types = "";

        if ($q !== '') {
            $whereConditions[] = "(s.ten_tt LIKE ? OR s.tacgia_tt LIKE ?)";
            $search = "%" . $q . "%";
            $params[] = $search;
            $params[] = $search;
            $types .= "ss";
        }

        if ($author !== '') {
            $whereConditions[] = "s.tacgia_tt LIKE ?";
            $params[] = "%" . $author . "%";
            $types .= "s";
        }

        if ($minDiscount > 0) {
            $whereConditions[] = "s.giamgia_tt >= ?";
            $params[] = $minDiscount;
            $types .= "i";
        }

        $whereClause = "";
        if (!empty($whereConditions)) {
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
        }

        // Determine sort order
        $orderBy = "s.id_tt DESC";
        if ($sortBy === "price-low") {
            $orderBy = "s.saugiamgia_tt ASC";
        } elseif ($sortBy === "price-high") {
            $orderBy = "s.saugiamgia_tt DESC";
        } elseif ($sortBy === "discount-high") {
            $orderBy = "s.giamgia_tt DESC, s.id_tt DESC";
        }

        // Count query
        $countQuery = "SELECT COUNT(DISTINCT s.id_tt) as total FROM sales s $whereClause";
        $stmtCount = $this->db->prepare($countQuery);
        if (!empty($params) && $stmtCount) {
            $stmtCount->bind_param($types, ...$params);
        }
        $total = 0;
        if ($stmtCount && $stmtCount->execute()) {
            $total = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
            $stmtCount->close();
        }

        // Items query
        $query = "SELECT DISTINCT s.*, GROUP_CONCAT(d.ten_dm SEPARATOR ', ') as category,
                         MAX(c.avg_rating) as avg_rating, MAX(c.review_count) as review_count
                  FROM sales s 
                  LEFT JOIN sales_danhmuc sd ON s.id_tt = sd.id_tt 
                  LEFT JOIN danhmuc d ON sd.id_dm = d.id_dm 
                  LEFT JOIN (
                      SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count
                      FROM comments
                      WHERE product_type = 'sale' AND status = 'approved'
                      GROUP BY product_id
                  ) c ON s.id_tt = c.product_id
                  $whereClause
                  GROUP BY s.id_tt
                  ORDER BY $orderBy
                  LIMIT ? OFFSET ?";
        
        $stmtItems = $this->db->prepare($query);
        if ($stmtItems) {
            $typesWithLimit = $types . "ii";
            $paramsWithLimit = array_merge($params, [$perPage, $offset]);
            $stmtItems->bind_param($typesWithLimit, ...$paramsWithLimit);
            $stmtItems->execute();
            $result = $stmtItems->get_result();
            
            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = $this->mapSale($row);
            }
            $stmtItems->close();
            
            return [
                'total' => $total,
                'items' => $items
            ];
        }

        return ['total' => 0, 'items' => []];
    }

    /**
     * Get products by category ID with pagination
     */
    public function getProductsByCategoryId(int $categoryId, int $page = 1, int $perPage = 12): array
    {
        $offset = ($page - 1) * $perPage;

        // Count query
        $countQuery = "SELECT COUNT(DISTINCT sd.id_sp) as total FROM sanpham_danhmuc sd WHERE sd.id_dm = ?";
        $stmtCount = $this->db->prepare($countQuery);
        $total = 0;
        if ($stmtCount) {
            $stmtCount->bind_param("i", $categoryId);
            $stmtCount->execute();
            $total = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
            $stmtCount->close();
        }

        // Items query
        $query = "SELECT DISTINCT s.*, GROUP_CONCAT(d.ten_dm SEPARATOR ', ') as category,
                         MAX(c.avg_rating) as avg_rating, MAX(c.review_count) as review_count
                  FROM sanpham s 
                  LEFT JOIN sanpham_danhmuc sd ON s.id_sp = sd.id_sp 
                  LEFT JOIN danhmuc d ON sd.id_dm = d.id_dm 
                  LEFT JOIN (
                      SELECT product_id, AVG(rating) as avg_rating, COUNT(*) as review_count
                      FROM comments
                      WHERE product_type = 'sanpham' AND status = 'approved'
                      GROUP BY product_id
                  ) c ON s.id_sp = c.product_id
                  WHERE sd.id_dm = ?
                  GROUP BY s.id_sp
                  ORDER BY s.id_sp DESC
                  LIMIT ? OFFSET ?";
        
        $stmtItems = $this->db->prepare($query);
        if ($stmtItems) {
            $stmtItems->bind_param("iii", $categoryId, $perPage, $offset);
            $stmtItems->execute();
            $result = $stmtItems->get_result();
            
            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = $this->mapProduct($row);
            }
            $stmtItems->close();
            
            return [
                'total' => $total,
                'items' => $items
            ];
        }

        return ['total' => 0, 'items' => []];
    }
}
