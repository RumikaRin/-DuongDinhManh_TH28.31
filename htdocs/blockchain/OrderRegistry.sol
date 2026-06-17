// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract VnpayOrderRegistry {
    // Cấu trúc lưu trữ một đơn hàng
    struct OrderRecord {
        string orderId;
        uint256 amountVND;
        string vnpayTxNo;
        uint256 timestamp;
        address registeredBy;
    }

    // Sự kiện được phát ra khi một đơn hàng mới được lưu lên chuỗi
    event OrderRegistered(
        string indexed orderId,
        uint256 amountVND,
        string vnpayTxNo,
        uint256 timestamp,
        address registeredBy
    );

    // Lưu trữ đơn hàng theo mã đơn (orderId)
    mapping(string => OrderRecord) public orders;
    
    // Địa chỉ của người quản trị (chủ cửa hàng)
    address public owner;

    constructor() {
        owner = msg.sender;
    }

    // Hàm kiểm tra quyền admin
    modifier onlyOwner() {
        require(msg.sender == owner, "Only owner can call this function");
        _;
    }

    /**
     * @dev Ghi nhận một giao dịch thanh toán VNPAY lên Blockchain
     * @param _orderId Mã đơn hàng trong MySQL
     * @param _amountVND Số tiền đã thanh toán bằng VNĐ
     * @param _vnpayTxNo Mã giao dịch trả về từ VNPAY
     */
    function recordPayment(string memory _orderId, uint256 _amountVND, string memory _vnpayTxNo) public onlyOwner {
        // Đảm bảo đơn hàng này chưa được ghi nhận trước đó
        require(orders[_orderId].timestamp == 0, "Order already registered");

        // Lưu thông tin đơn hàng
        orders[_orderId] = OrderRecord({
            orderId: _orderId,
            amountVND: _amountVND,
            vnpayTxNo: _vnpayTxNo,
            timestamp: block.timestamp,
            registeredBy: msg.sender
        });

        // Phát ra sự kiện để các ứng dụng (dApp) có thể lắng nghe
        emit OrderRegistered(_orderId, _amountVND, _vnpayTxNo, block.timestamp, msg.sender);
    }

    /**
     * @dev Lấy thông tin đơn hàng đã lưu trữ
     */
    function getOrder(string memory _orderId) public view returns (string memory, uint256, string memory, uint256, address) {
        OrderRecord memory order = orders[_orderId];
        require(order.timestamp != 0, "Order not found");
        return (order.orderId, order.amountVND, order.vnpayTxNo, order.timestamp, order.registeredBy);
    }
}
