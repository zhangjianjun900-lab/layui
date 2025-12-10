<?php
/**
 * 获取攻击日志API
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// 验证API密钥
if (!validate_api_key($_GET['api_key'] ?? '')) {
    send_json_response(['success' => false, 'message' => 'Invalid API key'], 401);
}

$limit = (int)($_GET['limit'] ?? 10);
$page = (int)($_GET['page'] ?? 1);
$offset = ($page - 1) * $limit;

// 获取今日攻击日志
$today_log_file = LOG_PATH . 'attacks_' . date('Y-m-d') . '.log';
$attacks = [];

if (file_exists($today_log_file)) {
    $lines = file($today_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $total = count($lines);
    
    // 反向取最近的记录
    $lines = array_reverse($lines);
    $attacks = array_slice($lines, 0, $limit);
    
    // 解析JSON
    foreach ($attacks as &$attack) {
        $attack = json_decode($attack, true);
    }
}

send_json_response([
    'success' => true,
    'data' => [
        'attacks' => $attacks,
        'total' => count($attacks)
    ]
]);