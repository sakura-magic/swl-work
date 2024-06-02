<?php
declare(strict_types=1);

namespace box\event;

use server\other\Console;
use server\other\ServerTool;
use Swoole\Server;
use work\CoLifeVariable;
use work\Config;
use work\cor\Log;
use work\cor\pdo\PdoConnectPool;
use work\cor\pdo\PdoPoolGather;
use work\cor\redis\RedisConnectPool;
use work\cor\redis\RedisPoolGather;
use work\DocParserFactory;
use work\GlobalVariable;
use work\HelperFun;
use work\Hook;
use work\Route;

class WorkerStart
{

    /**
     * worker
     * @param Server $server
     * @param int $workerId
     * @return void
     */
    public function access(Server $server, int $workerId)
    {
        if (function_exists('gc_enable')) {
            gc_enable();//开gc
        }
        HelperFun::setRandSeed();//重新设置随机种子，防止父进程调用过，导致work进程种子相同
        $this->loadFile();
        $this->initError();
        $process = isset($server->taskworker) && $server->taskworker == true ? 'task' : 'worker';
        if (!$this->manageProcessInfo($process, $workerId)) { //如果出现短时间内频繁拉起认为是有问题结束进程
            $server->shutdown();
            return;
        }
        $process = isset($server->taskworker) && $server->taskworker == true ? 'task' : 'worker';
        $setName = ServerTool::getServer()->getServerConfig("server.processNameConfig.{$process}", 'process');
        processName($setName . $workerId);
        $includeFileData = get_included_files();
        GlobalVariable::getManageVariable('_sys_')->set('beforeWorkerStartIncludeFile', $includeFileData, true);
        GlobalVariable::getManageVariable('_sys_')->set('workerId', $workerId, true);
        putenv("WORKER_ID={$workerId}");
        GlobalVariable::getManageVariable('_sys_')->set('currentCourse', $process, true);
        GlobalVariable::getManageVariable('_sys_')->set('workerServer', $server, true);
        putenv("CURRENT_COURSE={$process}");
        $memoryLimit = ServerTool::getServer()->getServerConfig('server.memory_limit', '64M');
        ini_set('memory_limit', $memoryLimit);//设置内存
        PdoPoolGather::getInstanceObj()->initialize();
        RedisPoolGather::getInstanceObj()->initialize();
        if ($server->taskworker !== true) {
            $this->initRoute();
        }
        $this->showError();
        GlobalVariable::getManageVariable('_sys_')->set('workerStartDone', true, true);
        Console::dump(["$process $workerId : start success ran : " . mt_rand(0,100)], 0);
        $serverName = ServerTool::getServer()->getServerConfig('name');
        $workerNum = ServerTool::getServer()->getServerConfig('server.setConfig.worker_num',swoole_cpu_num());
        Hook::getInstance($serverName ?? 'http')->runHook("workerStart:{$process}");
        Hook::getInstance($serverName ?? 'http')->destroyHook("workerStart:{$process}");
        Hook::getInstance($serverName ?? 'http')->runHook("workerStart:id:{$workerId}");
        Hook::getInstance($serverName ?? 'http')->destroyHook("workerStart:id:{$workerId}");
        if ($workerId < $workerNum) {
            Hook::getInstance($serverName ?? 'http')->runHook("workerStart:{$process}:{$workerId}");
            Hook::getInstance($serverName ?? 'http')->destroyHook("workerStart:{$process}:{$workerId}");
        } else {
            $taskIndex = $workerId - $workerNum;
            Hook::getInstance($serverName ?? 'http')->runHook("workerStart:{$process}:{$taskIndex}");
            Hook::getInstance($serverName ?? 'http')->destroyHook("workerStart:{$process}:{$taskIndex}");
        }
    }

    /**
     * 初始化
     */
    public function initError()
    {
        clearstatcache();
        error_reporting(E_ALL & ~E_NOTICE);
        register_shutdown_function([$this, 'phpShutdownFun']);//注册程序退出函数
        set_error_handler([$this, 'registerErrorCallback'], E_ALL | E_STRICT | E_WARNING | E_PARSE | E_RECOVERABLE_ERROR);
        set_exception_handler([$this,'setGlobalException']);
    }

    /**
     * 显示错误
     */
    public function showError()
    {
        $other = Config::getInstance()->get('other.debug');
        $showErrors = (bool)($other['debug'] ?? false);
        ini_set('display_errors', $showErrors ? 'on' : 'off');//是否开启错误
        ini_set('log_errors', $showErrors ? 'off' : 'on');
        ini_set('error_log', $other['phpErrorLogPath'] ?? (ROOT_PATH . DS . 'logs' . DS . 'run' . DS . 'php_error_log.log'));
    }

    /**
     * 初始化route信息
     */
    public function initRoute()
    {
        Route::getInstance()->init();//载入route信息
        HelperFun::scanFolder('route');
        if (IS_SWOOLE_SERVER) {
            Route::getInstance()->printInfo();
        }
        DocParserFactory::clear();
    }


    /**
     * 注册错误回调
     */
    public function registerErrorCallback($error_no, $error_str, $error_file, $error_line)
    {
        $smg = "php code system error file:{$error_file},info:{$error_str},line:{$error_line}";
        $log = new Log();
        $log->systemError($smg);
        $listData = [
            'no' => $error_no,
            'str' => $error_str,
            'file' => $error_file,
            'line' => $error_line,
            'joinMsg' => $smg
        ];
        CoLifeVariable::getManageVariable()->set('systemPhpErrorCallbackInfoMessage', $listData);
    }

    /**
     * 全局异常捕获函数
     */
    public function setGlobalException(\Throwable $exception)
    {
        $log = new Log();
        try{
            HelperFun::flushCo();
            $log->systemError(var_export($exception,true));
        }catch (\Throwable | \Error $e) {
            error_log('sys-->error->error:' . var_export($e,true));
        }
    }

    /**
     * 写入file信息
     * @param array $data
     */
    public function workFileInfo(string $fileName, array $data)
    {
        $processFileInfo = WORKER_INFO_CONF['initFile'] ?? '';
        $processFileInfo .= 'init_' . $fileName . '.work';
        Console::dumpFile($processFileInfo, serialize($data));
    }

    /**
     * 获取信息
     * @param string $fileName
     */
    public function getWorkFileInfo(string $fileName): ?array
    {
        $processFileInfo = WORKER_INFO_CONF['initFile'] ?? '';
        $processFileInfo .= 'init_' . $fileName . '.work';
        $data = ServerTool::readFileInfo($processFileInfo);
        if (empty($data)) {
            return [];
        }
        $data = unserialize($data);
        if (!is_array($data)) {
            return null;
        }
        return $data;
    }

    /**
     * 载入php执行文件
     */
    public function loadFile()
    {
        HelperFun::scanFolder('load' . DS . 'work');
        if (!IS_SWOOLE_SERVER) {
            HelperFun::scanFolder('load' . DS . 'work' . DS . 'fpm');
            Hook::getInstance('fpm')->runHook('workerStart');
            Hook::getInstance('fpm')->destroyHook('workerStart');
        } else {
            HelperFun::scanFolder('load' . DS . 'work' . DS . 'swl');
            $serverName = ServerTool::getServer()->getServerConfig('name');
            Hook::getInstance($serverName ?? 'http')->runHook('workerStart');
            Hook::getInstance($serverName ?? 'http')->destroyHook('workerStart');
        }
        Config::getInstance()->init();
    }

    /**
     * 程序关闭
     */
    public function phpShutdownFun()
    {
        $info = error_get_last();
        if (!empty($info)) {
            Console::dumpFile(WORKER_INFO_CONF['noThrowLastErrorFile'], serialize($info));
        }
    }

    /**
     * work启动管理
     */
    public function manageProcessInfo(string $processName, int $workerId): bool
    {
        $readInfo = $this->getWorkFileInfo($processName . $workerId);
        if ($readInfo === null) {
            return false;
        }
        $capacity = 10;//容量10
        $flow = 5;//每秒流量10
        $water = intval($readInfo['water'] ?? 0);
        $timestamp = $readInfo['timestamp'] ?? null;
        $res = HelperFun::funnelLimitFlow($capacity, $flow, $water, $timestamp);
        $this->workFileInfo($processName . $workerId, [
            'pid' => getmypid(),
            'gid' => getmygid(),
            'uid' => getmyuid(),
            'workerId' => $workerId,
            'timestamp' => $res['preTime'],
            'water' => $res['water']
        ]);
        return $res['code'] === 0;
    }
}