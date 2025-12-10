<?php
/**
 * WAF系统配置文件
 */

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'waf_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 系统配置
define('SITE_NAME', 'WAF防护系统');
define('VERSION', '1.0.0');
define('DEBUG', false);

// 防护配置
define('ENABLE_SQL_PROTECTION', true);
define('ENABLE_XSS_PROTECTION', true);
define('ENABLE_CC_PROTECTION', true);
define('CC_RATE_LIMIT', 100); // 每分钟请求数限制
define('CC_TIME_WINDOW', 60); // 时间窗口（秒）
define('BLOCK_TIME', 3600); // 封禁时间（秒）

// 日志配置
define('LOG_ATTACKS', true);
define('LOG_ACCESS', true);
define('MAX_LOG_RETENTION', 30); // 日志保留天数

// 路径配置
define('BASE_PATH', dirname(dirname(__FILE__)));
define('LOG_PATH', BASE_PATH . '/logs/');
define('CONFIG_PATH', BASE_PATH . '/config/');

// API密钥（用于保护API接口）
define('API_SECRET_KEY', 'waf_system_secret_key_2023');