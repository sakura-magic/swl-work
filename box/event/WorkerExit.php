<?php
declare(strict_types=1);

namespace box\event;

use work\cor\Log;
use work\cor\pdo\PdoPoolGather;
use work\cor\redis\RedisPoolGather;
use box\timer\TimerMemory;

class WorkerExit
{
    /**
     * 关闭
     */
    public function access(\Swoole\Server $server, int $workerId)
    {
        PdoPoolGather::unsetObj();
        RedisPoolGather::unsetObj();
        TimerMemory::cleanAllTimer();
        (new Log())->info("worker exit");
    }
}