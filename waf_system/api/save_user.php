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
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$role = $_POST['role'] ?? 'user';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// 验证必要字段
if (empty($username) || empty($email)) {
    send_json_response(['success' => false, 'message' => '用户名和邮箱不能为空']);
}

// 验证邮箱格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_json_response(['success' => false, 'message' => '邮箱格式不正确']);
}

// 验证角色
if (!in_array($role, ['admin', 'user'])) {
    send_json_response(['success' => false, 'message' => '角色不正确']);
}

// 验证密码（如果提供了密码）
if (!empty($password) && $password !== $confirm_password) {
    send_json_response(['success' => false, 'message' => '两次输入的密码不一致']);
}

try {
    $pdo = get_db_connection();
    if (!$pdo) {
        throw new Exception('数据库连接失败');
    }
    
    if ($id) {
        // 更新现有用户
        if (!empty($password)) {
            // 更新密码
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, password_hash = ? WHERE id = ?");
            $result = $stmt->execute([$username, $email, $role, $password_hash, $id]);
        } else {
            // 不更新密码
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
            $result = $stmt->execute([$username, $email, $role, $id]);
        }
        
        if ($result) {
            send_json_response(['success' => true, 'message' => '用户更新成功']);
        } else {
            send_json_response(['success' => false, 'message' => '用户更新失败']);
        }
    } else {
        // 检查用户名是否已存在
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->execute([$username, $email]);
        $existing = $check_stmt->fetch();
        
        if ($existing) {
            send_json_response(['success' => false, 'message' => '用户名或邮箱已存在']);
        }
        
        // 添加新用户
        $password_hash = password_hash($password ?: '123456', PASSWORD_DEFAULT); // 默认密码
        $stmt = $pdo->prepare("INSERT INTO users (username, email, role, password_hash) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$username, $email, $role, $password_hash]);
        
        if ($result) {
            send_json_response(['success' => true, 'message' => '用户添加成功']);
        } else {
            send_json_response(['success' => false, 'message' => '用户添加失败']);
        }
    }
} catch (Exception $e) {
    send_json_response(['success' => false, 'message' => $e->getMessage()]);
}