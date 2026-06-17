# 📊 Hướng dẫn Import Database

## 🔍 Vấn đề đã phát hiện

File SQL `manh (1).sql` **THIẾU** các bảng sau:
- `sanpham_danhmuc` (liên kết sản phẩm - danh mục)
- `sales_danhmuc` (liên kết sales - danh mục)
- `comments` (bình luận)
- `comment_helpful` (lượt thích bình luận)

Đây là nguyên nhân khiến sản phẩm không hiển thị trên web.

## ✅ Giải pháp

### Bước 1: Import file SQL chính

1. Mở **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Chọn database `manh` (hoặc tạo mới nếu chưa có)
3. Click tab **"Import"**
4. Chọn file: `manh (1).sql`
5. Click **"Go"** để import

### Bước 2: Tạo các bảng còn thiếu

Sau khi import xong file chính, bạn cần chạy file `create_missing_tables.sql`:

1. Trong phpMyAdmin, vẫn ở database `manh`
2. Click tab **"SQL"**
3. Copy toàn bộ nội dung file `create_missing_tables.sql`
4. Paste vào ô SQL
5. Click **"Go"**

**HOẶC** import trực tiếp:
1. Click tab **"Import"**
2. Chọn file: `create_missing_tables.sql`
3. Click **"Go"**

### Bước 3: Kiểm tra

Sau khi hoàn thành, kiểm tra xem các bảng đã được tạo:

```sql
SHOW TABLES;
```

Phải có các bảng:
- ✅ `sanpham_danhmuc`
- ✅ `sales_danhmuc`
- ✅ `comments`
- ✅ `comment_helpful`

## 🎯 Kết quả

Sau khi hoàn thành các bước trên:
- ✅ Website sẽ hiển thị sản phẩm từ database
- ✅ Code đã được cập nhật để tự động fallback nếu bảng không tồn tại
- ✅ Sản phẩm sẽ hiển thị ngay cả khi bảng `sanpham_danhmuc` rỗng

## 🐛 Nếu vẫn không hiển thị

1. **Kiểm tra file debug**: Truy cập `http://localhost/[thư-mục]/debug_products.php` để xem chi tiết lỗi
2. **Kiểm tra error log**: Xem file log của PHP để biết lỗi cụ thể
3. **Kiểm tra kết nối**: Đảm bảo `dbconnect.php` có thông tin đúng

## 📝 Lưu ý

- Các bảng `sanpham_danhmuc` và `sales_danhmuc` có thể rỗng (không có dữ liệu), điều này không ảnh hưởng đến việc hiển thị sản phẩm
- Code đã được cập nhật để tự động chuyển sang query đơn giản nếu JOIN thất bại
- Bảng `comments` và `comment_helpful` chỉ cần thiết nếu bạn muốn sử dụng tính năng bình luận
