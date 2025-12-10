<?php
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(['success' => false, 'message' => '只允许POST请求']);
}

// 验证API密钥
if (!validate_api_key($_POST['api_key'] ?? '')) {
    send_json_response(['success' => false, 'message' => 'Invalid API key'], 401);
}

$id = (int)($_POST['id'] ?? 0);
$enabled = (int)($_POST['enabled'] ?? 0);

if ($id <= 0) {
    send_json_response(['success' => false, 'message' => '无效的规则ID']);
}

if ($enabled !== 0 && $enabled !== 1) {
    send_json_response(['success' => false, 'message' => '无效的状态值']);
}

try {
    $pdo = get_db_connection();
    if (!$pdo) {
        throw new Exception('数据库连接失败');
    }
    
    $stmt = $pdo->prepare("UPDATE protection_rules SET enabled = ? WHERE id = ?");
    $result = $stmt->execute([$enabled, $id]);
    
    if ($result) {
        $status_text = $enabled ? '启用' : '禁用';
        send_json_response(['success' => true, 'message' => "规则{$status_text}成功"]);
    } else {
        send_json_response(['success' => false, 'message' => '规则状态更新失败']);
    }
} catch (Exception $e) {
    send_json_response(['success' => false, 'message' => $e->getMessage()]);
}