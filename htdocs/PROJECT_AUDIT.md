# Báo cáo audit và tối ưu dự án

Ngày thực hiện: 15/06/2026

## Tổng quan

Dự án là website nhà sách PHP thuần, dùng MariaDB/MySQL, JavaScript và CSS thuần. Audit ban đầu ghi nhận 88 file PHP, nhiều view được include qua `index.php`, schema database không đồng bộ với code, routing sai thư mục và nhiều thao tác thay đổi dữ liệu chưa được bảo vệ.

## Lỗi gốc đã sửa

### Danh mục không hoạt động

- Database ban đầu không có dữ liệu trong `danhmuc`, `sanpham_danhmuc`, `sales_danhmuc`.
- `header.php` cũ tự tạo bảng và gán cứng danh mục mỗi lần render.
- Router tìm view ở `htdocs/*.php` trong khi trang thật nằm tại `htdocs/views/*.php`.

Đã sửa bằng migration idempotent, helper slug dùng chung, router allowlist trỏ đúng `views/`, dropdown semantic và trạng thái rỗng rõ ràng. Database hiện có 8 danh mục, 20 liên kết sản phẩm và 8 liên kết sản phẩm sale.

### Cấu trúc và runtime

- Loại bỏ runtime DDL khỏi header, bình luận, checkout và admin.
- Chuẩn hóa kết nối database qua `dbconnect.php` và biến môi trường.
- Chuẩn hóa loại sản phẩm bình luận thành `sanpham` / `sale`.
- Chuyển các view được include thành fragment hợp lệ, không còn tài liệu HTML lồng nhau.
- Sửa toàn bộ đường dẫn `handlers/` không tồn tại ở giỏ hàng, thanh toán, đăng nhập, đăng ký và cài đặt.
- Tạo ba reporting view: `v_bestsellers`, `v_revenue_by_month`, `v_all_products`.

### Bảo mật và toàn vẹn dữ liệu

- Thêm helper xác thực admin, CSRF, quantity, product type, shipping fee và JSON response.
- Toàn bộ mutation chính dùng POST + CSRF: giỏ hàng, bình luận, thanh toán, tài khoản, đăng nhập/đăng ký/đăng xuất và thao tác admin.
- Chỉ tài khoản `admin` được vào quản trị; đăng nhập thành công regenerate session ID.
- Khóa file debug admin từng tự giả mạo session đăng nhập.
- Các thao tác xóa/chuyển đổi admin không còn dùng destructive GET.
- Checkout tính lại giá phía server, giới hạn số lượng 1-99, lọc item hợp lệ và dùng transaction.

## Redesign giao diện

Hướng thiết kế: nhà xuất bản Việt hiện đại, nền giấy sáng, chữ mực đậm, đỏ Kim Đồng làm màu nhấn.

- Header hai tầng với tìm kiếm, điều hướng, tài khoản và danh mục truy cập bằng bàn phím.
- Hero editorial mới, không phụ thuộc Swiper CDN.
- Hệ thống card sản phẩm, section, category, news, footer và responsive được thống nhất trong `redesign.css`.
- Viết lại trang đăng nhập/đăng ký theo layout chung.
- Thêm focus state, skip link và reduced-motion support.
- Loại bỏ HTML lồng nhau, script trùng, console debug và preload font không sử dụng.

## Kết quả kiểm thử

- Regression tests: **85 passed, 0 failed**.
- PHP lint: **88 file, 0 lỗi cú pháp**.
- Database: **12/12 bảng yêu cầu**, **3/3 reporting view**, không thiếu schema.
- HTTP smoke test: trang chủ, danh mục, sản phẩm, sale, tin tức, video, đăng nhập, đăng ký và admin login đều trả `200`, không có warning/fatal.
- Access smoke test: trang admin trực tiếp redirect về login; POST thiếu CSRF trả `419`; trang thanh toán/hồ sơ chưa đăng nhập trả `302`.
- Visual QA: đã chụp và kiểm tra desktop/mobile bằng Chrome headless.

## Phương án tối ưu tiếp theo

1. Tách dần CSS legacy trong `style.css` theo component rồi xóa các rule trùng; hiện `redesign.css` là lớp thiết kế chuẩn ưu tiên.
2. Tách truy vấn sản phẩm/card thành repository và partial dùng chung để giảm trùng giữa home, sản phẩm, sale và danh mục.
3. Bổ sung integration test có session/database riêng cho đăng nhập, checkout và CRUD admin.
4. Xóa hẳn các tiện ích debug khỏi bản production và cấu hình web server chặn truy cập file migration/check database.
5. Thêm phân trang phía server cho sản phẩm và cache query danh mục khi dữ liệu tăng lớn.

## Sao lưu

Database trước tối ưu đã được lưu tại `backup_before_optimization_2026-06-15.sql`.
