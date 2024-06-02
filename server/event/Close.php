<?php
declare(strict_types=1);

namespace server\event;

use work\cor\facade\Log;
use work\HelperFun;
use work\Hook;

class Close
{

    public function access(\Swoole\WebSocket\Server $ws, $fd)
    {
        $hookRunResult = Hook::getInstance('webSocket')->runHook('close', [$ws, $fd]);
        if ($hookRunResult === HOOK_RESULT_INFO['skipRun']) {
            return;
        }
        try {
            $obj = new \box\event\websocket\Close();
            $obj->access($ws, $fd);
        } catch (\Throwable | \PDOException | \RedisException | \Error | \Exception | \ErrorException | \TypeError | \ParseError $throwable) {
            $hookRunResult = Hook::getInstance('webSocket')->runHook('closeError', [$throwable, $ws, $fd]);
            if ($hookRunResult !== HOOK_RESULT_INFO['skipRun']) {
                $errorInfo = HelperFun::outErrorInfo($throwable);
                Log::error("http request error : \n" . $errorInfo);
            }
        } finally {
            Hook::getInstance('webSocket')->runHook('closeFinally');
            HelperFun::flushCo();
        }
    }
}