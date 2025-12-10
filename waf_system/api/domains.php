<?php
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../includes/functions.php';

// 验证API密钥
if (!validate_api_key($_GET['api_key'] ?? $_POST['api_key'] ?? '')) {
    send_json_response(['success' => false, 'message' => 'Invalid API key'], 401);
}

$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 10);
$offset = ($page - 1) * $limit;

try {
    $pdo = get_db_connection();
    if (!$pdo) {
        throw new Exception('数据库连接失败');
    }
    
    // 获取总数
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM protected_domains");
    $total = $count_stmt->fetchColumn();
    
    // 获取分页数据
    $stmt = $pdo->prepare("SELECT * FROM protected_domains ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    send_json_response([
        'success' => true,
        'data' => [
            'domains' => $domains,
            'total' => $total
        ]
    ]);
} catch (Exception $e) {
    send_json_response(['success' => false, 'message' => $e->getMessage()]);
}