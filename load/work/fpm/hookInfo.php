<?php
declare(strict_types=1);

use work\cor\ManageVariable;
use work\Hook;

//每次容器实例化时注册
Hook::getInstance('app')->setHook('instantiationContainer', function (\work\container\Container $container, ?int $cid, ManageVariable $manage) {
    //注册curl请求驱动
    $container->addContextualBinding(\work\cor\HttpRequest::class, \work\cor\http\face\NormalRequestInterface::class, \work\cor\http\req\NormalCurl::class);
    $container->bind(\work\cor\http\face\NormalRequestInterface::class, \work\cor\http\req\NormalCurl::class);
    //注册批量请求驱动
    $container->addContextualBinding(\work\cor\HttpBatchRequest::class, \work\cor\http\face\BatchRequestInterface::class, \work\cor\http\req\BatchCurl::class);
    $container->bind(\work\cor\http\face\BatchRequestInterface::class, \work\cor\http\req\BatchCurl::class);
    return null;
});