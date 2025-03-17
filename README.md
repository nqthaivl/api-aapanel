# OneTouchPro

**OneTouchPro** là một thư viện PHP mạnh mẽ để tương tác với API của [aaPanel](https://www.aapanel.com/), giúp bạn tự động hóa việc quản lý server, website, file, database và hơn thế nữa chỉ với vài dòng code.

## Tính năng
- **Quản lý hệ thống**: Lấy thông tin CPU, RAM, ổ đĩa, mạng, kiểm tra cập nhật panel.
- **Quản lý website**: Tạo, xóa, bật/tắt website/subdomain, backup, cấu hình chi tiết.
- **Quản lý file**: Tạo, đọc, ghi file, giải nén ZIP.
- **Quản lý database**: Tạo database MySQL.
- **Cấu hình nâng cao**: Giới hạn lưu lượng, truy cập mật khẩu, tài liệu mặc định, v.v.

## Yêu cầu
- PHP 7.0 trở lên
- aaPanel đã cài đặt trên server
- API key từ aaPanel (Settings > API Interface)
- cURL extension được bật trong PHP

## Cài đặt
1. Tải repository từ GitHub:
   ```bash
   git clone https://github.com/nqthaivl/api-aapanel.git
2. Khởi tạo
Để bắt đầu, bạn cần require file OneTouchPro.php và khởi tạo đối tượng với API key và URL của aaPanel:
   ```bash
   require_once 'OneTouchPro.php';
   $api_key = "YOUR_API_KEY"; // Thay bằng API key của bạn
   $panel_url = "http://YOUR_PANEL_IP:PORT"; // Thay bằng URL aaPanel (ví dụ: http://103.186.101.197:8888)
   $api = new \onetouchpro\OneTouchPro($api_key, $panel_url);
