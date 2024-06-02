<?php
declare(strict_types=1);

use work\Hook;

Hook::getInstance('http')
    ->setHook('boxRequest', function (\work\cor\basics\face\RequestManageInterface $request, \work\cor\basics\face\ResponseManageInterface $response) {
        $response->setHeader("Content-Type", "text/html;charset=utf-8");
        $response->setHeader("Access-Control-Allow-Origin", "*");
        $response->setHeader("Access-Control-Allow-Methods", "POST,GET,OPTIONS,PUT,DELETE");
        $response->setHeader("Access-Control-Allow-Headers","DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Authorization");
        return HOOK_RESULT_INFO['none'];
    });

Hook::getInstance('http')
    ->setHook("boxRequestRouteParseSuccess",function (\work\cor\Request $request,\work\cor\Response $response) {
        if ($request->method() === 'OPTIONS') {
            $response->sendStr();
            return HOOK_RESULT_INFO['skipRun'];
        }
        return HOOK_RESULT_INFO['none'];
    });

//Hook::initialize('http')->setHook('request',function($request,$response){
//    $log = new \work\cor\Log();
//    $log->error('test 协程id:' . Coroutine::getCid());
//    $log->error('test 协程id2:' . Coroutine::getCid());
//    $response->end('调用成功test');
//    return HOOK_RESULT_INFO['skipRun'];
//});