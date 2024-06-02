<?php
declare(strict_types=1);

namespace work\cor\pdo;

use server\other\Console;
use work\cor\anomaly\PdoCustomException;
use work\pool\PoolItemInterface;
use work\pool\PoolManager;

class PdoPoolObj extends PoolManager
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
        $field = ['db', 'host', 'port', 'dbName', 'userName', 'userPassword'];//必填字段
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

    protected function createObject(): ?object
    {
        if (empty($this->conf)) {
            return null;
        }
        try {
            return (new PdoLink($this->conf));
        } catch (PdoCustomException | \PDOException $e) {
            Console::dump(['pdo connect error', $e->getMessage()], -9999);
        }
        return null;
    }

    /**
     * 获取link
     * @throws \Throwable
     */
    public function getLink(float $timeout = 1.5): ?PdoLink
    {
        $pdoCon = $this->defer($timeout);;
        if ($pdoCon instanceof PdoLink) {
            return $pdoCon;
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
        if (!$item instanceof PdoLink) {
            return false;
        }
        return $item->pdoPing();
    }
}