<?php
/**
 * WAF系统主页面
 */
session_start();

// 检查登录状态
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// 加载配置
require_once 'config/config.php';
require_once 'includes/functions.php';

// 获取系统统计信息
$stats = get_system_stats();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo SITE_NAME; ?> - 首页</title>
    <link rel="stylesheet" href="static/layui/css/layui.css">
    <style>
        .layui-layout-admin .layui-body {
            bottom: 0;
            padding: 15px;
        }
        .layui-card-header {
            font-size: 16px;
        }
        .stat-card {
            text-align: center;
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            margin-top: 10px;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        .chart-container {
            height: 300px;
            margin-top: 20px;
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
                    <li class="layui-nav-item layui-nav-itemed">
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
                </ul>
            </div>
        </div>
        
        <div class="layui-body">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">系统概览</div>
                        <div class="layui-card-body">
                            <div class="layui-row layui-col-space15">
                                <div class="layui-col-md3">
                                    <div class="layui-card stat-card">
                                        <div class="layui-card-body">
                                            <div class="stat-number"><?php echo $stats['total_attacks']; ?></div>
                                            <div class="stat-label">总攻击次数</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="layui-col-md3">
                                    <div class="layui-card stat-card">
                                        <div class="layui-card-body">
                                            <div class="stat-number"><?php echo $stats['blocked_ips']; ?></div>
                                            <div class="stat-label">封禁IP数量</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="layui-col-md3">
                                    <div class="layui-card stat-card">
                                        <div class="layui-card-body">
                                            <div class="stat-number"><?php echo $stats['protected_domains']; ?></div>
                                            <div class="stat-label">保护域名数</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="layui-col-md3">
                                    <div class="layui-card stat-card">
                                        <div class="layui-card-body">
                                            <div class="stat-number"><?php echo $stats['active_rules']; ?></div>
                                            <div class="stat-label">激活规则数</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="layui-col-md6">
                    <div class="layui-card">
                        <div class="layui-card-header">实时监控</div>
                        <div class="layui-card-body">
                            <div id="realtime-chart" class="chart-container">
                                <table class="layui-table">
                                    <thead>
                                        <tr>
                                            <th>时间</th>
                                            <th>类型</th>
                                            <th>IP地址</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody id="realtime-attacks">
                                        <!-- 实时攻击数据将通过AJAX加载 -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="layui-col-md6">
                    <div class="layui-card">
                        <div class="layui-card-header">最近攻击</div>
                        <div class="layui-card-body">
                            <div id="recent-attacks-chart" class="chart-container">
                                <table class="layui-table">
                                    <thead>
                                        <tr>
                                            <th>时间</th>
                                            <th>类型</th>
                                            <th>IP</th>
                                            <th>详情</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recent-attacks">
                                        <!-- 最近攻击数据将通过AJAX加载 -->
                                    </tbody>
                                </table>
                            </div>
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
    layui.use(['element', 'table'], function(){
        var element = layui.element;
        var table = layui.table;
        
        // 加载实时攻击数据
        function loadRealtimeData() {
            fetch('api/attacks.php?limit=5')
                .then(response => response.json())
                .then(data => {
                    var tbody = document.getElementById('realtime-attacks');
                    tbody.innerHTML = '';
                    
                    data.forEach(function(attack) {
                        var row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${attack.timestamp}</td>
                            <td>${attack.type}</td>
                            <td>${attack.ip}</td>
                            <td>
                                <button class="layui-btn layui-btn-xs layui-btn-danger" onclick="blockIP('${attack.ip}')">封禁</button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => console.error('Error loading realtime data:', error));
        }
        
        // 加载最近攻击数据
        function loadRecentAttacks() {
            fetch('api/attacks.php?limit=5')
                .then(response => response.json())
                .then(data => {
                    var tbody = document.getElementById('recent-attacks');
                    tbody.innerHTML = '';
                    
                    data.forEach(function(attack) {
                        var row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${attack.timestamp}</td>
                            <td>${attack.type}</td>
                            <td>${attack.ip}</td>
                            <td><a href="view_log_details.php?id=${attack.id}">查看详情</a></td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => console.error('Error loading recent attacks:', error));
        }
        
        // 定时刷新数据
        loadRealtimeData();
        loadRecentAttacks();
        setInterval(function() {
            loadRealtimeData();
            loadRecentAttacks();
        }, 5000);
        
        // 封禁IP函数
        window.blockIP = function(ip) {
            if (confirm('确定要封禁IP: ' + ip + ' 吗？')) {
                fetch('api/block_ip.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ip: ip})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        layer.msg('IP封禁成功', {icon: 1});
                        loadRealtimeData();
                    } else {
                        layer.msg('封禁失败: ' + data.message, {icon: 2});
                    }
                })
                .catch(error => {
                    layer.msg('请求失败', {icon: 2});
                });
            }
        };
    });
    </script>
</body>
</html>