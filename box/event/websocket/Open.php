<?php
declare(strict_types=1);

namespace box\event\websocket;

use box\LiftMethod;
use server\Table;
use work\GlobalVariable;
use work\HelperFun;

class Open
{
    //todo open时候处理信息
    public function access(\Swoole\WebSocket\Server $server, \Swoole\Http\Request $request): int
    {
        $isStartOk = LiftMethod::checkRunOk();
        if (!$isStartOk) {
            //todo wokerStart启动未完成
            $server->close($request->fd);
            return -1;
        }
        HelperFun::getContainer()->bind(\Swoole\WebSocket\Server::class, fn() => $server);
        HelperFun::getContainer()->bind(\Swoole\Http\Request::class, fn() => $request);
        Table::getTable('wsUserInfo')->set('user_' . $request->fd, ['fd' => $request->fd, 'status' => 0, 'worker' => GlobalVariable::getManageVariable('_sys_')->get('workerId'), 'ptime' => time()]);
        $server->push($request->fd, json_encode(['event' => 'success', 'code' => 0, 'msg' => '连接成功', 'data' => ['fd' => var_export($request, true)]]));
        return 0;
    }
}