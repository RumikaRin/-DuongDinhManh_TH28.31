# Blockchain Audit Trail

Website đã được tích hợp lớp audit trail theo hướng blockchain-ready:

- MySQL vẫn là nguồn dữ liệu chính cho đơn hàng, sản phẩm, khách hàng và tài khoản.
- Mỗi hành động quan trọng được ghi vào `blockchain_audit_events`.
- Payload được chuẩn hóa, loại bỏ PII, sau đó hash SHA-256 thành `payload_hash`.
- Mỗi event có `previous_hash` và `event_hash` để tạo chuỗi hash local chống chỉnh sửa.
- Khi bật blockchain worker/RPC, hash này có thể được ghi lên smart contract `BookstoreAuditTrail`.
- Receipt on-chain được lưu ở `blockchain_receipts`.

## Cài đặt database

Chạy migration:

```powershell
D:\xampp\php\php.exe htdocs/database_migrate.php
```

Migration tạo hai bảng:

- `blockchain_audit_events`: lưu sự kiện local, hash, actor, status.
- `blockchain_receipts`: lưu tx hash, block, network, contract address sau khi ghi on-chain.

Nếu chưa cấu hình blockchain, hệ thống vẫn ghi audit local với status `disabled`.

## Cấu hình

Các biến môi trường hỗ trợ:

```env
BLOCKCHAIN_ENABLED=0
BLOCKCHAIN_NETWORK=local-disabled
BLOCKCHAIN_CHAIN_ID=0
BLOCKCHAIN_CONTRACT_ADDRESS=
BLOCKCHAIN_EXPLORER_BASE_URL=
```

Khi triển khai testnet/mainnet:

```env
BLOCKCHAIN_ENABLED=1
BLOCKCHAIN_NETWORK=sepolia
BLOCKCHAIN_CHAIN_ID=11155111
BLOCKCHAIN_CONTRACT_ADDRESS=0x...
BLOCKCHAIN_EXPLORER_BASE_URL=https://sepolia.etherscan.io/tx/
```

Lưu ý: source hiện có service ghi audit local và smart contract. Thành phần gửi transaction bằng RPC/private key nên chạy như worker riêng để tránh chặn request web.

## Dashboard admin

Vào admin, menu `Blockchain Audit` hoặc route:

```text
admin/index.php?route=blockchain_audit
```

Dashboard cho phép lọc theo entity/status và xem:

- entity/action
- actor
- status `disabled`, `pending`, `confirmed`, `failed`
- payload hash và event hash local
- tx hash/block nếu đã có receipt

## Các sự kiện đã được hook

- Đơn hàng: tạo đơn, cập nhật trạng thái.
- Sản phẩm: tạo, sửa, xóa, tạo từ sale.
- Sale: tạo, sửa, xóa, chuyển thành sản phẩm thường.
- Danh mục: tạo, sửa, xóa.
- Bình luận: gửi, phản hồi, xóa, đánh dấu hữu ích/bỏ đánh dấu.
- Tin tức: tạo, sửa, xóa.
- Người dùng: đăng ký, cập nhật hồ sơ, đổi mật khẩu.
- Khách hàng: tạo, xóa, đồng bộ từ đơn hàng.

## Chính sách dữ liệu

Audit không lưu raw PII vào payload:

- email, số điện thoại, địa chỉ
- mật khẩu/hash mật khẩu
- tên đăng nhập/tên người dùng raw
- nội dung bình luận/phản hồi/ghi chú

Actor name chỉ được đưa vào dạng hash. Payload on-chain chỉ nên dùng `payload_hash`, không đưa dữ liệu gốc lên chain.

## Smart contract

Contract nằm tại:

```text
contracts/BookstoreAuditTrail.sol
```

Contract hỗ trợ:

- owner quản lý recorder
- recorder được phép ghi proof
- emit `ProofRecorded(entityType, entityId, action, payloadHash, localAuditId)`

Triển khai đề xuất:

1. Deploy contract lên testnet.
2. Set địa chỉ contract vào `BLOCKCHAIN_CONTRACT_ADDRESS`.
3. Tạo worker đọc event `pending`.
4. Worker gọi `recordProof(...)`.
5. Worker cập nhật `blockchain_receipts` và đổi status event sang `confirmed` hoặc `failed`.
