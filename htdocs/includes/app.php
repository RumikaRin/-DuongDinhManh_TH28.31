<?php

declare(strict_types=1);

function app_database_config(): array
{
    return [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'database' => getenv('DB_DATABASE') ?: 'manh',
        'port' => (int)(getenv('DB_PORT') ?: 3306),
    ];
}

function app_slugify(string $value): string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $value = strtr($value, [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
    ]);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

    return trim($value, '-');
}

function app_allowed_routes(): array
{
    return [
        'home',
        'sanpham',
        'sales',
        'tintuc',
        'video',
        'chitiet',
        'chitietsales',
        'donhang',
        'hoso',
        'xemgiohang',
        'caidat',
        'thanhtoan',
        'dangky',
        'edit_hoso',
        'dangnhap',
        'timkiem',
        'danhmuc',
    ];
}

function app_is_allowed_route(string $route): bool
{
    return in_array($route, app_allowed_routes(), true);
}

function app_is_admin_session(array $session): bool
{
    return !empty($session['loggedin'])
        && isset($session['ten_tv'])
        && hash_equals('admin', mb_strtolower((string)$session['ten_tv'], 'UTF-8'));
}

function app_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function app_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(app_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function app_verify_csrf(?string $expected, ?string $provided): bool
{
    return is_string($expected)
        && $expected !== ''
        && is_string($provided)
        && hash_equals($expected, $provided);
}

function app_request_csrf_valid(): bool
{
    return app_verify_csrf($_SESSION['csrf_token'] ?? null, $_POST['csrf_token'] ?? null);
}

function app_normalize_quantity($quantity): int
{
    $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

    return $quantity !== false && $quantity >= 1 && $quantity <= 99 ? $quantity : 0;
}

function app_normalize_product_type(?string $type): string
{
    return in_array($type, ['sanpham', 'sale'], true) ? $type : '';
}

function app_shipping_fee($fee): int
{
    $fee = filter_var($fee, FILTER_VALIDATE_INT);

    return in_array($fee, [0, 30000, 50000], true) ? (int)$fee : 30000;
}

function app_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function app_require_admin(bool $json = false): void
{
    if (app_is_admin_session($_SESSION ?? [])) {
        return;
    }

    if ($json) {
        app_json_response(['success' => false, 'message' => 'Không có quyền truy cập.'], 403);
    }

    header('Location: login.php');
    exit;
}

function app_require_post_csrf(bool $json = false): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && app_request_csrf_valid()) {
        return;
    }

    if ($json) {
        app_json_response(['ok' => false, 'message' => 'Yêu cầu không hợp lệ hoặc đã hết hạn.'], 419);
    }

    http_response_code(419);
    exit('Yêu cầu không hợp lệ hoặc đã hết hạn.');
}

function app_cache_get(string $key)
{
    $cacheFile = __DIR__ . '/cache/' . md5($key) . '.json';
    if (!file_exists($cacheFile)) {
        return null;
    }
    $content = @file_get_contents($cacheFile);
    if ($content === false) {
        return null;
    }
    $data = json_decode($content, true);
    if (!is_array($data)) {
        return null;
    }
    if (!isset($data['expires_at']) || time() > $data['expires_at']) {
        @unlink($cacheFile);
        return null;
    }
    return $data['value'];
}

function app_cache_set(string $key, $value, int $ttl = 300): void
{
    $cacheDir = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0777, true);
    }
    $cacheFile = $cacheDir . '/' . md5($key) . '.json';
    $data = [
        'expires_at' => time() + $ttl,
        'value' => $value
    ];
    @file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE));
}

function app_cache_delete(string $key): void
{
    $cacheFile = __DIR__ . '/cache/' . md5($key) . '.json';
    if (file_exists($cacheFile)) {
        @unlink($cacheFile);
    }
}

function app_cache_clear(): void
{
    $cacheDir = __DIR__ . '/cache';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*.json');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
}
