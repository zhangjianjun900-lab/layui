<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '只允许POST请求']);
    exit;
}

// 获取POST数据
$db_host = $_POST['db_host'] ?? '';
$db_port = $_POST['db_port'] ?? '3306';
$db_name = $_POST['db_name'] ?? '';
$db_user = $_POST['db_user'] ?? '';
$db_pass = $_POST['db_pass'] ?? '';
$admin_user = $_POST['admin_user'] ?? '';
$admin_pass = $_POST['admin_pass'] ?? '';

// 验证数据
if (empty($db_host) || empty($db_port) || empty($db_name) || empty($db_user) || 
    empty($admin_user) || empty($admin_pass)) {
    echo json_encode(['success' => false, 'message' => '缺少必要参数']);
    exit;
}

if (strlen($admin_pass) < 6) {
    echo json_encode(['success' => false, 'message' => '管理员密码长度不能少于6位']);
    exit;
}

try {
    // 创建数据库连接
    $pdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 生成配置文件内容
    $config_content = "<?php
/**\n * WAF系统配置文件\n */\n\n// 数据库配置\ndefine('DB_HOST', '{$db_host}');\ndefine('DB_PORT', '{$db_port}');\ndefine('DB_NAME', '{$db_name}');\ndefine('DB_USER', '{$db_user}');\ndefine('DB_PASS', '{$db_pass}');\ndefine('DB_CHARSET', 'utf8mb4');\n\n// 系统配置\ndefine('SITE_NAME', 'WAF防护系统');\ndefine('VERSION', '1.0.0');\ndefine('DEBUG', false);\n\n// 防护配置\ndefine('ENABLE_SQL_PROTECTION', true);\ndefine('ENABLE_XSS_PROTECTION', true);\ndefine('ENABLE_CC_PROTECTION', true);\ndefine('CC_RATE_LIMIT', 100); // 每分钟请求数限制\ndefine('CC_TIME_WINDOW', 60); // 时间窗口（秒）\ndefine('BLOCK_TIME', 3600); // 封禁时间（秒）\n\n// 日志配置\ndefine('LOG_ATTACKS', true);\ndefine('LOG_ACCESS', true);\ndefine('MAX_LOG_RETENTION', 30); // 日志保留天数\n\n// 路径配置\ndefine('BASE_PATH', dirname(dirname(__FILE__))); // 修正路径\ndefine('LOG_PATH', BASE_PATH . '/logs/');\ndefine('CONFIG_PATH', BASE_PATH . '/config/');\n\n// API密钥（用于保护API接口）\ndefine('API_SECRET_KEY', 'waf_system_secret_key_' . time());\n";
    
    // 确保config目录存在
    if (!is_dir(dirname(__FILE__) . '/../config')) {
        mkdir(dirname(__FILE__) . '/../config', 0755, true);
    }
    
    // 写入配置文件
    $config_file = dirname(__FILE__) . '/../config/config.php';
    if (file_put_contents($config_file, $config_content) === false) {
        echo json_encode(['success' => false, 'message' => '配置文件写入失败，请检查目录权限']);
        exit;
    }
    
    // 插入默认管理员用户
    $default_password_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$admin_user, $default_password_hash, 'admin@example.com', 'admin']);
    
    // 插入默认防护规则
    $default_rules = [
        ['SQL注入防护', 'sql', '(\\%27)|(\\')|(--)|(%23)|(#)', 1, '检测SQL注释和引号'],
        ['XSS防护-Script标签', 'xss', '<script\\b[^<]*(?:(?!<\\/script>)<[^<]*)*<\\/script>', 1, '检测Script标签'],
        ['XSS防护-JavaScript协议', 'xss', 'javascript:', 1, '检测JavaScript协议'],
        ['XSS防护-On事件', 'xss', 'on(load|error|click|mouseover)=', 1, '检测常见事件处理器'],
        ['CC攻击防护', 'cc', '', 1, '限制单位时间内的请求次数']
    ];
    
    $rule_stmt = $pdo->prepare("INSERT INTO protection_rules (rule_name, rule_type, pattern, enabled, description) VALUES (?, ?, ?, ?, ?)");
    foreach ($default_rules as $rule) {
        $rule_stmt->execute($rule);
    }
    
    echo json_encode(['success' => true, 'message' => '配置文件创建成功']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}