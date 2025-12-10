<?php
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../includes/functions.php';

// 验证API密钥
if (!validate_api_key($_GET['api_key'] ?? $_POST['api_key'] ?? '')) {
    send_json_response(['success' => false, 'message' => 'Invalid API key'], 401);
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    send_json_response(['success' => false, 'message' => '无效的规则ID']);
}

try {
    $pdo = get_db_connection();
    if (!$pdo) {
        throw new Exception('数据库连接失败');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM protection_rules WHERE id = ?");
    $stmt->execute([$id]);
    $rule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($rule) {
        send_json_response([
            'success' => true,
            'data' => $rule
        ]);
    } else {
        send_json_response(['success' => false, 'message' => '规则不存在']);
    }
} catch (Exception $e) {
    send_json_response(['success' => false, 'message' => $e->getMessage()]);
}