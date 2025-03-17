<?php
/**
 * Application Name: API AAPANEL 
 * Description: Đoạn php kết nối với aapanel, giúp cho quá trình tương tác với aapanel đơn giản và thuận tiện
 * Version: 1.0
 * Author: Nguyễn Thái - 1Touch.Pro - chonanh.com
 * Date: March 03, 2025
 * License: MIT
 * Dependencies: PHP 7.4+, AAPANEL API Client
 */
namespace nguyenthai\touchpro;

/**
 * TOUCHPRO API
 *
 * Đây là lớp chính để tương tác với API quản lý hệ thống webserver.
 * Lớp này cung cấp các phương thức để quản lý trang web, tên miền phụ, SSL, log, file nén, v.v.
 *
 * @author Nguyen Thai
 * @package nguyenthai/touchproapi
 */
class touchpro
{
    /** @var string Khóa bí mật dùng để mã hóa các yêu cầu API */
    public $key = null;

    /** @var string URL của API đích (ví dụ: http://example.com) */
    public $url = null;

    /**
     * Mã hóa dữ liệu yêu cầu để bảo mật
     *
     * Hàm này tạo ra một token yêu cầu và thời gian yêu cầu để đảm bảo tính bảo mật
     * khi gửi dữ liệu đến API.
     *
     * @return array Mảng chứa token yêu cầu và thời gian
     */
    private function encrypt(): array
    {
        return [
            'request_token' => md5(time() . md5($this->key)), // Token được mã hóa bằng MD5
            'request_time' => time(), // Thời gian hiện tại
        ];
    }

    /**
     * Gửi yêu cầu HTTP POST với hỗ trợ cookie
     *
     * Hàm này thực hiện yêu cầu POST tới API, sử dụng cookie để duy trì phiên làm việc.
     *
     * @param string $url URL đích của API
     * @param array $data Dữ liệu cần gửi
     * @param int $timeout Thời gian chờ tối đa (giây), mặc định là 60
     * @return string Dữ liệu phản hồi từ API
     */
    private function httpPostCookie(string $url, array $data, int $timeout = 60): string
    {
        // Tạo file cookie để lưu trữ phiên, tên file dựa trên URL (mã hóa bằng MD5)
        $cookie_file = './' . md5($this->url) . '.cookie';
        if (!file_exists($cookie_file)) {
            $fp = fopen($cookie_file, 'w+');
            fclose($fp);
        }

        // Khởi tạo cURL để gửi yêu cầu
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // Chuyển mảng thành chuỗi query
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); // Lưu cookie
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); // Đọc cookie
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Trả về dữ liệu thay vì in trực tiếp
        curl_setopt($ch, CURLOPT_HEADER, 0); // Không trả về header
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Tắt xác minh SSL (không khuyến khích trong sản xuất)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Tắt xác minh SSL (không khuyến khích trong sản xuất)
        
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    /**
     * Lấy danh sách log từ hệ thống
     *
     * Hàm này truy vấn API để lấy các bản ghi log, hữu ích cho việc giám sát và phân tích.
     *
     * @return array|null Danh sách log dạng mảng, hoặc null nếu lỗi
     */
    public function logs(): ?array
    {
        $completeUrl = $this->url . '/data?action=getData';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['table'] = 'logs'; // Bảng dữ liệu cần truy vấn
        $data['limit'] = 10; // Giới hạn số bản ghi trả về
        $data['tojs'] = 'test'; // Tham số bổ sung, có thể liên quan đến giao diện

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Thêm một trang web mới vào hệ thống
     *
     * Hàm này tạo một trang web mới với các cấu hình như tên miền, đường dẫn, FTP, cơ sở dữ liệu, SSL, v.v.
     *
     * @param string $domain Tên miền của trang web
     * @param string $path Đường dẫn lưu trữ trên server
     * @param string $desc Mô tả của trang web
     * @param int $type_id ID loại trang web (mặc định là 0)
     * @param string $type Loại trang web (mặc định là 'php')
     * @param string $phpversion Phiên bản PHP (mặc định là '73')
     * @param string $port Cổng sử dụng (mặc định là '80')
     * @param string|null $ftp Bật FTP (nếu có)
     * @param string|null $ftpusername Tên người dùng FTP
     * @param string|null $ftppassword Mật khẩu FTP
     * @param string|null $sql Bật cơ sở dữ liệu (nếu có)
     * @param string|null $userdbase Tên người dùng cơ sở dữ liệu
     * @param string|null $passdbase Mật khẩu cơ sở dữ liệu
     * @param int $setSsl Bật SSL (0: không, 1: có, mặc định là 0)
     * @param int $forceSsl Buộc HTTPS (0: không, 1: có, mặc định là 0)
     * @return array|null Kết quả thêm trang web, hoặc null nếu lỗi
     */
    public function addSite(
        string $domain,
        string $path,
        string $desc,
        int $type_id = 0,
        string $type = 'php',
        string $phpversion = '73',
        string $port = '80',
        ?string $ftp = null,
        ?string $ftpusername = null,
        ?string $ftppassword = null,
        ?string $sql = null,
        ?string $userdbase = null,
        ?string $passdbase = null,
        int $setSsl = 0,
        int $forceSsl = 0
    ): ?array {
        $completeUrl = $this->url . '/site?action=AddSite';

        // Chuẩn bị dữ liệu JSON cho tên miền
        $datajson = [
            'domain' => $domain,
            'domainlist' => [], // Danh sách tên miền phụ (nếu có)
            'count' => 0, // Số lượng tên miền phụ
        ];

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['webname'] = json_encode($datajson);
        $data['path'] = "/www/wwwroot/" . $path; // Đường dẫn lưu trữ trên server
        $data['ps'] = $desc; // Mô tả trang web
        $data['type_id'] = $type_id; // ID loại trang web
        $data['type'] = $type; // Loại trang web
        $data['version'] = $phpversion; // Phiên bản PHP
        $data['port'] = $port; // Cổng sử dụng

        // Thêm thông tin FTP nếu có
        if (isset($ftp)) {
            $data['ftp'] = $ftp;
            $data['ftp_username'] = $ftpusername;
            $data['ftp_password'] = $ftppassword;
        }

        // Thêm thông tin cơ sở dữ liệu nếu có
        if (isset($sql)) {
            $data['sql'] = $sql;
            $data['datauser'] = $userdbase;
            $data['datapassword'] = $passdbase;
        }

        // Thiết lập mã hóa và SSL
        $data['codeing'] = 'utf8'; // Mã hóa mặc định
        $data['set_ssl'] = $setSsl; // Bật SSL
        $data['force_ssl'] = $forceSsl; // Buộc HTTPS

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Thêm một tên miền phụ vào hệ thống
     *
     * Hàm này tạo một bản ghi DNS cho tên miền phụ, trỏ tới một địa chỉ IP.
     *
     * @param string $subdomain Tên miền phụ (ví dụ: sub.example.com)
     * @param string $mainDomain Tên miền chính (ví dụ: example.com)
     * @param string $iptarget Địa chỉ IP mà tên miền phụ trỏ tới
     * @return array|null Kết quả thêm tên miền phụ, hoặc null nếu lỗi
     */
    public function addSubDomain(string $subdomain, string $mainDomain, string $iptarget): ?array
    {
        $completeUrl = $this->url . '/plugin?action=a&name=dns_manager&s=act_resolve';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['host'] = $subdomain; // Tên miền phụ
        $data['value'] = $iptarget; // Địa chỉ IP
        $data['domain'] = $mainDomain; // Tên miền chính
        $data['ttl'] = '600'; // Thời gian sống của bản ghi DNS (giây)
        $data['type'] = 'A'; // Loại bản ghi DNS (A: địa chỉ)
        $data['act'] = 'add'; // Hành động: thêm mới

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Xóa một tên miền phụ khỏi hệ thống
     *
     * Hàm này xóa một bản ghi DNS của tên miền phụ.
     *
     * @param string $subdomain Tên miền phụ (ví dụ: sub.example.com)
     * @param string $mainDomain Tên miền chính (ví dụ: example.com)
     * @param string $iptarget Địa chỉ IP mà tên miền phụ trỏ tới
     * @return array|null Kết quả xóa tên miền phụ, hoặc null nếu lỗi
     */
    public function deleteSubDomain(string $subdomain, string $mainDomain, string $iptarget): ?array
    {
        $completeUrl = $this->url . '/plugin?action=a&name=dns_manager&s=act_resolve';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['host'] = $subdomain; // Tên miền phụ
        $data['value'] = $iptarget; // Địa chỉ IP
        $data['domain'] = $mainDomain; // Tên miền chính
        $data['ttl'] = '600'; // Thời gian sống của bản ghi DNS (giây)
        $data['type'] = 'A'; // Loại bản ghi DNS (A: địa chỉ)
        $data['act'] = 'delete'; // Hành động: xóa

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Sửa đổi thông tin của một tên miền phụ
     *
     * Hàm này cập nhật bản ghi DNS của tên miền phụ, ví dụ: thay đổi địa chỉ IP.
     *
     * @param string $subdomain Tên miền phụ (ví dụ: sub.example.com)
     * @param string $mainDomain Tên miền chính (ví dụ: example.com)
     * @param string $iptarget Địa chỉ IP mới mà tên miền phụ trỏ tới
     * @param int $id ID của bản ghi DNS cần sửa đổi
     * @return array|null Kết quả sửa đổi tên miền phụ, hoặc null nếu lỗi
     */
    public function modifySubDomain(string $subdomain, string $mainDomain, string $iptarget, int $id): ?array
    {
        $completeUrl = $this->url . '/plugin?action=a&name=dns_manager&s=act_resolve';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['host'] = $subdomain; // Tên miền phụ
        $data['value'] = $iptarget; // Địa chỉ IP
        $data['domain'] = $mainDomain; // Tên miền chính
        $data['ttl'] = '600'; // Thời gian sống của bản ghi DNS (giây)
        $data['type'] = 'A'; // Loại bản ghi DNS (A: địa chỉ)
        $data['act'] = 'modify'; // Hành động: sửa đổi
        $data['id'] = $id; // ID của bản ghi DNS

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Lấy danh sách tên miền phụ của một tên miền chính
     *
     * Hàm này truy vấn API để lấy danh sách tất cả tên miền phụ hoặc thông tin chi tiết của một tên miền phụ cụ thể.
     *
     * @param string $domain Tên miền chính (ví dụ: example.com)
     * @param string|null $host Tên miền phụ cụ thể (nếu có, ví dụ: sub.example.com)
     * @return array|null Danh sách tên miền phụ, hoặc thông tin chi tiết của tên miền phụ cụ thể, hoặc null nếu lỗi
     */
    public function subDomainList(string $domain, ?string $host = null): ?array
    {
        $completeUrl = $this->url . '/plugin?action=a&name=dns_manager&s=get_resolve';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['domain'] = $domain; // Tên miền chính

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);
        $resultarray = json_decode($result, true); // Giải mã JSON thành mảng

        // Nếu có host, chỉ trả về thông tin của tên miền phụ cụ thể
        if ($host && is_array($resultarray)) {
            foreach ($resultarray as $i => $r) {
                if ($r['host'] == $host) {
                    $resultarray = $resultarray[$i];
                    $resultarray['id'] = $i; // Thêm ID của bản ghi
                    break;
                }
            }
        }

        return $resultarray;
    }

    /**
     * Giải nén một file nén (zip) trên server
     *
     * Hàm này gửi yêu cầu tới API để giải nén một file zip, hữu ích khi triển khai ứng dụng.
     *
     * @param string $sourceFile Đường dẫn tới file nén (zip)
     * @param string $destinationFile Đường dẫn đích để lưu các file sau khi giải nén
     * @param string|null $password Mật khẩu của file nén (nếu có)
     * @return array|null Kết quả giải nén, hoặc null nếu lỗi
     */
    public function unzip(string $sourceFile, string $destinationFile, ?string $password = null): ?array
    {
        $completeUrl = $this->url . '/files?action=UnZip';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['sfile'] = $sourceFile; // Đường dẫn file nguồn
        $data['dfile'] = $destinationFile; // Đường dẫn đích
        $data['type'] = 'zip'; // Loại file nén
        $data['coding'] = 'UTF-8'; // Mã hóa
        $data['password'] = $password; // Mật khẩu (nếu có)

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Buộc trang web sử dụng giao thức HTTPS
     *
     * Hàm này cấu hình trang web để chuyển hướng tất cả các yêu cầu HTTP sang HTTPS.
     *
     * @param string $sitename Tên của trang web (thường là tên miền)
     * @return array|null Kết quả cấu hình HTTPS, hoặc null nếu lỗi
     */
    public function forceHTTPS(string $sitename): ?array
    {
        $completeUrl = $this->url . '/site?action=HttpToHttps';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['siteName'] = $sitename; // Tên trang web

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Áp dụng chứng chỉ SSL cho một tên miền
     *
     * Hàm này gửi yêu cầu để tạo và cài đặt chứng chỉ SSL cho tên miền, đảm bảo kết nối an toàn.
     *
     * @param string $domain Tên miền cần áp dụng SSL
     * @param int $idDomain ID của tên miền trong hệ thống
     * @return array|null Kết quả cài đặt SSL, hoặc null nếu lỗi
     */
    public function applySSL(string $domain, int $idDomain): ?array
    {
        // Bước 1: Tạo hoặc lấy chứng chỉ SSL
        $completeUrl = $this->url . '/acme?action=apply_cert_api';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['domains'] = '["' . $domain . '"]'; // Danh sách tên miền (dạng JSON)
        $data['id'] = $idDomain; // ID của tên miền
        $data['auth_to'] = $idDomain; // ID xác thực
        $data['auth_type'] = 'http'; // Loại xác thực (HTTP)
        $data['auto_wildcard'] = '0'; // Không tự động tạo wildcard certificate

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);
        $result = json_decode($result, true); // Giải mã JSON thành mảng

        // Bước 2: Cài đặt chứng chỉ SSL
        $urlSSL = $this->url . '/site?action=SetSSL';

        // Chuẩn bị dữ liệu yêu cầu để cài đặt SSL
        $data2 = $this->encrypt();
        $data2['type'] = '1'; // Loại hành động (1: cài đặt SSL)
        $data2['siteName'] = $domain; // Tên miền
        $data2['key'] = $result['private_key'] ?? ''; // Khóa riêng của chứng chỉ
        $data2['csr'] = ($result['cert'] ?? '') . ' ' . ($result['root'] ?? ''); // Chứng chỉ SSL

        // Gửi yêu cầu và nhận phản hồi
        $result2 = $this->httpPostCookie($urlSSL, $data2);

        return json_decode($result2, true); // Giải mã JSON thành mảng
    }

    /**
     * Lấy danh sách các trang web hoặc dự án trên hệ thống
     *
     * Hàm này truy vấn API để lấy danh sách các trang web hoặc dự án, hỗ trợ phân trang và tìm kiếm.
     *
     * @param int $limit Giới hạn số lượng bản ghi trả về
     * @param int $page Số trang hiện tại (phân trang)
     * @param string $projectType Loại dự án ('php', 'nodejs', 'pm2', mặc định là 'php')
     * @param string|null $search Từ khóa tìm kiếm (nếu có)
     * @return array|null Danh sách trang web hoặc dự án, hoặc null nếu lỗi
     */
    public function siteList(int $limit, int $page, string $projectType = 'php', ?string $search = null): ?array
    {
        // Chọn endpoint dựa trên loại dự án
        switch ($projectType) {
            case 'nodejs':
                $completeUrl = $this->url . '/project/nodejs/get_project_list';
                break;
            case 'php':
                $completeUrl = $this->url . '/data?action=getData';
                break;
            case 'pm2':
                $completeUrl = $this->url . '/plugin?action=a&s=List&name=pm2';
                break;
            default:
                $completeUrl = $this->url . '/project/nodejs/get_project_list';
                break;
        }

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['limit'] = $limit; // Giới hạn số bản ghi
        $data['p'] = $page; // Số trang
        if ($search) {
            $data['search'] = $search; // Từ khóa tìm kiếm
        }

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    // === System Monitoring ===

    /**
     * Lấy thông tin tổng quan về hệ thống
     *
     * Hàm này truy vấn API để lấy thông tin tổng quan về hệ thống, bao gồm CPU, RAM, ổ đĩa, và tải hệ thống.
     *
     * @return array|null Thông tin tổng quan về hệ thống, hoặc null nếu lỗi
     */
    public function getSystemTotal(): ?array
    {
        $completeUrl = $this->url . '/system?action=GetSystemTotal';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Lấy thông tin phân vùng ổ đĩa
     *
     * Hàm này truy vấn API để lấy thông tin về các phân vùng ổ đĩa, bao gồm dung lượng và inode.
     *
     * @return array|null Danh sách phân vùng và thông tin dung lượng/inode, hoặc null nếu lỗi
     */
    public function getDiskInfo(): ?array
    {
        $completeUrl = $this->url . '/system?action=GetDiskInfo';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Lấy thông tin trạng thái thời gian thực (CPU, RAM, mạng, tải)
     *
     * Hàm này truy vấn API để lấy thông tin trạng thái thời gian thực của hệ thống, bao gồm CPU, RAM, mạng, và tải hệ thống.
     *
     * @return array|null Thông tin trạng thái hiện tại, hoặc null nếu lỗi
     */
    public function getNetwork(): ?array
    {
        $completeUrl = $this->url . '/system?action=GetNetwork';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Kiểm tra số lượng tác vụ cài đặt đang chạy
     *
     * Hàm này truy vấn API để lấy số lượng tác vụ cài đặt (như cài đặt phần mềm, cập nhật, v.v.) đang chạy trên hệ thống.
     *
     * @return int|null Số lượng tác vụ, hoặc null nếu lỗi
     */
    public function getTaskCount(): ?int
    {
        $completeUrl = $this->url . '/ajax?action=GetTaskCount';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành số nguyên
    }

    /**
     * Kiểm tra và thực hiện cập nhật panel
     *
     * Hàm này kiểm tra xem có bản cập nhật mới cho panel quản trị hay không, và có thể thực hiện cập nhật ngay nếu cần.
     *
     * @param bool $check Kiểm tra cưỡng chế (true: kiểm tra ngay, false: kiểm tra theo lịch trình, mặc định là false)
     * @param bool $force Thực hiện cập nhật ngay (true: cập nhật ngay, false: không, mặc định là false)
     * @return array|null Trạng thái cập nhật và thông tin phiên bản, hoặc null nếu lỗi
     */
    public function updatePanel(bool $check = false, bool $force = false): ?array
    {
        $completeUrl = $this->url . '/ajax?action=UpdatePanel';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        if ($check) {
            $data['check'] = 'true'; // Kiểm tra cưỡng chế
        }
        if ($force) {
            $data['force'] = 'true'; // Thực hiện cập nhật ngay
        }

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    // === Website Management ===

    /**
     * Lấy danh sách website
     *
     * Hàm này truy vấn API để lấy danh sách các website trên hệ thống, hỗ trợ phân trang, lọc theo loại, sắp xếp, và tìm kiếm.
     *
     * @param int $page Trang hiện tại (phân trang, mặc định là 1)
     * @param int $limit Số lượng website mỗi trang (mặc định là 15)
     * @param int $type Loại website (-1: tất cả, 0: mặc định, mặc định là -1)
     * @param string $order Sắp xếp (ví dụ: 'id desc', mặc định là 'id desc')
     * @param string|null $search Từ khóa tìm kiếm (nếu có, mặc định là null)
     * @return array|null Danh sách website, hoặc null nếu lỗi
     */
    public function getSiteList(int $page = 1, int $limit = 15, int $type = -1, string $order = 'id desc', ?string $search = null): ?array
    {
        $completeUrl = $this->url . '/data?action=getData';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['table'] = 'sites'; // Bảng dữ liệu cần truy vấn
        $data['p'] = $page; // Trang hiện tại
        $data['limit'] = $limit; // Số lượng mỗi trang
        $data['type'] = $type; // Loại website
        $data['order'] = $order; // Sắp xếp
        if ($search) {
            $data['search'] = $search; // Từ khóa tìm kiếm
        }

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Lấy danh sách phân loại website
     *
     * Hàm này truy vấn API để lấy danh sách các loại website (ví dụ: PHP, Node.js, v.v.) được hỗ trợ trên hệ thống.
     *
     * @return array|null Danh sách các loại website, hoặc null nếu lỗi
     */
    public function getSiteTypes(): ?array
    {
        $completeUrl = $this->url . '/site?action=get_site_types';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Lấy danh sách phiên bản PHP đã cài
     *
     * Hàm này truy vấn API để lấy danh sách các phiên bản PHP đã được cài đặt trên hệ thống.
     *
     * @return array|null Danh sách phiên bản PHP, hoặc null nếu lỗi
     */
    public function getPHPVersion(): ?array
    {
        $completeUrl = $this->url . '/site?action=GetPHPVersion';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    // === File Management ===

    /**
     * Xóa file trên server
     *
     * Hàm này gửi yêu cầu tới API để xóa một file cụ thể trên server.
     *
     * @param string $path Đường dẫn tới file cần xóa
     * @return array|null Kết quả xóa file, hoặc null nếu lỗi
     */
    public function deleteFile(string $path): ?array
    {
        $completeUrl = $this->url . '/files?action=DeleteFile';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['path'] = $path; // Đường dẫn tới file cần xóa

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Xóa thư mục trên server
     *
     * Hàm này gửi yêu cầu tới API để xóa một thư mục cụ thể trên server.
     *
     * @param string $path Đường dẫn tới thư mục cần xóa
     * @return array|null Kết quả xóa thư mục, hoặc null nếu lỗi
     */
    public function deleteDir(string $path): ?array
    {
        $completeUrl = $this->url . '/files?action=DeleteDir';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['path'] = $path; // Đường dẫn tới thư mục cần xóa

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Lấy đường dẫn gốc của website
     *
     * Hàm này truy vấn API để lấy đường dẫn gốc (root path) của một website cụ thể trên hệ thống.
     *
     * @param int $siteId ID của website
     * @return array|null Đường dẫn gốc của website, hoặc null nếu lỗi
     */
    public function getSitePath(int $siteId): ?array
    {
        $completeUrl = $this->url . '/data?action=getKey&table=sites&key=path';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['id'] = $siteId; // ID của website

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }

    /**
     * Sửa đường dẫn gốc của website
     *
     * Hàm này gửi yêu cầu tới API để cập nhật đường dẫn gốc (root path) của một website cụ thể trên hệ thống.
     *
     * @param int $siteId ID của website
     * @param string $path Đường dẫn mới
     * @return array|null Kết quả sửa đổi đường dẫn, hoặc null nếu lỗi
     */
    public function setSitePath(int $siteId, string $path): ?array
    {
        $completeUrl = $this->url . '/site?action=SetPath';

        // Chuẩn bị dữ liệu yêu cầu
        $data = $this->encrypt();
        $data['id'] = $siteId; // ID của website
        $data['path'] = $path; // Đường dẫn mới

        // Gửi yêu cầu và nhận phản hồi
        $result = $this->httpPostCookie($completeUrl, $data);

        return json_decode($result, true); // Giải mã JSON thành mảng
    }
}
