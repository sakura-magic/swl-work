<?php
declare(strict_types=1);

namespace server;

use server\event\Connect;
use server\event\Receive;
use server\event\Start;
use server\event\TcpClose;
use Swoole\Server;

class Tcp extends ServerBase
{
    /**
     * 绑定的事件
     * @var array
     */
    protected array $bindEventList = [
        "start" => Start::class,
        "connect" => Connect::class,
        "receive" => Receive::class,
        "close" => TcpClose::class
    ];

    /**
     * Tcp constructor.
     */
    public function __construct()
    {
        $this->config['server'] = TCP_SERVER_CONF;
        $this->config['name'] = 'tcp';
        $this->createTable();//创建swoole内存表
    }


    /**
     * 创建服务
     * @return Server
     */
    public function getSever(): Server
    {
        if (is_null($this->server)) {
            $this->server = new Server($this->config['server']['host'], $this->config['server']['port'], $this->config['server']['mode'], SWOOLE_SOCK_TCP);
            $this->server->set($this->config['server']['setConfig']);
            processName($this->config['server']['processNameConfig']['manager']);
        }
        return $this->server;
    }
}