<?php
declare(strict_types=1);

namespace server\event;

use work\cor\Log;
use work\cor\pdo\PdoPoolGather;
use work\cor\redis\RedisPoolGather;
use work\HelperFun;
use work\Hook;

class WorkerExit
{
    /**
     * work进程退出资源
     * @param \Swoole\Server $server
     * @param int $workerId
     */
    public function access(\Swoole\Server $server, int $workerId)
    {
        $result = Hook::getInstance('sys')->runHook('workerExit', [$server, $workerId]);
        if ($result === HOOK_RESULT_INFO['skipRun']) {
            return;
        }
        try {
            $obj = new \box\event\WorkerExit();
            $obj->access($server, $workerId);
        } catch (\Exception | \Throwable | \PDOException | \Error | \RedisException $throwable) {
            $result = Hook::getInstance('sys')->runHook('workerError', [$server, $workerId]);
            if ($result !== HOOK_RESULT_INFO['skipRun']) {
                return;
            }
            $phpErrorInfo = HelperFun::getPhpErrorInfo();
            $msg = $phpErrorInfo && isset($phpErrorInfo['joinMsg']) ? $phpErrorInfo['joinMsg'] : '';
            $throwError = [
                'message' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'trace' => $throwable->getTrace(),
                'php_error' => [$msg]
            ];
            $errorInfo = HelperFun::debugErrorInfoStr($throwError);
            (new Log())->error('workerExit error' . $errorInfo);
        }
    }
}