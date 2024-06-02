<?php
declare(strict_types=1);

namespace server\event;

use Swoole\Server;
use work\cor\facade\Log;
use work\HelperFun;
use work\Hook;

class TcpClose
{
    public function access(Server $server, $fd)
    {
        $hookRunResult = Hook::getInstance('tcp')->runHook('close', [$server, $fd]);
        if ($hookRunResult === HOOK_RESULT_INFO['skipRun']) {
            return;
        }
        try {
            $obj = new \box\event\tcp\Close();
            $obj->access($server, $fd);
        } catch (\Throwable | \PDOException | \RedisException | \Error | \Exception | \ErrorException | \TypeError | \ParseError $throwable) {
            $hookRunResult = Hook::getInstance('tcp')->runHook('closeError', [$throwable, $server, $fd]);
            if ($hookRunResult !== HOOK_RESULT_INFO['skipRun']) {
                $errorInfo = HelperFun::outErrorInfo($throwable);
                Log::error("tcp connect error : \n" . $errorInfo);
            }
        } finally {
           Hook::getInstance('tcp')->runHook('closeFinally');
           HelperFun::flushCo();
        }
    }
}