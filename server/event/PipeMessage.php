<?php
declare(strict_types=1);

namespace server\event;

use work\cor\facade\Log;
use work\HelperFun;
use work\Hook;

class PipeMessage
{
    public function access($server, $src_worker_id, $data)
    {
        $hookRunResult = Hook::getInstance('webSocket')->runHook('pipMessage', [$server, $src_worker_id, $data]);
        if ($hookRunResult === HOOK_RESULT_INFO['skipRun']) {
            return;
        }
        try {
            $obj = new \box\event\websocket\PipeMessage();
            $obj->access($server, $src_worker_id, $data);
        } catch (\Throwable | \PDOException | \RedisException | \Error | \Exception | \ErrorException | \TypeError | \ParseError $throwable) {
            $hookRunResult = Hook::getInstance('webSocket')->runHook('pipMessageError', [$throwable, $server, $src_worker_id, $data]);
            if ($hookRunResult !== HOOK_RESULT_INFO['skipRun']) {
                $errorInfo = HelperFun::outErrorInfo($throwable);
                Log::error("websocket pipeMessage error : \n" . $errorInfo);
            }
        } finally {
            Hook::getInstance('webSocket')->runHook('pipeFinally');
            HelperFun::flushCo();
        }
    }
}