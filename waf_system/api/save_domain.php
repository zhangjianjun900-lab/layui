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

$id = $_POST['id'] ?? null;
$domain = $_POST['domain'] ?? '';
$description = $_POST['description'] ?? '';
$enabled = isset($_POST['enabled']) ? (int)$_POST['enabled'] : 1;

// 验证必要字段
if (empty($domain)) {
    send_json_response(['success' => false, 'message' => '域名不能为空']);
}

// 验证域名格式
if (!filter_var('http://' . $domain, FILTER_VALIDATE_URL) && !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9](\.[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9])*$/i', $domain)) {
    send_json_response(['success' => false, 'message' => '域名格式不正确']);
}

try {
    $pdo = get_db_connection();
    if (!$pdo) {
        throw new Exception('数据库连接失败');
    }
    
    if ($id) {
        // 更新现有域名
        $stmt = $pdo->prepare("UPDATE protected_domains SET domain = ?, description = ?, enabled = ? WHERE id = ?");
        $result = $stmt->execute([$domain, $description, $enabled, $id]);
        
        if ($result) {
            send_json_response(['success' => true, 'message' => '域名更新成功']);
        } else {
            send_json_response(['success' => false, 'message' => '域名更新失败']);
        }
    } else {
        // 检查域名是否已存在
        $check_stmt = $pdo->prepare("SELECT id FROM protected_domains WHERE domain = ?");
        $check_stmt->execute([$domain]);
        $existing = $check_stmt->fetch();
        
        if ($existing) {
            send_json_response(['success' => false, 'message' => '域名已存在']);
        }
        
        // 添加新域名
        $stmt = $pdo->prepare("INSERT INTO protected_domains (domain, description, enabled) VALUES (?, ?, ?)");
        $result = $stmt->execute([$domain, $description, $enabled]);
        
        if ($result) {
            send_json_response(['success' => true, 'message' => '域名添加成功']);
        } else {
            send_json_response(['success' => false, 'message' => '域名添加失败']);
        }
    }
} catch (Exception $e) {
    send_json_response(['success' => false, 'message' => $e->getMessage()]);
}