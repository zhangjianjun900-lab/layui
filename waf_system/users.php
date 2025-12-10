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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo SITE_NAME; ?> - 用户管理</title>
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
                    <li class="layui-nav-item layui-nav-itemed">
                        <a href="users.php"><i class="layui-icon layui-icon-user"></i> 用户管理</a>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="layui-body">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">用户管理</div>
                        <div class="layui-card-body">
                            <div class="layui-form layui-border-box layui-table-view">
                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <button class="layui-btn" id="add-user-btn"><i class="layui-icon">&#xe654;</i> 添加用户</button>
                                        <button class="layui-btn layui-btn-normal" id="refresh-users-btn"><i class="layui-icon">&#xe669;</i> 刷新</button>
                                    </div>
                                </div>
                            </div>
                            
                            <table class="layui-table" id="users-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>用户名</th>
                                        <th>邮箱</th>
                                        <th>角色</th>
                                        <th>创建时间</th>
                                        <th>更新时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody id="users-tbody">
                                    <!-- 用户数据将通过AJAX加载 -->
                                </tbody>
                            </table>
                            
                            <div id="users-pagination" style="text-align: right; margin-top: 20px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="layui-footer">
            © <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> v<?php echo VERSION; ?> - Web应用防火墙系统
        </div>
    </div>

    <!-- 添加/编辑用户弹窗 -->
    <div id="user-form-dialog" style="display: none; padding: 20px;">
        <form class="layui-form" id="user-form" style="padding: 20px 0;">
            <input type="hidden" name="id" id="user-id">
            <div class="layui-form-item">
                <label class="layui-form-label">用户名</label>
                <div class="layui-input-block">
                    <input type="text" name="username" required lay-verify="required" placeholder="请输入用户名" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">邮箱</label>
                <div class="layui-input-block">
                    <input type="email" name="email" required lay-verify="required|email" placeholder="请输入邮箱" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">角色</label>
                <div class="layui-input-block">
                    <select name="role" required lay-verify="required">
                        <option value="user">普通用户</option>
                        <option value="admin">管理员</option>
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">密码</label>
                <div class="layui-input-block">
                    <input type="password" name="password" placeholder="留空则不修改密码" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">确认密码</label>
                <div class="layui-input-block">
                    <input type="password" name="confirm_password" placeholder="留空则不修改密码" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button class="layui-btn" lay-submit lay-filter="save-user">保存</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </form>
    </div>

    <script src="static/layui/layui.js"></script>
    <script>
    layui.use(['element', 'table', 'laypage', 'layer', 'form'], function(){
        var element = layui.element;
        var table = layui.table;
        var laypage = layui.laypage;
        var layer = layui.layer;
        var form = layui.form;
        
        // 加载用户数据
        function loadUsers(page = 1, limit = 10) {
            $.get('api/users.php', {
                page: page,
                limit: limit
            }, function(response){
                if(response.success) {
                    renderUsers(response.data.users, response.data.total);
                    
                    // 渲染分页
                    laypage.render({
                        elem: 'users-pagination',
                        count: response.data.total,
                        limit: limit,
                        curr: page,
                        layout: ['count', 'prev', 'page', 'next', 'limit', 'refresh', 'skip'],
                        jump: function(obj, first){
                            if(!first) {
                                loadUsers(obj.curr, obj.limit);
                            }
                        }
                    });
                } else {
                    layer.msg('加载失败：' + response.message, {icon: 2});
                }
            });
        }
        
        // 渲染用户数据
        function renderUsers(users, total) {
            var tbody = $('#users-tbody');
            if(users.length > 0) {
                var html = '';
                users.forEach(function(user) {
                    html += '<tr>';
                    html += '<td>' + user.id + '</td>';
                    html += '<td>' + user.username + '</td>';
                    html += '<td>' + user.email + '</td>';
                    html += '<td>' + (user.role === 'admin' ? '<span class="layui-badge layui-bg-orange">管理员</span>' : '<span class="layui-badge layui-bg-blue">普通用户</span>') + '</td>';
                    html += '<td>' + user.created_at + '</td>';
                    html += '<td>' + user.updated_at + '</td>';
                    html += '<td>';
                    html += '<a href="javascript:;" class="layui-btn layui-btn-xs" onclick="editUser(' + user.id + ')">编辑</a>';
                    html += '<a href="javascript:;" class="layui-btn layui-btn-xs layui-btn-danger" onclick="deleteUser(' + user.id + ')">删除</a>';
                    html += '</td>';
                    html += '</tr>';
                });
                tbody.html(html);
            } else {
                tbody.html('<tr><td colspan="7" style="text-align: center;">暂无数据</td></tr>');
            }
        }
        
        // 添加用户按钮事件
        $('#add-user-btn').on('click', function(){
            $('#user-id').val('');
            $('#user-form')[0].reset();
            form.render();
            
            layer.open({
                type: 1,
                title: '添加用户',
                area: ['600px', '500px'],
                content: $('#user-form-dialog'),
                cancel: function(){
                    $('#user-form-dialog').hide();
                }
            });
        });
        
        // 刷新按钮事件
        $('#refresh-users-btn').on('click', function(){
            loadUsers();
        });
        
        // 保存用户表单提交
        form.on('submit(save-user)', function(data){
            // 验证密码是否一致
            var password = $('input[name="password"]').val();
            var confirmPassword = $('input[name="confirm_password"]').val();
            
            if(password && password !== confirmPassword) {
                layer.msg('两次输入的密码不一致', {icon: 2});
                return false;
            }
            
            $.post('api/save_user.php', data.field, function(response){
                if(response.success) {
                    layer.msg(response.message, {icon: 1});
                    layer.closeAll('page');
                    loadUsers();
                } else {
                    layer.msg(response.message, {icon: 2});
                }
            });
            return false;
        });
        
        // 初始化加载
        loadUsers();
    });
    
    // 编辑用户
    function editUser(id) {
        layui.use(['form', 'layer'], function(){
            var form = layui.form;
            var layer = layui.layer;
            
            $.get('api/get_user.php', {id: id}, function(response){
                if(response.success) {
                    var user = response.data;
                    
                    $('#user-id').val(user.id);
                    $('input[name="username"]').val(user.username);
                    $('input[name="email"]').val(user.email);
                    $('select[name="role"]').val(user.role);
                    $('input[name="password"]').val('');
                    $('input[name="confirm_password"]').val('');
                    
                    form.render();
                    
                    layer.open({
                        type: 1,
                        title: '编辑用户',
                        area: ['600px', '500px'],
                        content: $('#user-form-dialog'),
                        cancel: function(){
                            $('#user-form-dialog').hide();
                        }
                    });
                } else {
                    layer.msg(response.message, {icon: 2});
                }
            });
        });
    }
    
    // 删除用户
    function deleteUser(id) {
        layui.use(['layer'], function(){
            var layer = layui.layer;
            
            layer.confirm('确定要删除此用户吗？', function(index){
                $.post('api/delete_user.php', {id: id}, function(response){
                    if(response.success) {
                        layer.msg(response.message, {icon: 1});
                        layui.use(['laypage'], function(){
                            var laypage = layui.laypage;
                            loadUsers(laypage.config.curr || 1, laypage.config.limit || 10);
                        });
                    } else {
                        layer.msg(response.message, {icon: 2});
                    }
                });
                layer.close(index);
            });
        });
    }
    </script>
</body>
</html>