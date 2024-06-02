<?php
declare(strict_types=1);

use work\Config;
use work\cor\FileGc;
use work\cor\ManageVariable;
use work\Hook;

//每次容器实例化时注册 todo 请注意协程方式下通过这种hook方式注册的绑定信息，才可以再子协程中实例化，否则会找不到绑定信息，除非您指定获取父协程容器(你要非常了解协程，否则不建议这么做)
Hook::getInstance('app')->setHook('instantiationContainer', function (\work\container\Container $container, ?int $cid, ManageVariable $manage) {
    //注册curl请求驱动
    $container->addContextualBinding(\work\cor\HttpRequest::class, \work\cor\http\face\NormalRequestInterface::class, \work\cor\http\swl\CoRequest::class);
    $container->bind(\work\cor\http\face\NormalRequestInterface::class, \work\cor\http\swl\CoRequest::class);
    //注册批量请求驱动
    $container->addContextualBinding(\work\cor\HttpBatchRequest::class, \work\cor\http\face\BatchRequestInterface::class, \work\cor\http\swl\CoBatchRequest::class);
    $container->bind(\work\cor\http\face\BatchRequestInterface::class, \work\cor\http\swl\CoBatchRequest::class);
    return null;
});
//webSocket定时器任务启动
Hook::getInstance('webSocket')->setHook('workerStart:worker', function () {
    \work\TimerMemory::addTick(30000, function () {
        $o = new \app\socket\Timed();
        $o->detectionLink();
    });
});
// session file gc
Hook::getInstance('http')->setHook('workerStart:task:0',function (){
    $clearSessionFile = false;
    \work\TimerMemory::addTick(50000,function () use (&$clearSessionFile) {
        if ($clearSessionFile) {
            unset($clearSessionFile);
            return ;
        }
        $clearSessionFile = true;
        $rand = mt_rand(1, 100);
        if ($rand <= 30) { //30%的概率
            $confPatch = (string)Config::getInstance()->get('session.sessionDir', ROOT_PATH . DS . 'logs' . DS . 'session');
            $life = Config::getInstance()->get('session.sessionLife',86400);
            $life = is_numeric($life) ? intval($life) : 0;
            $life = max($life,3600);
            $gcObj = new FileGc($confPatch,$life);
            $gcObj->gc();
        }
        $clearSessionFile = false;
        unset($clearSessionFile);
    });
});