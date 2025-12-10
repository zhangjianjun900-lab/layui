<?php
/**
 * 封禁IP API
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// 验证API密钥
if (!validate_api_key($_POST['api_key'] ?? '')) {
    send_json_response(['success' => false, 'message' => 'Invalid API key'], 401);
}

$ip = $_POST['ip'] ?? '';
$reason = $_POST['reason'] ?? 'Manual block';
$duration = (int)($_POST['duration'] ?? BLOCK_TIME);

if (empty($ip)) {
    send_json_response(['success' => false, 'message' => 'IP address is required'], 400);
}

// 验证IP格式
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    send_json_response(['success' => false, 'message' => 'Invalid IP address'], 400);
}

// 封禁IP
block_ip($ip, $reason, $duration);

send_json_response([
    'success' => true,
    'message' => 'IP blocked successfully'
]);