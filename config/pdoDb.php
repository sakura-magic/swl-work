<?php
return [
    //表名前缀
    "prefix" => "si_",
    //开启sql执行记录
    "recordLog" => false,
    //协程连接池每条调用是否自动归还，经过压测后结论是不适用调用频率高的情况，默认为false基本上是最优情况，如果您不了解请勿动
    "autoRet" => false,
    "worker" => [
        //默认调用
        "default" => [
            "db" => 'mysql',
            "host" => "localhost",
            "port" => 3306,
            "dbName" => "swl-work",
            "charSet" => "utf8mb4",
            "userName" => "root",
            "userPassword" => "root",
            "poolConnect" => true, //是否启用连接池 仅在cli swoole协程模式下生效
//            "poolConfListInfo" => [ //链接池配置
//                "minObjectNum" => 1,
//                "maxObjectNum" => 1
//            ]
        ],
        "test" => [
            "db" => 'mysql',
            "host" => "localhost",
            "port" => 3306,
            "dbName" => "test",
            "charSet" => "utf8mb4",
            "userName" => "root",
            "userPassword" => "root",
            "poolConnect" => true, //是否启用连接池
//            "poolConfListInfo" => [
//                "minObjectNum" => 1,
//                "maxObjectNum" => 1
//            ]
        ]
    ],
    //task进程
    "task" => [
        //默认调用
        "default" => [
            "db" => 'mysql',
            "host" => "127.0.0.1",
            "port" => 3306,
            "dbName" => "swl",
            "charSet" => "utf8mb4",
            "userName" => "swl",
            "userPassword" => "Seed2233...",
            "poolConnect" => false, //是否启用连接池
        ]
    ]

];