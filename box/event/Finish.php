<?php
declare(strict_types=1);

namespace box\event;

use work\cor\Log;
use work\GlobalVariable;

class Finish
{

    public function access(\Swoole\Server $server, int $taskId, $data)
    {
        $isStartOk = GlobalVariable::getManageVariable('_sys_')->get('workerStartDone');
        if (!$isStartOk) {
            //todo wokerStart启动未完成
            return -1;
        }
        $log = new Log();
        $log->info('finish success');
        $log->infoWrite();
        return 0;
    }
}