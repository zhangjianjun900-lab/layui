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

if ($id <= 0) {
    send_json_response(['success' => false, 'message' => '无效的规则ID']);
}

try {
    $pdo = get_db_connection();
    if (!$pdo) {
        throw new Exception('数据库连接失败');
    }
    
    $stmt = $pdo->prepare("DELETE FROM protection_rules WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        send_json_response(['success' => true, 'message' => '规则删除成功']);
    } else {
        send_json_response(['success' => false, 'message' => '规则删除失败']);
    }
} catch (Exception $e) {
    send_json_response(['success' => false, 'message' => $e->getMessage()]);
}