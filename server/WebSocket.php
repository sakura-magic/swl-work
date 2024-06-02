<?php
declare(strict_types=1);

namespace server;

use server\event\Close;
use server\event\Message;
use server\event\Open;
use server\event\PipeMessage;
use server\event\Request;
use server\event\Start;
use server\event\Task;
use server\event\WorkerExit;
use server\event\WorkerStart;
use Swoole\WebSocket\Server;

class WebSocket extends ServerBase
{
    /**
     * 绑定的事件
     * @var array
     */
    protected array $bindEventList = [
        "start" => Start::class,
        "request" => Request::class,
        "task" => Task::class,
        "workerStart" => WorkerStart::class,
        "open" => Open::class,
        "message" => Message::class,
        "close" => Close::class,
        "pipeMessage" => PipeMessage::class,
        "workerExit" => WorkerExit::class
    ];


    public function __construct()
    {
        $this->config['server'] = WEBSOCKET_SEVER;
        $this->config['name'] = 'webSocket';
        $this->createTable();
    }


    /**
     * 创建服务
     * @return Server
     */
    public function getSever(): Server
    {
        if (is_null($this->server)) {
            $this->server = new Server($this->config['server']['host'], $this->config['server']['port'], $this->config['server']['mode']);
            $this->server->set($this->config['server']['setConfig']);
            processName(WEBSOCKET_SEVER['processNameConfig']['manager']);
        }
        return $this->server;
    }

}