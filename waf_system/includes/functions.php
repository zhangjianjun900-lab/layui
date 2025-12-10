<?php
/**
 * WAF系统通用函数库
 */

/**
 * 获取数据库连接
 */
function get_db_connection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log("数据库连接失败: " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

/**
 * 记录攻击日志
 */
function log_attack($type, $details, $client_ip, $url, $method) {
    if (!defined('LOG_ATTACKS') || !LOG_ATTACKS) {
        return;
    }

    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => $type,
        'details' => $details,
        'ip' => $client_ip,
        'url' => $url,
        'method' => $method,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];

    // 记录到文件
    $log_file = LOG_PATH . 'attacks_' . date('Y-m-d') . '.log';
    file_put_contents($log_file, json_encode($log_entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    
    // 记录到数据库
    $pdo = get_db_connection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO attack_logs (timestamp, type, details, ip, url, method, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $log_entry['timestamp'],
                $log_entry['type'],
                $log_entry['details'],
                $log_entry['ip'],
                $log_entry['url'],
                $log_entry['method'],
                $log_entry['user_agent']
            ]);
        } catch (PDOException $e) {
            error_log("数据库记录攻击日志失败: " . $e->getMessage());
        }
    }
}

/**
 * 记录访问日志
 */
function log_access($client_ip, $url, $method, $status_code) {
    if (!defined('LOG_ACCESS') || !LOG_ACCESS) {
        return;
    }

    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $client_ip,
        'url' => $url,
        'method' => $method,
        'status' => $status_code,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];

    // 记录到文件
    $log_file = LOG_PATH . 'access_' . date('Y-m-d') . '.log';
    file_put_contents($log_file, json_encode($log_entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    
    // 记录到数据库（可选，如果存在访问日志表）
    $pdo = get_db_connection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO access_logs (timestamp, ip, url, method, status_code, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $log_entry['timestamp'],
                $log_entry['ip'],
                $log_entry['url'],
                $log_entry['method'],
                $log_entry['status'],
                $log_entry['user_agent']
            ]);
        } catch (PDOException $e) {
            // 如果访问日志表不存在，忽略错误（可能在数据库中没有创建该表）
            // error_log("数据库记录访问日志失败: " . $e->getMessage());
        }
    }
}

/**
 * 检查IP是否被封禁
 */
function is_ip_blocked($ip) {
    // 首先检查数据库
    $pdo = get_db_connection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM blocked_ips WHERE ip = ? AND (expires_at IS NULL OR expires_at > NOW())");
            $stmt->execute([$ip]);
            $blocked_ip = $stmt->fetch();
            if ($blocked_ip) {
                return true;
            }
        } catch (PDOException $e) {
            error_log("数据库查询封禁IP失败: " . $e->getMessage());
        }
    }
    
    // 然后检查文件中的封禁IP（向后兼容）
    $blocked_ips_file = CONFIG_PATH . 'blocked_ips.json';
    if (!file_exists($blocked_ips_file)) {
        return false;
    }

    $blocked_ips = json_decode(file_get_contents($blocked_ips_file), true);
    if (!$blocked_ips) {
        return false;
    }

    foreach ($blocked_ips as $blocked_ip => $info) {
        if ($info['expires_at'] < time()) {
            // 清除过期的封禁记录
            unset($blocked_ips[$blocked_ip]);
            file_put_contents($blocked_ips_file, json_encode($blocked_ips));
        } elseif ($blocked_ip === $ip || match_ip_against_cidr($ip, $blocked_ip)) {
            return true;
        }
    }

    return false;
}

/**
 * CIDR IP匹配
 */
function match_ip_against_cidr($ip, $cidr) {
    list($subnet, $mask) = explode('/', $cidr);
    if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($subnet, FILTER_VALIDATE_IP)) {
        return false;
    }

    $ip_binary = inet_pton($ip);
    $subnet_binary = inet_pton($subnet);
    
    if ($ip_binary === false || $subnet_binary === false) {
        return false;
    }

    $mask_binary = str_repeat(chr(255), intval($mask / 8)) . str_repeat(chr(0), 16 - ceil($mask / 8));
    if ($mask % 8) {
        $mask_binary .= chr((0xff << (8 - ($mask % 8))) & 0xff);
        $mask_binary .= str_repeat(chr(0), 15 - strlen($mask_binary));
    }

    return ($ip_binary & $mask_binary) === ($subnet_binary & $mask_binary);
}

/**
 * 封禁IP
 */
function block_ip($ip, $reason = 'Security violation', $duration = BLOCK_TIME) {
    // 将封禁信息存储到数据库
    $pdo = get_db_connection();
    if ($pdo) {
        try {
            // 检查IP是否已被封禁
            $stmt = $pdo->prepare("SELECT * FROM blocked_ips WHERE ip = ?");
            $stmt->execute([$ip]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // 如果IP已存在，更新封禁信息
                $expires_at = $duration ? date('Y-m-d H:i:s', time() + $duration) : null;
                $update_stmt = $pdo->prepare("UPDATE blocked_ips SET reason = ?, blocked_at = CURRENT_TIMESTAMP, expires_at = ? WHERE ip = ?");
                $update_stmt->execute([$reason, $expires_at, $ip]);
            } else {
                // 插入新的封禁记录
                $expires_at = $duration ? date('Y-m-d H:i:s', time() + $duration) : null;
                $insert_stmt = $pdo->prepare("INSERT INTO blocked_ips (ip, reason, expires_at) VALUES (?, ?, ?)");
                $insert_stmt->execute([$ip, $reason, $expires_at]);
            }
        } catch (PDOException $e) {
            error_log("数据库封禁IP失败: " . $e->getMessage());
        }
    }
    
    // 同时保存到文件（向后兼容）
    $blocked_ips_file = CONFIG_PATH . 'blocked_ips.json';
    $blocked_ips = [];
    
    if (file_exists($blocked_ips_file)) {
        $content = file_get_contents($blocked_ips_file);
        $blocked_ips = $content ? json_decode($content, true) : [];
    }

    $blocked_ips[$ip] = [
        'reason' => $reason,
        'blocked_at' => time(),
        'expires_at' => time() + $duration
    ];

    file_put_contents($blocked_ips_file, json_encode($blocked_ips));
}

/**
 * 检测SQL注入
 */
function detect_sql_injection($input) {
    $patterns = [
        '/(\%27)|(\')|(--)|(%23)|(#)/i',  // 注释符号
        '/((\%3D)|(=))[^\n]*((\%27)|(\')|(\-\-)|(\%3B)|(;))/i',  // SQL语句
        '/\w*((\%27)|(\'))((\%6F)|o|(\%4F))((\%72)|r|(\%52))/i',  // OR
        '/((\%27)|(\'))union/i',  // UNION
        '/exec(\s|\+)+(s|x)p\s+/i',  // 执行存储过程
        '/drop(\s|\+)+table/i',  // 删除表
        '/truncate(\s|\+)+table/i',  // 截断表
        '/drop(\s|\+)+database/i',  // 删除数据库
        '/shutdown(\s|\+)+(with|\+)now/i'  // 关闭服务器
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }

    return false;
}

/**
 * 检测XSS攻击
 */
function detect_xss($input) {
    $patterns = [
        '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',  // script标签
        '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',  // iframe标签
        '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi',  // object标签
        '/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/mi',  // embed标签
        '/<form\b[^<]*(?:(?!<\/form>)<[^<]*)*<\/form>/mi',  // form标签
        '/javascript:/i',  // javascript协议
        '/vbscript:/i',  // vbscript协议
        '/onload=/i',  // onload事件
        '/onerror=/i',  // onerror事件
        '/onclick=/i',  // onclick事件
        '/onmouseover=/i',  // onmouseover事件
        '/onfocus=/i',  // onfocus事件
        '/onblur=/i',  // onblur事件
        '/<svg\b[^<]*(?:(?!<\/svg>)<[^<]*)*<\/svg>/mi',  // svg标签
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }

    return false;
}

/**
 * 检测CC攻击
 */
function detect_cc_attack($client_ip) {
    if (!defined('ENABLE_CC_PROTECTION') || !ENABLE_CC_PROTECTION) {
        return false;
    }

    $requests_file = LOG_PATH . 'cc_requests_' . date('Y-m-d') . '.json';
    $requests = [];

    if (file_exists($requests_file)) {
        $content = file_get_contents($requests_file);
        $requests = $content ? json_decode($content, true) : [];
    }

    $current_time = time();
    $time_window_start = $current_time - CC_TIME_WINDOW;

    // 清理过期的请求记录
    $requests = array_filter($requests, function($request_time) use ($time_window_start) {
        return $request_time > $time_window_start;
    });

    // 添加当前请求
    $requests[] = $current_time;

    // 检查是否超过阈值
    $request_count = count($requests);
    if ($request_count > CC_RATE_LIMIT) {
        // 记录CC攻击日志
        log_attack('CC Attack', "IP {$client_ip} made {$request_count} requests in " . CC_TIME_WINDOW . " seconds", $client_ip, $_SERVER['REQUEST_URI'] ?? '', $_SERVER['REQUEST_METHOD'] ?? '');
        
        // 封禁IP
        block_ip($client_ip, "CC Attack - {$request_count} requests in " . CC_TIME_WINDOW . " seconds");
        
        file_put_contents($requests_file, json_encode($requests));
        return true;
    }

    file_put_contents($requests_file, json_encode($requests));
    return false;
}

/**
 * 获取客户端真实IP
 */
function get_client_ip() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            // 处理多个IP的情况，取第一个
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * 验证API密钥
 */
function validate_api_key($provided_key) {
    return hash_equals(API_SECRET_KEY, $provided_key);
}

/**
 * 发送JSON响应
 */
function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * 获取系统统计数据
 */
function get_system_stats() {
    $pdo = get_db_connection();
    
    // 统计攻击次数
    $attacks_count = 0;
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM attack_logs");
            $attacks_count = $stmt->fetchColumn();
        } catch (PDOException $e) {
            // 如果数据库表不存在，回退到文件统计
            $log_files = glob(LOG_PATH . 'attacks_*.log');
            foreach ($log_files as $file) {
                $attacks_count += count(file($file, FILE_SKIP_EMPTY_LINES));
            }
        }
    } else {
        // 如果数据库连接失败，使用文件统计
        $log_files = glob(LOG_PATH . 'attacks_*.log');
        foreach ($log_files as $file) {
            $attacks_count += count(file($file, FILE_SKIP_EMPTY_LINES));
        }
    }

    // 统计封禁IP数量
    $blocked_ips_count = 0;
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM blocked_ips WHERE expires_at IS NULL OR expires_at > NOW()");
            $blocked_ips_count = $stmt->fetchColumn();
        } catch (PDOException $e) {
            // 如果数据库表不存在，回退到文件统计
            $blocked_ips_file = CONFIG_PATH . 'blocked_ips.json';
            if (file_exists($blocked_ips_file)) {
                $blocked_ips = json_decode(file_get_contents($blocked_ips_file), true);
                if ($blocked_ips) {
                    // 过滤掉过期的IP
                    $current_time = time();
                    $active_blocked_ips = array_filter($blocked_ips, function($info) use ($current_time) {
                        return $info['expires_at'] > $current_time;
                    });
                    $blocked_ips_count = count($active_blocked_ips);
                }
            }
        }
    } else {
        // 如果数据库连接失败，使用文件统计
        $blocked_ips_file = CONFIG_PATH . 'blocked_ips.json';
        if (file_exists($blocked_ips_file)) {
            $blocked_ips = json_decode(file_get_contents($blocked_ips_file), true);
            if ($blocked_ips) {
                // 过滤掉过期的IP
                $current_time = time();
                $active_blocked_ips = array_filter($blocked_ips, function($info) use ($current_time) {
                    return $info['expires_at'] > $current_time;
                });
                $blocked_ips_count = count($active_blocked_ips);
            }
        }
    }

    // 统计保护域名数量（这里假设从配置文件读取）
    $protected_domains_count = 0;
    $domains_file = CONFIG_PATH . 'protected_domains.json';
    if (file_exists($domains_file)) {
        $domains = json_decode(file_get_contents($domains_file), true);
        $protected_domains_count = $domains ? count($domains) : 0;
    } else {
        // 如果文件不存在，创建一个空文件
        file_put_contents($domains_file, json_encode([]));
    }

    // 统计激活规则数量
    $active_rules_count = 0;
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM protection_rules WHERE enabled = 1");
            $active_rules_count = $stmt->fetchColumn();
        } catch (PDOException $e) {
            // 如果数据库表不存在，回退到文件统计
            $rules_file = CONFIG_PATH . 'rules.json';
            if (file_exists($rules_file)) {
                $rules = json_decode(file_get_contents($rules_file), true);
                $active_rules_count = $rules ? count(array_filter($rules, function($rule) {
                    return isset($rule['enabled']) && $rule['enabled'];
                })) : 0;
            } else {
                // 如果文件不存在，创建一个包含默认规则的文件
                $default_rules = [
                    'sql_protection' => ['name' => 'SQL注入防护', 'enabled' => ENABLE_SQL_PROTECTION],
                    'xss_protection' => ['name' => 'XSS防护', 'enabled' => ENABLE_XSS_PROTECTION],
                    'cc_protection' => ['name' => 'CC攻击防护', 'enabled' => ENABLE_CC_PROTECTION]
                ];
                file_put_contents($rules_file, json_encode($default_rules));
                $active_rules_count = count(array_filter($default_rules, function($rule) {
                    return isset($rule['enabled']) && $rule['enabled'];
                }));
            }
        }
    } else {
        // 如果数据库连接失败，使用文件统计
        $rules_file = CONFIG_PATH . 'rules.json';
        if (file_exists($rules_file)) {
            $rules = json_decode(file_get_contents($rules_file), true);
            $active_rules_count = $rules ? count(array_filter($rules, function($rule) {
                return isset($rule['enabled']) && $rule['enabled'];
            })) : 0;
        } else {
            // 如果文件不存在，创建一个包含默认规则的文件
            $default_rules = [
                'sql_protection' => ['name' => 'SQL注入防护', 'enabled' => ENABLE_SQL_PROTECTION],
                'xss_protection' => ['name' => 'XSS防护', 'enabled' => ENABLE_XSS_PROTECTION],
                'cc_protection' => ['name' => 'CC攻击防护', 'enabled' => ENABLE_CC_PROTECTION]
            ];
            file_put_contents($rules_file, json_encode($default_rules));
            $active_rules_count = count(array_filter($default_rules, function($rule) {
                return isset($rule['enabled']) && $rule['enabled'];
            }));
        }
    }

    return [
        'total_attacks' => $attacks_count,
        'blocked_ips' => $blocked_ips_count,
        'protected_domains' => $protected_domains_count,
        'active_rules' => $active_rules_count
    ];
}