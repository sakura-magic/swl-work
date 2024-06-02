<?php
declare(strict_types=1);
define('SWOOLE_TABLE_CACHE', [
    'default' => [
        //是否创建
        'create' => true,
        //总大小
        "size" => 1024,//表格最大行数
        //设置列类型
        "column" => [
            //name:列名，type:类型，size:大小
            ["name" => "num", "type" => \Swoole\Table::TYPE_INT, "size" => 8],//数字默认为8字节
            ["name" => "fnum", "type" => \Swoole\Table::TYPE_FLOAT, "size" => 8],//浮点数
            ["name" => "data", "type" => \Swoole\Table::TYPE_STRING, "size" => 65536]//字符串必须设置size
        ]
    ],
    'wsUserInfo' => [
        //是否创建
        'create' => true,
        //总大小
        "size" => 65535,//表格最大行数
        //设置列类型
        "column" => [
            //name:列名，type:类型，size:大小
            ["name" => "fd", "type" => \Swoole\Table::TYPE_INT, "size" => 8],//数字默认为8字节
            ["name" => "userId", "type" => \Swoole\Table::TYPE_STRING, "size" => 64],//用户Id
            ["name" => "status", "type" => \Swoole\Table::TYPE_INT, "size" => 8],//浮点数
            ["name" => "worker", "type" => \Swoole\Table::TYPE_INT, "size" => 8],//字符串必须设置size
            ["name" => "ptime", "type" => \Swoole\Table::TYPE_INT, "size" => 8] //上次ping的时间
        ]
    ],
//    //用户的map
    'userMapInfo' => [
        //是否创建
        'create' => true,
        'size' => 65535,//表格最大行数
        //设置列类型
        'column' => [
            ["name" => "fd", "type" => \Swoole\Table::TYPE_INT, "size" => 8],
            ["name" => "infoData", "type" => \Swoole\Table::TYPE_STRING, "size" => 2048]
        ]
    ]
]);