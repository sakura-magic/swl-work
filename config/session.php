<?php
return [
    //保存方式
    "saveOpt" => 'File',
    //选择index
    "redisIndex" => 3,
    //实时写入,非实时写入可能存在丢数据情况但性能最高
    "realWrite" => true,
    //session生命周期
    "sessionLife" => 7200,
    //前缀
    "prefix" => 'session_id_',
    //file路径
    "sessionDir" => ROOT_PATH . DS . 'logs' . DS . 'session_info'
];