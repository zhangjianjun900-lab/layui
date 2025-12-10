<?php
/**
 * WAF系统部署脚本
 */

// 检查PHP版本
if (version_compare(PHP_VERSION, '7.0', '<')) {
    die('PHP版本过低，请使用PHP 7.0或更高版本');
}

// 检查必需的扩展
$required_extensions = ['json', 'filter', 'pcre', 'session'];
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
echo "2. 建议修改 config/config.php 中的 API_SECRET_KEY\n";
echo "3. 检查并设置适当的防护参数\n";
echo "4. 定期备份配置和日志文件\n";