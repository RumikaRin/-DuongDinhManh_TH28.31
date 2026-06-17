# HƯỚNG DẪN SỬ DỤNG FILE ĐỒ ÁN

## 📝 Các file đã tạo:

1. **DO_AN_WEBSITE_COMPLETE.html** - File đồ án hoàn chỉnh (Khuyên dùng)
2. **DO_AN_WEBSITE_BANSACH_P1.html** - Phần 1: Trang bìa và mục lục
3. **DO_AN_WEBSITE_BANSACH_P2.html** - Phần 2: Mở đầu và Chương I
4. **DO_AN_WEBSITE_BANSACH_P3.html** - Phần 3: Chương II và III
5. **DO_AN_WEBSITE_BANSACH_P4.html** - Phần 4: Kết luận và Tài liệu tham khảo

## 🔄 Cách chuyển sang Word (.docx):

### Cách 1: Mở trực tiếp bằng MS Word
1. Click chuột phải vào file `DO_AN_WEBSITE_COMPLETE.html`
2. Chọn **Open with** → **Microsoft Word**
3. Word sẽ tự động chuyển đổi HTML sang định dạng Word
4. Chọn **File** → **Save As** → Đặt tên file và chọn định dạng **.docx**

### Cách 2: Copy từ trình duyệt
1. Mở file `DO_AN_WEBSITE_COMPLETE.html` bằng Chrome/Firefox
2. Nhấn **Ctrl + A** để chọn tất cả
3. Nhấn **Ctrl + C** để copy
4. Mở Microsoft Word
5. Nhấn **Ctrl + V** để paste
6. Format lại nếu cần và lưu file

### Cách 3: Sử dụng LibreOffice (Miễn phí)
1. Tải LibreOffice từ https://www.libreoffice.org
2. Mở LibreOffice Writer
3. Chọn **File** → **Open** → Chọn file HTML
4. Chọn **File** → **Save As** → Chọn định dạng **.docx**

## 📋 Nội dung đồ án:

### **ĐỀ TÀI: WEBSITE BÁN SÁCH TRỰC TUYẾN**

✅ **Đã hoàn thành đầy đủ các phần:**

- **Mở đầu**: Giới thiệu về đề tài, lý do chọn đề tài
- **Chương I**: Tìm hiểu ngôn ngữ PHP và MySQL
- **Chương II**: Phân tích và thiết kế website
  - Phân tích yêu cầu chức năng/phi chức năng
  - Thiết kế hệ thống
  - Thiết kế database với 12 bảng
- **Chương III**: Xây dựng và triển khai
  - Giao diện người dùng (8 chức năng chính)
  - Giao diện quản trị (6 module quản lý)
  - Các chức năng kỹ thuật (8 chức năng core)
- **Kết luận**: 
  - Kết quả đạt được
  - Hạn chế
  - Hướng phát triển
- **Tài liệu tham khảo**

## 🎯 Đặc điểm nổi bật:

1. **Nội dung chi tiết**: Dựa trên code thực tế của project
2. **Database đầy đủ**: Mô tả 12 bảng với quan hệ rõ ràng
3. **Chức năng thực tế**: 
   - Đăng ký/Đăng nhập với password_hash()
   - Giỏ hàng với SESSION
   - Quản lý sản phẩm thường và khuyến mãi
   - Hệ thống bình luận với duyệt
   - Dashboard admin với thống kê
4. **Bảo mật**: Prepared statements, XSS protection
5. **Format chuẩn**: Theo mẫu đồ án học thuật

## 📚 Database Schema:

```
12 bảng chính:
- users (6 trường)
- sanpham (6 trường)  
- sales (8 trường)
- danhmuc (3 trường)
- donhang (9 trường)
- donhang_chitiet (4 trường)
- comments (9 trường)
- tintuc (5 trường)
- customers (6 trường)
- sanpham_danhmuc (2 trường)
- sales_danhmuc (2 trường)
- comment_helpful (3 trường)
```

## ⚠️ Lưu ý khi chỉnh sửa:

1. **Font chữ**: Giữ nguyên Times New Roman, size 13pt
2. **Line spacing**: 1.6 
3. **Margins**: 2.5cm mỗi bên
4. **Heading**: H1 (16pt), H2 (14pt), H3 (13pt)
5. **Thụt đầu dòng**: 1.5cm cho đoạn văn
6. **Căn lề**: Justify cho nội dung, Center cho tiêu đề

## 📌 Checklist trước khi nộp:

- [ ] Đổi tên file thành: **DuongDinhManh_TH28.31_DoAn.docx**
- [ ] Kiểm tra thông tin sinh viên (tên, MSV, lớp)
- [ ] Thêm tên GVHD nếu cần
- [ ] Kiểm tra số trang (khoảng 45-50 trang)
- [ ] In thử để kiểm tra format
- [ ] Export PDF để backup

## 💡 Tips:

- Có thể thêm screenshots của website vào phần Chương III
- Thêm biểu đồ ER từ file `database_diagram.html`
- Copy code mẫu từ các file PHP để làm ví dụ
- Tham khảo `database_schema.md` cho chi tiết database

---
**Chúc bạn hoàn thành tốt đồ án!** 🎓
