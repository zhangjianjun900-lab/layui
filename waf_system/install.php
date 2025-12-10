<?php
// 检查是否已安装
if (file_exists('config.php') && filesize('config.php') > 0) {
    die('系统已安装，请勿重复安装！如需重新安装，请先删除config.php文件。');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>WAF系统 - 在线安装</title>
    <link rel="stylesheet" href="https://www.layuicdn.com/layui/css/layui.css" media="all">
    <style>
        body { background-color: #f2f2f2; padding: 20px 0; }
        .install-container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); overflow: hidden; }
        .install-header { background: #1e9fff; color: #fff; text-align: center; padding: 30px 20px; }
        .install-body { padding: 40px; }
        .install-footer { text-align: center; padding: 20px; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>WAF系统在线安装</h1>
            <p>请按提示完成系统安装</p>
        </div>
        
        <div class="install-body">
            <form class="layui-form" action="" method="post">
                <div class="layui-form-item">
                    <label class="layui-form-label">数据库主机</label>
                    <div class="layui-input-block">
                        <input type="text" name="db_host" required lay-verify="required" placeholder="例如：localhost" autocomplete="off" class="layui-input" value="localhost">
                    </div>
                </div>
                
                <div class="layui-form-item">
                    <label class="layui-form-label">数据库端口</label>
                    <div class="layui-input-block">
                        <input type="text" name="db_port" required lay-verify="required" placeholder="例如：3306" autocomplete="off" class="layui-input" value="3306">
                    </div>
                </div>
                
                <div class="layui-form-item">
                    <label class="layui-form-label">数据库名</label>
                    <div class="layui-input-block">
                        <input type="text" name="db_name" required lay-verify="required" placeholder="请输入数据库名" autocomplete="off" class="layui-input">
                    </div>
                </div>
                
                <div class="layui-form-item">
                    <label class="layui-form-label">数据库用户名</label>
                    <div class="layui-input-block">
                        <input type="text" name="db_user" required lay-verify="required" placeholder="请输入数据库用户名" autocomplete="off" class="layui-input">
                    </div>
                </div>
                
                <div class="layui-form-item">
                    <label class="layui-form-label">数据库密码</label>
                    <div class="layui-input-block">
                        <input type="password" name="db_pass" required lay-verify="required" placeholder="请输入数据库密码" autocomplete="off" class="layui-input">
                    </div>
                </div>
                
                <div class="layui-form-item">
                    <label class="layui-form-label">管理员账号</label>
                    <div class="layui-input-block">
                        <input type="text" name="admin_user" required lay-verify="required" placeholder="请输入管理员账号" autocomplete="off" class="layui-input" value="admin">
                    </div>
                </div>
                
                <div class="layui-form-item">
                    <label class="layui-form-label">管理员密码</label>
                    <div class="layui-input-block">
                        <input type="password" name="admin_pass" required lay-verify="required" placeholder="请输入管理员密码" autocomplete="off" class="layui-input">
                    </div>
                </div>
                
                <div class="layui-form-item">
                    <label class="layui-form-label">确认密码</label>
                    <div class="layui-input-block">
                        <input type="password" name="admin_pass_confirm" required lay-verify="required" placeholder="请再次输入管理员密码" autocomplete="off" class="layui-input">
                    </div>
                </div>
                
                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="install">立即安装</button>
                        <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="install-footer">
            <p>WAF系统在线安装程序</p>
        </div>
    </div>

    <script src="https://www.layuicdn.com/layui/layui.js" charset="utf-8"></script>
    <script>
    layui.use(['form', 'layer'], function(){
        var form = layui.form;
        var layer = layui.layer;
        
        form.on('submit(install)', function(data){
            var formData = data.field;
            
            // 验证两次密码是否一致
            if(formData.admin_pass !== formData.admin_pass_confirm) {
                layer.msg('两次输入的密码不一致', {icon: 2});
                return false;
            }
            
            // 禁用提交按钮防止重复提交
            $('.layui-btn-normal').attr('disabled', true).text('安装中...');
            
            // 发送安装请求
            $.ajax({
                url: 'install.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(res) {
                    if(res.code === 1) {
                        layer.msg(res.msg, {icon: 1, time: 2000}, function(){
                            // 安装成功后跳转到登录页面
                            window.location.href = 'login.php';
                        });
                    } else {
                        layer.msg(res.msg, {icon: 2});
                        // 重新启用按钮
                        $('.layui-btn-normal').attr('disabled', false).text('立即安装');
                    }
                },
                error: function() {
                    layer.msg('安装请求失败', {icon: 2});
                    // 重新启用按钮
                    $('.layui-btn-normal').attr('disabled', false).text('立即安装');
                }
            });
            
            return false; // 阻止表单默认提交
        });
    });
    </script>
    
    <script>
    // 添加基本的AJAX支持（如果服务器端处理POST请求）
    if(typeof jQuery === 'undefined') {
        document.write('<script src="https://code.jquery.com/jquery-3.6.0.min.js"><\/script>');
    }
    </script>
</body>
</html>

<?php
// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取POST数据
    $db_host = $_POST['db_host'] ?? '';
    $db_port = $_POST['db_port'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $admin_user = $_POST['admin_user'] ?? '';
    $admin_pass = $_POST['admin_pass'] ?? '';
    $admin_pass_confirm = $_POST['admin_pass_confirm'] ?? '';
    
    // 验证数据
    if (empty($db_host) || empty($db_port) || empty($db_name) || empty($db_user) || empty($db_pass) || 
        empty($admin_user) || empty($admin_pass) || empty($admin_pass_confirm)) {
        echo json_encode(['code' => 0, 'msg' => '所有字段都是必填的']);
        exit;
    }
    
    if ($admin_pass !== $admin_pass_confirm) {
        echo json_encode(['code' => 0, 'msg' => '两次输入的密码不一致']);
        exit;
    }
    
    if (strlen($admin_pass) < 6) {
        echo json_encode(['code' => 0, 'msg' => '管理员密码长度不能少于6位']);
        exit;
    }
    
    try {
        // 创建数据库连接
        $pdo = new PDO("mysql:host={$db_host};port={$db_port};charset=utf8", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 创建数据库（如果不存在）
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
        
        // 选择数据库
        $pdo->exec("USE `{$db_name}`;");
        
        // 创建数据表
        $tables_sql = "
        -- 创建管理员表
        CREATE TABLE IF NOT EXISTS `waf_admin` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        -- 创建攻击日志表
        CREATE TABLE IF NOT EXISTS `waf_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ip` varchar(45) NOT NULL,
            `url` varchar(500) NOT NULL,
            `method` varchar(10) NOT NULL,
            `params` text,
            `attack_type` varchar(50) NOT NULL,
            `user_agent` varchar(500) DEFAULT NULL,
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_ip` (`ip`),
            KEY `idx_time` (`create_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        -- 创建封禁IP表
        CREATE TABLE IF NOT EXISTS `waf_banned_ips` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ip` varchar(45) NOT NULL,
            `reason` varchar(255) DEFAULT NULL,
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `expire_time` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ip` (`ip`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        -- 创建防护规则表
        CREATE TABLE IF NOT EXISTS `waf_rules` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `rule_name` varchar(100) NOT NULL,
            `rule_type` varchar(50) NOT NULL,
            `rule_pattern` varchar(500) NOT NULL,
            `status` tinyint(1) NOT NULL DEFAULT '1',
            `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        -- 插入默认管理员账号
        INSERT IGNORE INTO `waf_admin` (`username`, `password`) VALUES ('{$admin_user}', '" . password_hash($admin_pass, PASSWORD_DEFAULT) . "');

        -- 插入默认防护规则
        INSERT IGNORE INTO `waf_rules` (`rule_name`, `rule_type`, `rule_pattern`, `status`) VALUES
        ('SQL注入检测1', 'sql_injection', '(union\\\\s+select|select.*from|insert.*into|update.*set|delete.*from)', 1),
        ('SQL注入检测2', 'sql_injection', '(drop\\\\s+table|create\\\\s+table|alter\\\\s+table)', 1),
        ('XSS检测1', 'xss', '(&lt;script|<script)', 1),
        ('XSS检测2', 'xss', '(javascript:|vbscript:|data:)', 1),
        ('XSS检测3', 'xss', '(&lt;iframe|<iframe)', 1),
        ('XSS检测4', 'xss', '(&lt;object|<object)', 1),
        ('XSS检测5', 'xss', '(&lt;embed|<embed)', 1),
        ('XSS检测6', 'xss', '(&lt;link|<link)', 1),
        ('XSS检测7', 'xss', '(&lt;meta|<meta)', 1),
        ('命令执行检测', 'command', '(;.*\\\\|.*&&.*|.*||.*)', 1);
        ";
        
        // 执行建表语句
        $pdo->exec($tables_sql);
        
        // 生成配置文件
        $config_content = "<?php
// 数据库配置
define('DB_HOST', '{$db_host}');
define('DB_PORT', '{$db_port}');
define('DB_NAME', '{$db_name}');
define('DB_USER', '{$db_user}');
define('DB_PASS', '{$db_pass}');
define('DB_CHARSET', 'utf8mb4');

// 安全配置
define('ADMIN_USER', '{$admin_user}');
define('ADMIN_PASS', '" . password_hash($admin_pass, PASSWORD_DEFAULT) . "');

// 系统配置
define('SITE_URL', \$_SERVER['HTTP_HOST']);
define('WAF_VERSION', '1.0.0');
define('DEBUG_MODE', false);

// 防护配置
define('ENABLE_SQL_FILTER', true);
define('ENABLE_XSS_FILTER', true);
define('ENABLE_CC_FILTER', true);
define('CC_MAX_REQUESTS', 100);
define('CC_TIME_WINDOW', 60);
define('ENABLE_IP_BAN', true);
define('LOG_ATTACKS', true);

// 数据库连接函数
function get_db_connection() {
    static \$pdo = null;
    if (\$pdo === null) {
        try {
            \$dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException \$e) {
            die('数据库连接失败: ' . \$e->getMessage());
        }
    }
    return \$pdo;
}
";
        
        // 写入配置文件
        if (file_put_contents('config.php', $config_content) === false) {
            echo json_encode(['code' => 0, 'msg' => '配置文件写入失败，请检查目录权限']);
            exit;
        }
        
        // 删除安装文件以确保安全
        if (file_exists(__FILE__)) {
            unlink(__FILE__);
        }
        
        echo json_encode(['code' => 1, 'msg' => '安装成功！安装文件已自动删除，系统已准备就绪。']);
        
    } catch (PDOException $e) {
        echo json_encode(['code' => 0, 'msg' => '数据库操作失败: ' . $e->getMessage()]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['code' => 0, 'msg' => '安装过程中发生错误: ' . $e->getMessage()]);
        exit;
    }
}
?>