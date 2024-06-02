<?php
declare(strict_types=1);

namespace work\cor\redis;

use server\other\Console;
use work\cor\anomaly\RedisCustomException;
use work\pool\PoolItemInterface;
use work\pool\PoolManager;

class RedisPoolObj extends PoolManager
{
    private array $conf = [];

    /**
     * 设置config信息,为了防止后续更改配置与之前不同导致不可以控的因素，禁止二次改配置
     */
    public function setConfigInfo(array $config = []): bool
    {
        if (empty($config) || !empty($this->conf)) {
            return false;
        }
        $field = ["host", "port", "auth", "index"];//必填字段
        foreach ($field as $value) {
            if (!isset($config[$value])) {
                return false;
            }
        }
        foreach ($field as $value) {
            $this->conf[$value] = $config[$value];
        }
        return true;
    }

    /**
     * redisLink
     * @return object|null
     */
    protected function createObject(): ?object
    {
        if (empty($this->conf)) {
            return null;
        }
        try {
            return (new RedisLink($this->conf));
        } catch (RedisCustomException | \RedisException $e) {
            Console::dump(['redis connect error', $e->getMessage()], -9998);
        }
        return null;
    }

    /**
     * 获取link
     * @throws \Throwable
     */
    public function getLink(float $timeout = 1.5): ?RedisLink
    {
        $redisCon = $this->defer($timeout);;
        if ($redisCon instanceof RedisLink) {
            return $redisCon;
        }
        return null;
    }

    /**
     * 主动回收
     * @throws \Throwable
     */
    public function pushLink(PoolItemInterface $item): bool
    {
        return $this->recycleObj($item);
    }

    /**
     * 连接检测
     * @param PoolItemInterface|null $item
     * @return bool
     */
    protected function itemIntervalCheck(?PoolItemInterface $item): bool
    {
        if (!$item instanceof RedisLink) {
            return false;
        }
        return $item->ping();
    }

}