<?php
// Ví dụ sử dụng
$api_key = "YOUR_API_KEY"; // Thay bằng API key thực tế
$panel_url = "http://YOUR_PANEL_IP:PORT"; // Thay bằng IP/port thực tế

$api = new \onetouchpro\OneTouchPro($api_key, $panel_url);

// 1. Lấy thông tin hệ thống
$result = $api->getSystemTotal();
echo "Thông tin hệ thống: " . ($result ? json_encode($result) : "Lỗi") . "\n";

// 2. Tạo website
$result = $api->addSite("example.com", "example.com", "My website");
$site_id = $result['siteId'] ?? null;
echo "Tạo website: " . ($result['siteStatus'] ? "Thành công" : $result['msg']) . "\n";

// 3. Thêm subdomain
if ($site_id) {
    $result = $api->addSubDomain($site_id, "example.com", "sub.example.com");
    echo "Thêm subdomain: " . ($result['status'] ? "Thành công" : $result['msg']) . "\n";

    // 4. Tạo database
    $result = $api->createDatabase("example_db", "example_user", "example_pass123");
    echo "Tạo database: " . ($result['status'] ? "Thành công" : $result['msg']) . "\n";

    // 5. Tạo file .htaccess
    $htaccess = "RewriteEngine On\nRewriteRule ^test$ index.php [L]";
    $result = $api->saveFile("/www/wwwroot/example.com/.htaccess", $htaccess);
    echo "Tạo file: " . ($result['status'] ? "Thành công" : $result['msg']) . "\n";

    // 6. Giải nén file ZIP
    $result = $api->unzip("/www/wwwroot/example.com/data.zip", "/www/wwwroot/example.com/extracted/");
    echo "Giải nén: " . ($result['status'] ? "Thành công" : $result['msg']) . "\n";

    // 7. Xóa subdomain
    $result = $api->deleteSubDomain($site_id, "example.com", "sub.example.com");
    echo "Xóa subdomain: " . ($result['status'] ? "Thành công" : $result['msg']) . "\n";

    // 8. Xóa website
    $result = $api->deleteSite($site_id, "example.com");
    echo "Xóa website: " . ($result['status'] ? "Thành công" : $result['msg']) . "\n";
}
?>
