<?php
declare(strict_types=1);

namespace server\other;

use box\event\Task;
use box\event\WorkerStart;
use work\GlobalVariable;

class FpmTimerManage
{
    private array $pool = [];//进程池


    private array $config = [];


    private ?array $taskInfo = null;

    private int $pid;

    private ManageLink $manageLink;

    private int $countNumber = 0;

    public function __construct(ManageLink $manageLink)
    {
        $this->config = FPM_TASK_TIMER_CONFIG;
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
            $cmdArr = $this->taskInfo;
            $this->taskInfo = null;
            $status = $this->run($cmdArr);
            if (in_array($status, [-2, -1, 1])) {
                return $status;
            }
        }
        $tableName = $this->config['table'] ?? 'timer_info';
        $limitNumber = $this->config['poolSize'] ?? 1;
        $limitNumber = is_numeric($limitNumber) ? intval($limitNumber) : 1;
        $limitNumber = $limitNumber - count($this->pool);
        if ($limitNumber <= 0) {
            return -2;
        }
        $maxNumberSelect = $limitNumber * 2;
        $nowTime = time();
        $timeInfo = date('Y-m-d H:i:s');
        for ($i = 0; $i < $maxNumberSelect; $i++) {
            $listInfo = $this->manageLink->getPdo()->table($tableName)->where([
                ['startTime', '<=', $timeInfo],
                ['endTime', '>=', $timeInfo],
                ['isStatus', '=', 1],
                ['runStatus', 'in', [0, 2]]
            ])->limit($i * $limitNumber, $limitNumber)->select();
            if ($listInfo === false || empty($listInfo)) {
                break;
            }
            foreach ($listInfo as $value) {
                if ($value['interval'] <= 0 && $value['runStatus'] != 0) { //如果没设置按秒运行
                    continue;
                }
                if ($value['interval'] > 0) {
                    $lastTime = empty($value['lastRunTime']) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value['lastRunTime'])
                        ? strtotime($value['startTime']) : strtotime($value['lastRunTime']);
                    $lastTime = $lastTime == false ? 0 : $lastTime;
                    if ($lastTime + $value['interval'] > $nowTime) {
                        continue;
                    }
                }
                $update = $this->manageLink->getPdo()->table($tableName)
                    ->where(['id' => $value['id'], 'isStatus' => 1])
                    ->where('runStatus', 'in', [0, 2])
                    ->update([
                        'lastRunTime' => date('Y-m-d H:i:s'),
                        'runStatus' => 1
                    ]);
                if ($update == false) { //更新失败不处理
                    continue;
                }
                $status = $this->run($value);
                if (in_array($status, [-2, -1, 1])) {
                    return $status;
                }
            }
        }
        return 0;
    }


    /**
     * 开始运行
     */
    private function run(array $info): int
    {
        $size = $this->config['poolSize'] ?? 1;
        if (count($this->pool) >= $size) { //已达创建子进程上限
            return -2;
        }
        $pid = pcntl_fork();
        if ($pid == -1) {
            $this->taskInfo = $info;
            return -1;
        } else if ($pid) {
            //创建进程成功
            $this->pool[$pid] = [
                "startTime" => time(),
                "cmdInfo" => $info,
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
            $timeOut = empty($val['cmdInfo']['timeOut']) ? ($this->config['defaultTimeOut'] ?? 3600) : $val['cmdInfo']['timeOut'];
            $timeOut = max(intval($timeOut), 20);
            if (time() > $timeOut + $val['startTime']) {
                posix_kill($key, SIGKILL); //杀死子进程
            }
        }
    }


    /**
     * 子进程处理内容
     */
    private function childProgress(array $info)
    {
        processName(($this->config['processName'] ?? 'php_fpm_timer') . min($this->countNumber + 1, 999999));
        $worker = new WorkerStart();
        $worker->initError();
        GlobalVariable::getManageVariable('_sys_')->set('currentCourse', 'task', true);
        $worker->loadFile();
        $worker->showError();
        $task = new Task();
        $task->dispose(getmypid(), $this->pid, [
            'class' => $info['className'],
            'method' => $info['method'],
            'init' => $info,
            'data' => $info['params']
        ]);
    }

}