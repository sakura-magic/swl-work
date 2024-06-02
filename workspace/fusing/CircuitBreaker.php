<?php
declare(strict_types=1);

namespace work\fusing;

class CircuitBreaker implements FusingFace
{
    /**
     * 是否开启熔断
     * @var bool
     */
    private bool $isOpen = false;
    /**
     * 重置超时时间
     * @var int
     */
    private int $resetTimeOut = 1;
    /**
     * 失败阀值
     * @var int
     */
    private int $failThreshold = 1;//失败阈值
    /**
     * 成功阀值
     * @var int
     */
    private int $successThreshold = 1;//成功恢复阀值
    /**
     * 上次失败时间
     * @var int
     */
    private int $lastFailTime = 0;

    /**
     * 上次失败数量
     * @var int
     */
    private int $failCount = 0;

    /**
     * 成功数量
     * @var int
     */
    private int $successCount = 0;
    /**
     * 标识
     * @var string
     */
    private string $key;


    public function __construct(string $key,array $conf = [])
    {
        $this->key = $key;

        if (isset($conf['timeOut']) && is_numeric($conf['timeOut'])) {
            $this->resetTimeOut = max(intval($conf['timeOut']),$this->resetTimeOut);
        }
        if (isset($conf['failThreshold']) && is_numeric($conf['failThreshold'])) {
            $this->failThreshold = max(intval($conf['failThreshold']),$this->failThreshold);
        }
        if (isset($conf['successThreshold']) && is_numeric($conf['successThreshold'])) {
            $this->successThreshold = max(intval($conf['successThreshold']),$this->successThreshold);
        }
    }

    /**
     * 校验
     * @return bool
     */
    public function allowRequest(): bool
    {
        if ($this->isOpen) {
            $now = time();
            if ($now - $this->lastFailTime > $this->resetTimeOut) {
                return $this->halfOpen();
            }
            return false;
        }
        return true;
    }

    /**
     * 半开
     */
    private function halfOpen (): bool
    {
        $totalAttempts = $this->successCount + $this->failCount;
        $scope = 50;
        if ($this->successCount > 0) {
            $ratio = $this->successCount / $totalAttempts;
            $scope = floor($ratio * 75);
        }
        return mt_rand(1,100) <= $scope;
    }


    /**
     * 失败记录
     */
    public function recordFailure() : void
    {
        $this->failCount++;
        if ($this->failCount >= $this->failThreshold) {
            $this->open();
        }
    }

    /**
     * 开启熔断
     */
    private function open() : void
    {
        $this->isOpen = true;
        $this->successCount = 0;
        $this->failCount = 0;
        $this->lastFailTime = time();
    }

    /**
     * 成功
     */
    public function recordSuccess() : void
    {
        $this->successCount++;
        if ($this->successCount >= $this->successThreshold) {
            $this->reset();
        }
    }

    /**
     * 回复状态
     */
    private function reset()
    {
        $this->successCount = 0;
        $this->failCount = 0;
        $this->isOpen = false;
        $this->lastFailTime = 0;
    }

    /**
     * 获取设定key
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }
}