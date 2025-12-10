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
    send_json_response(['success' => false, 'message' => '无效的用户ID']);
}

try {
    $pdo = get_db_connection();
    if (!$pdo) {
        throw new Exception('数据库连接失败');
    }
    
    $stmt = $pdo->prepare("SELECT id, username, email, role, created_at, updated_at FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        send_json_response([
            'success' => true,
            'data' => $user
        ]);
    } else {
        send_json_response(['success' => false, 'message' => '用户不存在']);
    }
} catch (Exception $e) {
    send_json_response(['success' => false, 'message' => $e->getMessage()]);
}