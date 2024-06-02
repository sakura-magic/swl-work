<?php
declare(strict_types=1);

namespace server\event;

use work\cor\facade\Log;
use work\HelperFun;
use work\Hook;

class Finish
{

    /**
     * @param \Swoole\Server $server
     * @param int $taskId
     * @param $data
     * @return array|void
     */
    public function access(\Swoole\Server $server, int $taskId, $data)
    {
        $result = Hook::getInstance('sys')->runHook('finish', [$server, $taskId, $data]);
        if ($result === HOOK_RESULT_INFO['skipRun']) {
            return;
        }
        if (!is_string($data)) {
            return;
        }
        try {
            $obj = new \box\event\Finish();
            $result = $obj->access($server, $taskId, $data);
            $resCode = 'resCode';
            $resFlag = is_numeric($result) && method_exists($obj, 'resReturn') && property_exists($obj, $resCode);
            $resFlag = $resFlag && ($obj->{$resCode} === null || in_array($result, $obj->{$resCode}));
            if ($resFlag) {
                return $obj->resReturn();
            }
        } catch (\Throwable | \PDOException | \RedisException | \Error | \Exception $throwable) {
            $result = Hook::getInstance('sys')->runHook('finishError', [$server, $taskId, $data]);
            if ($result !== HOOK_RESULT_INFO['skipRun']) {
                return;
            }
            $errorInfo = HelperFun::outErrorInfo($throwable);
            Log::error("http task error : \n" . $errorInfo);
        } finally {
            Hook::getInstance('sys')->runHook('finishFinally');
            HelperFun::flushCo();
        }
    }

}