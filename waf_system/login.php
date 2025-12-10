<?php
/**
 * WAF系统登录页面
 */
session_start();

// 如果已登录，直接跳转到首页
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header('Location: index.php');
    exit;
}

$error_message = '';

// 加载配置
require_once 'config/config.php';

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // 连接数据库
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // 查询用户
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        // 验证密码
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error_message = '用户名或密码错误';
        }
    } catch (PDOException $e) {
        $error_message = '数据库连接错误: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>WAF防护系统 - 登录</title>
    <link rel="stylesheet" href="static/layui/css/layui.css">
    <style>
        .login-container {
            width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">WAF防护系统</h2>
        
        <?php if ($error_message): ?>
        <div class="layui-alert layui-alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <form class="layui-form" method="post">
            <div class="layui-form-item">
                <label class="layui-form-label">用户名</label>
                <div class="layui-input-block">
                    <input type="text" name="username" required lay-verify="required" placeholder="请输入用户名" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">密码</label>
                <div class="layui-input-block">
                    <input type="password" name="password" required lay-verify="required" placeholder="请输入密码" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button class="layui-btn layui-btn-fluid" lay-submit lay-filter="login">登录</button>
                </div>
            </div>
        </form>
    </div>

    <script src="static/layui/layui.js"></script>
    <script>
    layui.use(['form'], function(){
        var form = layui.form;
        
        // 监听提交
        form.on('submit(login)', function(data){
            // 提交表单
            return true;
        });
    });
    </script>
</body>
</html>