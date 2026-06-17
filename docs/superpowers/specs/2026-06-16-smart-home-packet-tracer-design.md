# Thiết kế dự án Smart Home trên Cisco Packet Tracer

Ngày: 2026-06-16
Báo cáo nguồn: `C:/Users/sansm/Downloads/DuongDinhManhTH28.31/DuongDinhManhTH28.31-CNN4.0.docx`
Vị trí Cisco Packet Tracer: `D:/Cisco Packet Tracer 9.0.0/bin/PacketTracer.exe`

## Mục tiêu

Tạo một bộ tài liệu dựng dự án Cisco Packet Tracer để tái hiện mô hình mạng nhà thông minh được mô tả trong báo cáo "Phân tích và mô phỏng nhà thông minh bằng công cụ Cisco Packet Tracer".

Bộ tài liệu cuối cùng phải giúp người dùng mở Cisco Packet Tracer 9.0.0, đặt đúng thiết bị, cấu hình IP/dịch vụ, kết nối các thiết bị IoT về Home Gateway, kiểm thử hệ thống và lưu thành file `.pkt` có thể nộp.

Do file `.pkt` của Packet Tracer là định dạng nhị phân riêng của Cisco, dự án này không sinh file `.pkt` bằng cách chỉnh sửa text/script. Cách làm đáng tin cậy là tạo bộ hướng dẫn và cấu hình đầy đủ để người dùng nhập trong Packet Tracer, sau đó lưu lại thành file `.pkt` cuối cùng.

## Phạm vi

Dự án sẽ mô phỏng hệ thống nhà thông minh trong báo cáo ở mức thực hành phù hợp để chấm bài:

- Mạng nhà thông minh trung tâm dựa trên các hình 2.1, 3.9, 3.10 và 3.11 trong báo cáo.
- Đường kết nối lõi: Internet/Cloud -> Home Router/Home Gateway -> Switch -> PC điều khiển, DNS Server, IoT/Web Server.
- Các phân hệ IoT trong báo cáo:
  - kiểm soát cửa ra vào thông minh,
  - điều hòa và thông gió thông minh,
  - giám sát năng lượng mặt trời,
  - chiếu sáng thông minh,
  - phát hiện cháy và kích hoạt vòi phun nước,
  - giám sát an ninh bằng camera và báo động,
  - tưới cây thông minh,
  - mô phỏng thang máy nội bộ,
  - phòng điều khiển trung tâm/dashboard.
- Địa chỉ IP tĩnh của server giữ đúng theo báo cáo:
  - DNS Server: `192.168.2.2`
  - IoT/Web Server: `192.168.2.3`
- Home Gateway, thiết bị IoT, PC điều khiển và thiết bị di động/văn phòng dùng DHCP, trừ khi Packet Tracer yêu cầu IP tĩnh cho một tình huống minh họa cụ thể.

Bộ dựng không cố gắng đặt đủ toàn bộ "hơn 100" thiết bị IoT riêng lẻ. Thay vào đó, mỗi phân hệ sẽ có thiết bị đại diện đầy đủ chức năng, kèm ghi chú mở rộng số lượng để khớp báo cáo nếu cần.

## Kiến trúc

Topology dùng mô hình 3 lớp logic:

1. Lớp thiết bị: cảm biến, thiết bị chấp hành, thiết bị gia dụng thông minh, camera, khóa cửa, báo động, vòi phun nước, thiết bị năng lượng mặt trời và thiết bị mô phỏng thang máy.
2. Lớp gateway/access: Home Gateway/Home Router và Switch. Thiết bị IoT không dây đăng ký về gateway; server và PC có dây kết nối qua switch.
3. Lớp ứng dụng: DNS Server và IoT/Web Server cung cấp phân giải tên miền, dashboard và điểm đăng ký IoT. PC điều khiển và thiết bị di động truy cập dashboard qua HTTP/tên miền.

Canvas chính trong Packet Tracer sẽ được chia thành các khu vực có nhãn:

- Lõi mạng và phòng điều khiển
- Cửa thông minh
- Điều hòa và thông gió
- Năng lượng mặt trời
- Chiếu sáng
- Phòng cháy chữa cháy
- An ninh
- Tưới cây
- Thang máy

Cách bố trí này bám theo screenshot trong báo cáo nhưng vẫn giữ mô hình gọn, dễ đọc và dễ chấm.

## Kế hoạch thiết bị

Thiết bị lõi:

- 1 Home Router hoặc Home Gateway
- 1 Switch-PT
- 1 Cloud/Internet nếu phiên bản Packet Tracer hỗ trợ phù hợp
- 1 Cable Modem nếu mô hình Packet Tracer đang dùng cần thiết bị này
- 1 PC điều khiển
- 1 DNS Server
- 1 IoT/Web Server
- 1 Central Office Server hoặc thiết bị truy cập di động tùy chọn để minh họa điều khiển từ xa

Thiết bị IoT đại diện:

- Cửa thông minh: Smart Door Lock, keypad/card reader hoặc RFID reader, cảm biến chuyển động/PIR, IP Camera, Alarm.
- Điều hòa và thông gió: Temperature Sensor, Smart AC, Ventilation Fan/Blower, CO2 Sensor hoặc Air Quality Sensor, Air Damper.
- Năng lượng mặt trời: Solar Panel, Charge Controller, Battery, Inverter, Power/Energy Meter và một vài tải tiêu thụ.
- Chiếu sáng: 4 đến 6 Smart Light, Motion Sensor, Light Sensor/Photo Sensor.
- Phòng cháy chữa cháy: Smoke Sensor, Heat/Fire Sensor, Fire Alarm/Siren, Sprinkler, Exit Light.
- An ninh: 2 đến 4 IP Camera, Motion Sensor, Door/Window Sensor, Siren/Alarm.
- Tưới cây: Soil Moisture Sensor, Rain Sensor, Water Pump, Solenoid Valve, Sprinkler.
- Thang máy: Elevator Panel/Button, Door Sensor, Motor, Indicator/Light, Alarm.

Ghi chú mở rộng sẽ liệt kê các số lượng lớn hơn theo báo cáo, ví dụ 20 Smart LED, 10 cảm biến PIR, 8 cảm biến nhiệt độ/độ ẩm, 8 cảm biến khói, 8 cảm biến lửa, 12 vòi phun nước và 4 van tưới.

## Địa chỉ IP và dịch vụ

Mạng LAN chính: `192.168.2.0/24`

Bảng địa chỉ đề xuất:

| Thiết bị | Cách cấp địa chỉ | IP |
| --- | --- | --- |
| LAN của Home Router/Gateway | Tĩnh | `192.168.2.1` |
| DNS Server | Tĩnh | `192.168.2.2` |
| IoT/Web Server | Tĩnh | `192.168.2.3` |
| PC điều khiển | DHCP | Cấp từ router/gateway |
| Home Gateway, nếu tách riêng khỏi router | DHCP hoặc đặt trước IP | Cấp từ router/gateway |
| Thiết bị IoT | DHCP | Cấp từ router/gateway |
| Mobile/Central Office client | DHCP | Cấp từ router/gateway hoặc WAN mô phỏng |

Dịch vụ cần bật:

- DHCP trên Home Router/Gateway cho mạng LAN `192.168.2.0/24`.
- Bản ghi DNS `www.smarthome.local` trỏ về `192.168.2.3`.
- HTTP trên IoT/Web Server.
- Dịch vụ IoT Registration nếu Packet Tracer hỗ trợ trong mô hình đang dùng.
- Minh họa MQTT tùy chọn nếu file mẫu IoT của Packet Tracer 9.0.0 hỗ trợ ổn định.

## Quy tắc tự động hóa

Hướng dẫn sẽ có quy tắc IoT hoặc logic kiểu Blockly cho các kịch bản chính trong báo cáo:

- Chiếu sáng: nếu phát hiện chuyển động và ánh sáng môi trường thấp thì bật đèn; nếu không còn chuyển động thì tắt đèn sau một khoảng thời gian.
- Điều hòa: nếu nhiệt độ từ `30 C` trở lên thì bật điều hòa và quạt; nếu nhiệt độ giảm về `24 C` hoặc thấp hơn thì tắt điều hòa.
- Phòng cháy chữa cháy: nếu mức khói cao thì bật báo động; nếu xác nhận cháy/nhiệt cao thì bật vòi phun nước và đèn thoát hiểm.
- An ninh: nếu phát hiện chuyển động trái phép hoặc cửa/cửa sổ bị mở bất thường thì bật còi báo động và camera giám sát.
- Tưới cây: nếu độ ẩm đất dưới `30%` thì mở van hoặc bật bơm; dừng khi độ ẩm phục hồi.
- Cửa thông minh: xác thực thành công thì mở khóa; xác thực sai nhiều lần thì kích hoạt báo động.

Các quy tắc phải đủ đơn giản để nhập thủ công trong Packet Tracer, đồng thời dùng ảnh trích xuất từ báo cáo làm tham chiếu trực quan.

## Sản phẩm bàn giao

Sau khi thiết kế này được duyệt, bước triển khai sẽ tạo:

- `docs/packet_tracer/smart_home_build_guide.md`: hướng dẫn dựng mô hình Packet Tracer từng bước.
- `docs/packet_tracer/device_inventory.md`: danh sách thiết bị, số lượng, khu vực đặt thiết bị và số lượng mở rộng tùy chọn.
- `docs/packet_tracer/ip_addressing_and_services.md`: kế hoạch IP, DHCP, DNS, HTTP và dịch vụ IoT.
- `docs/packet_tracer/automation_rules.md`: logic IoT/Blockly cần nhập trong Packet Tracer.
- `docs/packet_tracer/test_checklist.md`: checklist kiểm thử kết nối và các kịch bản IoT.
- `docs/packet_tracer/README.md`: file bắt đầu nhanh, liên kết toàn bộ tài liệu.

Nội dung và ảnh đã trích xuất từ báo cáo đang nằm trong `docs/packet_tracer_extraction/` để làm nguồn tham khảo, nhưng không phải sản phẩm bàn giao cuối cùng.

## Kiểm chứng

Bộ tài liệu sẽ được đối chiếu với báo cáo theo các điểm:

- thể hiện đủ các phân hệ chính,
- IP server khớp chính xác với báo cáo,
- quy tắc DHCP/IP tĩnh khớp với báo cáo,
- có tên miền DNS `www.smarthome.local`,
- đường kết nối lõi bám theo Home Gateway -> Cable Modem/Cloud -> Router/Switch -> Server trong phạm vi thiết bị Packet Tracer cho phép,
- có test case cho ping, DNS, truy cập web/dashboard và từng kịch bản tự động hóa IoT.

Việc kiểm chứng file `.pkt` cuối cùng cần thực hiện bằng cách mở Packet Tracer, làm theo hướng dẫn, lưu dự án và chạy checklist đi kèm.
