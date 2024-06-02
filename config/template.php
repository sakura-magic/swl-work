<?php
//模板参数
return [
    //是否开启缓存
    'catch' => false,
    //缓存地址
    'templateCatch' => ROOT_PATH . DS . 'logs' . DS .'view' . DS,
    //模板文件后缀
    'suffix' => 'html',
    //异步超时时间单位秒
    'taskTimeOut' => 3,
    //重载
    'auto_reload' => true,
    //优化
    'optimizations' => 0,
    //全局变量
    'globalVariable' => [
        //站点名称
        "__SITE_NAME__" => 'test',
        //环境
        "__ENV__" => "dev",
        //api
        "__API_ENCODE__" => false,
        //时间
        "__VERSION__" => '123',
        //key
        "__API_KEY__" => '',
        //公共资源位置
        "__PUBLIC__" => '',
        //页面静态资源位置
        "__STATIC__" => ''
    ],
    //其他参数值
    'option' => [
        'debug' => true,
        'autoescape' => 'html',
        'auto_reload' => true
    ]
];