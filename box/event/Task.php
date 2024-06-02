<?php
declare(strict_types=1);

namespace box\event;

use work\CoLifeVariable;
use work\GlobalVariable;

class Task
{

    private array $result = [];

    /**
     * 指定执行return操作的状态码，如果为null代表所有状态，状态码必须是数字
     * @var array|null
     */
    public ?array $resCode = [0];

    /**
     * 入口函数
     */
    public function access(\Swoole\Server $server, $taskId, $reactorId, $data): int
    {
        $isStartOk = GlobalVariable::getManageVariable('_sys_')->get('workerStartDone');
        if (!$isStartOk) {
            //todo wokerStart启动未完成
            return -1;
        }
        $result = $this->dispose($taskId, $reactorId, $data);
        $server->finish(serialize($result));
        return 1;
    }


    public function dispose($taskId, $reactorId, $data): array
    {
        $code = 0;
        try {
            $data = empty($data) ? '' : unserialize($data);
            if (!$data || !isset($data['class'])) {
                $code = -1;
                throw new \Exception('参数错误');
            }
            $method = empty($data['method']) || !is_string($data['method']) ? 'run' : $data['method'];
            $className = $data['class'];
            if (!preg_match('/^\\\\?app\\\\task\\\\.*$/',$className)) {
                $code = -2;
                throw new \Exception('task任务调用必须是app\task下的类:' . $className);
            }
            if (!class_exists($className)) {
                $code = -2;
                throw new \Exception('类不存在class:' . $className);
            }
            $code = -4;
            $init = $data['init'] ?? null;
            if (is_array($init)) {
                $class = new $className(...$init);
            } else if (isset($data['init'])) {
                $class = new $className($init);
            } else {
                $class = new $className();
            }
            if (!method_exists($class, $method)) {
                $code = -5;
                throw new \Exception("方法不存在class:{$className},method:{$method}");
            }
            $data = $data['data'] ?? null;
            if (is_array($init)) {
                $res = $class->{$method}(...$data);
            } else if (isset($data['data'])) {
                $res = $class->{$method}($data);
            } else {
                $res = $class->{$method}();
            }
            unset($class);
            $code = 0;
        } catch (\Throwable | \Exception | \Error $e) {
            $res = $e->getMessage();
        }
        return [
            'code' => $code,
            'reactorId' => $reactorId,
            'taskId' => $taskId,
            'result' => $res
        ];
    }



}