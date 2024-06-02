<?php
declare(strict_types=1);
define('WEBSOCKET_SEVER', [
    "host" => "0.0.0.0",
    "port" => 9510,
    "mode" => SWOOLE_BASE,
    "setConfig" => [
        //最大tcp连接数
        'max_conn' => 500,
        //是否开启协程
        'enable_coroutine' => true,
        //协程最大创建数量swoole默认单个协程为2M
        'max_coroutine' => 3000,
        //屏幕打印信息
        'log_file' => ROOT_PATH . DS . 'logs' . DS . 'run' . DS . 'log' . DS . 'runLog',
        //设置日期分割
        'log_rotation' => SWOOLE_LOG_ROTATION_DAILY,
        //启动的worker数量
        'worker_num' =>  4,
        //hook策略
        'hook_flags' => SWOOLE_HOOK_CURL | SWOOLE_HOOK_TCP | SWOOLE_HOOK_UNIX | SWOOLE_HOOK_SOCKETS | SWOOLE_HOOK_STREAM_FUNCTION,
        //最大请求数，超出重新启动进程,开启这个参数建议使用process模式防止worker重启造成连接断开
//        'max_request' => 1000000,
        //backlog此参数将决定最多同时有多少个等待 accept 的连接
        'backlog' => 128,
        //显示错误信息
        'display_errors' => true,
        //守护进程
        'daemonize' => false,
        //pid
        'pid_file' => ROOT_PATH . DS . 'logs' . DS . 'run' . DS . 'server.pid',
        //task进程
        'task_worker_num' => 2,
        //task进程处理最大次数退出，防止内存溢出
//        'task_max_request' => 100000,
        //重载
        'max_wait_time' => 16,
        //异步重载
        'reload_async' => true,
        //worker最多socketBuffer大小
        "socket_buffer_size" => 64 * 1024 * 1024,
        // 表示一个连接如果600秒内未向服务器发送任何数据，此连接将被强制关闭
        'heartbeat_idle_time' => 600,
        // 表示每60秒遍历一次
        'heartbeat_check_interval' => 80,
    ],
    'memory_limit' => '256M',//运行内存
    //名称配置
    "processNameConfig" => [
        "master" => "php_swoole_master",
        "worker" => "php_swoole_worker",
        "manager" => "php_swoole_manager",
        "task" => "php_swoole_task_worker"
    ]
]);