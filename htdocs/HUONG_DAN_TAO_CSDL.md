# 📊 Hướng dẫn tạo lại Cơ sở dữ liệu

## ✅ File đã được tạo lại

File `database_create.sql` đã được tạo lại dựa trên phân tích toàn bộ code trong dự án.

## 🔍 Những thay đổi chính

### 1. **Bảng `comments` - Đã cập nhật đúng cấu trúc**
- ✅ Cột `username` (lưu tên người dùng)
- ✅ Cột `content` (không phải `comment`)
- ✅ Cột `reply` (phản hồi từ admin)
- ✅ Cột `reply_by`, `reply_date` (thông tin phản hồi)
- ✅ Cột `helpful_count` (số lượt thích)
- ✅ Cột `updated_at` (ngày cập nhật)

### 2. **Bảng `donhang` - Đã thêm cột email**
- ✅ Cột `email` (được thêm trong code `xuly_thanhtoan.php`)

### 3. **Bảng `sanpham` - Cột tùy chọn**
- ✅ Cột `id_dm` (có thể được thêm sau, không bắt buộc)

### 4. **Views bổ sung**
- ✅ `v_all_products` - Tổng hợp tất cả sản phẩm (sanpham + sales)

## 📋 Cách sử dụng

### **Cách 1: Import qua phpMyAdmin (Khuyên dùng)**

1. **Mở phpMyAdmin:**
   ```
   http://localhost/phpmyadmin
   ```

2. **Tạo database mới (nếu chưa có):**
   - Click tab **"Databases"**
   - Nhập tên: `manh`
   - Chọn Collation: `utf8mb4_unicode_ci`
   - Click **"Create"**

3. **Import file SQL:**
   - Chọn database `manh` ở sidebar
   - Click tab **"Import"**
   - Click **"Choose File"** → Chọn file `database_create.sql`
   - Click **"Go"**

4. **Kiểm tra:**
   - Xem có bao nhiêu bảng được tạo (phải có 12 bảng)
   - Kiểm tra dữ liệu mẫu đã được insert chưa

---

### **Cách 2: Import qua MySQL Command Line**

1. **Mở Command Prompt hoặc PowerShell**

2. **Chuyển đến thư mục chứa file SQL:**
   ```bash
   cd "D:\file cần backup\Đồ Án tổng hợp\DuongDinhManh_TH28.31"
   ```

3. **Chạy lệnh import:**
   ```bash
   mysql -u root -p manh < database_create.sql
   ```
   (Nhập mật khẩu MySQL, mặc định XAMPP là trống → Enter)

---

### **Cách 3: Chạy SQL trực tiếp trong phpMyAdmin**

1. Mở phpMyAdmin
2. Chọn database `manh`
3. Click tab **"SQL"**
4. Copy toàn bộ nội dung file `database_create.sql`
5. Paste vào ô SQL
6. Click **"Go"**

---

## 🔄 Nếu database đã tồn tại

### **Option 1: Xóa và tạo lại (Mất dữ liệu cũ)**

```sql
DROP DATABASE IF EXISTS manh;
-- Sau đó chạy lại database_create.sql
```

### **Option 2: Chỉ cập nhật các bảng thiếu**

File SQL sử dụng `CREATE TABLE IF NOT EXISTS`, nên có thể chạy lại an toàn. Tuy nhiên, nếu cấu trúc đã thay đổi, cần:

1. **Backup database hiện tại:**
   - phpMyAdmin → Export → Go

2. **Chạy các lệnh ALTER TABLE:**
   ```sql
   -- Thêm cột email vào donhang (nếu chưa có)
   ALTER TABLE donhang ADD COLUMN email VARCHAR(255) NULL AFTER diachi;
   
   -- Cập nhật bảng comments (nếu cần)
   ALTER TABLE comments ADD COLUMN username VARCHAR(100) NOT NULL AFTER user_id;
   ALTER TABLE comments ADD COLUMN content TEXT NOT NULL AFTER rating;
   ALTER TABLE comments ADD COLUMN reply TEXT NULL;
   ALTER TABLE comments ADD COLUMN reply_by VARCHAR(100) NULL;
   ALTER TABLE comments ADD COLUMN reply_date TIMESTAMP NULL;
   ALTER TABLE comments ADD COLUMN helpful_count INT DEFAULT 0;
   ALTER TABLE comments ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
   ```

---

## ✅ Kiểm tra sau khi tạo

### **1. Kiểm tra số lượng bảng:**
```sql
SHOW TABLES;
```
Phải có 12 bảng:
- users
- danhmuc
- sanpham
- sales
- sanpham_danhmuc
- sales_danhmuc
- donhang
- donhang_chitiet
- tintuc
- comments
- comment_helpful
- customers

### **2. Kiểm tra cấu trúc bảng comments:**
```sql
DESCRIBE comments;
```
Phải có các cột: `username`, `content`, `reply`, `reply_by`, `reply_date`, `helpful_count`

### **3. Kiểm tra dữ liệu mẫu:**
```sql
SELECT * FROM danhmuc;  -- Phải có 8 danh mục
SELECT * FROM users WHERE ten_tv = 'admin';  -- Phải có tài khoản admin
```

### **4. Kiểm tra Views:**
```sql
SHOW FULL TABLES WHERE Table_type = 'VIEW';
```
Phải có 3 views:
- v_bestsellers
- v_revenue_by_month
- v_all_products

---

## 🔑 Thông tin đăng nhập Admin

Sau khi import, bạn có thể đăng nhập admin với:
- **Username:** `admin`
- **Password:** `admin123`

---

## ⚠️ Lưu ý quan trọng

1. **Backup trước khi import:** Nếu database đã có dữ liệu, hãy backup trước
2. **Charset:** Tất cả bảng sử dụng `utf8mb4` để hỗ trợ tiếng Việt
3. **Foreign Keys:** Đảm bảo thứ tự tạo bảng đúng (users trước, sau đó các bảng khác)
4. **Auto Increment:** Bảng `sanpham` và `sales` KHÔNG dùng AUTO_INCREMENT cho id (theo code)

---

## 🐛 Xử lý lỗi

### **Lỗi: "Table already exists"**
- File SQL dùng `IF NOT EXISTS`, nên an toàn
- Nếu muốn tạo lại, xóa bảng cũ trước

### **Lỗi: "Foreign key constraint fails"**
- Kiểm tra bảng `users` đã được tạo chưa
- Đảm bảo thứ tự tạo bảng đúng

### **Lỗi: "Unknown column 'content' in 'comments'"**
- Bảng comments cũ có thể dùng tên cột khác
- Chạy ALTER TABLE để đổi tên cột:
  ```sql
  ALTER TABLE comments CHANGE COLUMN comment content TEXT NOT NULL;
  ```

---

## 📞 Hỗ trợ

Nếu gặp vấn đề, kiểm tra:
1. MySQL đang chạy (XAMPP Control Panel)
2. Database `manh` đã được tạo
3. File SQL không bị lỗi cú pháp
4. Quyền truy cập MySQL đúng (root, mật khẩu trống)
