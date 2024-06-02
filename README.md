# swl-work
one easy php framework  
一个简单的php框架  

# 环境
php : 7.4.x  
swoole : 4.6.x

# 兼容两种模式fpm与swoole

fpm模式:  
通过public index.php 入口文件调用 支持pathInfo或index.php?s=/route  

命令行方式:  
通过public index.php 调用: php index.php s=/route a=1 b=2  

swoole模式:  
通过server.php 运行服务  
php server.php http start 启动http服务  
php server.php http stop  停止http服务  
php server.php http reload 重载http服务  
php server.php http restart 重启http服务  
  
php server.php websocket start 启动websocket服务与http类似


# 路由
1.解析注释方式  
2.配置路由文件

