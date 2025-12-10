<?php
/**
 * WAF系统部署脚本
 */

// 检查PHP版本
if (version_compare(PHP_VERSION, '7.0', '<')) {
    die('PHP版本过低，请使用PHP 7.0或更高版本');
}

// 检查必需的扩展
$required_extensions = ['json', 'filter', 'pcre', 'session', 'pdo', 'pdo_mysql'];
$missing_extensions = [];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    die('缺少必需的PHP扩展: ' . implode(', ', $missing_extensions));
}

// 创建必要的目录
$dirs_to_create = [
    'logs',
    'config',
    'includes',
    'api',
    'static/layui/css',
    'modules'
];

foreach ($dirs_to_create as $dir) {
    $full_path = __DIR__ . '/' . $dir;
    if (!is_dir($full_path)) {
        if (!mkdir($full_path, 0755, true)) {
            die("无法创建目录: {$dir}");
        }
    }
}

// 检查写入权限
$writable_paths = [
    __DIR__ . '/logs',
    __DIR__ . '/config'
];

foreach ($writable_paths as $path) {
    if (!is_writable($path)) {
        die("路径不可写: {$path}");
    }
}

// 创建初始配置文件
$config_file = __DIR__ . '/config/config.php';
if (!file_exists($config_file)) {
    $config_content = '<?php
/**
 * WAF系统配置文件
 */

// 数据库配置
define(\'DB_HOST\', \'localhost\');
define(\'DB_NAME\', \'waf_system\');
define(\'DB_USER\', \'root\');
define(\'DB_PASS\', \'\');
define(\'DB_CHARSET\', \'utf8mb4\');

// 系统配置
define(\'SITE_NAME\', \'WAF防护系统\');
define(\'VERSION\', \'1.0.0\');
define(\'DEBUG\', false);

// 防护配置
define(\'ENABLE_SQL_PROTECTION\', true);
define(\'ENABLE_XSS_PROTECTION\', true);
define(\'ENABLE_CC_PROTECTION\', true);
define(\'CC_RATE_LIMIT\', 100); // 每分钟请求数限制
define(\'CC_TIME_WINDOW\', 60); // 时间窗口（秒）
define(\'BLOCK_TIME\', 3600); // 封禁时间（秒）

// 日志配置
define(\'LOG_ATTACKS\', true);
define(\'LOG_ACCESS\', true);
define(\'MAX_LOG_RETENTION\', 30); // 日志保留天数

// 路径配置
define(\'BASE_PATH\', dirname(dirname(__FILE__)));
define(\'LOG_PATH\', BASE_PATH . \'/logs/\');
define(\'CONFIG_PATH\', BASE_PATH . \'/config/\');

// API密钥（用于保护API接口）
define(\'API_SECRET_KEY\', \'waf_system_secret_key_\' . time());
';
    file_put_contents($config_file, $config_content);
}

// 初始化数据库
echo "正在初始化数据库...\n";
try {
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // 创建数据库（如果不存在）
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_general_ci");
    
    // 选择数据库
    $pdo->exec("USE " . DB_NAME);
    
    // 创建攻击日志表
    $pdo->exec("CREATE TABLE IF NOT EXISTS attack_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        timestamp DATETIME NOT NULL,
        type VARCHAR(50) NOT NULL,
        details TEXT,
        ip VARCHAR(45) NOT NULL,
        url TEXT,
        method VARCHAR(10),
        user_agent TEXT,
        INDEX idx_timestamp (timestamp),
        INDEX idx_ip (ip),
        INDEX idx_type (type)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
    
    // 创建封禁IP表
    $pdo->exec("CREATE TABLE IF NOT EXISTS blocked_ips (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL UNIQUE,
        reason TEXT,
        blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        INDEX idx_ip (ip),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
    
    // 创建用户表
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
    
    // 创建防护规则表
    $pdo->exec("CREATE TABLE IF NOT EXISTS protection_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rule_name VARCHAR(100) NOT NULL,
        rule_type ENUM('sql', 'xss', 'cc', 'custom') NOT NULL,
        pattern TEXT,
        enabled TINYINT(1) DEFAULT 1,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
    
    // 创建访问日志表
    $pdo->exec("CREATE TABLE IF NOT EXISTS access_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        timestamp DATETIME NOT NULL,
        ip VARCHAR(45) NOT NULL,
        url TEXT,
        method VARCHAR(10),
        status_code INT,
        user_agent TEXT,
        INDEX idx_timestamp (timestamp),
        INDEX idx_ip (ip)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET);
    
    // 插入默认管理员用户
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
    $default_password_hash = password_hash('admin', PASSWORD_DEFAULT);
    $stmt->execute(['admin', $default_password_hash, 'admin@example.com', 'admin']);
    
    // 插入默认防护规则
    $default_rules = [
        ['SQL注入防护', 'sql', '(\\%27)|(\\')|(--)|(%23)|(#)', 1, '检测SQL注释和引号'],
        ['XSS防护-Script标签', 'xss', '<script\\b[^<]*(?:(?!<\\/script>)<[^<]*)*<\\/script>', 1, '检测Script标签'],
        ['XSS防护-JavaScript协议', 'xss', 'javascript:', 1, '检测JavaScript协议'],
        ['XSS防护-On事件', 'xss', 'on(load|error|click|mouseover)=', 1, '检测常见事件处理器']
    ];
    
    $rule_stmt = $pdo->prepare("INSERT IGNORE INTO protection_rules (rule_name, rule_type, pattern, enabled, description) VALUES (?, ?, ?, ?, ?)");
    foreach ($default_rules as $rule) {
        $rule_stmt->execute($rule);
    }
    
    echo "数据库初始化成功！\n";
} catch (PDOException $e) {
    echo "数据库初始化失败: " . $e->getMessage() . "\n";
    echo "请检查数据库配置并在config/config.php中正确设置数据库连接参数。\n";
}

// 创建初始封禁IP文件
$blocked_ips_file = __DIR__ . '/config/blocked_ips.json';
if (!file_exists($blocked_ips_file)) {
    file_put_contents($blocked_ips_file, '{}');
}

// 设置目录权限
chmod(__DIR__ . '/logs', 0755);
chmod(__DIR__ . '/config', 0755);

echo "WAF系统部署完成！\n";
echo "默认登录信息：\n";
echo "用户名：admin\n";
echo "密码：admin\n";
echo "请访问 login.php 进行登录\n";

// 提示用户修改默认密码
echo "\n安全提示：\n";
echo "1. 请立即修改默认登录密码\n";
echo "2. 建议修改 config/config.php 中的数据库连接参数\n";
echo "3. 检查并设置适当的防护参数\n";
echo "4. 定期备份配置和日志文件\n";
echo "5. 确保数据库连接安全\n";