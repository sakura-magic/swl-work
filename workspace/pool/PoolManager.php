<?php
declare(strict_types=1);

namespace work\pool;

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Timer;
use work\HelperFun;
use work\SwlBase;

abstract class PoolManager
{
    private ?Conf $conf;//配置类信息
    private ?Channel $channel = null;
    private bool $destroy = false;//销毁
    private ?int $intervalCheckTimerId = null;//定时器
    private int $createdNumber = 0;//创建的数量
    private array $objHashList = [];//obj hash
    private array $context = [];//上下问
    private array $inUseObject = [];//使用中的对象
    private ?int $loadAverageTimerId = null;//定时器id
    private float $loadWaitTime = 0;
    private int $loadUserNum = 0;
    private bool $loadAverageTimerRun = false;//空闲连接销毁定时器
    private bool $intervalCheckTimerRun = false;//检查定时器
    private int $frequencyGetObjNum = 0;//获取频率
    private int $getObjTime = 0;//获取时间
    private array $deferStatusList = [];//协程结束回调状态标


    abstract protected function createObject(): ?object;//创建对象

    private function __clone()
    {
    }

    public function __construct(?Conf $conf = null)
    {
        if ($conf === null) {
            $conf = new Conf();
        }
        if ($conf->getMinObjectNum() >= $conf->getMaxObjectNum()) {
            throw new \Exception("pool max num is small than min num for " . static::class . " error");
        }
        $this->conf = $conf;
    }

    //初始化

    /**
     * @throws \Exception
     */
    private function init(): void
    {
        if (!is_null($this->channel) || $this->destroy) {
            return;
        }
        $this->channel = new Channel($this->conf->getMaxObjectNum() + 8);//创建channel管理
        if ($this->conf->getIntervalCheckTime() > 0) { //判断是否启动检查定时器
            $timerId = Timer::tick($this->conf->getIntervalCheckTime(), $this->intervalCheck());
            if (!is_numeric($timerId)) {
                throw new \Exception('create swl timer tick for' . static::class . 'error');
            }
            $this->intervalCheckTimerId = $timerId;
        }
        $loadTimerId = Timer::tick($this->conf->getLoadAverageCheckTime(), $this->loadAverageFun());
        if (!is_numeric($loadTimerId)) {
            throw new \Exception('creat swl timer tick for' . static::class . 'error');
        }
        $this->loadAverageTimerId = $loadTimerId;
    }

    //检查函数
    private function intervalCheck(): \Closure
    {
        return function () {
            if ($this->intervalCheckTimerRun) {
                return;
            }
            $this->intervalCheckTimerRun = true;
            try {
                $this->idleCheck();
                $this->keepMin();
            } catch (\Throwable $throwable) {
                trigger_error($throwable->getMessage());
            }
            $this->intervalCheckTimerRun = false;
        };
    }

    /**
     * 超过timeout未出队列使用的，将会被回收
     * @throws \Exception
     * @throws \Throwable
     */
    protected function idleCheck(?int $timeout = null)
    {
        if (is_null($timeout)) {
            $timeout = $this->conf->getMaxIdleTime();
        }
        $this->init();
        $size = $this->channel->length();
        for ($i = 0; $i < $size; $i++) {
            if ($this->channel->isEmpty()) { //如果为空跳出循环
                break;
            }
            $item = $this->channel->pop(0.001);
            if (!$item instanceof PoolItemInterface) {
                continue;
            }
            if (time() - $item->getLastUseTime() > $timeout) {
                $num = $this->conf->getMinObjectNum();
                if ($this->createdNumber > $num) {
                    //标记为不在队列，允许进行gc
                    $hash = $item->getObjHashInfo();
                    $this->objHashList[$hash] = false;
                    $this->unsetObj($item);
                }
            }
            //执行检查
            if (!$this->itemIntervalCheck($item)) {
                $hash = $item->getObjHashInfo();
                $this->objHashList[$hash] = true;
                $this->unsetObj($item);
            } else {
                //如果itemIntervalCheck 为真，则重新标记为已经使用过
                $item->setLastUseTime(time());
                $this->channel->push($item);
            }
        }
    }

    /**
     * 释放对象
     * @param PoolItemInterface $obj
     * @return bool
     * @throws \Throwable
     */
    public function unsetObj(PoolItemInterface $obj): bool
    {
        if ($this->isInPool($obj) || !$obj instanceof PoolItemInterface) {//判断对象是否是该pool里
            return false;
        }
        $cid = Coroutine::getCid();//获取当前协程id
        if (isset($this->context[$cid]) && $this->context[$cid]->getObjHashInfo() === $obj->getObjHashInfo()) {
            unset($this->context[$cid]);
        }
        $hash = $obj->getObjHashInfo();
        unset($this->objHashList[$hash]);
        unset($this->inUseObject[$hash]);
        if ($obj instanceof PoolCheckInterface) {
            try {
                $obj->gc();
            } catch (\Throwable $throwable) {
                throw $throwable;
            } finally {
                $this->createdNumber--;
            }
        } else {
            $this->createdNumber--;
        }
        return true;
    }

    /**
     * 是否在pool里
     * @param PoolItemInterface $obj
     * @return bool
     */
    public function isInPool(PoolItemInterface $obj): bool
    {
        if ($this->isPoolObject($obj)) {
            return $this->objHashList[$obj->getObjHashInfo()];
        } else {
            return false;
        }
    }

    /**
     * 是否是pool对象
     * @param $obj
     * @return bool
     */
    public function isPoolObject(PoolItemInterface $obj): bool
    {
        if (!empty($obj->getObjHashInfo())) {
            return isset($this->objHashList[$obj->getObjHashInfo()]);
        } else {
            return false;
        }
    }

    /**
     * 检查函数，如果有需要请重写该函数
     * @param PoolItemInterface|null $item
     * @return bool
     */
    protected function itemIntervalCheck(?PoolItemInterface $item): bool
    {
        return true;
    }

    /**
     * 保持最小连接数
     * @param int|null $num
     * @throws \Throwable
     */
    public function keepMin(?int $num = null): int
    {
        $currentAdd = 0;
        if (is_null($num)) {
            $num = $this->conf->getMinObjectNum();
        }
        if ($this->createdNumber >= $num) {
            return $currentAdd;
        }
        $left = $num - $this->createdNumber;
        while ($left--) {
            if (!$this->initializeObj()) {
                break;
            }
            $currentAdd++;
        }
        return $currentAdd;
    }

    /**
     * 初始化
     * @throws \Exception
     * @throws \Throwable
     */
    private function initializeObj(): bool
    {
        if ($this->destroy) {
            return false;
        }
        $this->init();
        if ($this->createdNumber >= $this->conf->getMaxObjectNum()) {
            return false;
        }
        $this->createdNumber++;
        try {
            $obj = $this->createObject();
            if (!$obj instanceof PoolItemInterface) {
                throw new \Exception('create class error');
            }
            $hash = HelperFun::character(12);
            $this->objHashList[$hash] = true;
            $obj->setObjHashInfo($hash);
            $obj->setLastUseTime(time());
            $this->channel->push($obj);
            return true;
        } catch (\Throwable $throwable) {
            $this->createdNumber--;
            return false;
        }
    }

    /**
     * 平均值处理
     */
    private function loadAverageFun(): \Closure
    {
        return function () {
            if ($this->loadAverageTimerRun) {
                return;
            }
            $this->loadAverageTimerRun = true;
            $loadWaitTime = $this->loadWaitTime;
            $loadUseNum = $this->loadUserNum;
            $this->loadWaitTime = 0;
            $this->loadUserNum = 0;
            if ($loadUseNum <= 0) { //避免分母为0
                $loadUseNum = 1;
            }
            $average = $loadWaitTime / $loadUseNum;
            if ($this->conf->getLoadAverageTime() <= $average) {
                $this->loadAverageTimerRun = false;
                return;
            }
            $nowTime = time();
            $leakNum = ($nowTime - $this->getObjTime) * $this->conf->getMinObjectNum();
            $water = $this->frequencyGetObjNum - $leakNum;
            $water = $water < 0 ? 0 : $water;
            $this->frequencyGetObjNum = 0;
            $this->getObjTime = $nowTime;
            $decNum = intval($this->createdNumber * 0.3);//负载小尝试释放30%
            $keepNumber = $this->createdNumber - $decNum;
            if ($water > $keepNumber) {
                $this->loadAverageTimerRun = false;
                return;
            }
            if ($keepNumber <= $this->conf->getMinObjectNum()) {
                $this->loadAverageTimerRun = false;
                return;
            }
            while ($decNum--) {
                try {
                    $temp = $this->getObj(0.001, 0);
                    if ($temp) {
                        $this->unsetObj($temp);
                    }
                } catch (\Throwable | \Exception $e) {
                    echo "pool timer error ->" . $e->getMessage() . "\n";
                }
            }
            $this->frequencyGetObjNum = 0;
            $this->getObjTime = $nowTime;
            $this->loadAverageTimerRun = false;
        };
    }

    /**
     * @param float|null $timeout
     * @param int $tryTimes
     * @throws \Exception
     * @throws \Throwable
     */
    public function getObj(?float $timeout = null, int $tryTimes = 3): ?object
    {
        if ($this->destroy) {
            return null;
        }
        $this->init();
        if (is_null($timeout)) {
            $timeout = $this->conf->getGetObjectTimeout();
        }
        $this->frequencyGetObjNum++;
        $this->getObjTime = time();
        if ($this->channel->isEmpty()) {
            try {
                $bool = $this->initializeObj();
                if ($bool === false) {
                    if ($tryTimes <= 0) {
                        throw new \Exception("initObject fail after 3 times");
                    } else {
                        $tryTimes--;
                        return $this->getObj($timeout, $tryTimes);
                    }
                }
            } catch (\Throwable $throwable) {
                if ($tryTimes <= 0) {
                    throw  new \Exception("initObject fail after 3 times case " . $throwable->getMessage());
                } else {
                    $tryTimes--;
                    return $this->getObj($timeout, $tryTimes);
                }
            }
        }
        $start = microtime(true);
        $object = $this->channel->pop($timeout);
        $take = microtime(true) - $start;
        $this->loadWaitTime += $take;
        if (!$object instanceof PoolItemInterface) {
            return null;
        }
        $hash = $object->getObjHashInfo();
        $this->objHashList[$hash] = false;
        $this->inUseObject[$hash] = $object;
        $object->setLastUseTime(time());
        if ($object instanceof PoolCheckInterface) {
            try {
                if ($object->beforeUse() === false) {
                    $this->unsetObj($object);
                    if ($tryTimes <= 0) {
                        return null;
                    } else {
                        $tryTimes--;
                        return $this->getObj($timeout, $tryTimes);
                    }
                }
            } catch (\Throwable $throwable) {
                $this->unsetObj($object);
                if ($tryTimes <= 0) {
                    throw $throwable;
                } else {
                    $tryTimes--;
                    return $this->getObj($timeout, $tryTimes);
                }
            }
        }
        $this->loadUserNum++;
        return $object;
    }

    /**
     * 回收对象
     * @param PoolItemInterface $obj
     * @return bool
     * @throws \Throwable
     */
    public function recycleObj(PoolItemInterface $obj,bool $deferRun = false): bool
    {
        if (!$deferRun && !$obj->recoverable()) {
            return false;
        }
        if ($this->destroy) {
            $this->unsetObj($obj);
            return true;
        }
        $this->init();
        if (!$this->isPoolObject($obj) || $this->isInPool($obj)) {
            return false;
        }
        $cid = Coroutine::getCid();
        if (isset($this->context[$cid]) && $this->context[$cid]->getObjHashInfo() === $obj->getObjHashInfo()) {
            $this->context[$cid] = null;
            unset($this->context[$cid]);
        }
        $hash = $obj->getObjHashInfo();
        $this->objHashList[$hash] = true;
        unset($this->inUseObject[$hash]);
        if ($obj instanceof PoolCheckInterface) {
            try {
                $obj->objectRestore();
            } catch (\Throwable $throwable) {
                $this->objHashList[$hash] = false;
                $this->unsetObj($obj);
                return false;
            }
        }
        $this->channel->push($obj);
        return true;
    }

    /**
     * 销毁pool实例
     * @throws \Exception
     * @throws \Throwable
     */
    public function destroy(): void
    {
        $this->destroy = true;
        $this->init();
        if ($this->intervalCheckTimerId && Timer::exists($this->intervalCheckTimerId)) {
            Timer::clear($this->intervalCheckTimerId);
            $this->intervalCheckTimerId = null;
        }
        if ($this->loadAverageTimerId && Timer::exists($this->loadAverageTimerId)) {
            Timer::clear($this->loadAverageTimerId);
            $this->loadAverageTimerId = null;
        }
        if (!is_null($this->channel)) {
            if (SwlBase::inCoroutine()) {
                while (!$this->channel->isEmpty()) {
                    $item = $this->channel->pop(0.01);
                    $this->unsetObj($item);
                }
                foreach ($this->inUseObject as $item) {
                    $this->unsetObj($item);
                }
            }
            $this->inUseObject = [];
            $this->channel->close();
            $this->channel = null;
        }
    }


    /**
     * 标记带回收的实例
     * @throws \Throwable
     */
    public function defer(?float $timeout = 0.3): ?object
    {
        $cid = Coroutine::getCid();
        if (isset($this->context[$cid])) {
            return $this->context[$cid];
        }
        $obj = $this->getObj($timeout);
        if ($obj) {
            $this->context[$cid] = $obj;
            if (!isset($this->deferStatusList[$cid]) || $this->deferStatusList[$cid] < 1) {
                $this->deferStatusList[$cid] = 1;
                Coroutine::defer(function () use ($cid) {
                    $this->deferStatusList[$cid] = 0;
                    unset($this->deferStatusList[$cid]);
                    if (isset($this->context[$cid]) && !empty($this->context[$cid])) {
                        $obj = $this->context[$cid];
                        unset($this->context[$cid]);
                        try{
                            $flag = $this->recycleObj($obj,true);
                            if (!$flag && $this->createdNumber > 0) {
                                $this->unsetObj($obj);
                            }
                        }catch (\Exception | \Throwable | \Error $e) {
                            $this->unsetObj($obj);
                        }
                    }
                });
            }
            return $this->defer($timeout);
        } else {
            throw new \Exception("pool is empty");
        }
    }

    /**
     * @throws \Throwable
     */
    public function reset(): self
    {
        $this->destroy();
        $this->createdNumber = 0;
        $this->destroy = false;
        $this->context = [];
        $this->objHashList = [];
        return $this;
    }
}