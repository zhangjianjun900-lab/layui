<div class="layui-row layui-col-space15">
    <div class="layui-col-md12">
        <div class="layui-card">
            <div class="layui-card-header">攻击日志</div>
            <div class="layui-card-body">
                <div class="layui-form layui-border-box layui-table-view">
                    <div class="layui-form-item">
                        <div class="layui-inline">
                            <label class="layui-form-label">开始日期</label>
                            <div class="layui-input-inline" style="width: 120px;">
                                <input type="text" class="layui-input" id="start-date" placeholder="开始日期">
                            </div>
                        </div>
                        <div class="layui-inline">
                            <label class="layui-form-label">结束日期</label>
                            <div class="layui-input-inline" style="width: 120px;">
                                <input type="text" class="layui-input" id="end-date" placeholder="结束日期">
                            </div>
                        </div>
                        <div class="layui-inline">
                            <label class="layui-form-label">攻击类型</label>
                            <div class="layui-input-inline" style="width: 120px;">
                                <select name="attack-type">
                                    <option value="">全部类型</option>
                                    <option value="SQL Injection">SQL注入</option>
                                    <option value="XSS">XSS攻击</option>
                                    <option value="CC Attack">CC攻击</option>
                                    <option value="Other">其他</option>
                                </select>
                            </div>
                        </div>
                        <div class="layui-inline">
                            <button class="layui-btn" id="search-btn"><i class="layui-icon">&#xe615;</i> 搜索</button>
                            <button class="layui-btn layui-btn-danger" id="clear-logs-btn"><i class="layui-icon">&#xe640;</i> 清空日志</button>
                        </div>
                    </div>
                </div>
                
                <table class="layui-table" id="attack-logs-table">
                    <thead>
                        <tr>
                            <th>时间</th>
                            <th>攻击类型</th>
                            <th>IP地址</th>
                            <th>URL</th>
                            <th>请求方法</th>
                            <th>详情</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" style="text-align: center;">暂无数据</td>
                        </tr>
                    </tbody>
                </table>
                
                <div id="attack-logs-pagination" style="text-align: right; margin-top: 20px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
layui.use(['form', 'laydate', 'table', 'laypage'], function(){
    var form = layui.form;
    var laydate = layui.laydate;
    var table = layui.table;
    var laypage = layui.laypage;
    
    // 初始化日期选择器
    laydate.render({
        elem: '#start-date',
        format: 'yyyy-MM-dd'
    });
    
    laydate.render({
        elem: '#end-date',
        format: 'yyyy-MM-dd'
    });
    
    // 搜索按钮事件
    $('#search-btn').on('click', function(){
        loadAttackLogs();
    });
    
    // 清空日志按钮事件
    $('#clear-logs-btn').on('click', function(){
        layer.confirm('确定要清空所有攻击日志吗？此操作不可恢复！', function(index){
            $.post('api/clear_attack_logs.php', function(response){
                if(response.success) {
                    layer.msg('日志已清空', {icon: 1});
                    loadAttackLogs();
                } else {
                    layer.msg('清空失败：' + response.message, {icon: 2});
                }
            });
            layer.close(index);
        });
    });
    
    // 加载攻击日志
    function loadAttackLogs(page = 1, limit = 10) {
        var startDate = $('#start-date').val();
        var endDate = $('#end-date').val();
        var attackType = $('select[name="attack-type"]').val();
        
        $.get('api/attack_logs.php', {
            page: page,
            limit: limit,
            start_date: startDate,
            end_date: endDate,
            attack_type: attackType
        }, function(response){
            if(response.success) {
                renderAttackLogs(response.data.logs, response.data.total);
                
                // 渲染分页
                laypage.render({
                    elem: 'attack-logs-pagination',
                    count: response.data.total,
                    limit: limit,
                    curr: page,
                    layout: ['count', 'prev', 'page', 'next', 'limit', 'refresh', 'skip'],
                    jump: function(obj, first){
                        if(!first) {
                            loadAttackLogs(obj.curr, obj.limit);
                        }
                    }
                });
            } else {
                layer.msg('加载失败：' + response.message, {icon: 2});
            }
        });
    }
    
    // 渲染攻击日志
    function renderAttackLogs(logs, total) {
        var tbody = $('#attack-logs-table tbody');
        if(logs.length > 0) {
            var html = '';
            logs.forEach(function(log) {
                html += '<tr>';
                html += '<td>' + log.timestamp + '</td>';
                html += '<td>' + log.type + '</td>';
                html += '<td>' + log.ip + '</td>';
                html += '<td>' + log.url + '</td>';
                html += '<td>' + log.method + '</td>';
                html += '<td>' + (log.details || '-') + '</td>';
                html += '<td>';
                html += '<a href="javascript:;" class="layui-btn layui-btn-xs" onclick="blockIP(\'' + log.ip + '\')">封禁</a>';
                html += '<a href="javascript:;" class="layui-btn layui-btn-xs layui-btn-normal" onclick="viewDetails(\'' + log.ip + '\')">详情</a>';
                html += '</td>';
                html += '</tr>';
            });
            tbody.html(html);
        } else {
            tbody.html('<tr><td colspan="7" style="text-align: center;">暂无数据</td></tr>');
        }
    }
    
    // 封禁IP
    window.blockIP = function(ip) {
        layer.confirm('确定要封禁IP: ' + ip + ' 吗？', function(index) {
            $.post('api/block_ip.php', {
                ip: ip,
                reason: '手动封禁 - 来自攻击日志'
            }, function(response){
                if(response.success) {
                    layer.msg('IP封禁成功', {icon: 1});
                    loadAttackLogs();
                } else {
                    layer.msg('封禁失败：' + response.message, {icon: 2});
                }
            });
            layer.close(index);
        });
    };
    
    // 查看详情
    window.viewDetails = function(ip) {
        layer.open({
            type: 2,
            title: '攻击详情',
            shadeClose: true,
            shade: 0.8,
            area: ['600px', '400px'],
            content: 'view_log_details.php?ip=' + ip
        });
    };
    
    // 初始化加载
    loadAttackLogs();
});

// 重新定义blockIP函数以避免重复定义
function blockIP(ip) {
    layui.use(['layer'], function(){
        var layer = layui.layer;
        
        layer.confirm('确定要封禁IP: ' + ip + ' 吗？', function(index) {
            $.post('api/block_ip.php', {
                ip: ip,
                reason: '手动封禁 - 来自攻击日志'
            }, function(response){
                if(response.success) {
                    layer.msg('IP封禁成功', {icon: 1});
                    // 重新加载日志
                    layui.use(['laypage'], function(){
                        var laypage = layui.laypage;
                        loadAttackLogs(laypage.config.curr || 1, laypage.config.limit || 10);
                    });
                } else {
                    layer.msg('封禁失败：' + response.message, {icon: 2});
                }
            });
            layer.close(index);
        });
    });
}
</script>