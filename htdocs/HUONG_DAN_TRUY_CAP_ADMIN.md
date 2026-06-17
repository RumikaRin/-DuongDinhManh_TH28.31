# 🔐 Hướng dẫn truy cập Admin Panel

## ❌ Vấn đề hiện tại

Khi truy cập `localhost/admin` hoặc `localhost/DuongDinhManh_TH28.31/admin`, bạn thấy trang **XAMPP Dashboard** thay vì trang **Admin Login** của website.

---

## ✅ Giải pháp

### **Bước 1: Đảm bảo thư mục dự án trong htdocs**

Thư mục dự án phải nằm trong: `C:\xampp\htdocs\DuongDinhManh_TH28.31`

**Kiểm tra:**
- Mở File Explorer
- Đi đến: `C:\xampp\htdocs\`
- Xem có thư mục `DuongDinhManh_TH28.31` không
- Trong thư mục đó phải có thư mục `admin`

**Nếu chưa có:**
1. Copy toàn bộ thư mục dự án vào `C:\xampp\htdocs\`
2. Hoặc tạo Symbolic Link (xem hướng dẫn trước)

---

### **Bước 2: Truy cập đúng URL**

**URL đúng để vào Admin:**

```
http://localhost/DuongDinhManh_TH28.31/admin/login.php
```

Hoặc:

```
http://localhost/DuongDinhManh_TH28.31/admin/
```

**URL SAI (sẽ ra trang XAMPP):**
- ❌ `http://localhost/admin` 
- ❌ `http://localhost/dashboard/`
- ❌ `http://localhost/phpmyadmin/` (đây là phpMyAdmin, không phải admin website)

---

### **Bước 3: Kiểm tra Apache đang chạy**

1. Mở **XAMPP Control Panel**
2. Đảm bảo **Apache** đang chạy (màu xanh)
3. Nếu chưa chạy → Nhấn nút **Start**

---

## 📋 Các URL quan trọng

### **Website chính:**
```
http://localhost/DuongDinhManh_TH28.31/index.php
http://localhost/DuongDinhManh_TH28.31/
```

### **Admin Panel:**
```
http://localhost/DuongDinhManh_TH28.31/admin/login.php
http://localhost/DuongDinhManh_TH28.31/admin/
```

### **Admin Dashboard (sau khi đăng nhập):**
```
http://localhost/DuongDinhManh_TH28.31/admin/index.php?route=dashboard
```

### **phpMyAdmin (quản lý database):**
```
http://localhost/phpmyadmin
```

---

## 🔑 Thông tin đăng nhập Admin

**Tài khoản mặc định:**
- **Username:** `admin`
- **Password:** `admin123`

*(Theo file database_create.sql)*

---

## 🚨 Xử lý lỗi thường gặp

### **Lỗi 1: Trang trắng hoặc lỗi PHP**

**Nguyên nhân:** Database chưa được tạo

**Giải pháp:**
1. Truy cập: `http://localhost/phpmyadmin`
2. Tạo database tên: `manh`
3. Import file: `database_create.sql`

---

### **Lỗi 2: "Kết nối database thất bại"**

**Nguyên nhân:** MySQL chưa chạy

**Giải pháp:**
1. XAMPP Control Panel → Start **MySQL**
2. Đợi đến khi hiển thị màu xanh

---

### **Lỗi 3: "404 Not Found"**

**Nguyên nhân:** Thư mục không nằm trong htdocs

**Giải pháp:**
1. Copy thư mục vào `C:\xampp\htdocs\`
2. Hoặc tạo Symbolic Link

---

### **Lỗi 4: Redirect về login.php liên tục**

**Nguyên nhân:** Session không hoạt động

**Giải pháp:**
1. Xóa cookies trình duyệt
2. Thử trình duyệt khác
3. Kiểm tra file `dbconnect.php` có lỗi không

---

## ✅ Checklist

- [ ] Thư mục dự án nằm trong `C:\xampp\htdocs\DuongDinhManh_TH28.31`
- [ ] Apache đang chạy (màu xanh trong XAMPP Control Panel)
- [ ] MySQL đang chạy (màu xanh trong XAMPP Control Panel)
- [ ] Database `manh` đã được tạo
- [ ] Đã import file `database_create.sql`
- [ ] Truy cập đúng URL: `http://localhost/DuongDinhManh_TH28.31/admin/login.php`
- [ ] Thấy trang đăng nhập Admin (không phải trang XAMPP)

---

## 🎯 Quick Start

1. **Mở XAMPP Control Panel** → Start Apache + MySQL
2. **Truy cập:** `http://localhost/DuongDinhManh_TH28.31/admin/login.php`
3. **Đăng nhập:**
   - Username: `admin`
   - Password: `admin123`
4. **Vào Dashboard:** Sẽ tự động chuyển đến `index.php?route=dashboard`

---

**Lưu ý:** Nếu vẫn thấy trang XAMPP, đảm bảo bạn đang truy cập đúng URL với đường dẫn đầy đủ đến thư mục dự án.
