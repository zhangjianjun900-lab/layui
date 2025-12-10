<div class="layui-row layui-col-space15">
    <div class="layui-col-md12">
        <div class="layui-card">
            <div class="layui-card-header">系统概览</div>
            <div class="layui-card-body">
                <div class="layui-row layui-col-space15">
                    <div class="layui-col-md3">
                        <div class="layui-card stat-card">
                            <div class="layui-card-body">
                                <div class="stat-number" id="today-attacks">0</div>
                                <div class="stat-label">今日攻击次数</div>
                            </div>
                        </div>
                    </div>
                    <div class="layui-col-md3">
                        <div class="layui-card stat-card">
                            <div class="layui-card-body">
                                <div class="stat-number" id="blocked-ips-count">0</div>
                                <div class="stat-label">当前封禁IP数</div>
                            </div>
                        </div>
                    </div>
                    <div class="layui-col-md3">
                        <div class="layui-card stat-card">
                            <div class="layui-card-body">
                                <div class="stat-number" id="protected-domains-count">0</div>
                                <div class="stat-label">受保护域名数</div>
                            </div>
                        </div>
                    </div>
                    <div class="layui-col-md3">
                        <div class="layui-card stat-card">
                            <div class="layui-card-body">
                                <div class="stat-number" id="system-status">正常</div>
                                <div class="stat-label">系统状态</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="layui-row layui-col-space15">
    <div class="layui-col-md8">
        <div class="layui-card">
            <div class="layui-card-header">实时攻击监控</div>
            <div class="layui-card-body">
                <table class="layui-table">
                    <thead>
                        <tr>
                            <th>时间</th>
                            <th>类型</th>
                            <th>IP地址</th>
                            <th>URL</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="attack-log-tbody">
                        <tr><td colspan="5">暂无数据</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="layui-col-md4">
        <div class="layui-card">
            <div class="layui-card-header">防护状态</div>
            <div class="layui-card-body">
                <ul class="layui-timeline">
                    <li class="layui-timeline-item">
                        <i class="layui-icon layui-timeline-axis">&#xe63f;</i>
                        <div class="layui-timeline-content layui-text">
                            <h3 class="layui-timeline-title">SQL注入防护</h3>
                            <p>当前状态：<span id="sql-status" class="layui-badge layui-bg-green">启用</span></p>
                        </div>
                    </li>
                    <li class="layui-timeline-item">
                        <i class="layui-icon layui-timeline-axis">&#xe63f;</i>
                        <div class="layui-timeline-content layui-text">
                            <h3 class="layui-timeline-title">XSS防护</h3>
                            <p>当前状态：<span id="xss-status" class="layui-badge layui-bg-green">启用</span></p>
                        </div>
                    </li>
                    <li class="layui-timeline-item">
                        <i class="layui-icon layui-timeline-axis">&#xe63f;</i>
                        <div class="layui-timeline-content layui-text">
                            <h3 class="layui-timeline-title">CC攻击防护</h3>
                            <p>当前状态：<span id="cc-status" class="layui-badge layui-bg-green">启用</span></p>
                        </div>
                    </li>
                    <li class="layui-timeline-item">
                        <i class="layui-icon layui-timeline-axis">&#xe63f;</i>
                        <div class="layui-timeline-content layui-text">
                            <h3 class="layui-timeline-title">访问频率限制</h3>
                            <p>当前状态：<span id="rate-limit-status" class="layui-badge layui-bg-green">启用</span></p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// 更新统计数据
function updateDashboardStats() {
    $.get('api/stats.php', function(data) {
        if(data.success) {
            $('#today-attacks').text(data.data.total_attacks || 0);
            $('#blocked-ips-count').text(data.data.blocked_ips || 0);
            $('#protected-domains-count').text(data.data.protected_domains || 0);
            $('#system-status').text(data.data.system_status || '正常');
        }
    });
}

// 加载最新攻击日志
function loadLatestAttacks() {
    $.get('api/attacks.php?limit=5', function(data) {
        if(data.success && data.data.attacks.length > 0) {
            var html = '';
            data.data.attacks.forEach(function(attack) {
                html += '<tr>';
                html += '<td>' + attack.timestamp + '</td>';
                html += '<td>' + attack.type + '</td>';
                html += '<td>' + attack.ip + '</td>';
                html += '<td>' + attack.url + '</td>';
                html += '<td><a href="javascript:;" class="layui-btn layui-btn-xs" onclick="blockIP(\'' + attack.ip + '\')">封禁</a></td>';
                html += '</tr>';
            });
            $('#attack-log-tbody').html(html);
        } else {
            $('#attack-log-tbody').html('<tr><td colspan="5">暂无数据</td></tr>');
        }
    });
}

// 封禁IP
function blockIP(ip) {
    layer.confirm('确定要封禁IP: ' + ip + ' 吗？', function(index) {
        $.post('api/block_ip.php', {
            ip: ip,
            reason: '手动封禁'
        }, function(data) {
            if(data.success) {
                layer.msg('IP封禁成功', {icon: 1});
                updateDashboardStats();
                loadLatestAttacks();
            } else {
                layer.msg('操作失败: ' + data.message, {icon: 2});
            }
        });
        layer.close(index);
    });
}

// 初始化数据
updateDashboardStats();
loadLatestAttacks();

// 每30秒自动刷新一次
setInterval(function() {
    updateDashboardStats();
    loadLatestAttacks();
}, 30000);
</script>