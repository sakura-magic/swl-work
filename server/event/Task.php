<?php
declare(strict_types=1);

namespace server\event;

use Swoole\Server;
use work\CoLifeVariable;
use work\cor\facade\Log;
use work\HelperFun;
use work\Hook;

class Task
{
    /**
     * @param Server $server
     * @param $taskId
     * @param $reactorId
     * @param $data
     * @return void|string
     */
    public function access(Server $server, $taskId, $reactorId, $data)
    {
        $result = Hook::getInstance('sys')->runHook('task', [$server, $taskId, $reactorId, $data]);
        if ($result === HOOK_RESULT_INFO['skipRun']) {
            return;
        }
        if (!is_string($data)) {
            return;
        }
        try {
            CoLifeVariable::getManageVariable()->set('taskServer', $server);
            $obj = new \box\event\Task();
            $obj->access($server, $taskId, $reactorId, $data);
        } catch (\Throwable | \PDOException | \RedisException | \Error | \Exception $throwable) {
            $result = Hook::getInstance('sys')->runHook('taskError', [$server, $taskId, $reactorId, $data]);
            if ($result !== HOOK_RESULT_INFO['skipRun']) {
                return;
            }
            $errorInfo = HelperFun::outErrorInfo($throwable);
            Log::error("http task error : \n" . $errorInfo);
        } finally {
            Hook::getInstance('sys')->runHook('taskFinally');
            HelperFun::flushCo();
        }
    }
}