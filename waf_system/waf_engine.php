<?php
/**
 * WAF防护引擎核心文件
 * 用于保护其他网站，需要在目标网站的入口文件前引入此文件
 */

// 引入配置和函数库
require_once 'config/config.php';
require_once 'includes/functions.php';

class WAFEngine 
{
    private $client_ip;
    private $request_url;
    private $request_method;
    
    public function __construct() 
    {
        $this->client_ip = get_client_ip();
        $this->request_url = $_SERVER['REQUEST_URI'] ?? '';
        $this->request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
    
    /**
     * 执行防护检查
     */
    public function run() 
    {
        // 检查IP是否被封禁
        if (is_ip_blocked($this->client_ip)) {
            $this->block_request('IP Blocked', "IP {$this->client_ip} is blocked by WAF system");
            return;
        }
        
        // 检测CC攻击
        if (defined('ENABLE_CC_PROTECTION') && ENABLE_CC_PROTECTION) {
            if ($this->detect_cc_attack()) {
                $this->block_request('CC Attack', "CC attack detected from IP {$this->client_ip}");
                return;
            }
        }
        
        // 检测SQL注入
        if (defined('ENABLE_SQL_PROTECTION') && ENABLE_SQL_PROTECTION) {
            if ($this->detect_sql_injection()) {
                $this->block_request('SQL Injection', "SQL injection attempt detected from IP {$this->client_ip}");
                return;
            }
        }
        
        // 检测XSS攻击
        if (defined('ENABLE_XSS_PROTECTION') && ENABLE_XSS_PROTECTION) {
            if ($this->detect_xss()) {
                $this->block_request('XSS Attack', "XSS attack attempt detected from IP {$this->client_ip}");
                return;
            }
        }
        
        // 记录正常访问日志
        log_access($this->client_ip, $this->request_url, $this->request_method, 200);
    }
    
    /**
     * 检测CC攻击
     */
    private function detect_cc_attack() 
    {
        return detect_cc_attack($this->client_ip);
    }
    
    /**
     * 检测SQL注入
     */
    private function detect_sql_injection() 
    {
        // 检查GET参数
        foreach ($_GET as $param => $value) {
            if (is_string($value) && detect_sql_injection($value)) {
                log_attack('SQL Injection', "Parameter: {$param}, Value: {$value}", $this->client_ip, $this->request_url, $this->request_method);
                return true;
            }
        }
        
        // 检查POST数据
        foreach ($_POST as $param => $value) {
            if (is_string($value) && detect_sql_injection($value)) {
                log_attack('SQL Injection', "POST Parameter: {$param}, Value: {$value}", $this->client_ip, $this->request_url, $this->request_method);
                return true;
            }
        }
        
        // 检查REQUEST_URI
        if (detect_sql_injection($this->request_url)) {
            log_attack('SQL Injection', "URL: {$this->request_url}", $this->client_ip, $this->request_url, $this->request_method);
            return true;
        }
        
        return false;
    }
    
    /**
     * 检测XSS攻击
     */
    private function detect_xss() 
    {
        // 检查GET参数
        foreach ($_GET as $param => $value) {
            if (is_string($value) && detect_xss($value)) {
                log_attack('XSS', "Parameter: {$param}, Value: {$value}", $this->client_ip, $this->request_url, $this->request_method);
                return true;
            }
        }
        
        // 检查POST数据
        foreach ($_POST as $param => $value) {
            if (is_string($value) && detect_xss($value)) {
                log_attack('XSS', "POST Parameter: {$param}, Value: {$value}", $this->client_ip, $this->request_url, $this->request_method);
                return true;
            }
        }
        
        // 检查REQUEST_URI
        if (detect_xss($this->request_url)) {
            log_attack('XSS', "URL: {$this->request_url}", $this->client_ip, $this->request_url, $this->request_method);
            return true;
        }
        
        return false;
    }
    
    /**
     * 阻止请求
     */
    private function block_request($type, $details) 
    {
        // 记录攻击日志
        log_attack($type, $details, $this->client_ip, $this->request_url, $this->request_method);
        
        // 返回403禁止访问页面
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>访问被阻止</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f5f5f5; text-align: center; padding: 50px; }
                .container { background-color: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: inline-block; margin: 0 auto; }
                h1 { color: #d9534f; }
                p { color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>访问被阻止</h1>
                <p>您的访问请求因安全原因被阻止</p>
                <p><strong>原因:</strong> ' . htmlspecialchars($details) . '</p>
                <p><strong>时间:</strong> ' . date('Y-m-d H:i:s') . '</p>
                <p><strong>IP地址:</strong> ' . htmlspecialchars($this->client_ip) . '</p>
            </div>
        </body>
        </html>';
        
        exit;
    }
}

// 实例化并运行WAF引擎
$waf = new WAFEngine();
$waf->run();