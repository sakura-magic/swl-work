<?php
return [
    //默认使用
    "defaultUse" => "default",
    //开启redis执行记录
    "recordLog" => false,
    //协程连接池每条调用是否自动归还，经过压测后结论是不适用调用频率高的情况，默认为false基本上是最优情况，如果您不了解请勿动
    "autoRet" => false,
    "worker" => [
        "default" => [
            "host" => "localhost",
            "port" => 6379,
            "auth" => '',
            "index" => 0,
            "poolConnect" => true, //是否启用连接池
        ],
        "test" => [
            "host" => "localhost",
            "port" => 6379,
            "auth" => '',
            "index" => 0,
            "poolConnect" => false, //是否启用连接池
        ]
    ],
    "task" =>[
        "default" => [
            "host" => "localhost",
            "port" => 6379,
            "auth" => '',
            "index" => 0,
            "timeOut" => 1,
            "poolConnect" => false, //是否启用连接池
        ]
    ]

];