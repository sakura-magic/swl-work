<?php
declare(strict_types=1);

namespace server\event;

use work\Config;
use work\cor\facade\Log;
use work\HelperFun;
use work\Hook;

class Open
{

    public function access(\Swoole\WebSocket\Server $server, \Swoole\Http\Request $request)
    {
        $hookRunResult = Hook::getInstance('webSocket')->runHook('open', [$server, $request]);
        if ($hookRunResult === HOOK_RESULT_INFO['skipRun']) {
            return;
        }
        try {
            $obj = new \box\event\websocket\Open();
            $obj->access($server, $request);
        } catch (\Throwable | \PDOException | \RedisException | \Error | \Exception | \ErrorException | \TypeError | \ParseError $throwable) {
            $hookRunResult = Hook::getInstance('webSocket')->runHook('openError', [$throwable, $server, $request]);
            if ($hookRunResult !== HOOK_RESULT_INFO['skipRun']) {
                $errorInfo = HelperFun::outErrorInfo($throwable);
                Log::error("websocket open error : \n" . $errorInfo);
                if (Config::getInstance()->get('other.debug')) {
                    $server->push($request->fd, json_encode([
                        'event' => 'system_error',
                        'code' => -999,
                        'msg' => $errorInfo,
                        'data' => []
                    ]));
                } else {
                    $server->push($request->fd, json_encode([
                        'event' => 'system_error',
                        'code' => -999,
                        'msg' => '系统繁忙，请稍后再试',
                        'data' => []
                    ]));
                }
            }
        } finally {
            Hook::getInstance('webSocket')->runHook('openFinally');
            HelperFun::flushCo();
        }
    }
}