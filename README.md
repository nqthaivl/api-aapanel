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
3. Tạo website
   ```bash
   $result = $api->addSite("example.com", "example.com", "My website");
   $site_id = $result['siteId'] ?? null;
   echo $result['siteStatus'] ? "Website created!" : $result['msg'];
4. Thêm subdomain
   ```bash
   if ($site_id) {
       $result = $api->addSubDomain($site_id, "example.com", "sub.example.com");
       echo $result['status'] ? "Subdomain added!" : $result['msg'];
   }
5. Tạo database
   ```bash
   $result = $api->createDatabase("mydb", "myuser", "mypassword");
   echo $result['status'] ? "Database created!" : $result['msg'];
6. Tạo file .htaccess
   ```bash
   $htaccess = "RewriteEngine On\nRewriteRule ^test$ index.php [L]";
   $result = $api->saveFile("/www/wwwroot/example.com/.htaccess", $htaccess);
   echo $result['status'] ? "File saved!" : $result['msg'];
7. Giải nén file zip
   ```bash
    $result = $api->unzip("/www/wwwroot/example.com/data.zip", "/www/wwwroot/example.com/extracted/");
    echo "Giải nén: " . ($result['status'] ? "Thành công" : $result['msg']) . "\n";
8. Xóa subdomain
   ```bash
   $result = $api->deleteSubDomain($site_id, "example.com", "sub.example.com");
   echo "Xóa subdomain: " . ($result['status'] ? "Thành công" : $result['msg']) . "\n";
9. Xóa website
   ```bash
   $result = $api->deleteSite($site_id, "example.com");
   echo "Xóa website: " . ($result['status'] ? "Thành công" : $result['msg']) . "\n";

