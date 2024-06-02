<?php
declare(strict_types=1);
define('UDP_SERVER_CONF', [
    "host" => "0.0.0.0",
    "port" => 9511,
    "mode" => SWOOLE_PROCESS,
    "setConfig" => [
        //是否开启协程
        'enable_coroutine' => true,
        //协程最大创建数量swoole默认单个协程为2M
        'max_coroutine' => 1024,
        //屏幕打印信息
        'log_file' => ROOT_PATH . DS . 'logs' . DS . 'run' . DS . 'log' . DS . 'runLog',
        //设置日期分割
        'log_rotation' => SWOOLE_LOG_ROTATION_DAILY,
        //启动的worker数量
        'worker_num' => 1,
        //hook策略
        'hook_flags' => SWOOLE_HOOK_ALL,
        //最大请求数，超出重新启动进程
//        'max_request' => 1000000,
        //backlog此参数将决定最多同时有多少个等待 accept 的连接
        'backlog' => 128,
        //显示错误信息
        'display_errors' => true,
        //守护进程
        'daemonize' => false,
        //pid
        'pid_file' => ROOT_PATH . DS . 'logs' . DS . 'run' . DS . '.server.pid',
        //task进程
//        'task_worker_num' => 3,
        //task进程处理最大次数退出，防止内存溢出
//        'task_max_request' => 200,
        //协程task
//        'task_enable_coroutine' => true,
        //重载
        'max_wait_time' => 16,
        //异步重载
        'reload_async' => true,
        //worker最多socketBuffer大小
        "socket_buffer_size" => 64 * 1024 * 1024
    ],
    'memory_limit' => '512M',//运行内存
    //名称配置
    "processNameConfig" => [
        "master" => "php_swoole_master",
        "worker" => "php_swoole_worker",
        "manager" => "php_swoole_manager",
        "task" => "php_swoole_task_worker"
    ]
]);