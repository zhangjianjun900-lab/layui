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

$id = $_POST['id'] ?? null;
$rule_name = $_POST['rule_name'] ?? '';
$rule_type = $_POST['rule_type'] ?? '';
$pattern = $_POST['pattern'] ?? '';
$description = $_POST['description'] ?? '';
$enabled = isset($_POST['enabled']) ? (int)$_POST['enabled'] : 1;

// 验证必要字段
if (empty($rule_name) || empty($rule_type)) {
    send_json_response(['success' => false, 'message' => '规则名称和类型不能为空']);
}

try {
    $pdo = get_db_connection();
    if (!$pdo) {
        throw new Exception('数据库连接失败');
    }
    
    if ($id) {
        // 更新现有规则
        $stmt = $pdo->prepare("UPDATE protection_rules SET rule_name = ?, rule_type = ?, pattern = ?, description = ?, enabled = ? WHERE id = ?");
        $result = $stmt->execute([$rule_name, $rule_type, $pattern, $description, $enabled, $id]);
        
        if ($result) {
            send_json_response(['success' => true, 'message' => '规则更新成功']);
        } else {
            send_json_response(['success' => false, 'message' => '规则更新失败']);
        }
    } else {
        // 添加新规则
        $stmt = $pdo->prepare("INSERT INTO protection_rules (rule_name, rule_type, pattern, description, enabled) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$rule_name, $rule_type, $pattern, $description, $enabled]);
        
        if ($result) {
            send_json_response(['success' => true, 'message' => '规则添加成功']);
        } else {
            send_json_response(['success' => false, 'message' => '规则添加失败']);
        }
    }
} catch (Exception $e) {
    send_json_response(['success' => false, 'message' => $e->getMessage()]);
}