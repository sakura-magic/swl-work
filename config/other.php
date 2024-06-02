<?php
return [
    //jwt配置key
    'jwtKey' => '123456',
    //时长单位s
    'exp' => 86400,
    //错误调试
    'debug' => false,
    //路由记录
    'routeInfo' => ROOT_PATH . DS .  'logs' . DS . 'run' . DS . 'route' . DS,
    //lockFile记录位置
    'lockFilePath' =>  ROOT_PATH . DS . 'logs' . DS . 'lock_info',
];