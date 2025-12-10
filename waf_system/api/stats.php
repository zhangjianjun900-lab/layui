<?php
/**
 * 获取系统统计信息API
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// 验证API密钥
if (!validate_api_key($_GET['api_key'] ?? '')) {
    send_json_response(['success' => false, 'message' => 'Invalid API key'], 401);
}

// 获取今日攻击次数
$today_log_file = LOG_PATH . 'attacks_' . date('Y-m-d') . '.log';
$total_attacks = 0;
if (file_exists($today_log_file)) {
    $lines = file($today_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $total_attacks = count($lines);
}

// 获取封禁IP数量
$blocked_ips_file = CONFIG_PATH . 'blocked_ips.json';
$blocked_ips = 0;
if (file_exists($blocked_ips_file)) {
    $content = file_get_contents($blocked_ips_file);
    $ips = $content ? json_decode($content, true) : [];
    if ($ips) {
        // 过滤掉已过期的IP
        $current_time = time();
        $active_ips = array_filter($ips, function($info) use ($current_time) {
            return $info['expires_at'] > $current_time;
        });
        $blocked_ips = count($active_ips);
    }
}

// 获取受保护域名数量（这里简单模拟，实际应从数据库获取）
$protected_domains = 5; // 模拟数据

// 系统状态（这里简单判断是否有异常）
$system_status = '正常';

send_json_response([
    'success' => true,
    'data' => [
        'total_attacks' => $total_attacks,
        'blocked_ips' => $blocked_ips,
        'protected_domains' => $protected_domains,
        'system_status' => $system_status
    ]
]);