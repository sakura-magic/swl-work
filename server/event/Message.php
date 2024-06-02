<?php
declare(strict_types=1);

namespace server\event;

use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use work\Config;
use work\cor\facade\Log;
use work\cor\facade\Wsl;
use work\HelperFun;
use work\Hook;

class Message
{

    public function access(Server $server, Frame $frame)
    {
        $hookRunResult = Hook::getInstance('webSocket')->runHook('message', [$server, $frame]);
        if ($hookRunResult === HOOK_RESULT_INFO['skipRun']) {
            return;
        }
        HelperFun::getContainer()->bind(Server::class, fn() => $server);
        HelperFun::getContainer()->bind(Frame::class, fn() => $frame);
        $obj = null;
        try {
            $obj = new \box\event\websocket\Message();
            $obj->access($server, $frame);
            if (method_exists($obj,'done')) {
                $obj->done();
            }
            unset($obj);
        } catch (\Throwable | \PDOException | \RedisException | \Error | \Exception | \ErrorException | \TypeError | \ParseError $throwable) {
            $hookRunResult = Hook::getInstance('webSocket')->runHook('messageError', [$throwable, $server, $frame]);
            if ($hookRunResult !== HOOK_RESULT_INFO['skipRun']) {
                $errorInfo = HelperFun::outErrorInfo($throwable);
                Log::error("websocket message error : \n" . $errorInfo);
                if (Config::getInstance()->get('other.debug')) {
                    $errorInfo = HelperFun::outErrorInfo($throwable, "<br>");
                    Wsl::pushSelf(Wsl::jsonPackData('system_error', -999, $errorInfo, []));
                } else {
                    Wsl::pushSelf(Wsl::jsonPackData('system_error', -999, '系统繁忙，请稍后再试', []));
                }
            }
            if ($obj instanceof \box\event\http\Request && method_exists($obj,'error')) {
                try {
                    $obj->error($throwable);
                }catch (\Throwable | \Error $e) {
                    Log::error("websocket message error 2: \n" . var_export($e,true));
                }
            }
        } finally {
            Hook::getInstance('webSocket')->runHook('messageFinally');
            HelperFun::flushCo();
        }
    }
}