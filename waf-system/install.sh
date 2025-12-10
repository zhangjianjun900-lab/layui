#!/bin/bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:/www/server/phpmyadmin

# 获取当前脚本所在目录
curPath=`pwd`
rootPath=$(dirname "$curPath")

# 设置WAF安装路径
wafPath="/www/server/waf"

# 检查是否为卸载操作
action=$1
if [ "$action" == "uninstall" ]; then
    # 停止WAF服务
    if [ -f "/etc/init.d/waf" ]; then
        /etc/init.d/waf stop
    fi
    
    # 删除WAF服务脚本
    rm -f /etc/init.d/waf
    
    # 删除WAF程序文件
    rm -rf $wafPath
    
    # 删除宝塔面板菜单项
    if [ -f "/www/server/panel/data/plugin.json" ]; then
        # 这里可以添加删除宝塔面板菜单项的逻辑
        echo "WAF plugin uninstalled"
    fi
    
    echo "WAF uninstalled successfully"
    exit 0
fi

# 创建WAF安装目录
mkdir -p $wafPath
mkdir -p $wafPath/logs
mkdir -p $wafPath/config
mkdir -p $wafPath/ssl

# 复制WAF文件到目标目录
cp -r /workspace/waf-system/* $wafPath/

# 安装Node.js依赖（如果有的话）
cd $wafPath
if [ -f "package.json" ]; then
    npm install --production
fi

# 创建WAF启动脚本
cat > /etc/init.d/waf << 'EOF'
#!/bin/bash
### BEGIN INIT INFO
# Provides:          waf
# Required-Start:    $local_fs $remote_fs $network
# Required-Stop:     $local_fs $remote_fs $network
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: Layui WAF Service
# Description:       Layui WAF Web Application Firewall
### END INIT INFO

# Source function library
. /etc/rc.d/init.d/functions

USER="root"
DAEMON="waf-server.js"
ROOT_DIR="/www/server/waf"
SERVER="$ROOT_DIR/$DAEMON"
LOCK_FILE="/var/lock/subsys/waf"

start() {
    echo -n "Starting WAF service: "
    touch $LOCK_FILE
    daemon --user "$USER" --pidfile="$LOCK_FILE" $SERVER
    RETVAL=$?
    echo
    [ $RETVAL -eq 0 ] && echo_success || echo_failure
    echo
    return $RETVAL
}

stop() {
    echo -n "Shutting down WAF service: "
    killproc $DAEMON
    RETVAL=$?
    echo
    [ $RETVAL -eq 0 ] && rm -f $LOCK_FILE
    [ $RETVAL -eq 0 ] && echo_success || echo_failure
    echo
    return $RETVAL
}

restart() {
    stop
    start
}

status() {
    if [ -f "$LOCK_FILE" ]; then
        echo "WAF service is running."
        return 0
    else
        echo "WAF service is stopped."
        return 3
    fi
}

case "$1" in
    start)
        start
        ;;
    stop)
        stop
        ;;
    restart)
        restart
        ;;
    status)
        status
        ;;
    *)
        echo "Usage: {start|stop|restart|status}"
        exit 1
        ;;
esac

exit $?
EOF

# 设置启动脚本权限
chmod +x /etc/init.d/waf

# 设置开机自启
chkconfig --add waf
chkconfig --level 2345 waf on

# 启动WAF服务
/etc/init.d/waf start

echo "WAF installed successfully"