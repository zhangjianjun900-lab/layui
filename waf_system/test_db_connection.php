<?php
/**
 * WAF系统数据库连接测试脚本
 */

// 加载配置
require_once 'config/config.php';
require_once 'includes/functions.php';

echo "正在测试数据库连接...\n";

try {
    $pdo = get_db_connection();
    
    if ($pdo) {
        echo "✓ 数据库连接成功！\n";
        
        // 测试查询
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "✓ 用户表连接正常，当前用户数量: " . $result['count'] . "\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM attack_logs");
        $result = $stmt->fetch();
        echo "✓ 攻击日志表连接正常，当前日志数量: " . $result['count'] . "\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM blocked_ips WHERE expires_at IS NULL OR expires_at > NOW()");
        $result = $stmt->fetch();
        echo "✓ 封禁IP表连接正常，当前封禁数量: " . $result['count'] . "\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM protection_rules");
        $result = $stmt->fetch();
        echo "✓ 防护规则表连接正常，当前规则数量: " . $result['count'] . "\n";
        
        echo "\n数据库测试完成！\n";
    } else {
        echo "✗ 数据库连接失败！\n";
    }
} catch (Exception $e) {
    echo "✗ 数据库测试失败: " . $e->getMessage() . "\n";
}