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

// 验证权限（必须是管理员）
session_start();
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['role'] !== 'admin') {
    send_json_response(['success' => false, 'message' => '权限不足'], 403);
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    send_json_response(['success' => false, 'message' => '无效的用户ID']);
}

// 不能删除自己
if (isset($_SESSION['user_id']) && $id == $_SESSION['user_id']) {
    send_json_response(['success' => false, 'message' => '不能删除自己']);
}

try {
    $pdo = get_db_connection();
    if (!$pdo) {
        throw new Exception('数据库连接失败');
    }
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        send_json_response(['success' => true, 'message' => '用户删除成功']);
    } else {
        send_json_response(['success' => false, 'message' => '用户删除失败']);
    }
} catch (Exception $e) {
    send_json_response(['success' => false, 'message' => $e->getMessage()]);
}