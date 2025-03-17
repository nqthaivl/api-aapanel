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
namespace onetouchpro;

/**
 * OneTouchPro API
 *
 * Lớp này cung cấp giao diện để tương tác với API aaPanel, cho phép quản lý hệ thống, website, file, database và hơn thế nữa.
 *
 * @author NGUYENTHAI
 * @package onetouchpro/api
 */
class OneTouchPro
{
    public $key = null;  // Khóa API
    public $url = null;  // Địa chỉ URL của aaPanel

    /**
     * Khởi tạo class với API key và URL của aaPanel
     * 
     * @param string $api_key Khóa API từ aaPanel (lấy từ Settings > API Interface)
     * @param string $panel_url Địa chỉ URL của aaPanel (ví dụ: http://103.186.101.197:8888)
     */
    public function __construct($api_key, $panel_url)
    {
        // Gán giá trị cho biến key từ tham số truyền vào
        $this->key = $api_key;
        // Gán giá trị cho biến url và loại bỏ dấu / ở cuối nếu có
        $this->url = rtrim($panel_url, '/');
    }

    /**
     * Tạo chữ ký bảo mật cho yêu cầu API
     * 
     * @return array Mảng chứa request_time và request_token
     */
    private function encrypt()
    {
        // Lấy thời gian hiện tại dưới dạng Unix timestamp
        $request_time = time();
        // Tạo chữ ký bằng cách mã hóa MD5 thời gian + mã hóa MD5 của key
        return [
            'request_time' => $request_time,
            'request_token' => md5($request_time . md5($this->key)),
        ];
    }

    /**
     * Gửi yêu cầu POST đến aaPanel với cookie
     * 
     * @param string $url Địa chỉ URL đầy đủ để gửi yêu cầu
     * @param array $data Dữ liệu gửi đi
     * @param int $timeout Thời gian chờ tối đa (giây)
     * @return string Phản hồi thô từ server
     */
    private function httpPostCookie($url, $data, $timeout = 60)
    {
        // Định nghĩa đường dẫn file cookie dựa trên URL của aaPanel
        $cookie_file = './' . md5($this->url) . '.cookie';
        // Kiểm tra nếu file cookie chưa tồn tại, tạo mới
        if (!file_exists($cookie_file)) {
            $fp = fopen($cookie_file, 'w+'); // Mở file để ghi
            fclose($fp); // Đóng file sau khi tạo
        }

        // Khởi tạo cURL
        $ch = curl_init();
        // Thiết lập URL để gửi yêu cầu
        curl_setopt($ch, CURLOPT_URL, $url);
        // Thiết lập thời gian chờ tối đa
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        // Sử dụng phương thức POST
        curl_setopt($ch, CURLOPT_POST, 1);
        // Chuyển dữ liệu thành chuỗi query string và gửi đi
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        // Lưu cookie vào file
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        // Sử dụng cookie từ file
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        // Trả về kết quả thay vì in ra
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // Không bao gồm header trong phản hồi
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // Bỏ qua kiểm tra SSL (dùng cho server không có HTTPS)
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // Thực thi yêu cầu và lấy kết quả
        $output = curl_exec($ch);
        // Đóng phiên cURL
        curl_close($ch);
        // Trả về phản hồi
        return $output;
    }

    // === System Status Related ===

    /**
     * Lấy thông tin thống kê cơ bản của hệ thống
     * 
     * @return array Thông tin hệ thống (CPU, RAM, OS, v.v.)
     */
    public function getSystemTotal()
    {
        // Định nghĩa URL endpoint
        $completeUrl = $this->url . '/system?action=GetSystemTotal';
        // Tạo dữ liệu với chữ ký
        $data = $this->encrypt();
        // Gửi yêu cầu và trả về kết quả dưới dạng JSON
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Lấy thông tin phân vùng ổ đĩa
     * 
     * @return array Danh sách phân vùng và thông tin dung lượng/inode
     */
    public function getDiskInfo()
    {
        $completeUrl = $this->url . '/system?action=GetDiskInfo';
        $data = $this->encrypt();
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Lấy thông tin trạng thái thời gian thực (CPU, RAM, mạng, tải)
     * 
     * @return array Thông tin trạng thái hiện tại
     */
    public function getNetwork()
    {
        $completeUrl = $this->url . '/system?action=GetNetwork';
        $data = $this->encrypt();
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Kiểm tra số lượng tác vụ cài đặt đang chạy
     * 
     * @return int Số lượng tác vụ
     */
    public function getTaskCount()
    {
        $completeUrl = $this->url . '/ajax?action=GetTaskCount';
        $data = $this->encrypt();
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Kiểm tra cập nhật panel
     * 
     * @param bool $check Kiểm tra cưỡng chế
     * @param bool $force Thực hiện cập nhật ngay
     * @return array Trạng thái cập nhật và thông tin phiên bản
     */
    public function updatePanel($check = false, $force = false)
    {
        $completeUrl = $this->url . '/ajax?action=UpdatePanel';
        $data = $this->encrypt();
        if ($check) $data['check'] = 'true';
        if ($force) $data['force'] = 'true';
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    // === Website Management ===

    /**
     * Lấy danh sách website
     * 
     * @param int $page Trang hiện tại
     * @param int $limit Số lượng mỗi trang
     * @param int $type Loại website (-1: tất cả, 0: mặc định)
     * @param string $order Sắp xếp (ví dụ: 'id desc')
     * @param string $search Từ khóa tìm kiếm
     * @return array Danh sách website
     */
    public function getSiteList($page = 1, $limit = 15, $type = -1, $order = 'id desc', $search = null)
    {
        $completeUrl = $this->url . '/data?action=getData';
        $data = $this->encrypt();
        $data['table'] = 'sites';
        $data['p'] = $page;
        $data['limit'] = $limit;
        $data['type'] = $type;
        $data['order'] = $order;
        if ($search) $data['search'] = $search;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Lấy danh sách phân loại website
     * 
     * @return array Danh sách các loại website
     */
    public function getSiteTypes()
    {
        $completeUrl = $this->url . '/site?action=get_site_types';
        $data = $this->encrypt();
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Lấy danh sách phiên bản PHP đã cài
     * 
     * @return array Danh sách phiên bản PHP
     */
    public function getPHPVersion()
    {
        $completeUrl = $this->url . '/site?action=GetPHPVersion';
        $data = $this->encrypt();
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Thêm website mới
     * 
     * @param string $domain Tên miền chính
     * @param string $path Đường dẫn thư mục gốc
     * @param string $description Mô tả website
     * @param int $type_id ID loại website
     * @param string $type Loại dự án (php)
     * @param string $phpversion Phiên bản PHP
     * @param string $port Cổng
     * @return array Kết quả thêm website
     */
    public function addSite($domain, $path, $description, $type_id = 0, $type = 'php', $phpversion = '73', $port = '80')
    {
        $completeUrl = $this->url . '/site?action=AddSite';
        $datajson = ['domain' => $domain, 'domainlist' => [], 'count' => 0];
        $data = $this->encrypt();
        $data['webname'] = json_encode($datajson);
        $data['path'] = "/www/wwwroot/" . $path;
        $data['ps'] = $description;
        $data['type_id'] = $type_id;
        $data['type'] = $type;
        $data['version'] = $phpversion;
        $data['port'] = $port;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Xóa website
     * 
     * @param int $siteId ID của website
     * @param string $webname Tên miền chính
     * @param bool $deleteFtp Xóa FTP liên quan
     * @param bool $deleteDatabase Xóa database liên quan
     * @param bool $deletePath Xóa thư mục gốc
     * @return array Kết quả xóa website
     */
    public function deleteSite($siteId, $webname, $deleteFtp = true, $deleteDatabase = true, $deletePath = true)
    {
        $completeUrl = $this->url . '/site?action=DeleteSite';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $data['webname'] = $webname;
        if ($deleteFtp) $data['ftp'] = '1';
        if ($deleteDatabase) $data['database'] = '1';
        if ($deletePath) $data['path'] = '1';
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Dừng website
     * 
     * @param int $siteId ID của website
     * @param string $name Tên miền chính
     * @return array Kết quả dừng website
     */
    public function stopSite($siteId, $name)
    {
        $completeUrl = $this->url . '/site?action=SiteStop';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $data['name'] = $name;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Khởi động website
     * 
     * @param int $siteId ID của website
     * @param string $name Tên miền chính
     * @return array Kết quả khởi động website
     */
    public function startSite($siteId, $name)
    {
        $completeUrl = $this->url . '/site?action=SiteStart';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $data['name'] = $name;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Thiết lập thời gian hết hạn website
     * 
     * @param int $siteId ID của website
     * @param string $edate Ngày hết hạn (YYYY-MM-DD, hoặc 0000-00-00 để vĩnh viễn)
     * @return array Kết quả thiết lập
     */
    public function setSiteExpiration($siteId, $edate)
    {
        $completeUrl = $this->url . '/site?action=SetEdate';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $data['edate'] = $edate;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Sửa ghi chú website
     * 
     * @param int $siteId ID của website
     * @param string $ps Ghi chú mới
     * @return array Kết quả sửa ghi chú
     */
    public function setSiteNote($siteId, $ps)
    {
        $completeUrl = $this->url . '/data?action=setPs&table=sites';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $data['ps'] = $ps;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Lấy danh sách backup của website
     * 
     * @param int $siteId ID của website
     * @param int $page Trang hiện tại
     * @param int $limit Số lượng mỗi trang
     * @return array Danh sách backup
     */
    public function getSiteBackupList($siteId, $page = 1, $limit = 5)
    {
        $completeUrl = $this->url . '/data?action=getData&table=backup';
        $data = $this->encrypt();
        $data['p'] = $page;
        $data['limit'] = $limit;
        $data['type'] = '0';
        $data['search'] = $siteId;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Tạo backup cho website
     * 
     * @param int $siteId ID của website
     * @return array Kết quả tạo backup
     */
    public function createSiteBackup($siteId)
    {
        $completeUrl = $this->url . '/site?action=ToBackup';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Xóa backup của website
     * 
     * @param int $backupId ID của backup
     * @return array Kết quả xóa backup
     */
    public function deleteSiteBackup($backupId)
    {
        $completeUrl = $this->url . '/site?action=DelBackup';
        $data = $this->encrypt();
        $data['id'] = $backupId;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Lấy danh sách domain của website
     * 
     * @param int $siteId ID của website
     * @return array Danh sách domain
     */
    public function getDomainList($siteId)
    {
        $completeUrl = $this->url . '/data?action=getData&table=domain';
        $data = $this->encrypt();
        $data['search'] = $siteId;
        $data['list'] = 'true';
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Thêm domain (subdomain) vào website
     * 
     * @param int $siteId ID của website
     * @param string $mainDomain Tên miền chính
     * @param string $domain Domain mới (subdomain)
     * @return array Kết quả thêm domain
     */
    public function addSubDomain($siteId, $mainDomain, $domain)
    {
        $completeUrl = $this->url . '/site?action=AddDomain';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $data['webname'] = $mainDomain;
        $data['domain'] = $domain;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Xóa domain (subdomain)
     * 
     * @param int $siteId ID của website
     * @param string $mainDomain Tên miền chính
     * @param string $domain Domain cần xóa
     * @param string $port Cổng của domain
     * @return array Kết quả xóa domain
     */
    public function deleteSubDomain($siteId, $mainDomain, $domain, $port = '80')
    {
        $completeUrl = $this->url . '/site?action=DelDomain';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $data['webname'] = $mainDomain;
        $data['domain'] = $domain;
        $data['port'] = $port;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Lấy danh sách quy tắc pseudo-static có sẵn
     * 
     * @param string $siteName Tên website
     * @return array Danh sách quy tắc
     */
    public function getRewriteList($siteName)
    {
        $completeUrl = $this->url . '/site?action=GetRewriteList';
        $data = $this->encrypt();
        $data['siteName'] = $siteName;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Lấy nội dung file
     * 
     * @param string $path Đường dẫn file
     * @return array Nội dung file
     */
    public function getFileBody($path)
    {
        $completeUrl = $this->url . '/files?action=GetFileBody';
        $data = $this->encrypt();
        $data['path'] = $path;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Lưu nội dung file
     * 
     * @param string $path Đường dẫn file
     * @param string $content Nội dung file
     * @return array Kết quả lưu file
     */
    public function saveFile($path, $content)
    {
        $completeUrl = $this->url . '/files?action=SaveFileBody';
        $data = $this->encrypt();
        $data['path'] = $path;
        $data['data'] = $content;
        $data['encoding'] = 'utf-8';
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Giải nén file ZIP
     * 
     * @param string $sourceFile Đường dẫn file ZIP
     * @param string $destinationFile Thư mục đích
     * @param string|null $password Mật khẩu (nếu có)
     * @return array Kết quả giải nén
     */
    public function unzip($sourceFile, $destinationFile, $password = null)
    {
        $completeUrl = $this->url . '/files?action=UnZip';
        $data = $this->encrypt();
        $data['sfile'] = $sourceFile;
        $data['dfile'] = $destinationFile;
        $data['type'] = 'zip';
        $data['coding'] = 'UTF-8';
        if ($password) $data['password'] = $password;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Lấy đường dẫn gốc của website
     * 
     * @param int $siteId ID của website
     * @return array Đường dẫn gốc
     */
    public function getSitePath($siteId)
    {
        $completeUrl = $this->url . '/data?action=getKey&table=sites&key=path';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Lấy cấu hình chống vượt trạm, thư mục chạy, trạng thái log
     * 
     * @param int $siteId ID của website
     * @param string $path Đường dẫn gốc
     * @return array Cấu hình chi tiết
     */
    public function getDirUserINI($siteId, $path)
    {
        $completeUrl = $this->url . '/site?action=GetDirUserINI';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $data['path'] = $path;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Bật/tắt chống vượt trạm (tự động đảo ngược trạng thái)
     * 
     * @param string $path Đường dẫn gốc
     * @return array Kết quả thay đổi
     */
    public function setDirUserINI($path)
    {
        $completeUrl = $this->url . '/site?action=SetDirUserINI';
        $data = $this->encrypt();
        $data['path'] = $path;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Bật/tắt ghi log truy cập
     * 
     * @param int $siteId ID của website
     * @return array Kết quả thay đổi
     */
    public function setAccessLog($siteId)
    {
        $completeUrl = $this->url . '/site?action=logsOpen';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Sửa đường dẫn gốc website
     * 
     * @param int $siteId ID của website
     * @param string $path Đường dẫn mới
     * @return array Kết quả sửa đổi
     */
    public function setSitePath($siteId, $path)
    {
        $completeUrl = $this->url . '/site?action=SetPath';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $data['path'] = $path;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Thiết lập thư mục chạy của website
     * 
     * @param int $siteId ID của website
     * @param string $runPath Thư mục chạy (dựa trên gốc)
     * @return array Kết quả thiết lập
     */
    public function setSiteRunPath($siteId, $runPath)
    {
        $completeUrl = $this->url . '/site?action=SetSiteRunPath';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $data['runPath'] = $runPath;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Thiết lập truy cập bằng mật khẩu
     * 
     * @param int $siteId ID của website
     * @param string $username Tên người dùng
     * @param string $password Mật khẩu
     * @return array Kết quả thiết lập
     */
    public function setPasswordAccess($siteId, $username, $password)
    {
        $completeUrl = $this->url . '/site?action=SetHasPWD';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $data['username'] = $username;
        $data['password'] = $password;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Tắt truy cập bằng mật khẩu
     * 
     * @param int $siteId ID của website
     * @return array Kết quả tắt
     */
    public function closePasswordAccess($siteId)
    {
        $completeUrl = $this->url . '/site?action=CloseHasPWD';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Lấy cấu hình giới hạn lưu lượng (chỉ hỗ trợ Nginx)
     * 
     * @param int $siteId ID của website
     * @return array Cấu hình giới hạn
     */
    public function getTrafficLimit($siteId)
    {
        $completeUrl = $this->url . '/site?action=GetLimitNet';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Thiết lập hoặc bật giới hạn lưu lượng (chỉ hỗ trợ Nginx)
     * 
     * @param int $siteId ID của website
     * @param int $perserver Giới hạn đồng thời
     * @param int $perip Giới hạn IP đơn
     * @param int $limit_rate Giới hạn lưu lượng (KB/s)
     * @return array Kết quả thiết lập
     */
    public function setTrafficLimit($siteId, $perserver, $perip, $limit_rate)
    {
        $completeUrl = $this->url . '/site?action=SetLimitNet';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $data['perserver'] = $perserver;
        $data['perip'] = $perip;
        $data['limit_rate'] = $limit_rate;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Tắt giới hạn lưu lượng (chỉ hỗ trợ Nginx)
     * 
     * @param int $siteId ID của website
     * @return array Kết quả tắt
     */
    public function closeTrafficLimit($siteId)
    {
        $completeUrl = $this->url . '/site?action=CloseLimitNet';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Lấy thông tin tài liệu mặc định
     * 
     * @param int $siteId ID của website
     * @return array Danh sách tài liệu mặc định
     */
    public function getDefaultIndex($siteId)
    {
        $completeUrl = $this->url . '/site?action=GetIndex';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Thiết lập tài liệu mặc định
     * 
     * @param int $siteId ID của website
     * @param string $index Danh sách tài liệu mặc định (phân cách bằng dấu phẩy)
     * @return array Kết quả thiết lập
     */
    public function setDefaultIndex($siteId, $index)
    {
        $completeUrl = $this->url . '/site?action=SetIndex';
        $data = $this->encrypt();
        $data['id'] = $siteId;
        $data['Index'] = $index; // Chú ý: API dùng 'Index' với chữ I in hoa
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }

    /**
     * Tạo database mới
     * 
     * @param string $name Tên database
     * @param string $username Tên người dùng
     * @param string $password Mật khẩu
     * @param string $encoding Mã hóa (mặc định: utf8)
     * @return array Kết quả tạo database
     */
    public function createDatabase($name, $username, $password, $encoding = 'utf8')
    {
        $completeUrl = $this->url . '/database?action=AddDatabase';
        $data = $this->encrypt();
        $data['name'] = $name;
        $data['username'] = $username;
        $data['password'] = $password;
        $data['codeing'] = $encoding;
        $data['db_user'] = $username;
        $data['dataAccess'] = '127.0.0.1';
        $data['address'] = '127.0.0.1';
        $data['type'] = 'MySQL';
        $result = $this->httpPostCookie($completeUrl, $data);
        return json_decode($result, true);
    }
}
?>
