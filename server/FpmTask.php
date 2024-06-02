<?php
declare(strict_types=1);

namespace server;

use server\agreement\FpmTaskInterface;
use server\agreement\ServerBasics;
use server\other\Console;
use server\other\FpmTaskManage;
use server\other\ManageLink;
use server\other\ServerTool;
use work\GlobalVariable;

class FpmTask implements ServerBasics
{
    private array $config = [];
    private int $pid;
    //fpm list
    private array $poolList = [];

    public function __construct()
    {
        $this->config = FPM_TASK_CONFIG;
    }

    public function start(): bool
    {
        if (file_exists($this->getServerConfig('pidFile'))) {
            Console::dump(["The file " . $this->getServerConfig('pidFile') . "exists."], SERVER_ERROR['start']);
            return false;
        }
        $taskList = $this->getServerConfig('taskClass', []);
        if (!is_array($taskList) || count($taskList) < 1) {
            Console::dump(["The task class is empty."], SERVER_ERROR['start']);
            return false;
        }
        foreach ($taskList as $val) {
            if (!class_exists($val)) {
                Console::dump(["{$val} class is not exists."], SERVER_ERROR['start']);
                return false;
            }
        }
        if (empty($this->config['outPutFile']) || !ServerTool::createDir($this->config['outPutFile'])) {
            Console::dump(["create output file dir error"], SERVER_ERROR['start']);
            return false;
        }
        $pid = pcntl_fork();
        if ($pid == -1) {
            Console::dump(["could not fork"], SERVER_ERROR['success']);
            return false;
        } else if ($pid) {
            $this->pid = $pid;
            Console::dump(["start success"], SERVER_ERROR['success']);
            return false;
        } else {
            $sid = posix_setsid();
            if ($sid < 0) {
                Console::dump(["setSid error"], SERVER_ERROR['stop']);
                return false;
            }
            global $STDOUT, $STDERR;
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);
            $STDOUT = fopen($this->getServerConfig('stdout', '/dev/null'), "rw+");
            $STDERR = fopen($this->getServerConfig('stderr', '/dev/null'), "rw+");
            //这里是子进程
            $this->runProcess($taskList);
            return true;
        }
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
     * 结束
     * @return bool
     */
    public function stop(): bool
    {
        if (file_exists($this->getServerConfig('pidFile'))) {
            $pid = file_get_contents($this->getServerConfig('pidFile'));
            posix_kill(intval($pid), SIGKILL);
            unlink($this->getServerConfig('pidFile'));
            Console::dump(['stop success'], SERVER_ERROR['success']);
            return true;
        }
        Console::dump(['not found pidFile'], SERVER_ERROR['stop']);
        return false;
    }

    /**
     * 退出
     * @return bool
     */
    public function quit(): bool
    {
        if (file_exists($this->getServerConfig('pidFile'))) {
            $pid = file_get_contents($this->getServerConfig('pidFile'));
            posix_kill(intval($pid), SIGUSR1);
            unlink($this->getServerConfig('pidFile'));
            Console::dump(['quit success'], SERVER_ERROR['success']);
            return true;
        }
        Console::dump(['not found pidFile'], SERVER_ERROR['stop']);
        return false;
    }

    /**
     * 暂不支持reload
     * @return bool
     */
    public function reload(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function restart(): bool
    {
        if (!$this->quit()) {
            return false;
        }
        sleep(2);
        if (!$this->start()) {
            return false;
        }
        return true;
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }


    /**
     * 运行
     */
    protected function runProcess(array $taskList)
    {

        $stdout = fopen($this->config['outPutFile'], 'a');
        if (!$stdout) {
            Console::dump(['open output file error'], SERVER_ERROR['success']);
            exit(1);
        }
        stream_set_blocking($stdout, false);
        $exitInfo = false;
        pcntl_signal(SIGUSR1, function () use (&$exitInfo) { //触发自定义信号，退出管理进程
            $exitInfo = true;
        });
        file_put_contents($this->getServerConfig('pidFile'), getmypid());
        $this->pid = getmypid();
        processName($this->getServerConfig('processName', 'php-task-pool'));
        GlobalVariable::getManageVariable('_sys_')->set('currentCourse', 'task');
        $manageLink = new ManageLink();
        foreach ($taskList as $val) {
            $obj = new $val($manageLink);
            if ($obj instanceof FpmTaskInterface) {
                $className = get_class($obj);
                $this->poolList[$className] = $obj;
            }
        }
        unset($obj, $className);
        while (true) {
            $start = microtime(true);
            foreach ($this->poolList as &$val) {
                if ($val instanceof FpmTaskInterface) {
                    ob_start();
                    $status = $val->start();
                    $content = ob_get_clean();
                    if (!empty($content)) {
                        fwrite($stdout, $content);
                    }
                    if ($status == 1) {  //返回状态是1说明进入了子进程
                        $exitInfo = true;
                        break;
                    }
                }
            }
            $manageLink->free();
            pcntl_signal_dispatch();
            if ($exitInfo) {
                break;
            }
            $end = microtime(true);
            $costTime = round(($start - $end) * 1000000);
            $sleepSec = intval(1000000 - $costTime);
            if ($sleepSec > 5000) {
                usleep($sleepSec);
            }
        }
        fclose($stdout);
        exit(0);
    }


}