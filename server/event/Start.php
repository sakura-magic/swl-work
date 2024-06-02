<?php
declare(strict_types=1);

namespace server\event;

use server\other\Console;
use server\other\ServerTool;
use work\Hook;

class Start
{
    /**
     * 入口调用
     * @return void
     */
    public function access()
    {
        Console::tableDump(['server', 'host', 'port', 'work'], [
            [
                ServerTool::getServer()->getServerConfig('name', 'none'),
                ServerTool::getServer()->getServerConfig('server.host', 'none'),
                ServerTool::getServer()->getServerConfig('server.port', 'none'),
                ServerTool::getServer()->getServerConfig('server.setConfig.worker_num', '1')
            ]
        ], true);
        Hook::getInstance('sys')->runHook('start');
        Hook::getInstance('sys')->destroyHook('start');
        if (ServerTool::getServer()->getServerConfig('name') !== null) {
            Hook::getInstance(ServerTool::getServer()->getServerConfig('name'))->runHook('start');
            Hook::getInstance(ServerTool::getServer()->getServerConfig('name'))->destroyHook('start');
        }
        processName(ServerTool::getServer()->getServerConfig('server.processNameConfig.master', 'master'));
    }

}