<?php
declare(strict_types=1);

namespace work\pool;
class Conf
{
    protected int $maxObjectNum = 20; //最大连接数
    protected int $minObjectNum = 5;//最小链接数
    protected int $intervalCheckTime = 120 * 1000;//间隔检查时间
    protected int $loadAverageCheckTime = 80 * 1000;//
    protected int $maxIdleTime = 96;//闲置最大回收时间
    protected float $loadAverageTime = 0.001; //读取平均耗时
    protected float $getObjectTimeout = 3.0;//过期时间

    /**
     * 参数配置
     * Conf constructor.
     * @param array $configData
     */
    public function __construct(array $configData = [])
    {
        foreach ($configData as $key => $val) {
            if (property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }
    }

    //获取最小连接数
    public function getMinObjectNum(): int
    {
        return $this->minObjectNum;
    }

    //获取最大连接数
    public function getMaxObjectNum(): int
    {
        return $this->maxObjectNum;
    }

    //获取间隔检查时间
    public function getIntervalCheckTime(): int
    {
        return $this->intervalCheckTime;
    }

    //获取闲置最大回收时间
    public function getMaxIdleTime(): int
    {
        return $this->maxIdleTime;
    }

    public function getLoadAverageTime(): float
    {
        return $this->loadAverageTime;
    }

    public function getLoadAverageCheckTime(): int
    {
        return $this->loadAverageCheckTime;
    }

    //获取对象超时时间
    public function getGetObjectTimeout(): float
    {
        return $this->getObjectTimeout;
    }
}