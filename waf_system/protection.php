<div class="layui-row layui-col-space15">
    <div class="layui-col-md12">
        <div class="layui-card">
            <div class="layui-card-header">防护设置</div>
            <div class="layui-card-body">
                <form class="layui-form" action="api/update_protection_settings.php" method="post">
                    <div class="layui-form-item">
                        <label class="layui-form-label">SQL注入防护</label>
                        <div class="layui-input-block">
                            <input type="checkbox" name="sql_protection" lay-skin="switch" lay-text="开启|关闭" checked>
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <label class="layui-form-label">XSS防护</label>
                        <div class="layui-input-block">
                            <input type="checkbox" name="xss_protection" lay-skin="switch" lay-text="开启|关闭" checked>
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <label class="layui-form-label">CC攻击防护</label>
                        <div class="layui-input-block">
                            <input type="checkbox" name="cc_protection" lay-skin="switch" lay-text="开启|关闭" checked>
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <label class="layui-form-label">CC防护阈值</label>
                        <div class="layui-input-inline" style="width: 100px;">
                            <input type="number" name="cc_rate_limit" value="100" class="layui-input">
                        </div>
                        <div class="layui-input-inline" style="width: auto;">
                            <span class="layui-word-aux">次/分钟</span>
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <label class="layui-form-label">封禁时间</label>
                        <div class="layui-input-inline" style="width: 100px;">
                            <input type="number" name="block_time" value="3600" class="layui-input">
                        </div>
                        <div class="layui-input-inline" style="width: auto;">
                            <span class="layui-word-aux">秒</span>
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <label class="layui-form-label">日志记录</label>
                        <div class="layui-input-block">
                            <input type="checkbox" name="log_attacks" lay-skin="switch" lay-text="开启|关闭" checked>
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <div class="layui-input-block">
                            <button class="layui-btn" lay-submit lay-filter="save-protection-settings">保存设置</button>
                            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="layui-row layui-col-space15">
    <div class="layui-col-md12">
        <div class="layui-card">
            <div class="layui-card-header">防护规则管理</div>
            <div class="layui-card-body">
                <div class="layui-btn-container" style="margin-bottom: 20px;">
                    <button class="layui-btn layui-btn-sm" id="add-rule-btn"><i class="layui-icon">&#xe654;</i> 添加规则</button>
                    <button class="layui-btn layui-btn-sm layui-btn-danger" id="delete-rules-btn"><i class="layui-icon">&#xe640;</i> 删除选中</button>
                </div>
                
                <table class="layui-table" id="rules-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" lay-filter="all-select" id="all-select-checkbox"></th>
                            <th>规则名称</th>
                            <th>规则类型</th>
                            <th>规则内容</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="checkbox" name="rule-check" value="1"></td>
                            <td>SQL注入检测规则</td>
                            <td>SQL Injection</td>
                            <td>检测常见SQL注入特征</td>
                            <td><span class="layui-badge layui-bg-green">启用</span></td>
                            <td>
                                <a href="javascript:;" class="layui-btn layui-btn-xs">编辑</a>
                                <a href="javascript:;" class="layui-btn layui-btn-xs layui-btn-danger">删除</a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" name="rule-check" value="2"></td>
                            <td>XSS攻击检测规则</td>
                            <td>XSS</td>
                            <td>检测常见XSS特征</td>
                            <td><span class="layui-badge layui-bg-green">启用</span></td>
                            <td>
                                <a href="javascript:;" class="layui-btn layui-btn-xs">编辑</a>
                                <a href="javascript:;" class="layui-btn layui-btn-xs layui-btn-danger">删除</a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" name="rule-check" value="3"></td>
                            <td>CC攻击检测规则</td>
                            <td>CC Attack</td>
                            <td>检测高频访问</td>
                            <td><span class="layui-badge layui-bg-green">启用</span></td>
                            <td>
                                <a href="javascript:;" class="layui-btn layui-btn-xs">编辑</a>
                                <a href="javascript:;" class="layui-btn layui-btn-xs layui-btn-danger">删除</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
layui.use(['form', 'layer', 'table'], function(){
    var form = layui.form;
    var layer = layui.layer;
    var table = layui.table;
    
    // 监听开关变化
    form.on('switch', function(data){
        console.log('开关状态：'+ (this.checked ? 'ON' : 'OFF'));
    });
    
    // 监听表单提交
    form.on('submit(save-protection-settings)', function(data){
        $.post('api/update_protection_settings.php', data.field, function(response){
            if(response.success) {
                layer.msg('设置保存成功', {icon: 1});
            } else {
                layer.msg('保存失败：' + response.message, {icon: 2});
            }
        });
        return false; // 阻止表单跳转
    });
    
    // 添加规则按钮
    $('#add-rule-btn').on('click', function(){
        layer.open({
            type: 2,
            title: '添加防护规则',
            shadeClose: true,
            shade: 0.8,
            area: ['600px', '400px'],
            content: 'add_rule.php' // 需要创建这个页面
        });
    });
    
    // 全选/取消全选
    form.on('checkbox(all-select)', function(data){
        var child = $('#rules-table tbody input[name="rule-check"]');
        child.each(function(index, item){
            item.checked = data.elem.checked;
        });
        form.render('checkbox');
    });
    
    // 删除选中规则
    $('#delete-rules-btn').on('click', function(){
        var checked = $('#rules-table tbody input[name="rule-check"]:checked');
        if(checked.length === 0) {
            layer.msg('请先选择要删除的规则', {icon: 2});
            return;
        }
        
        layer.confirm('确定要删除选中的 '+checked.length+' 条规则吗？', function(index){
            var ids = [];
            checked.each(function(){
                ids.push($(this).val());
            });
            
            $.post('api/delete_rules.php', {ids: ids}, function(response){
                if(response.success) {
                    layer.msg('删除成功', {icon: 1});
                    // 刷新页面
                    location.reload();
                } else {
                    layer.msg('删除失败：' + response.message, {icon: 2});
                }
            });
            
            layer.close(index);
        });
    });
});
</script>