<?php
declare(strict_types=1);

namespace box\event\udp;
use box\LiftMethod;
use Swoole\Server;

class Packet
{
    public function access(Server $server, $data, $clientInfo)
    {
        $isStartOk = LiftMethod::checkRunOk();
        if (!$isStartOk) {
            //todo wokerStart启动未完成
            return -1;
        }
        var_dump([
            $server,
            $data,
            $clientInfo
        ]);
        $server->sendto($clientInfo['address'],$clientInfo['port'],"fdsas");
    }
}