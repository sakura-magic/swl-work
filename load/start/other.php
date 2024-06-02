<?php
declare(strict_types=1);
//定义hook信号量
define('HOOK_RESULT_INFO', [
    'none' => 0,//如果不做操作，请返回此参数
    'skipRun' => -1,//返回此信号代表挂载点必须自己处理内容，避免服务不可用
    'skipHttpIcoCheck' => -2
]);
//worker配置信息
define('WORKER_INFO_CONF', [
    'initFile' => ROOT_PATH . DS . 'logs' . DS . 'run' . DS . '.worker-init' . DS,
    'noThrowLastErrorFile' => ROOT_PATH . DS . 'logs' . DS . 'run' . DS . '.phplog.err'
]);
//项目名
define('RUN_SEVER_PROJECT_NAME', 'TES');
//代号
define('RUN_SERVER_MARK', 'CYL');
//批次
define('RUN_SERVER_LOT', 1);
//是否打开了过滤
define('MAGIC_QUOTES_GPC', (bool) ini_set("magic_quotes_runtime", '0'));