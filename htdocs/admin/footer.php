</div>
    </div>

    <footer style="
        position: fixed;
        bottom: 0;
        left: 260px;
        right: 0;
        height: 50px;
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(20px);
        border-top: 1px solid rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 24px;
        z-index: 998;
    ">
        <div style="color: rgba(255, 255, 255, 0.7); font-size: 14px;">
            © 2025 Dương Đình Mạnh - Admin Dashboard
        </div>
        <div style="display: flex; gap: 16px; align-items: center;">
            <a href="https://discord.gg/ohshit8960" target="_blank" style="
                color: rgba(255, 255, 255, 0.7);
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 6px;
                transition: color 0.3s;
                font-size: 14px;
            " onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.7)'">
                <i class='bx bxl-discord-alt' style="font-size: 18px;"></i>
                <span>Liên hệ</span>
            </a>
            <span style="color: rgba(255, 255, 255, 0.4);">|</span>
            <span style="color: rgba(255, 255, 255, 0.7); font-size: 13px;">
                Version 1.0
            </span>
        </div>
    </footer>

    <script>
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            // Close notification dropdown
            const notif = document.querySelector('.notif');
            const notifDropdown = document.getElementById('notif-dropdown');
            if (notif && notifDropdown && !notif.contains(e.target)) {
                notifDropdown.classList.remove('show');
            }
        });

        // Smooth scroll for sidebar links
        document.querySelectorAll('.menu-item, .submenu-item').forEach(item => {
            if (item.tagName === 'A') {
                item.addEventListener('click', function(e) {
                    // Add loading state
                    const mainContent = document.querySelector('.main-content');
                    if (mainContent) {
                        mainContent.style.opacity = '0.6';
                        setTimeout(() => {
                            mainContent.style.opacity = '1';
                        }, 300);
                    }
                });
            }
        });

        // Auto-hide notifications after 10 seconds
        let notifTimeout;
        function toggleNotif() {
            const nd = document.getElementById('notif-dropdown');
            nd.classList.toggle('show');
            
            if (nd.classList.contains('show')) {
                markNotifsRead();
                
                // Clear existing timeout
                if (notifTimeout) clearTimeout(notifTimeout);
                
                // Set new timeout to auto-close
                notifTimeout = setTimeout(() => {
                    nd.classList.remove('show');
                }, 10000);
            } else {
                // Clear timeout if manually closed
                if (notifTimeout) clearTimeout(notifTimeout);
            }
        }

        // Enhance table row hover effect
        document.addEventListener('DOMContentLoaded', function() {
            const tables = document.querySelectorAll('table tbody tr');
            tables.forEach(row => {
                row.style.transition = 'all 0.3s ease';
            });
        });

        // Add animation to stat cards on load
        window.addEventListener('load', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animation = `slideIn 0.6s ease-out ${index * 0.1}s both`;
            });
        });

        // Responsive sidebar toggle for mobile - REMOVED TO AVOID CONFLICT
        // Function moved to header.php to avoid duplicate definitions

        // Handle window resize - Updated to work with CSS classes
        window.addEventListener('resize', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const footer = document.querySelector('footer');
            
            if (window.innerWidth > 768) {
                // Desktop: Use CSS classes
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
            } else {
                // Mobile: Use CSS classes
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
        });

        // Add keyframe animation for stat cards
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            /* Mobile menu button */
            @media (max-width: 768px) {
                .logo::before {
                    content: '☰';
                    margin-right: 12px;
                    font-size: 24px;
                    cursor: pointer;
                }
                
                footer {
                    left: 0 !important;
                }
            }
        `;
        document.head.appendChild(style);

        // Add mobile menu toggle on logo click - Updated to use CSS classes
        if (window.innerWidth <= 768) {
            document.querySelector('.logo').addEventListener('click', function() {
                const sidebar = document.querySelector('.sidebar');
                const mainContent = document.querySelector('.main-content');
                
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            });
        }
    </script>
</body>
</html>
<?php
// Close database connection
if (isset($ketnoi)) {
    $ketnoi->close();
}
?>