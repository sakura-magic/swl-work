<?php
declare(strict_types=1);

namespace box\event\tcp;
use box\LiftMethod;

class Close
{
    public function access($server, $fd)
    {
        $isStartOk = LiftMethod::checkRunOk();
        if (!$isStartOk) {
            //todo wokerStart启动未完成
            return -1;
        }
        echo "close event\n";
    }
}