<?php
declare(strict_types=1);

namespace server;

use server\agreement\ServerBasics;

/**
 * 启动器
 */
class Initiator
{

    private array $serverContent = [];
    private array $instruct = [
        "http" => [
            "class" => Http::class,
            "command" => ["start", "stop", "reload", "restart"],
            "option" => [],
            "before" => ["loadFile" => ['load' . DS . 'start' . DS . 'swl', 'load' . DS . 'start']]
        ],
        "websocket" => [
            "class" => WebSocket::class,
            "command" => ["start", "stop", "reload", "restart"],
            "option" => [],
            "before" => ["loadFile" => ['load' . DS . 'start' . DS . 'swl', 'load' . DS . 'start']]
        ],
        "tcp" => [
            "class" => Tcp::class,
            "command" => ["start", "stop", "reload", "restart"],
            "option" => [],
            "before" => ["loadFile" => ['load' . DS . 'start' . DS . 'swl', 'load' . DS . 'start']]
        ],
        "udp" => [
            "class" => Udp::class,
            "command" => ["start", "stop", "reload", "restart"],
            "option" => [],
            "before" => ["loadFile" => ['load' . DS . 'start' . DS . 'swl', 'load' . DS . 'start']]
        ],
        "fpmtask" => [
            "class" => FpmTask::class,
            "command" => ["start", "stop", "restart", "quit"],
            "option" => [],
            "before" => ["loadFile" => ['load' . DS . 'start' . DS . 'fpm', 'load' . DS . 'start']]
        ]
    ];
    private bool $check = false;
    private ?ServerBasics $baseServerInfo = null;

    /**
     * @return bool|null
     */
    public function readCommand(): ?bool
    {
        global $argc, $argv;
        // CMD最多只兼容5个参数
        if ($argc <= 1 || $argc > 3) {
            echo "arguments error\n";
            return false;
        }
        $this->serverContent['server'] = $argv[1] ?? null;
        $this->serverContent['action'] = !empty($argv[2]) ? strtolower($argv[2]) : null;
        $this->serverContent['option'] = !empty($argv[3]) ? strtolower($argv[3]) : null;
        unset($argc, $argv);
        if (!isset($this->instruct[$this->serverContent['server']])) {
            echo "server invalid\n";
            return false;
        }
        $serverInfo = $this->instruct[$this->serverContent['server']];
        if (!empty($this->serverContent['action']) && !in_array($this->serverContent['action'], $serverInfo['command'] ?? [])) {
            echo "action invalid\n";
            return false;
        }
        if (!empty($this->serverContent['option']) && !in_array($this->serverContent['option'], $serverInfo['option'] ?? [])) {
            echo "option invalid\n";
            return false;
        }
        $this->check = true;
        return true;
    }

    /**
     * 执行命令
     */
    public function runCmd()
    {
        $server = $this->getInstance();
        if (is_null($server)) {
            return null;
        }
        $result = null;
        if (!empty($this->serverContent['action'])) {
            $result = $server->{$this->serverContent['action']}();
        }
        return $result;
    }

    /**
     * 获取实例
     */
    public function getInstance(): ?ServerBasics
    {
        if (is_null($this->baseServerInfo)) {
            if (!$this->check) {
                return null;
            }
            $configInfo = $this->instruct[$this->serverContent['server']] ?? [];
            if (empty($configInfo)) {
                return null;
            }
            $serverName = $configInfo['class'];
            if (!empty($configInfo['before']) && is_array($configInfo['before'])) {
                foreach ($configInfo['before'] as $key => $value) {
                    if (method_exists($this, $key)) {
                        $this->{$key}($value);
                    }
                }
            }
            $this->baseServerInfo = new $serverName($this->serverContent);
        }
        return $this->baseServerInfo;
    }

    /**
     * 载入信息
     * @param array $info
     */
    public function loadFile(array $info): void
    {
        foreach ($info as $k => $v) {
            \server\other\ServerTool::loadIncFile($v);
        }
    }


}