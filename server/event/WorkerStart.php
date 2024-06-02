<?php
declare(strict_types=1);

namespace server\event;

use server\other\Console;
use server\other\ServerTool;
use work\Hook;

class WorkerStart
{
    public function access(\Swoole\Server $server, int $workerId)
    {
        try {
            processName(HTTP_SERVER_CONFIG['processNameConfig'][isset($server->taskworker) && $server->taskworker == true ? 'task' : 'worker'] . $workerId);
            Hook::getInstance('sys')->runHook('workerStart', [$server, $workerId]);
            Hook::getInstance('sys')->destroyHook('workerStart');
            $obj = new \box\event\WorkerStart();
            $obj->access($server, $workerId);
        } catch (\Exception | \Error | \Throwable $e) {
            Console::dump([
                'error' => 'workerStart',
                'info' => $e->getMessage()
            ], -9000);
        }
    }
}