# WAF防护系统

这是一个基于Layui框架开发的Web应用防火墙（WAF）系统，可以部署到网站上为其他网站提供防护服务。

## 系统特性

- SQL注入防护
- XSS攻击防护
- CC攻击防护
- IP封禁管理
- 攻击日志记录
- 实时监控面板
- 灵活的规则配置

## 部署说明

### 1. 服务端部署（WAF管理面板）

将整个 `waf_system` 目录上传到您的服务器，通过Web访问安装。

1. 访问 `http://your-domain.com/waf_system/login.php`
2. 使用默认账号密码登录：admin / admin
3. 在防护设置中配置防护规则

### 2. 客户端部署（被保护网站）

对于需要保护的网站，在其入口文件（如 `index.php`）的最开始添加以下代码：

```php
<?php
// 引入WAF防护引擎
require_once '/path/to/waf_system/waf_engine.php';
?>
```

或者，您也可以在网站的 `.htaccess` 文件中添加：

```apache
php_value auto_prepend_file "/path/to/waf_system/waf_engine.php"
```

## 使用方法

### 保护网站

1. 在WAF管理面板中添加受保护的域名
2. 在目标网站中引入 `waf_engine.php`
3. 系统将自动开始防护

### 管理面板功能

- **仪表盘**：查看系统概览和实时攻击监控
- **防护管理**：配置各种防护规则
- **安全日志**：查看攻击日志和访问记录
- **封禁IP**：管理被封禁的IP地址
- **系统设置**：配置系统参数

## 配置说明

主要配置文件位于 `config/config.php`：

- `ENABLE_SQL_PROTECTION`：是否启用SQL注入防护
- `ENABLE_XSS_PROTECTION`：是否启用XSS防护
- `ENABLE_CC_PROTECTION`：是否启用CC攻击防护
- `CC_RATE_LIMIT`：CC防护阈值（每分钟请求数）
- `BLOCK_TIME`：IP封禁时间（秒）

## API接口

系统提供以下API接口（需要API密钥验证）：

- `/api/stats.php` - 获取系统统计信息
- `/api/attacks.php` - 获取攻击日志
- `/api/block_ip.php` - 封禁IP地址
- `/api/unblock_ip.php` - 解封IP地址

## 安全建议

1. 修改默认登录密码
2. 定期备份日志和配置
3. 监控系统性能
4. 定期更新防护规则
5. 配置适当的防护阈值以避免误杀

## 技术支持

如需技术支持，请联系开发者。

---

**注意**：使用本系统前请做好备份，建议先在测试环境验证功能。