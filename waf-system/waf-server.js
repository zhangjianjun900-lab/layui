/**
 * WAF主服务器
 */
const express = require('express');
const http = require('http');
const path = require('path');
const WAFCore = require('./services/waf-engine');
const wafConfig = require('./config/waf-config');

// 创建Express应用
const app = express();
const server = http.createServer(app);

// 初始化WAF核心
const waf = new WAFCore();

// 中间件：解析JSON和URL编码数据
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// 应用WAF中间件
app.use((req, res, next) => {
  waf.checkRequest(req, res, next);
});

// API路由
app.use('/api', require('./api'));

// 主页 - WAF控制面板
app.get('/', (req, res) => {
  res.send(`
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Layui WAF 控制面板</title>
        <link rel="stylesheet" href="/layui/css/layui.css" media="all">
        <style>
            body { padding: 20px; }
            .stats-card { margin-bottom: 20px; }
            .attack-log { max-height: 400px; overflow-y: auto; }
        </style>
    </head>
    <body>
        <h1 class="layui-header">Layui WAF 控制面板</h1>
        
        <div class="layui-row layui-col-space15">
            <!-- 统计卡片 -->
            <div class="layui-col-md12">
                <div class="layui-card stats-card">
                    <div class="layui-card-header">WAF 统计信息</div>
                    <div class="layui-card-body">
                        <div class="layui-row layui-col-space10">
                            <div class="layui-col-md2">
                                <div class="layui-card">
                                    <div class="layui-card-body">
                                        <p>总请求数</p>
                                        <h2 id="total-requests">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="layui-card">
                                    <div class="layui-card-body">
                                        <p>已封禁IP</p>
                                        <h2 id="blocked-ips">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="layui-card">
                                    <div class="layui-card-body">
                                        <p>SQL注入拦截</p>
                                        <h2 id="sql-blocked">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="layui-card">
                                    <div class="layui-card-body">
                                        <p>XSS攻击拦截</p>
                                        <h2 id="xss-blocked">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="layui-card">
                                    <div class="layui-card-body">
                                        <p>CC攻击拦截</p>
                                        <h2 id="cc-blocked">0</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md2">
                                <div class="layui-card">
                                    <div class="layui-card-body">
                                        <p>敏感文件访问</p>
                                        <h2 id="sensitive-blocked">0</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- WAF状态控制 -->
            <div class="layui-col-md4">
                <div class="layui-card">
                    <div class="layui-card-header">WAF 状态控制</div>
                    <div class="layui-card-body">
                        <div class="layui-form-item">
                            <label class="layui-form-label">WAF状态</label>
                            <div class="layui-input-block">
                                <input type="checkbox" name="waf_enabled" lay-skin="switch" lay-text="开启|关闭" checked>
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">防护模式</label>
                            <div class="layui-input-block">
                                <select name="waf_mode">
                                    <option value="detect">检测模式</option>
                                    <option value="block">拦截模式</option>
                                </select>
                            </div>
                        </div>
                        <button class="layui-btn layui-btn-normal" id="save-config">保存配置</button>
                    </div>
                </div>
            </div>
            
            <!-- 规则配置 -->
            <div class="layui-col-md8">
                <div class="layui-card">
                    <div class="layui-card-header">防护规则配置</div>
                    <div class="layui-card-body">
                        <table class="layui-table">
                            <thead>
                                <tr>
                                    <th>防护类型</th>
                                    <th>状态</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>SQL注入防护</td>
                                    <td><span class="layui-badge layui-bg-green">启用</span></td>
                                    <td><button class="layui-btn layui-btn-xs layui-btn-danger">禁用</button></td>
                                </tr>
                                <tr>
                                    <td>XSS防护</td>
                                    <td><span class="layui-badge layui-bg-green">启用</span></td>
                                    <td><button class="layui-btn layui-btn-xs layui-btn-danger">禁用</button></td>
                                </tr>
                                <tr>
                                    <td>CC攻击防护</td>
                                    <td><span class="layui-badge layui-bg-green">启用</span></td>
                                    <td><button class="layui-btn layui-btn-xs layui-btn-danger">禁用</button></td>
                                </tr>
                                <tr>
                                    <td>敏感文件访问防护</td>
                                    <td><span class="layui-badge layui-bg-green">启用</span></td>
                                    <td><button class="layui-btn layui-btn-xs layui-btn-danger">禁用</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- 攻击日志 -->
            <div class="layui-col-md12">
                <div class="layui-card">
                    <div class="layui-card-header">实时攻击日志</div>
                    <div class="layui-card-body">
                        <div class="attack-log">
                            <table class="layui-table">
                                <thead>
                                    <tr>
                                        <th>时间</th>
                                        <th>IP地址</th>
                                        <th>攻击类型</th>
                                        <th>URL</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody id="attack-logs">
                                    <tr><td colspan="5">暂无攻击记录</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="/layui/layui.js" charset="utf-8"></script>
        <script>
            layui.use(['form', 'element'], function(){
                var form = layui.form;
                var element = layui.element;
                
                // 更新统计数据
                function updateStats() {
                    fetch('/api/stats')
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('total-requests').textContent = data.totalRequests || 0;
                            document.getElementById('blocked-ips').textContent = data.blockedIPs || 0;
                        });
                }
                
                // 初始加载统计数据
                updateStats();
                
                // 每10秒更新一次统计数据
                setInterval(updateStats, 10000);
                
                // 保存配置按钮事件
                document.getElementById('save-config').addEventListener('click', function() {
                    layer.msg('配置已保存', { icon: 1 });
                });
            });
        </script>
    </body>
    </html>
  `);
});

// 静态文件服务 - 提供Layui前端资源
app.use('/layui', express.static('/workspace/dist'));

// 错误处理中间件
app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).send('Something broke!');
});

// 启动服务器
const PORT = process.env.PORT || 8080;
server.listen(PORT, () => {
  console.log(`WAF Server running on port ${PORT}`);
  waf.log('info', `WAF Server started on port ${PORT}`, { url: `http://localhost:${PORT}` });
});

// 优雅关闭
process.on('SIGTERM', () => {
  console.log('WAF Server shutting down...');
  server.close(() => {
    console.log('WAF Server closed.');
  });
});