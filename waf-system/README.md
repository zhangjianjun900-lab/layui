# Layui WAF - Web Application Firewall

基于Layui框架开发的Web应用防火墙系统，专为宝塔面板设计，提供SQL注入、XSS、CC攻击等多种安全防护功能。

## 功能特性

- **SQL注入防护**: 检测并阻止常见的SQL注入攻击
- **XSS防护**: 防止跨站脚本攻击
- **CC攻击防护**: 检测并限制频繁请求
- **敏感文件访问防护**: 阻止对敏感文件的访问
- **IP封禁管理**: 支持手动和自动IP封禁
- **实时监控**: 提供实时攻击日志和统计信息
- **灵活配置**: 支持在线修改防护规则

## 架构设计

```
waf-system/
├── api/                 # API接口
│   └── index.js         # 主API路由
├── config/              # 配置文件
│   └── waf-config.js    # WAF核心配置
├── controllers/         # 控制器（预留）
├── models/              # 数据模型（预留）
├── services/            # 业务逻辑
│   └── waf-engine.js    # WAF核心引擎
├── utils/               # 工具函数（预留）
├── public/              # 静态资源（预留）
├── waf-server.js        # WAF主服务器
├── bt-plugin.json       # 宝塔面板插件配置
├── install.sh           # 安装脚本
└── package.json         # Node.js项目配置
```

## 部署到宝塔面板

### 方法一：直接安装

1. 将整个 `waf-system` 目录复制到宝塔面板的插件目录：
   ```bash
   cp -r /workspace/waf-system /www/server/waf
   ```

2. 安装依赖：
   ```bash
   cd /www/server/waf
   npm install
   ```

3. 启动服务：
   ```bash
   pm2 start waf-server.js --name waf
   ```

### 方法二：使用宝塔面板插件机制

1. 将 `bt-plugin.json` 和 `install.sh` 文件配置好
2. 通过宝塔面板的第三方插件功能进行安装

## 配置说明

主要配置位于 `config/waf-config.js` 文件中：

```javascript
waf: {
  enabled: true,        // 是否启用WAF
  mode: 'detect',       // 模式：detect(检测)/block(拦截)
  logLevel: 'info',     // 日志级别
  logFile: '/var/log/waf/access.log' // 日志文件路径
}
```

## API接口

- `GET /api/stats` - 获取WAF统计信息
- `GET /api/logs` - 获取攻击日志
- `GET /api/config` - 获取WAF配置
- `POST /api/config` - 更新WAF配置
- `GET /api/blocked-ips` - 获取封禁IP列表
- `POST /api/block-ip` - 手动封禁IP
- `POST /api/unblock-ip` - 解封IP

## 控制面板

访问 `http://your-server:8080` 即可进入WAF控制面板，可以实时查看防护状态、攻击日志，并进行配置管理。

## 安全注意事项

1. 生产环境中请确保WAF服务运行在安全的网络环境中
2. 定期更新防护规则以应对新型攻击
3. 监控WAF性能，避免影响正常业务
4. 定期备份配置文件和日志

## 开发说明

如需二次开发，请注意：

1. 修改防护规则时要考虑误报率
2. 新增功能时要充分考虑安全性
3. 性能优化很重要，避免成为瓶颈
4. 遵循最小权限原则

## 许可证

MIT License