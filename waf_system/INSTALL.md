# WAF防护系统安装部署指南

## 系统要求

- PHP 7.0 或更高版本
- Web服务器 (Apache/Nginx)
- 支持的PHP扩展: json, filter, pcre, session

## 部署步骤

### 1. 上传文件
将整个 `waf_system` 目录上传到您的Web服务器

### 2. 设置权限
确保以下目录具有写入权限:
- `/logs` - 用于存储日志文件
- `/config` - 用于存储配置文件

```bash
chmod -R 755 /path/to/waf_system
chmod -R 777 /path/to/waf_system/logs
chmod -R 777 /path/to/waf_system/config
```

### 3. 运行部署脚本
访问 `http://your-domain.com/waf_system/deploy.php` 运行部署脚本

或通过命令行运行：
```bash
php /path/to/waf_system/deploy.php
```

### 4. 登录系统
- 访问 `http://your-domain.com/waf_system/login.php`
- 默认账号：admin
- 默认密码：admin

### 5. 配置系统
- 首次登录后立即修改密码
- 在"系统设置"中配置各项参数
- 根据需要调整防护规则

## 使用WAF保护其他网站

### 方法一：在目标网站入口文件中引入
在需要保护的网站入口文件（如 index.php）开头添加：

```php
<?php
require_once '/path/to/waf_system/waf_engine.php';
?>
```

### 方法二：通过.htaccess配置
在目标网站根目录的 `.htaccess` 文件中添加：

```apache
php_value auto_prepend_file "/path/to/waf_system/waf_engine.php"
```

## 目录结构说明

```
waf_system/
├── config/                 # 配置文件目录
│   └── config.php          # 系统配置文件
├── includes/               # 函数库文件
│   └── functions.php       # 通用函数库
├── api/                    # API接口目录
│   ├── stats.php           # 统计信息API
│   ├── attacks.php         # 攻击日志API
│   └── block_ip.php        # 封禁IP API
├── static/                 # 静态资源目录
│   └── layui/              # Layui前端框架
├── logs/                   # 日志文件目录
├── modules/                # 模块目录
├── waf_engine.php          # WAF核心防护引擎
├── index.php               # 系统主入口
├── login.php               # 登录页面
├── deploy.php              # 部署脚本
├── README.md               # 说明文档
└── INSTALL.md              # 安装指南
```

## 安全建议

1. **修改默认凭证**：首次访问后立即修改默认用户名和密码
2. **保护配置文件**：确保配置文件无法通过Web访问
3. **定期更新**：定期检查并更新防护规则
4. **备份数据**：定期备份配置和日志文件
5. **监控性能**：关注系统对网站性能的影响

## 常见问题

### Q: 如何自定义防护规则？
A: 可以在后台的"防护管理" - "规则管理"中添加或修改防护规则

### Q: 如何查看攻击日志？
A: 在"安全日志" - "攻击日志"中可以查看详细的攻击记录

### Q: 如何解封误封的IP？
A: 在"封禁IP管理"中可以搜索并解封指定IP

### Q: WAF会影响网站性能吗？
A: 系统经过优化，对性能影响极小，通常在毫秒级别

## 技术支持

如需技术支持，请联系开发者或查阅相关文档。

---

**注意**：使用前请在测试环境验证，确保不会影响正常业务功能。