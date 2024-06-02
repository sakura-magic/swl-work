<?php
declare(strict_types=1);
namespace box\task;
use server\other\ServerTool;
use Swoole\Coroutine;
use work\SwlBase;

class SwlTask
{
    private int $done = 0;

    private ?string $method = null;

    private string $taskClass;

    private ?array $initList = null;

    private ?array $data = null;

    private ?\Closure $thenFun = null;

    private ?\Closure $catchFun = null;

    private ?int $cid = null;

    private int $taskId = -1;

    /**
     * SwlTask constructor.
     * @param string $className
     */
    public function __construct(string $className,?string $method = null)
    {
        $this->taskClass = $className;
        $this->method = $method;
        if (SwlBase::inCoroutine()) {
            $this->cid = Coroutine::getCid();
        }
    }

    /**
     * init参数
     * @param ...$init
     */
    public function init(...$init): SwlTask
    {
        $this->initList = $init;
        return $this;
    }


    /**
     * @param ...$data
     */
    public function param(...$data): SwlTask
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param \Closure $fun
     */
    public function then(\Closure $fun): SwlTask
    {
        $this->thenFun = $fun;
        return $this;
    }

    /**
     * @param \Closure $fun
     */
    public function catch(\Closure $fun): SwlTask
    {
        $this->catchFun = $fun;
        return $this;
    }

    /**
     * 异步执行
     */
    public function asyncRun(): bool
    {
        if ($this->done) {
            return false;
        }
        $taskBody = $this->getTaskBodyInfo();
        if ($this->thenFun || $this->catchFun) {
            $flag = ServerTool::getServer()->getSever()->task($taskBody,$this->taskId,[$this,"finishAsyncTask"]);
        } else {
            $flag = ServerTool::getServer()->getSever()->task($taskBody,$this->taskId);
        }
        return is_numeric($flag);
    }

    /**
     * 获取任务构建body
     * @param string|null $swlTaskSign
     * @return string
     */
    private function getTaskBodyInfo(?string $swlTaskSign = null): string
    {
        $bodyInfo = [
            "class" => $this->taskClass
        ];
        if ($this->method) {
            $bodyInfo["method"] = $this->method;
        }
        if ($this->initList) {
            $bodyInfo["init"] = $this->initList;
        }
        if ($this->data) {
            $bodyInfo["data"] = $this->data;
        }
        if ($swlTaskSign) {
            $bodyInfo["swlTaskSignCall"] = $swlTaskSign;
        }
        return serialize($bodyInfo);
    }

    /**
     * 同步执行
     */
    public function waitRun(float $timeOut = 0.5)
    {
        if ($this->done) {
            return false;
        }
        $taskBody = $this->getTaskBodyInfo();
        $result = ServerTool::getServer()->getSever()->taskwait($taskBody,$timeOut,$this->taskId);
        $this->done = 1;
        $this->thenFun = null;
        $this->catchFun = null;
        return $result;
    }

    /**
     * @return int|null
     */
    public function getCid(): ?int
    {
        return $this->cid;
    }

    /**
     * 异步完成管理器调用
     * @param \Swoole\Server $server
     * @param int $taskId
     * @param string $data
     * @return bool
     */
    public function finishAsyncTask(\Swoole\Server $server,int $taskId, string $data): bool
    {
        $data = unserialize($data);
        if (is_array($data) && $data['code'] == 0 && $this->thenFun) {
            $callback = $this->thenFun;
            $callback($data,$taskId,$server,$this);
        }
        $this->thenFun = null;
        if ((!is_array($data) || $data['code'] != 0) && $this->catchFun) {
            $callback = $this->catchFun;
            $callback($data,$taskId,$server,$this);
        }
        $this->thenFun = null;
        $this->done = 1;
        return true;
    }
}