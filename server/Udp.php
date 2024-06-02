<?php
declare(strict_types=1);

namespace server;

use server\event\Packet;
use server\event\Task;
use Swoole\Server;

class Udp extends ServerBase
{
    protected array $bindEventList = [
        "packet" => Packet::class,
        "task" => Task::class
    ];

    /**
     * udp constructor.
     */
    public function __construct()
    {
        $this->config['server'] = UDP_SERVER_CONF;
        $this->config['name'] = 'udp';
        $this->createTable();//创建swoole内存表
    }


    /**
     * 创建服务
     * @return Server
     */
    public function getSever(): Server
    {
        if (is_null($this->server)) {
            $this->server = new Server($this->config['server']['host'], $this->config['server']['port'], $this->config['server']['mode'], SWOOLE_SOCK_UDP);
            $this->server->set($this->config['server']['setConfig']);
            processName($this->config['server']['processNameConfig']['manager']);
        }
        return $this->server;
    }
}