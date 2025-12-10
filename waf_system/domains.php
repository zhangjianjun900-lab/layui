<div class="layui-row layui-col-space15">
    <div class="layui-col-md12">
        <div class="layui-card">
            <div class="layui-card-header">域名管理</div>
            <div class="layui-card-body">
                <div class="layui-btn-container" style="margin-bottom: 20px;">
                    <button class="layui-btn layui-btn-sm" id="add-domain-btn"><i class="layui-icon">&#xe654;</i> 添加域名</button>
                    <button class="layui-btn layui-btn-sm layui-btn-danger" id="delete-domains-btn"><i class="layui-icon">&#xe640;</i> 删除选中</button>
                </div>
                
                <table class="layui-table" id="domains-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" lay-filter="all-select" id="all-select-domains"></th>
                            <th>域名</th>
                            <th>状态</th>
                            <th>防护级别</th>
                            <th>添加时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="checkbox" name="domain-check" value="1"></td>
                            <td>example.com</td>
                            <td><span class="layui-badge layui-bg-green">已启用</span></td>
                            <td>高级</td>
                            <td>2023-01-01 10:00:00</td>
                            <td>
                                <a href="javascript:;" class="layui-btn layui-btn-xs">编辑</a>
                                <a href="javascript:;" class="layui-btn layui-btn-xs layui-btn-danger">删除</a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" name="domain-check" value="2"></td>
                            <td>test.com</td>
                            <td><span class="layui-badge layui-bg-green">已启用</span></td>
                            <td>标准</td>
                            <td>2023-01-02 14:30:00</td>
                            <td>
                                <a href="javascript:;" class="layui-btn layui-btn-xs">编辑</a>
                                <a href="javascript:;" class="layui-btn layui-btn-xs layui-btn-danger">删除</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div id="domains-pagination" style="text-align: right; margin-top: 20px;"></div>
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
        var child = $('#domains-table tbody input[name="domain-check"]');
        child.each(function(index, item){
            item.checked = data.elem.checked;
        });
        form.render('checkbox');
    });
    
    // 添加域名按钮
    $('#add-domain-btn').on('click', function(){
        layer.open({
            type: 2,
            title: '添加域名',
            shadeClose: true,
            shade: 0.8,
            area: ['500px', '400px'],
            content: 'add_domain.php'
        });
    });
    
    // 删除选中域名
    $('#delete-domains-btn').on('click', function(){
        var checked = $('#domains-table tbody input[name="domain-check"]:checked');
        if(checked.length === 0) {
            layer.msg('请先选择要删除的域名', {icon: 2});
            return;
        }
        
        layer.confirm('确定要删除选中的 '+checked.length+' 个域名吗？', function(index){
            var ids = [];
            checked.each(function(){
                ids.push($(this).val());
            });
            
            $.post('api/delete_domains.php', {ids: ids}, function(response){
                if(response.success) {
                    layer.msg('删除成功', {icon: 1});
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