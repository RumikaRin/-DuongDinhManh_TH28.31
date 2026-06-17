<?php
// FILE: admin/header.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../dbconnect.php';
app_require_admin();
$conn = $ketnoi;

// Route and title
$route = isset($_GET['route']) ? $_GET['route'] : 'dashboard';
$page_title = ucfirst(str_replace('_', ' ', $route));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMIN - <?php echo $page_title; ?> | Dương Đình Mạnh</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Ẩn tất cả thanh trượt */
        * {
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        
        *::-webkit-scrollbar {
            display: none !important;
        }
        
        html, body {
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        
        html::-webkit-scrollbar, body::-webkit-scrollbar {
            display: none !important;
        }

        /* Background Video */
        #bg-video {
            position: fixed;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%);
            z-index: -2;
            object-fit: cover;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }

        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            z-index: 1000;
            display: flex;
            align-items: center;
            padding: 0 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        /* Toggle Button */
        .toggle-sidebar {
            color: rgba(255, 255, 255, 0.9);
            font-size: 24px;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
            padding: 8px;
            border-radius: 8px;
        }

        .toggle-sidebar:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
        }

        .logo i {
            font-size: 28px;
            background: linear-gradient(135deg, rgb(255, 228, 145) 0%, rgb(97, 76, 0) 100%);
            padding: 8px;
            border-radius: 10px;
        }

        .header-right {
            display: flex;
            gap: 20px;
            align-items: center;
            position: relative;
        }

        /* Notifications */
        .notif {
            position: relative;
        }

        .notif-btn {
            color: rgba(255, 255, 255, 0.9);
            font-size: 22px;
            cursor: pointer;
            transition: color 0.3s;
        }

        .notif-btn:hover {
            color: #ffffff;
        }

        .notif-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: #fff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.4);
        }

        .notif-dropdown {
            position: absolute;
            right: 0;
            top: 140%;
            width: 360px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            opacity: 0;
            transform: translateY(-10px);
            pointer-events: none;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
        }

        .notif-dropdown.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .notif-head {
            padding: 16px;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notif-list {
            max-height: 320px;
            overflow-y: auto;
        }

        .notif-item {
            padding: 12px 16px;
            display: flex;
            gap: 12px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: background 0.2s;
        }

        .notif-item:hover {
            background: rgba(0, 0, 0, 0.03);
        }

        .notif-item .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgb(255, 228, 145), rgb(97, 76, 0));
            margin-top: 6px;
            flex-shrink: 0;
        }

        .notif-item .content {
            flex: 1;
        }

        .notif-item .title {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .notif-item .sub {
            font-size: 12px;
            color: #7f8c8d;
        }

        /* User Menu */
        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
        }

        .user-menu:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgb(255, 228, 145), rgb(97, 76, 0));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
        }

        .user-menu span {
            color: #ffffff;
            font-weight: 500;
            font-size: 14px;
        }

        .user-menu i.fa-chevron-down {
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
            transition: transform 0.3s;
        }

        .user-menu:hover i.fa-chevron-down {
            transform: rotate(180deg);
        }

        /* DROPDOWN - FIXED */
        .dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            background:rgba(255, 255, 255, 0.43);
            min-width: 200px;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            pointer-events: none;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
            z-index: 9999;
        }

        .dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }

        .dropdown a, .dropdown button {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #2c3e50 !important;
            text-decoration: none;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
            font-weight: 500;
            font-size: 14px;
            border-radius: 8px;
            margin: 4px 8px;
            background: transparent;
            border: none;
            width: calc(100% - 16px);
            text-align: left;
            cursor: pointer;
            font-family: inherit;
        }

        .dropdown a:hover, .dropdown button:hover {
            background: var(--primary);
            color: #ffffff !important;
            transform: translateX(4px);
        }

        .dropdown a i, .dropdown button i {
            width: 20px;
            text-align: center;
            color: #2c3e50;
            font-size: 18px;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
        }

        .dropdown a:hover i, .dropdown button:hover i {
            color: #ffffff;
            transform: scale(1.1);
        }

        /* Dark mode cho dropdown */
        [data-theme="dark"] .dropdown {
            background: #2d2d2d;
            border: 1px solid #444;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6);
        }

        [data-theme="dark"] .dropdown a, [data-theme="dark"] .dropdown button {
            color: #ffffff !important;
        }

        [data-theme="dark"] .dropdown a i, [data-theme="dark"] .dropdown button i {
            color: #ffffff;
        }

        [data-theme="dark"] .dropdown a:hover i, [data-theme="dark"] .dropdown button:hover i {
            color: #ffffff;
            transform: scale(1.1);
        }

        [data-theme="dark"] .dropdown a:hover, [data-theme="dark"] .dropdown button:hover {
            background: var(--primary);
            color: #ffffff !important;
            transform: translateX(4px);
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 70px;
            width: 260px;
            height: calc(100vh - 70px);
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.15);
            overflow-y: auto;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
            z-index: 999;
        }

        .sidebar.collapsed {
            transform: translateX(-260px) !important;
            visibility: hidden !important;
            opacity: 0 !important;
            width: 0 !important;
        }

        .sidebar:not(.collapsed) {
            transform: translateX(0) !important;
            visibility: visible !important;
            opacity: 1 !important;
            width: 260px !important;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .menu-item {
            padding: 14px 20px;
            color: rgba(255, 255, 255, 0.85);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
            text-decoration: none;
            margin: 4px 12px;
            border-radius: 10px;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            transform: translateX(4px);
        }

        .sidebar.collapsed .menu-item:hover {
            transform: translateX(0);
        }

        .menu-item.active {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border: 1px solid #e0e0e0;
            font-weight: 600;
        }

        .menu-item i {
            width: 22px;
            text-align: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .submenu {
            padding-left: 12px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .submenu.show {
            max-height: 500px;
        }

        .submenu-item {
            padding: 10px 20px 10px 48px;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
            text-decoration: none;
            display: block;
            margin: 2px 12px;
            border-radius: 8px;
            font-size: 14px;
        }

        .submenu-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            margin-top: 70px;
            padding: 30px 60px;
            min-height: calc(100vh - 70px);
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 0px !important;
            padding: 30px 20px;
        }

        /* Footer responsive to sidebar */
        footer {
            transition: left 0.3s ease;
        }

        .sidebar.collapsed ~ * footer,
        .sidebar.collapsed + .main-content + footer {
            left: 0 !important;
        }

        .sidebar:not(.collapsed) ~ * footer,
        .sidebar:not(.collapsed) + .main-content + footer {
            left: 260px !important;
        }

        /* Breadcrumb */
        .breadcrumb {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.9);
        }

        .breadcrumb a {
            color: rgb(255, 228, 145);
            text-decoration: none;
            transition: color 0.3s;
        }

        .breadcrumb a:hover {
            color: #ffffff;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 32px;
            color: #ffffff;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Form Styling */
        label {
            display: block;
            color: #ffffff;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 15px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        input[type="text"],
        input[type="number"],
        input[type="email"],
        input[type="password"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            color: #ffffff;
            font-size: 15px;
            font-weight: 500;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        input[type="text"]::placeholder,
        input[type="number"]::placeholder,
        input[type="email"]::placeholder,
        input[type="password"]::placeholder,
        textarea::placeholder {
            color: rgba(255, 255, 255, 0.5);
            font-weight: 400;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: rgb(255, 228, 145);
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 12px rgba(255, 228, 145, 0.3);
        }

        select option {
            background: rgba(30, 30, 30, 0.95);
            color: #ffffff;
            padding: 8px;
        }

        textarea {
            min-height: 150px;
            resize: vertical;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        /* Buttons */
        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            border: 1px solid #e0e0e0;
        }

        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 255, 255, 0.2);
        }

        .btn-success {
            background: linear-gradient(135deg, #00f2c3, #00d4aa);
            color: #ffffff;
            border: 1px solid rgba(0, 242, 195, 0.3);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #00d4aa, #00b894);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 242, 195, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, rgba(200, 100, 100, 0.6), rgba(160, 80, 80, 0.6));
            color: #ffffff;
            border: 1px solid rgba(200, 100, 100, 0.7);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, rgba(200, 100, 100, 0.8), rgba(160, 80, 80, 0.8));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(200, 100, 100, 0.3);
        }

        .btn-info {
            background: linear-gradient(135deg, rgba(100, 150, 200, 0.6), rgba(80, 120, 160, 0.6));
            color: #ffffff;
            border: 1px solid rgba(100, 150, 200, 0.7);
        }

        .btn-info:hover {
            background: linear-gradient(135deg, rgba(100, 150, 200, 0.8), rgba(80, 120, 160, 0.8));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(100, 150, 200, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }

        .btn-sm {
            padding: 8px 18px;
            font-size: 14px;
        }

        .btn-sm.btn-primary {
            background: rgba(102, 126, 234, 0.2);
            border: 1px solid rgba(102, 126, 234, 0.4);
            color: #667eea;
            backdrop-filter: blur(10px);
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
        }

        .btn-sm.btn-primary:hover {
            background: rgba(102, 126, 234, 0.3);
            border-color: rgba(102, 126, 234, 0.6);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(25px);
            padding: 28px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease, border-color 0.3s ease, box-shadow 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-info h3 {
            font-size: 36px;
            color: #ffffff;
            margin-bottom: 6px;
            font-weight: 800;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
            word-break: break-word;
            overflow-wrap: break-word;
            line-height: 1.1;
        }

        .stat-info p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 15px;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        .stat-icon {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            position: relative;
            z-index: 1;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #00f2c3 0%, #00d4aa 100%);
            box-shadow: 0 4px 16px rgba(0, 242, 195, 0.4);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #ff8d72 0%, #ff6b6b 100%);
            box-shadow: 0 4px 16px rgba(255, 141, 114, 0.4);
        }

        .stat-icon.red {
            background: linear-gradient(135deg, #fd5d93 0%, #e74c7c 100%);
            box-shadow: 0 4px 16px rgba(253, 93, 147, 0.4);
        }

        /* Panel */
        .panel {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(25px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            margin-bottom: 24px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
        }

        .panel:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        .panel-header {
            padding: 24px 28px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.05);
        }

        .panel-title {
            font-size: 24px;
            color: #ffffff;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .panel-body {
            padding: 24px;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
        }

        table th {
            padding: 18px 16px;
            text-align: left;
            font-weight: 700;
            color: #ffffff;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            font-size: 15px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        table td {
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }

        table tbody tr {
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }

        table tbody tr:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Badge */
        .badge {
            padding: 8px 14px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
            backdrop-filter: blur(10px);
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background: rgba(0, 242, 195, 0.25);
            color: #00f2c3;
            border: 1px solid rgba(0, 242, 195, 0.4);
            box-shadow: 0 2px 8px rgba(0, 242, 195, 0.2);
        }

        .badge-warning {
            background: rgba(255, 141, 114, 0.25);
            color: #ff8d72;
            border: 1px solid rgba(255, 141, 114, 0.4);
            box-shadow: 0 2px 8px rgba(255, 141, 114, 0.2);
        }

        .badge-danger {
            background: rgba(253, 93, 147, 0.25);
            color: #fd5d93;
            border: 1px solid rgba(253, 93, 147, 0.4);
            box-shadow: 0 2px 8px rgba(253, 93, 147, 0.2);
        }

        .badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Action Buttons */
        .action-btns {
            display: flex;
            gap: 8px;
        }

        /* Form Grid */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 260px;
                left: -260px;
            }
            .sidebar.collapsed {
                left: -260px !important;
                transform: translateX(-100%) !important;
                visibility: hidden !important;
                opacity: 0 !important;
            }
            .sidebar:not(.collapsed) {
                left: 0 !important;
                transform: translateX(0) !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 20px;
            }
            .main-content.expanded {
                margin-left: 0 !important;
                padding: 20px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <script>

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const footer = document.querySelector('footer');
            
            if (!sidebar || !mainContent) return;
            
            const isCollapsed = sidebar.classList.contains('collapsed');
            
            if (isCollapsed) {
                // Show sidebar
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                sidebar.style.cssText = 'transform: translateX(0) !important; visibility: visible !important; opacity: 1 !important; width: 260px !important;';
                mainContent.style.cssText = 'margin-left: 260px !important;';
                if (footer) footer.style.cssText = 'left: 260px !important;';
            } else {
                // Hide sidebar
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                sidebar.style.cssText = 'transform: translateX(-260px) !important; visibility: hidden !important; opacity: 0 !important; width: 0 !important;';
                mainContent.style.cssText = 'margin-left: 0 !important;';
                if (footer) footer.style.cssText = 'left: 0 !important;';
            }
            
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }

        function toggleSubmenu(id) {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar.classList.contains('collapsed')) return;
            
            var submenu = document.getElementById(id);
            submenu.classList.toggle('show');
        }

        function toggleUserMenu() {
            var dd = document.getElementById('user-dropdown');
            dd.classList.toggle('show');
        }

        document.addEventListener('click', function(e) {
            var um = document.getElementById('user-menu');
            var dd = document.getElementById('user-dropdown');
            if (!um || !dd) return;
            if (!um.contains(e.target) && !dd.contains(e.target)) {
                dd.classList.remove('show');
            }
        });

        // Notifications
        let notifData = { unread: 0, last_max_id: 0, items: [] };
        async function fetchNotifs() {
            try {
                const res = await fetch('notifications.php', { cache: 'no-store' });
                if (!res.ok) return;
                notifData = await res.json();
                renderNotifs();
            } catch (err) {}
        }
        function renderNotifs() {
            const badge = document.getElementById('notif-badge');
            if (badge) {
                if (notifData.unread > 0) {
                    badge.textContent = notifData.unread;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
            const list = document.getElementById('notif-list');
            if (!list) return;
            list.innerHTML = '';
            for (const it of notifData.items) {
                const row = document.createElement('div');
                row.className = 'notif-item';
                row.innerHTML = `<div class="dot"></div>
                    <div class="content">
                        <div class="title">Đơn #${it.id} - ${it.name || ''}</div>
                        <div class="sub">${it.phone || ''} • ${new Intl.NumberFormat('vi-VN').format(it.total)}đ • ${it.status} • ${it.time}</div>
                    </div>`;
                list.appendChild(row);
            }
        }
        async function markNotifsRead() {
            try {
                const body = new FormData();
                body.append('csrf_token', '<?php echo app_csrf_token(); ?>');
                await fetch('notifications_mark_read.php', { method: 'POST', body });
            } catch (err) {}
            const badge = document.getElementById('notif-badge');
            if (badge) badge.style.display = 'none';
        }
        function toggleNotif() {
            const nd = document.getElementById('notif-dropdown');
            nd.classList.toggle('show');
            if (nd.classList.contains('show')) {
                markNotifsRead();
            }
        }

        // Restore sidebar state
        window.addEventListener('load', () => {
            fetchNotifs();
            const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            // Force sidebar to be visible on page load
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
            
            // Then apply saved state if any
            if (collapsed) {
                setTimeout(() => {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                }, 100);
            }
            
            // Add event listener to toggle button
            const toggleBtn = document.querySelector('.toggle-sidebar');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
        });
    </script>
</head>
<body>
    <video id="bg-video" autoplay loop muted playsinline>
        <source src="img/sp/Gargantua_BGM.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <i class='bx bx-menu toggle-sidebar'></i>
                <div class="logo">  
                    <span>Quản lý NXB Kim Đồng</span>
                </div>
            </div>
            <div class="header-right">
                <div class="notif">
                    <i class='bx bx-bell notif-btn' onclick="toggleNotif()"></i>
                    <span id="notif-badge" class="notif-badge" style="display:none;">0</span>
                    <div id="notif-dropdown" class="notif-dropdown">
                        <div class="notif-head">
                            Thông báo mới
                            <a href="index.php?route=manage_orders" class="btn btn-sm btn-primary" style="padding:6px 12px;font-size:12px;">Xem tất cả</a>
                        </div>
                        <div id="notif-list" class="notif-list"></div>
                    </div>
                </div>
                <div class="user-menu" id="user-menu" onclick="toggleUserMenu()">
                    <div class="user-avatar">
                        <i class='bx bx-user'></i>
                    </div>
                    <span><?php echo isset($_SESSION['ten_tv']) ? htmlspecialchars($_SESSION['ten_tv']) : 'Admin'; ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <!-- DROPDOWN BÊN NGOÀI user-menu -->
                <div class="dropdown" id="user-dropdown">
                    <a href="index.php?route=account">
                        <i class='bx bx-user-circle'></i>
                        <span>Tài khoản</span>
                    </a>
                    <form method="post" action="logout.php" style="margin:0; padding:0;">
                        <?php echo app_csrf_field(); ?>
                        <button type="submit">
                            <i class='bx bx-log-out'></i>
                            <span>Đăng xuất</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar">
        <a href="index.php?route=dashboard" class="menu-item <?php echo ($route == 'dashboard') ? 'active' : ''; ?>">
            <i class='bx bx-tachometer'></i>
            <span>Dashboard</span>
        </a>

        <?php $is_catalog_active = in_array($route, ['addproducts', 'manage_products', 'manufacturers', 'categories', 'add_sale']); ?>
        <div class="menu-item <?php echo $is_catalog_active ? 'active' : ''; ?>" onclick="toggleSubmenu('catalog')">
            <i class='bx bx-box'></i>
            <span>Catalog</span>
            <i class='bx bx-chevron-down' style="margin-left: auto;"></i>
        </div>
        <div class="submenu <?php echo $is_catalog_active ? 'show' : ''; ?>" id="catalog">
            <a href="index.php?route=manage_products" class="submenu-item">Quản lý Sản phẩm</a>
            <a href="index.php?route=addproducts" class="submenu-item">Thêm Sản phẩm</a>
            <a href="index.php?route=add_sale" class="submenu-item">Thêm Sale</a>
            <a href="index.php?route=categories" class="submenu-item">Danh mục</a>
            <a href="index.php?route=manufacturers" class="submenu-item">Nhà sản xuất</a>
        </div>

        <?php $is_sales_active = in_array($route, ['manage_orders', 'customers']); ?>
        <div class="menu-item <?php echo $is_sales_active ? 'active' : ''; ?>" onclick="toggleSubmenu('sales')">
            <i class='bx bx-cart'></i>
            <span>Sales</span>
            <i class='bx bx-chevron-down' style="margin-left: auto;"></i>
        </div>
        <div class="submenu <?php echo $is_sales_active ? 'show' : ''; ?>" id="sales">
            <a href="index.php?route=manage_orders" class="submenu-item">Đơn hàng</a>
            <a href="index.php?route=customers" class="submenu-item">Khách hàng</a>
        </div>

        <a href="index.php?route=reports" class="menu-item <?php echo ($route == 'reports') ? 'active' : ''; ?>">
            <i class='bx bx-bar-chart-alt-2'></i>
            <span>Reports</span>
        </a>

        <a href="index.php?route=blockchain_audit" class="menu-item <?php echo ($route == 'blockchain_audit') ? 'active' : ''; ?>">
            <i class='bx bx-shield-quarter'></i>
            <span>Blockchain Audit</span>
        </a>

        <a href="index.php?route=comments" class="menu-item <?php echo ($route == 'comments') ? 'active' : ''; ?>">
            <i class='bx bx-message-square-dots'></i>
            <span>Bình luận</span>
        </a>    

        <?php $is_news_active = in_array($route, ['manage_news', 'add_news', 'edit_news']); ?>
        <div class="menu-item <?php echo $is_news_active ? 'active' : ''; ?>" onclick="toggleSubmenu('news')">
            <i class='bx bx-news'></i>
            <span>Tin tức</span>
            <i class='bx bx-chevron-down' style="margin-left: auto;"></i>
        </div>
        <div class="submenu <?php echo $is_news_active ? 'show' : ''; ?>" id="news">
            <a href="index.php?route=manage_news" class="submenu-item">Quản lý Tin tức</a>
            <a href="index.php?route=add_news" class="submenu-item">Thêm Tin tức</a>
        </div>
    </div>

    <div class="main-content">
        <div id="content-area">
