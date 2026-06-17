<?php
// FILE: index.php
// 1. Bao gồm phần đầu (header.php chứa kết nối DB và layout)
include 'header.php';

// 2. Định tuyến nội dung
// $route đã được định nghĩa trong header.php
switch ($route) {
    case 'dashboard':
        include 'dashboard.php';
        break;
    case 'manage_orders':
        include __DIR__ . '/template/manage_orders.php';
        break;
    case 'manage_products':
        include __DIR__ . '/template/manage_products.php';
        break;
    case 'addproducts':
        include __DIR__ . '/add_products.php';
        break;
    case 'manufacturers':
        include __DIR__ . '/template/manufacturers.php';
        break;
    case 'account':
        include __DIR__ . '/template/account.php';
        break;
    case 'edit_product':
        include __DIR__ . '/template/edit_product.php';
        break;
    case 'edit_sale':
        include __DIR__ . '/template/edit_sale.php';
        break;
    case 'add_sale':
        include __DIR__ . '/add_sale.php';
        break;
    case 'customers':
        include __DIR__ . '/template/customers.php';
        break;
    case 'categories':
        include __DIR__ . '/template/categories.php';
        break;
    case 'reports':
        include __DIR__ . '/template/reports.php';
        break;
    case 'blockchain_audit':
        include __DIR__ . '/template/blockchain_audit.php';
        break;
    case 'comments':
        include __DIR__ . '/template/comments.php';
        break;
    case 'manage_news':
        include __DIR__ . '/template/manage_news.php';
        break;
    case 'add_news':
        include __DIR__ . '/add_news.php';
        break;
    case 'edit_news':
        include __DIR__ . '/edit_news.php';
        break;
    default:
        include 'dashboard.php';
        break;
}

// 3. Bao gồm phần cuối (footer.php)
include 'footer.php';
?>
