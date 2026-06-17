<?php

require_once __DIR__ . '/includes/app.php';

$page = isset($_GET['go']) ? trim((string)$_GET['go']) : 'home';
if (!app_is_allowed_route($page)) {
    http_response_code(404);
    $page = 'home';
}

$file = __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $page . '.php';
if (is_file($file)) {
    include $file;
} else {
    http_response_code(404);
    include __DIR__ . '/views/home.php';
}
