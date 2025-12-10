<div class="layui-row layui-col-space15">
    <div class="layui-col-md12">
        <div class="layui-card">
            <div class="layui-card-header">封禁IP管理</div>
            <div class="layui-card-body">
                <div class="layui-form layui-border-box layui-table-view">
                    <div class="layui-form-item">
                        <div class="layui-inline">
                            <input type="text" class="layui-input" id="search-ip" placeholder="搜索IP地址">
                        </div>
                        <div class="layui-inline">
                            <button class="layui-btn" id="search-blocked-ip-btn"><i class="layui-icon">&#xe615;</i> 搜索</button>
                            <button class="layui-btn layui-btn-danger" id="unblock-selected-btn"><i class="layui-icon">&#xe640;</i> 解封选中</button>
                        </div>
                    </div>
                </div>
                
                <table class="layui-table" id="blocked-ips-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" lay-filter="all-select" id="all-select-blocked"></th>
                            <th>IP地址</th>
                            <th>封禁原因</th>
                            <th>封禁时间</th>
                            <th>过期时间</th>
                            <th>剩余时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="checkbox" name="blocked-ip-check" value="192.168.1.100"></td>
                            <td>192.168.1.100</td>
                            <td>CC攻击</td>
                            <td>2023-01-01 10:00:00</td>
                            <td>2023-01-02 10:00:00</td>
                            <td>23小时</td>
                            <td>
                                <a href="javascript:;" class="layui-btn layui-btn-xs layui-btn-normal" onclick="unblockIP('192.168.1.100')">解封</a>
                                <a href="javascript:;" class="layui-btn layui-btn-xs layui-btn-danger" onclick="deleteBlockRecord('192.168.1.100')">删除记录</a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" name="blocked-ip-check" value="10.0.0.50"></td>
                            <td>10.0.0.50</td>
                            <td>SQL注入</td>
                            <td>2023-01-01 15:30:00</td>
                            <td>2023-01-03 15:30:00</td>
                            <td>2天</td>
                            <td>
                                <a href="javascript:;" class="layui-btn layui-btn-xs layui-btn-normal" onclick="unblockIP('10.0.0.50')">解封</a>
                                <a href="javascript:;" class="layui-btn layui-btn-xs layui-btn-danger" onclick="deleteBlockRecord('10.0.0.50')">删除记录</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div id="blocked-ips-pagination" style="text-align: right; margin-top: 20px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="layui-row layui-col-space15">
    <div class="layui-col-md12">
        <div class="layui-card">
            <div class="layui-card-header">手动封禁IP</div>
            <div class="layui-card-body">
                <form class="layui-form" id="manual-block-form">
                    <div class="layui-form-item">
                        <label class="layui-form-label">IP地址</label>
                        <div class="layui-input-inline" style="width: 200px;">
                            <input type="text" name="ip" required lay-verify="required" placeholder="请输入IP地址" class="layui-input">
                        </div>
                        <label class="layui-form-label">封禁原因</label>
                        <div class="layui-input-inline" style="width: 200px;">
                            <input type="text" name="reason" required lay-verify="required" placeholder="请输入封禁原因" class="layui-input">
                        </div>
                        <label class="layui-form-label">封禁时长</label>
                        <div class="layui-input-inline" style="width: 150px;">
                            <select name="duration">
                                <option value="3600">1小时</option>
                                <option value="86400">1天</option>
                                <option value="604800">7天</option>
                                <option value="2592000">30天</option>
                                <option value="0">永久</option>
                            </select>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <div class="layui-input-block">
                            <button class="layui-btn" lay-submit lay-filter="manual-block">封禁IP</button>
                            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
layui.use(['form', 'table', 'laypage', 'layer'], function(){
    var form = layui.form;
    var table = layui.table;
    var laypage = layui.laypage;
    var layer = layui.layer;
    
    // 全选/取消全选
    form.on('checkbox(all-select)', function(data){
        var child = $('#blocked-ips-table tbody input[name="blocked-ip-check"]');
        child.each(function(index, item){
            item.checked = data.elem.checked;
        });
        form.render('checkbox');
    });
    
    // 搜索按钮
    $('#search-blocked-ip-btn').on('click', function(){
        var ip = $('#search-ip').val();
        // 这里应该调用API进行搜索
        console.log('搜索IP:', ip);
    });
    
    // 解封选中按钮
    $('#unblock-selected-btn').on('click', function(){
        var checked = $('#blocked-ips-table tbody input[name="blocked-ip-check"]:checked');
        if(checked.length === 0) {
            layer.msg('请先选择要解封的IP', {icon: 2});
            return;
        }
        
        layer.confirm('确定要解封选中的 '+checked.length+' 个IP吗？', function(index){
            var ips = [];
            checked.each(function(){
                ips.push($(this).val());
            });
            
            $.post('api/unblock_ips.php', {ips: ips}, function(response){
                if(response.success) {
                    layer.msg('解封成功', {icon: 1});
                    location.reload();
                } else {
                    layer.msg('解封失败：' + response.message, {icon: 2});
                }
            });
            
            layer.close(index);
        });
    });
    
    // 监听手动封禁表单提交
    form.on('submit(manual-block)', function(data){
        $.post('api/block_ip.php', data.field, function(response){
            if(response.success) {
                layer.msg('IP封禁成功', {icon: 1});
                $('#manual-block-form')[0].reset();
            } else {
                layer.msg('封禁失败：' + response.message, {icon: 2});
            }
        });
        return false; // 阻止表单跳转
    });
    
    // 解封IP函数
    window.unblockIP = function(ip) {
        layer.confirm('确定要解封IP: ' + ip + ' 吗？', function(index){
            $.post('api/unblock_ip.php', {ip: ip}, function(response){
                if(response.success) {
                    layer.msg('解封成功', {icon: 1});
                    location.reload();
                } else {
                    layer.msg('解封失败：' + response.message, {icon: 2});
                }
            });
            layer.close(index);
        });
    };
    
    // 删除封禁记录
    window.deleteBlockRecord = function(ip) {
        layer.confirm('确定要删除该封禁记录吗？', function(index){
            $.post('api/delete_block_record.php', {ip: ip}, function(response){
                if(response.success) {
                    layer.msg('记录删除成功', {icon: 1});
                    location.reload();
                } else {
                    layer.msg('删除失败：' + response.message, {icon: 2});
                }
            });
            layer.close(index);
        });
    };
});

// 全局解封IP函数
function unblockIP(ip) {
    layui.use(['layer'], function(){
        var layer = layui.layer;
        
        layer.confirm('确定要解封IP: ' + ip + ' 吗？', function(index){
            $.post('api/unblock_ip.php', {ip: ip}, function(response){
                if(response.success) {
                    layer.msg('解封成功', {icon: 1});
                    location.reload();
                } else {
                    layer.msg('解封失败：' + response.message, {icon: 2});
                }
            });
            layer.close(index);
        });
    });
}
</script>