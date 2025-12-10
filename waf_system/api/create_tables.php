<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '只允许POST请求']);
    exit;
}

$db_host = $_POST['db_host'] ?? '';
$db_port = $_POST['db_port'] ?? '3306';
$db_name = $_POST['db_name'] ?? '';
$db_user = $_POST['db_user'] ?? '';
$db_pass = $_POST['db_pass'] ?? '';

try {
    $pdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 创建攻击日志表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `attack_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `timestamp` datetime NOT NULL,
        `type` varchar(50) NOT NULL,
        `details` text,
        `ip` varchar(45) NOT NULL,
        `url` text,
        `method` varchar(10),
        `user_agent` text,
        PRIMARY KEY (`id`),
        KEY `idx_timestamp` (`timestamp`),
        KEY `idx_ip` (`ip`),
        KEY `idx_type` (`type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // 创建封禁IP表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `blocked_ips` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `ip` varchar(45) NOT NULL UNIQUE,
        `reason` text,
        `blocked_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `expires_at` timestamp NULL,
        PRIMARY KEY (`id`),
        KEY `idx_ip` (`ip`),
        KEY `idx_expires` (`expires_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // 创建用户表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL UNIQUE,
        `password_hash` varchar(255) NOT NULL,
        `email` varchar(100),
        `role` enum('admin', 'user') DEFAULT 'user',
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // 创建防护规则表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `protection_rules` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `rule_name` varchar(100) NOT NULL,
        `rule_type` enum('sql', 'xss', 'cc', 'custom') NOT NULL,
        `pattern` text,
        `enabled` tinyint(1) DEFAULT 1,
        `description` text,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // 创建访问日志表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `access_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `timestamp` datetime NOT NULL,
        `ip` varchar(45) NOT NULL,
        `url` text,
        `method` varchar(10),
        `status_code` int,
        `user_agent` text,
        PRIMARY KEY (`id`),
        KEY `idx_timestamp` (`timestamp`),
        KEY `idx_ip` (`ip`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // 创建保护域名表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `protected_domains` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `domain` varchar(255) NOT NULL UNIQUE,
        `description` text,
        `enabled` tinyint(1) DEFAULT 1,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_domain` (`domain`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // 创建备份记录表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `backup_records` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `filename` varchar(255) NOT NULL,
        `size` int,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `description` text,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo json_encode(['success' => true, 'message' => '数据表创建成功']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}