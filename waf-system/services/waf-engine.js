/**
 * WAF核心防护引擎
 */
const fs = require('fs');
const path = require('path');
const wafConfig = require('../config/waf-config');

class WAFCore {
  constructor() {
    this.config = wafConfig;
    this.attackCounter = new Map(); // 攻击计数器
    this.blockedIPs = new Map(); // 封禁IP列表
    this.initLogging();
  }

  // 初始化日志系统
  initLogging() {
    const logDir = path.dirname(this.config.waf.logFile);
    if (!fs.existsSync(logDir)) {
      fs.mkdirSync(logDir, { recursive: true });
    }
  }

  // 记录日志
  log(level, message, req) {
    if (this.config.waf.logLevel === 'debug' || 
        (this.config.waf.logLevel === 'info' && level !== 'debug')) {
      const timestamp = new Date().toISOString();
      const logEntry = `[${timestamp}] [${level.toUpperCase()}] ${message} - IP: ${req.ip || req.connection.remoteAddress} - URL: ${req.url}\n`;
      fs.appendFileSync(this.config.waf.logFile, logEntry);
    }
  }

  // 检查是否在白名单中
  isWhitelisted(req) {
    // 检查IP白名单
    const clientIP = req.ip || req.connection.remoteAddress;
    if (this.config.whitelist.ips.includes(clientIP)) {
      return true;
    }
    
    // 检查URL白名单
    if (this.config.whitelist.urls.includes(req.url)) {
      return true;
    }
    
    return false;
  }

  // 检查是否被封禁
  isBlocked(req) {
    const clientIP = req.ip || req.connection.remoteAddress;
    const blockedUntil = this.blockedIPs.get(clientIP);
    
    if (blockedUntil && blockedUntil > Date.now()) {
      return true;
    } else if (blockedUntil && blockedUntil <= Date.now()) {
      // 清除过期的封禁
      this.blockedIPs.delete(clientIP);
    }
    
    return false;
  }

  // SQL注入检测
  detectSQLInjection(req) {
    if (!this.config.rules.sqlInjection.enabled) return false;
    
    const params = [...Object.values(req.query || {}), ...Object.values(req.body || {})];
    const keywords = this.config.rules.sqlInjection.keywords;
    
    for (const param of params) {
      if (typeof param === 'string') {
        const lowerParam = param.toLowerCase();
        for (const keyword of keywords) {
          if (lowerParam.includes(keyword.toLowerCase())) {
            this.log('warn', `SQL Injection attempt detected: ${keyword}`, req);
            return true;
          }
        }
      }
    }
    
    return false;
  }

  // XSS检测
  detectXSS(req) {
    if (!this.config.rules.xss.enabled) return false;
    
    const params = [...Object.values(req.query || {}), ...Object.values(req.body || {})];
    const patterns = this.config.rules.xss.patterns;
    
    for (const param of params) {
      if (typeof param === 'string') {
        const lowerParam = param.toLowerCase();
        for (const pattern of patterns) {
          if (lowerParam.includes(pattern.toLowerCase())) {
            this.log('warn', `XSS attempt detected: ${pattern}`, req);
            return true;
          }
        }
      }
    }
    
    // 检查HTTP头中的XSS
    for (const [header, value] of Object.entries(req.headers)) {
      if (typeof value === 'string') {
        const lowerValue = value.toLowerCase();
        for (const pattern of patterns) {
          if (lowerValue.includes(pattern.toLowerCase())) {
            this.log('warn', `XSS in header ${header} detected: ${pattern}`, req);
            return true;
          }
        }
      }
    }
    
    return false;
  }

  // 敏感文件访问检测
  detectSensitiveFileAccess(req) {
    if (!this.config.rules.sensitiveFiles.enabled) return false;
    
    const url = req.url.toLowerCase();
    const patterns = this.config.rules.sensitiveFiles.patterns;
    
    for (const pattern of patterns) {
      if (url.includes(pattern.toLowerCase())) {
        this.log('warn', `Sensitive file access attempt: ${pattern}`, req);
        return true;
      }
    }
    
    return false;
  }

  // CC攻击检测
  detectCCAttack(req) {
    if (!this.config.rules.ccAttack.enabled) return false;
    
    const clientIP = req.ip || req.connection.remoteAddress;
    const now = Date.now();
    const timeWindow = this.config.rules.ccAttack.timeWindow * 1000; // 转换为毫秒
    
    // 获取该IP的请求记录
    let requests = this.attackCounter.get(clientIP) || [];
    
    // 清除过期请求记录
    requests = requests.filter(timestamp => now - timestamp < timeWindow);
    
    // 添加当前请求
    requests.push(now);
    this.attackCounter.set(clientIP, requests);
    
    // 检查是否超过阈值
    if (requests.length > this.config.rules.ccAttack.requestLimit) {
      // 封禁IP
      const blockDuration = this.config.rules.ccAttack.blockDuration * 1000;
      this.blockedIPs.set(clientIP, now + blockDuration);
      
      this.log('warn', `CC Attack detected from IP: ${clientIP}, ${requests.length} requests in ${this.config.rules.ccAttack.timeWindow}s`, req);
      return true;
    }
    
    return false;
  }

  // 文件上传检测
  detectMaliciousUpload(file) {
    if (!this.config.rules.fileUpload.enabled) return false;
    
    // 检查文件大小
    if (file.size > this.config.rules.fileUpload.maxSize) {
      return true;
    }
    
    // 检查文件扩展名
    const ext = path.extname(file.originalname).substring(1).toLowerCase();
    if (!this.config.rules.fileUpload.allowedTypes.includes(ext)) {
      return true;
    }
    
    // 检查文件内容（简单实现）
    if (this.config.rules.fileUpload.checkContent) {
      const content = fs.readFileSync(file.path, 'utf8');
      // 检查是否包含恶意内容
      if (content.toLowerCase().includes('<script') || 
          content.toLowerCase().includes('javascript:')) {
        return true;
      }
    }
    
    return false;
  }

  // 主防护检查函数
  checkRequest(req, res, next) {
    // 检查是否启用WAF
    if (!this.config.waf.enabled) {
      return next();
    }
    
    // 检查是否在白名单中
    if (this.isWhitelisted(req)) {
      return next();
    }
    
    // 检查是否被封禁
    if (this.isBlocked(req)) {
      this.log('info', `Blocked request from banned IP: ${req.ip || req.connection.remoteAddress}`, req);
      return res.status(403).send('Forbidden: Your IP has been blocked by WAF');
    }
    
    // 执行各种安全检测
    const checks = [
      { name: 'SQL Injection', check: () => this.detectSQLInjection(req) },
      { name: 'XSS', check: () => this.detectXSS(req) },
      { name: 'Sensitive File Access', check: () => this.detectSensitiveFileAccess(req) },
      { name: 'CC Attack', check: () => this.detectCCAttack(req) }
    ];
    
    for (const check of checks) {
      if (check.check()) {
        if (this.config.waf.mode === 'block') {
          this.log('alert', `${check.name} attack blocked`, req);
          return res.status(403).send('Forbidden: Attack detected and blocked by WAF');
        } else {
          this.log('info', `${check.name} attack detected (monitoring mode)`, req);
          // 在检测模式下，仍然允许请求通过，但记录攻击
        }
      }
    }
    
    next();
  }
  
  // 获取统计信息
  getStats() {
    return {
      totalRequests: this.attackCounter.size,
      blockedIPs: this.blockedIPs.size,
      config: this.config
    };
  }
}

module.exports = WAFCore;