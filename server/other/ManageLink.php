<?php
declare(strict_types=1);

namespace server\other;

use work\cor\PdoQuery;
use work\cor\RedisQuery;

class ManageLink
{
    //pdo
    private ?PdoQuery $pdo = null;
    //redis
    private ?RedisQuery $redis = null;

    /**
     * 获取pdo
     * @return PdoQuery|null
     */
    public function getPdo(): ?PdoQuery
    {
        if (!$this->pdo instanceof PdoQuery) {
            $this->pdo = new PdoQuery();
        }
        return $this->pdo;
    }

    /**
     * 获取redis
     */
    public function getRedis(): ?RedisQuery
    {
        if (!$this->redis instanceof RedisQuery) {
            $this->redis = new RedisQuery();
        }
        return $this->redis;
    }

    /**
     * 释放连接
     */
    public function free()
    {
        $this->redis = null;
        $this->pdo = null;
    }
}