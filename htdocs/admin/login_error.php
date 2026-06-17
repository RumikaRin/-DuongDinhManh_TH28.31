<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
require_once __DIR__ . '/../includes/app.php';
$message = isset($_GET['message']) ? $_GET['message'] : 'Sai tài khoản hoặc mật khẩu. Vui lòng thử lại!';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ADMIN - Đăng nhập thất bại</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        #bg-video {
            position: fixed;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%);
            z-index: -1;
            object-fit: cover;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 0;
        }

        .login-container {
            width: 100%;
            max-width: 480px;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .login-panel {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            padding: 48px 40px;
            animation: shake 0.6s ease-out;
        }

        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            10%, 30%, 50%, 70%, 90% {
                transform: translateX(-8px);
            }
            20%, 40%, 60%, 80% {
                transform: translateX(8px);
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 32px;
        }

        .error-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: rgba(239, 68, 68, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(239, 68, 68, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .error-icon i {
            font-size: 40px;
            color: #ef4444;
        }

        .logo-section h1 {
            font-size: 42px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }

        .logo-section p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
            margin-bottom: 4px;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .error-message i {
            font-size: 24px;
            color: #ef4444;
            flex-shrink: 0;
        }

        .error-message p {
            color: #ffffff;
            font-size: 15px;
            margin: 0;
            line-height: 1.5;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            font-size: 20px;
        }

        .input-group input {
            width: 100%;
            padding: 16px 18px 16px 52px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: #ffffff;
            font-size: 15px;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
        }

        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .input-group input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            font-size: 20px;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: #ffffff;
        }

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .forgot-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .forgot-link:hover {
            color: #ffffff;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, rgb(255, 228, 145) 0%, rgb(97, 76, 0) 100%);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.6);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 28px 0;
            color: rgba(255, 255, 255, 0.6);
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
        }

        .divider span {
            padding: 0 16px;
        }

        .social-login {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .social-btn {
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.9);
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .social-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
        }

        .signup-link {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        .signup-link a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            margin-left: 4px;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 24px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 13px;
        }

        .help-text {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 12px;
            padding: 16px;
            margin-top: 20px;
        }

        .help-text p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin: 0 0 8px 0;
            line-height: 1.5;
        }

        .help-text ul {
            margin: 8px 0 0 20px;
            padding: 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
        }

        .help-text li {
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <video id="bg-video" autoplay loop muted playsinline>
        <source src="img/sp/Gargantua_BGM.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    
    <div class="login-container">
        <div class="login-panel">
            <div class="logo-section">
                <div class="error-icon">
                    <i class='bx bx-error-circle'></i>
                </div>
            </div>

            <div class="error-message">
                <i class='bx bx-x-circle'></i>
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>

            <form method="post" action="xuly_admin_login.php">
                <?php echo app_csrf_field(); ?>
                <div class="input-group">
                    <i class='bx bx-envelope'></i>
                    <input type="text" name="ten_tv" placeholder="Email address or Username" required autofocus />
                </div>

                <div class="input-group">
                    <i class='bx bx-lock-alt'></i>
                    <input type="password" id="password" name="mk_tv" placeholder="Password" required />
                    <i class='bx bx-hide password-toggle' id="togglePassword"></i>
                </div>

                <div class="options">
                    <label class="remember-me">
                        <input type="checkbox" />
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="submit-btn">Đăng Nhập</button>

                <div class="help-text">
                    <p><i class='bx bx-info-circle'></i> <strong>Gợi ý:</strong></p>
                    <ul>
                        <li>Kiểm tra lại tên đăng nhập và mật khẩu</li>
                        <li>Đảm bảo Caps Lock không bật</li>
                    </ul>
                </div>

                <div class="divider">
                    <span>Liên hệ</span>
                </div>

                <div class="social-login">
                    <button type="button" class="social-btn">
                        <i class='bx bxl-chrome'></i>
                    </button>
                    <button type="button" class="social-btn">
                        <i class='bx bxl-twitter'></i>
                    </button>
                    <button type="button" class="social-btn">
                        <i class='bx bxl-discord-alt'></i>
                    </button>
                </div>

            </form>

            <div class="footer">
                © 2025 Dương Đình Mạnh!
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            
            this.classList.toggle('bx-hide');
            this.classList.toggle('bx-show');
        });

        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = this.querySelector('.submit-btn');
            btn.style.opacity = '0.7';
            btn.textContent = 'Đang xử lý...';
        });

        // Auto focus vào input đầu tiên
        document.querySelector('input[name="ten_tv"]').focus();
    </script>
</body>
</html>
