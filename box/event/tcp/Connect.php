<?php
declare(strict_types=1);

namespace box\event\tcp;

use box\LiftMethod;
use Swoole\Server;
use work\HelperFun;

class Connect
{
    /**
     * @param Server $server
     * @param int $fd
     */
    public function access(Server $server, int $fd)
    {
        $isStartOk = LiftMethod::checkRunOk();
        if (!$isStartOk) {
            //todo wokerStart启动未完成
            $server->close($fd);
            return -1;
        }
        HelperFun::getContainer()->bind(Server::class, fn() => $server);//将服务实例绑定到容器内
        echo "connect event\n";
    }
}