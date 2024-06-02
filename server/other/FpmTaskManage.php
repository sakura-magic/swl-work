<?php
declare(strict_types=1);

namespace server\other;

use box\event\Task;
use box\event\WorkerStart;
use server\agreement\FpmTaskInterface;
use work\GlobalVariable;

class FpmTaskManage implements FpmTaskInterface
{

    private array $pool = [];//进程池


    private array $config = [];


    private ?string $taskInfo = null;

    private int $pid;

    private ManageLink $manageLink;

    private int $countNumber = 0;

    public function __construct(ManageLink $manageLink)
    {
        $this->config = FPM_TASK_QUEUE_CONFIG;
        $this->pid = getmypid();
        $this->manageLink = $manageLink;
    }

    /**
     *
     */
    public function start(): int
    {
        $this->progress();
        if (!is_null($this->taskInfo)) {
            $cmdStr = $this->taskInfo;
            $this->taskInfo = null;
            $status = $this->run($cmdStr);
            if (in_array($status, [-2, -1, 1])) {
                return $status;
            }
        }
        $redisKey = $this->config['listKey'] ?? 'fpm-task-list';
        $len = $this->manageLink->getRedis()->llen($redisKey);
        if ($len === false || $len <= 0) {
            return 0;
        }
        $maxNumber = $len * 2;
        $size = $this->config['poolSize'] ?? 1;
        do {
            if (count($this->pool) >= $size) { //已达创建子进程上限
                return -2;
            }
            $cmdStr = $this->manageLink->getRedis()->rPop($redisKey);
            if ($cmdStr === false || empty($cmdStr)) {
                return 0;
            }
            $status = $this->run($cmdStr);
            if (in_array($status, [-2, -1, 1])) {
                return $status;
            }
        } while ($maxNumber--);
        return 0;
    }


    /**
     * 开始运行
     */
    private function run(string $info): int
    {
        $size = $this->config['poolSize'] ?? 1;
        if (count($this->pool) >= $size) { //已达创建子进程上限
            return -2;
        }
        if (empty($info)) {
            return 0;
        }
        $pid = pcntl_fork();
        if ($pid == -1) {
            $this->taskInfo = $info;
        } else if ($pid) {
            //创建进程成功
            $this->pool[$pid] = [
                "startTime" => time(),
                "cmdInfo" => unserialize($info),
            ];
            $this->countNumber++;
            $this->countNumber = min($this->countNumber, 999999);
        } else {
            $this->childProgress($info);
            return 1;
        }
        return 0;
    }


    /**
     * 进程处理
     */
    private function progress()
    {
        foreach ($this->pool as $key => $val) {
            $result = pcntl_waitpid($key, $status, WNOHANG);
            if ($result == -1 || $result > 0) {//子进程已退出
                unset($this->pool[$key]);
                continue;
            }
            $timeOut = $val['cmdInfo']['timeOut'] ?? ($this->config['defaultTimeOut'] ?? 3600);
            $timeOut = max(intval($timeOut), 20);
            if (time() > $timeOut + $val['startTime']) {
                posix_kill($key, SIGKILL); //杀死子进程
            }
        }
    }


    /**
     * 子进程处理内容
     */
    private function childProgress(string $info)
    {
        processName(($this->config['processName'] ?? 'php_fpm_task') . min($this->countNumber + 1, 999999));
        $worker = new WorkerStart();
        $worker->initError();
        GlobalVariable::getManageVariable('_sys_')->set('currentCourse', 'task', true);
        $worker->loadFile();
        $worker->showError();
        $task = new Task();
        $task->dispose(getmypid(), $this->pid, $info);
    }

}