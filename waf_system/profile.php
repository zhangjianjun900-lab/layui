<div class="layui-row layui-col-space15">
    <div class="layui-col-md12">
        <div class="layui-card">
            <div class="layui-card-header">个人资料</div>
            <div class="layui-card-body">
                <form class="layui-form" action="api/update_profile.php" method="post">
                    <div class="layui-form-item">
                        <label class="layui-form-label">用户名</label>
                        <div class="layui-input-block">
                            <input type="text" name="username" value="admin" readonly class="layui-input layui-bg-gray">
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <label class="layui-form-label">新密码</label>
                        <div class="layui-input-block">
                            <input type="password" name="new_password" placeholder="留空则不修改密码" class="layui-input">
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <label class="layui-form-label">确认密码</label>
                        <div class="layui-input-block">
                            <input type="password" name="confirm_password" placeholder="请再次输入新密码" class="layui-input">
                        </div>
                    </div>
                    
                    <div class="layui-form-item">
                        <div class="layui-input-block">
                            <button class="layui-btn" lay-submit lay-filter="save-profile">保存资料</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
layui.use(['form', 'layer'], function(){
    var form = layui.form;
    var layer = layui.layer;
    
    // 监听表单提交
    form.on('submit(save-profile)', function(data){
        if(data.field.new_password !== data.field.confirm_password) {
            layer.msg('两次输入的密码不一致', {icon: 2});
            return false;
        }
        
        $.post('api/update_profile.php', data.field, function(response){
            if(response.success) {
                layer.msg('资料更新成功', {icon: 1});
            } else {
                layer.msg('更新失败：' + response.message, {icon: 2});
            }
        });
        return false; // 阻止表单跳转
    });
});
</script>