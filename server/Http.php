<?php
declare(strict_types=1);

namespace server;

use server\event\Finish;
use server\event\Request;
use server\event\Start;
use server\event\Task;
use server\event\WorkerExit;
use server\event\WorkerStart;
use Swoole\Http\Server;

class Http extends ServerBase
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
        "workerExit" => WorkerExit::class,
        "finish" => Finish::class
    ];

    public function __construct()
    {
        $this->config['server'] = HTTP_SERVER_CONFIG;
        $this->config['name'] = 'http';
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
            processName(HTTP_SERVER_CONFIG['processNameConfig']['manager']);
        }
        return $this->server;
    }


}