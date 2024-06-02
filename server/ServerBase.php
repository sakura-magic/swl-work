<?php
declare(strict_types=1);

namespace server;

use server\other\Console;
use server\other\ServerException;
use server\other\ServerTool;
use Swoole\Server;
use server\agreement\ServerBasics;

class  ServerBase implements ServerBasics
{

    /**
     * 服务对象
     * @var Server|null
     */
    protected ?Server $server = null;
    protected bool $check = true;

    protected array $config = [];
    //绑定的事件
    protected array $bindEventList = [];

    /**
     * 初始化
     * @return bool
     */
    protected function init(): bool
    {
        if (is_null($this->server)) {
            return false;
        }
        return $this->eventBind();
    }

    /**
     * 获取服务
     * @return Server|null
     */
    public function getSever(): ?Server
    {
        return $this->server;
    }

    /**
     * 运行服务
     * @return bool|void
     * @throws ServerException
     */
    public function start(): bool
    {
        if (!$this->errorInfo()) {
            return false;
        }
        $pidFile = $this->getServerConfig('server.setConfig.pid_file');
        if (!empty($pidFile) && file_exists($pidFile)) {
            // 读取主进程ID
            $pid = intval(file_get_contents($pidFile));
            if ($pid !== 0) {
                \Co\run(function () use ($pid) {
                    $res = \Swoole\Coroutine\System::exec('ps -ef | grep ' . $pid);
                    $masterName = $this->getServerConfig('server.processNameConfig.master');
                    if (strpos($res['output'], $masterName) !== false) {
                        Console::dump(['error server is start ' . $pid], SERVER_ERROR['start']);
                        $this->check = false;
                    }
                });
            }
        }
        if (!$this->check) {
            return false;
        }
        $this->getSever();
        if (!$this->init()) {
            return false;
        }
        ServerTool::setServer($this);
        return $this->server->start();
    }

    /**
     * 重载服务
     * @return bool
     */
    public function reload(): bool
    {
        if (!$this->errorInfo()) {
            return false;
        }
        $pidFile = $this->getServerConfig('server.setConfig.pid_file');
        if (!isset($pidFile) || !file_exists($pidFile)) {
            Console::dump(['not found pidFile'], SERVER_ERROR['stop']);
            return false;
        }
        // 读取主进程ID
        $pid = intval(file_get_contents($pidFile));
        if (0 === $pid) {
            Console::dump(['pid error'], SERVER_ERROR['reload']);
            return false;
        } else if (posix_kill($pid, SIGUSR1)) {
            Console::dump(['workerReload success'], SERVER_ERROR['success']);
            if (!isset($this->config['server']['mode']) || $this->config['server']['mode'] === SWOOLE_BASE) {
                return true;
            }
//            Console::dump(['taskReload success'],0);
            return true;
        } else {
            Console::dump(['reload unusual'], SERVER_ERROR['reload']);
            return false;
        }
    }

    /**
     * 停止服务
     * @return bool
     */
    public function stop(): bool
    {
        $pidFile = $this->getServerConfig('server.setConfig.pid_file');
        if ($pidFile === null || !file_exists($pidFile)) {
            Console::dump(['not found pidFile'], SERVER_ERROR['stop']);
            return false;
        }
        // 读取主进程ID
        $pid = intval(file_get_contents($pidFile));
        if (0 === $pid) {
            Console::dump(['pid error'], SERVER_ERROR['stop']);
            return false;
        } else if (posix_kill($pid, SIGTERM) && file_put_contents($pidFile, 0)) {
            Console::dump(['stop success'], SERVER_ERROR['success']);
            return true;
        } else {
            Console::dump(['stop unusual'], SERVER_ERROR['stop']);
            return false;
        }
    }

    /**
     * 重启服务
     * @return bool
     * @throws ServerException
     */
    public function restart(): bool
    {
        if (!$this->stop()) {
            return false;
        }
        Console::dump(['stop loading...'], SERVER_ERROR['success']);
        sleep(15);
        return $this->start();
    }

    /**
     * 获取配置信息
     * @param string $key
     * @param $default
     * @return array|mixed|null
     */
    public function getServerConfig(string $key = '', $default = null)
    {
        if (empty($key)) {
            return $this->config;
        }
        $names = explode('.', $key);
        $config = $this->config;
        foreach ($names as $val) {
            if (isset($config[$val])) {
                $config = $config[$val];
            } else {
                return $default;
            }
        }
        return $config;
    }

    /**
     * 绑定事件
     * @return bool
     */
    protected function eventBind(): bool
    {
        if (empty($this->bindEventList)) {
            Console::dump(['The event cannot be empty'], -1);
            return false;
        }
        $mode = $this->getServerConfig('server.mode');
        $mode = $mode !== SWOOLE_PROCESS;
        foreach ($this->bindEventList as $key => $value) {
            if ($mode && strtolower($key) === 'start') { //base模式不能调用start
                continue;
            }
            if (!class_exists($value)) {
                Console::dump([$value . ' class not found'], -1);
                return false;
            }
            $obj = new $value;
            if (!method_exists($obj, 'access')) {
                Console::dump(["There is no access method in the {$value}"], -1);
                return false;
            }
            $this->server->on(ucfirst($key), [$obj, 'access']);
        }
        return true;
    }

    /**
     * work信息判断
     * @throws ServerException
     */
    private function loaderInfoWorkerMessage(): array
    {
        $files = ServerTool::scanFolder(WORKER_INFO_CONF['initFile'] ?? '', ['work']);
        if (empty($files)) {
            throw new ServerException('read work file error');
        }
        $list = [];
        foreach ($files['work'] ?? [] as $value) {
            $info = ServerTool::readFileInfo((WORKER_INFO_CONF['initFile'] ?? '') . $value['basename']);
            $fileInfo = explode('_', $value['filename']);
            $processInfo = end($fileInfo);
            $processListInfo = [
                'name' => mb_substr($processInfo, 0, -1),
                'index' => mb_substr($processInfo, -1, 1),
                'fileInfo' => unserialize($info)
            ];
            $list[] = $processListInfo;
        }
        return $list;
    }

    /**
     * 创建table
     * @return void
     */
    public function createTable()
    {
        Table::initialize();
    }

    /**
     * 判断是否有错误信息
     * @return bool
     */
    public function errorInfo(): bool
    {
        if (strtolower($_SERVER['USER'] ?? '') == 'root' || strtolower($_SERVER['SUDO_USER'] ?? '') == 'root') {
            Console::dump(['Do not use the [root] account'], -9);
            return false;
        }
        $info = ServerTool::readFileInfo(WORKER_INFO_CONF['noThrowLastErrorFile']);
        if (!empty($info)) {
            Console::dump(['please eliminate errors', unserialize($info)], -1);
            return false;
        }
        $eachInfo = [
            $this->getServerConfig('server.setConfig.pid_file', ''),
            $this->getServerConfig('server.setConfig.log_file', ''),
            WORKER_INFO_CONF['initFile'] ?? '',
            WORKER_INFO_CONF['noThrowLastErrorFile'] ?? '',
            ROOT_PATH . DS . 'logs' . DS . 'run' . DS . 'php_error_log.log',
            ROOT_PATH . DS . 'logs' . DS . 'session_info' . DS . 'session_init',
            ROOT_PATH . DS . 'logs' . DS . 'log' . DS . 'info' . DS . 'info_init',
            ROOT_PATH . DS . 'logs' . DS . 'log' . DS . 'error' . DS . 'error_init',
            ROOT_PATH . DS . 'logs' . DS . 'log' . DS . 'system_error' . DS . 'system_error_init',
            ROOT_PATH . DS . 'logs' . DS . 'lock_info' . DS . 'lock_info_init',
            ROOT_PATH . DS . 'logs' . DS . 'view' . DS . 'view_init',
        ];
        foreach ($eachInfo as $val) {
            if (!empty($val) && !ServerTool::createDir($val)) {
                Console::dump(['The file has no write permission.Procedure', $val], -2);
                return false;
            }
        }
        return true;
    }

}