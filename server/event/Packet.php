<?php
declare(strict_types=1);

namespace server\event;

use work\cor\facade\Log;
use work\HelperFun;
use work\Hook;

class Packet
{

    /**
     * 入口调用
     * @param $server
     * @param $data
     * @param $clientInfo
     */
    public function access($server, $data, $clientInfo)
    {
        $hookRunResult = Hook::getInstance('udp')->runHook('packet', [$server, $data, $clientInfo]);
        if ($hookRunResult === HOOK_RESULT_INFO['skipRun']) {
            return;
        }
        try {
            $obj = new \box\event\udp\Packet();
            $obj->access($server, $data, $clientInfo);
        } catch (\Throwable | \PDOException | \RedisException | \Error | \Exception | \ErrorException | \TypeError | \ParseError $throwable) {
            $hookRunResult = Hook::getInstance('udp')->runHook('packetError', [$throwable, $server, $data, $clientInfo]);
            if ($hookRunResult !== HOOK_RESULT_INFO['skipRun']) {
                $errorInfo = HelperFun::outErrorInfo($throwable);
                Log::error("tcp connect error : \n" . $errorInfo);
            }
        } finally {
            Hook::getInstance('udp')->runHook('packetFinally');
            HelperFun::flushCo();
        }
    }
}