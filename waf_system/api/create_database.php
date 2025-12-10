<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '只允许POST请求']);
    exit;
}

$db_host = $_POST['db_host'] ?? '';
$db_port = $_POST['db_port'] ?? '3306';
$db_name = $_POST['db_name'] ?? '';
$db_user = $_POST['db_user'] ?? '';
$db_pass = $_POST['db_pass'] ?? '';

try {
    $pdo = new PDO("mysql:host={$db_host};port={$db_port};charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 创建数据库（如果不存在）
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    
    echo json_encode(['success' => true, 'message' => '数据库创建成功']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}