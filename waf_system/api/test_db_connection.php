<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '只允许POST请求']);
    exit;
}

$db_host = $_POST['db_host'] ?? '';
$db_port = $_POST['db_port'] ?? '3306';
$db_user = $_POST['db_user'] ?? '';
$db_pass = $_POST['db_pass'] ?? '';

try {
    $pdo = new PDO("mysql:host={$db_host};port={$db_port};charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 测试连接
    $pdo->query("SELECT 1");
    
    echo json_encode(['success' => true, 'message' => '数据库连接成功']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}