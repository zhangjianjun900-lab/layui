<?php
/**
 * 批量解封IP API
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// 验证API密钥
if (!validate_api_key($_POST['api_key'] ?? '')) {
    send_json_response(['success' => false, 'message' => 'Invalid API key'], 401);
}

$ips = $_POST['ips'] ?? [];

if (empty($ips) || !is_array($ips)) {
    send_json_response(['success' => false, 'message' => 'IPs array is required'], 400);
}

// 验证IP格式
foreach ($ips as $ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        send_json_response(['success' => false, 'message' => "Invalid IP address: {$ip}"], 400);
    }
}

// 从封禁列表中移除IP
$blocked_ips_file = CONFIG_PATH . 'blocked_ips.json';
$blocked_ips = [];

if (file_exists($blocked_ips_file)) {
    $content = file_get_contents($blocked_ips_file);
    $blocked_ips = $content ? json_decode($content, true) : [];
}

$unblocked_count = 0;
foreach ($ips as $ip) {
    if (isset($blocked_ips[$ip])) {
        unset($blocked_ips[$ip]);
        $unblocked_count++;
    }
}

file_put_contents($blocked_ips_file, json_encode($blocked_ips));

send_json_response([
    'success' => true,
    'message' => "{$unblocked_count} IP(s) unblocked successfully"
]);