<?php
return [
    'uploadPath' => ROOT_PATH . DS . 'public' . DS . 'upload' . DS . 'images' . DS,
    //上传地址
    '__upload__' => '',

    //上传图片地址
    '__uploadImg__' => '',

    'jwtConf' => [
        //加密key值
        'lockKey' => '12312',
        //接收
        'aut' => 'test',
        //签发
        'iss' => 'kks',
        //过期时间
        'timeout' => 86400
    ]
];