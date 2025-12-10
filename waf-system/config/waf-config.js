/**
 * WAF系统配置文件
 */
const wafConfig = {
  // WAF基本配置
  waf: {
    enabled: true, // 是否启用WAF
    mode: 'detect', // 模式：detect(检测)/block(拦截)
    logLevel: 'info', // 日志级别
    logFile: '/var/log/waf/access.log', // 日志文件路径
  },
  
  // 安全规则配置
  rules: {
    // SQL注入防护
    sqlInjection: {
      enabled: true,
      keywords: [
        'union', 'select', 'insert', 'update', 'delete', 'drop', 'create', 'alter',
        'exec', 'execute', 'script', 'declare', 'union', 'having', 'sleep',
        'benchmark', 'extractvalue', 'updatexml', 'floor', 'count', 'concat'
      ]
    },
    
    // XSS防护
    xss: {
      enabled: true,
      patterns: [
        '<script', 'javascript:', 'vbscript:', 'onerror', 'onload', 
        'onclick', 'onmouseover', 'onmouseout', 'onfocus', 'onblur',
        '<iframe', '<object', '<embed', 'data:', 'expression'
      ]
    },
    
    // 文件上传防护
    fileUpload: {
      enabled: true,
      allowedTypes: ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
      maxSize: 10 * 1024 * 1024, // 10MB
      checkContent: true
    },
    
    // CC攻击防护
    ccAttack: {
      enabled: true,
      requestLimit: 100, // 每分钟最大请求数
      timeWindow: 60, // 时间窗口（秒）
      blockDuration: 300 // 封禁时长（秒）
    },
    
    // 敏感文件访问防护
    sensitiveFiles: {
      enabled: true,
      patterns: [
        '.git', '.svn', '.htaccess', '.env', 'config.php', 'wp-config.php',
        'config.json', 'config.xml', 'database.php', 'backup.sql', 'admin.php'
      ]
    }
  },
  
  // IP白名单
  whitelist: {
    ips: ['127.0.0.1', '::1'], // 本地IP白名单
    urls: ['/api/health', '/api/status'] // 不受保护的URL
  },
  
  // 告警配置
  alerts: {
    email: '',
    webhook: '',
    threshold: 10 // 达到多少次攻击触发告警
  }
};

module.exports = wafConfig;