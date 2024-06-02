<?php
declare(strict_types=1);
defined('IS_SWOOLE_SERVER') || define('IS_SWOOLE_SERVER', false);

const FPM_TASK_CONFIG = [
    //pid
    "pidFile" => ROOT_PATH . DS . 'logs' . DS . 'run' . DS . '.fpm-task.pid',
    //task class
    "taskClass" => [
        \server\other\FpmTimerManage::class,
        \server\other\FpmTaskManage::class
    ],
    //输出信息
    "outPutFile" => ROOT_PATH . DS . 'logs' . DS . 'other' . DS . 'fpm-task.log',
    //linux 标准输出
    "stdout" => "/dev/null",
    //linux 标准错误输出
    "stderr" => "/dev/null"
];


//队列任务
const FPM_TASK_QUEUE_CONFIG = [
    //redisKey
    "listKey" => "list-key-fet",
    //最大进程数量
    "poolSize" => 3,
    //默认超时时间单位秒
    "defaultTimeOut" => 3600,
    //配置进程名
    "processName" => "php-task-fpm"
];

//定时任务
const FPM_TASK_TIMER_CONFIG = [
    //表名
    "table" => "si_timer_info",
    //最大进程数量
    "poolSize" => 12,
    //默认超时时间单位秒
    "defaultTimeOut" => 3600,
    //配置进程名
    "processName" => "php-timer-fpm"
];