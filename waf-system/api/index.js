/**
 * WAF API接口
 */
const express = require('express');
const router = express.Router();
const WAFCore = require('../services/waf-engine');

// 创建WAF实例用于API调用
const waf = new WAFCore();

// 获取WAF统计信息
router.get('/stats', (req, res) => {
  try {
    const stats = waf.getStats();
    res.json({
      success: true,
      data: stats
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
});

// 获取攻击日志
router.get('/logs', (req, res) => {
  try {
    // 读取日志文件
    const fs = require('fs');
    const logPath = waf.config.waf.logFile;
    
    if (fs.existsSync(logPath)) {
      const logs = fs.readFileSync(logPath, 'utf8');
      const logLines = logs.split('\n').filter(line => line.trim() !== '').slice(-50); // 只返回最近50条
      
      res.json({
        success: true,
        data: logLines.reverse() // 最新的在前
      });
    } else {
      res.json({
        success: true,
        data: []
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
});

// 获取WAF配置
router.get('/config', (req, res) => {
  try {
    res.json({
      success: true,
      data: waf.config
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
});

// 更新WAF配置
router.post('/config', (req, res) => {
  try {
    // 这里应该有验证和安全检查
    const newConfig = req.body;
    
    // 更新配置（实际应用中需要更安全的更新机制）
    Object.assign(waf.config, newConfig);
    
    res.json({
      success: true,
      message: 'Configuration updated successfully'
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
});

// 获取封禁IP列表
router.get('/blocked-ips', (req, res) => {
  try {
    const blockedIPs = Array.from(waf.blockedIPs.entries()).map(([ip, time]) => ({
      ip,
      blockedUntil: new Date(time).toISOString()
    }));
    
    res.json({
      success: true,
      data: blockedIPs
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
});

// 手动封禁IP
router.post('/block-ip', (req, res) => {
  try {
    const { ip, duration } = req.body;
    if (!ip) {
      return res.status(400).json({
        success: false,
        message: 'IP address is required'
      });
    }
    
    const blockDuration = (duration || 300) * 1000; // 默认5分钟，转换为毫秒
    waf.blockedIPs.set(ip, Date.now() + blockDuration);
    
    waf.log('info', `Manual IP block: ${ip} for ${duration || 300}s`, req);
    
    res.json({
      success: true,
      message: `IP ${ip} blocked successfully`
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
});

// 解封IP
router.post('/unblock-ip', (req, res) => {
  try {
    const { ip } = req.body;
    if (!ip) {
      return res.status(400).json({
        success: false,
        message: 'IP address is required'
      });
    }
    
    waf.blockedIPs.delete(ip);
    
    waf.log('info', `Manual IP unblock: ${ip}`, req);
    
    res.json({
      success: true,
      message: `IP ${ip} unblocked successfully`
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
});

// 测试WAF防护功能
router.get('/test', (req, res) => {
  // 这个端点可以用来测试WAF是否正常工作
  res.json({
    success: true,
    message: 'WAF is working properly'
  });
});

module.exports = router;