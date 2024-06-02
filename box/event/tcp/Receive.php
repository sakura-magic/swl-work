<?php
declare(strict_types=1);

namespace box\event\tcp;

use box\LiftMethod;
use Swoole\Server;


class Receive
{
    public function access(Server $server, $fd, $reactor_id, $data)
    {
        $isStartOk = LiftMethod::checkRunOk();
        if (!$isStartOk) {
            //todo wokerStart启动未完成
            return -1;
        }
        var_dump([
            'server' => $server,
            'fd' => $fd,
            'reactorId' => $reactor_id,
            'data' => $data
        ]);
        $server->send($fd, "hello world\n");
    }
}