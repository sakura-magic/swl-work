<?php
declare(strict_types=1);

namespace box\event\websocket;

use app\socket\controller\Com;
use box\LiftMethod;
use server\Table;
use Swoole\WebSocket\Server;
use work\HelperFun;

class Close
{
    public function access(Server $ws, $fd)
    {
        $isStartOk = LiftMethod::checkRunOk();
        if (!$isStartOk) {
            //todo wokerStart启动未完成
            return -1;
        }
        HelperFun::getContainer()->bind(\Swoole\WebSocket\Server::class, fn() => $ws);
        $userData = Table::getTable('wsUserInfo')->get('user_' . $fd);
        if (!$userData) {
            return -1;
        }
        $com = new Com();
        $com->tapeOut($userData['userId']);
        if (!empty($userData['userId'])) {
            Table::getTable('userMapInfo')->del($userData['userId']);
        }
        Table::getTable('wsUserInfo')->del('user_' . $fd);
        return 0;
    }
}