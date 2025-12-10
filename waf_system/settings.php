<?php
session_start();

// 检查登录状态
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// 检查权限（必须是管理员）
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// 加载配置
require_once 'config/config.php';
require_once 'includes/functions.php';

// 获取系统统计信息
$stats = get_system_stats();

// 处理设置保存
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enable_sql = (int)($_POST['enable_sql'] ?? 0);
    $enable_xss = (int)($_POST['enable_xss'] ?? 0);
    $enable_cc = (int)($_POST['enable_cc'] ?? 0);
    $cc_rate_limit = (int)($_POST['cc_rate_limit'] ?? 100);
    $cc_time_window = (int)($_POST['cc_time_window'] ?? 60);
    $block_time = (int)($_POST['block_time'] ?? 3600);
    $log_attacks = (int)($_POST['log_attacks'] ?? 1);
    $log_access = (int)($_POST['log_access'] ?? 1);
    $max_log_retention = (int)($_POST['max_log_retention'] ?? 30);
    
    try {
        $pdo = get_db_connection();
        if (!$pdo) {
            throw new Exception('数据库连接失败');
        }
        
        // 更新防护规则表中的设置
        $stmt = $pdo->prepare("UPDATE protection_rules SET enabled = ? WHERE rule_type = 'sql'");
        $stmt->execute([$enable_sql]);
        
        $stmt = $pdo->prepare("UPDATE protection_rules SET enabled = ? WHERE rule_type = 'xss'");
        $stmt->execute([$enable_xss]);
        
        $stmt = $pdo->prepare("UPDATE protection_rules SET enabled = ? WHERE rule_type = 'cc'");
        $stmt->execute([$enable_cc]);
        
        $message = '系统设置保存成功！';
    } catch (Exception $e) {
        $message = '保存失败：' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo SITE_NAME; ?> - 系统设置</title>
    <link rel="stylesheet" href="static/layui/css/layui.css">
    <style>
        .layui-layout-admin .layui-body {
            bottom: 0;
            padding: 15px;
        }
        .layui-card-header {
            font-size: 16px;
        }
    </style>
</head>
<body class="layui-layout-body">
    <div class="layui-layout layui-layout-admin">
        <div class="layui-header">
            <div class="layui-logo"><?php echo SITE_NAME; ?></div>
            
            <ul class="layui-nav layui-layout-right">
                <li class="layui-nav-item">
                    <a href="javascript:;">
                        <i class="layui-icon layui-icon-username"></i> <?php echo $_SESSION['username']; ?>
                    </a>
                    <dl class="layui-nav-child">
                        <dd><a href="profile.php">个人资料</a></dd>
                        <dd><a href="settings.php">系统设置</a></dd>
                        <dd><a href="logout.php">退出登录</a></dd>
                    </dl>
                </li>
            </ul>
        </div>
        
        <div class="layui-side layui-bg-black">
            <div class="layui-side-scroll">
                <ul class="layui-nav layui-nav-tree" lay-filter="sidebar">
                    <li class="layui-nav-item">
                        <a href="index.php"><i class="layui-icon layui-icon-console"></i> 控制台</a>
                    </li>
                    <li class="layui-nav-item">
                        <a href="dashboard.php"><i class="layui-icon layui-icon-chart"></i> 仪表盘</a>
                    </li>
                    <li class="layui-nav-item">
                        <a href="attack_logs.php"><i class="layui-icon layui-icon-log"></i> 攻击日志</a>
                    </li>
                    <li class="layui-nav-item">
                        <a href="access_logs.php"><i class="layui-icon layui-icon-read"></i> 访问日志</a>
                    </li>
                    <li class="layui-nav-item">
                        <a href="blocked_ips.php"><i class="layui-icon layui-icon-auz"></i> 封禁IP</a>
                    </li>
                    <li class="layui-nav-item">
                        <a href="rules.php"><i class="layui-icon layui-icon-template-1"></i> 防护规则</a>
                    </li>
                    <li class="layui-nav-item">
                        <a href="domains.php"><i class="layui-icon layui-icon-app"></i> 保护域名</a>
                    </li>
                    <li class="layui-nav-item">
                        <a href="users.php"><i class="layui-icon layui-icon-user"></i> 用户管理</a>
                    </li>
                    <li class="layui-nav-item layui-nav-itemed">
                        <a href="settings.php"><i class="layui-icon layui-icon-set"></i> 系统设置</a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="layui-body">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">系统设置</div>
                        <div class="layui-card-body">
                            <?php if ($message): ?>
                            <div class="layui-alert <?php echo strpos($message, '成功') ? 'layui-alert-success' : 'layui-alert-danger'; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                            <?php endif; ?>
                            
                            <form class="layui-form" method="post">
                                <div class="layui-form-item">
                                    <legend style="font-size: 16px; margin: 20px 0 10px 0; border-bottom: 1px solid #eee; padding-bottom: 5px;">防护设置</legend>
                                </div>
                                
                                <div class="layui-form-item">
                                    <label class="layui-form-label">SQL注入防护</label>
                                    <div class="layui-input-block">
                                        <input type="checkbox" name="enable_sql" value="1" lay-skin="switch" lay-text="开启|关闭" <?php echo ENABLE_SQL_PROTECTION ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <label class="layui-form-label">XSS攻击防护</label>
                                    <div class="layui-input-block">
                                        <input type="checkbox" name="enable_xss" value="1" lay-skin="switch" lay-text="开启|关闭" <?php echo ENABLE_XSS_PROTECTION ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <label class="layui-form-label">CC攻击防护</label>
                                    <div class="layui-input-block">
                                        <input type="checkbox" name="enable_cc" value="1" lay-skin="switch" lay-text="开启|关闭" <?php echo ENABLE_CC_PROTECTION ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item layui-form-pane">
                                    <label class="layui-form-label">CC防护限制</label>
                                    <div class="layui-row layui-col-space10">
                                        <div class="layui-col-md6">
                                            <input type="number" name="cc_rate_limit" value="<?php echo CC_RATE_LIMIT; ?>" min="1" max="10000" required lay-verify="required|number" placeholder="每分钟请求数限制" class="layui-input">
                                            <div class="layui-form-mid layui-word-aux">每分钟请求数限制</div>
                                        </div>
                                        <div class="layui-col-md6">
                                            <input type="number" name="cc_time_window" value="<?php echo CC_TIME_WINDOW; ?>" min="1" max="3600" required lay-verify="required|number" placeholder="时间窗口（秒）" class="layui-input">
                                            <div class="layui-form-mid layui-word-aux">时间窗口（秒）</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item layui-form-pane">
                                    <label class="layui-form-label">封禁时间</label>
                                    <div class="layui-input-block">
                                        <input type="number" name="block_time" value="<?php echo BLOCK_TIME; ?>" min="60" max="2592000" required lay-verify="required|number" placeholder="封禁时间（秒）" class="layui-input">
                                        <div class="layui-form-mid layui-word-aux">封禁时间（秒）</div>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <legend style="font-size: 16px; margin: 20px 0 10px 0; border-bottom: 1px solid #eee; padding-bottom: 5px;">日志设置</legend>
                                </div>
                                
                                <div class="layui-form-item">
                                    <label class="layui-form-label">记录攻击日志</label>
                                    <div class="layui-input-block">
                                        <input type="checkbox" name="log_attacks" value="1" lay-skin="switch" lay-text="开启|关闭" <?php echo LOG_ATTACKS ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <label class="layui-form-label">记录访问日志</label>
                                    <div class="layui-input-block">
                                        <input type="checkbox" name="log_access" value="1" lay-skin="switch" lay-text="开启|关闭" <?php echo LOG_ACCESS ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item layui-form-pane">
                                    <label class="layui-form-label">日志保留天数</label>
                                    <div class="layui-input-block">
                                        <input type="number" name="max_log_retention" value="<?php echo MAX_LOG_RETENTION; ?>" min="1" max="365" required lay-verify="required|number" placeholder="日志保留天数" class="layui-input">
                                        <div class="layui-form-mid layui-word-aux">日志保留天数</div>
                                    </div>
                                </div>
                                
                                <div class="layui-form-item">
                                    <div class="layui-input-block">
                                        <button class="layui-btn" lay-submit lay-filter="save-settings">保存设置</button>
                                        <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="layui-footer">
            © <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> v<?php echo VERSION; ?> - Web应用防火墙系统
        </div>
    </div>

    <script src="static/layui/layui.js"></script>
    <script>
    layui.use(['element', 'form'], function(){
        var element = layui.element;
        var form = layui.form;
    });
    </script>
</body>
</html>