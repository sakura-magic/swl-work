<?php
declare(strict_types=1);

namespace server\event;

use work\cor\facade\Log;
use work\HelperFun;
use work\Hook;

class Receive
{
    /**
     * 接受到tcp的信息
     * @param $server
     * @param $fd
     * @param $reactor_id
     * @param $data
     */
    public function access($server, $fd, $reactor_id, $data)
    {
        $hookRunResult = Hook::getInstance('tcp')->runHook('receive', [$server, $fd]);
        if ($hookRunResult === HOOK_RESULT_INFO['skipRun']) {
            return;
        }
        try {
            $obj = new \box\event\tcp\Receive();
            $obj->access($server, $fd, $reactor_id, $data);
        } catch (\Throwable | \PDOException | \RedisException | \Error | \Exception | \ErrorException | \TypeError | \ParseError $throwable) {
            $hookRunResult = Hook::getInstance('tcp')->runHook('receiveError', [$throwable, $server, $fd]);
            if ($hookRunResult !== HOOK_RESULT_INFO['skipRun']) {
                $errorInfo = HelperFun::outErrorInfo($throwable);
                Log::error("tcp connect error : \n" . $errorInfo);
            }
        } finally {
            Hook::getInstance('tcp')->runHook('receiveFinally');
            HelperFun::flushCo();
        }
    }
}