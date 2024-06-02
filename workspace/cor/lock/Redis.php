<?php
namespace work\cor\lock;
use work\cor\RedisQuery;
use work\HelperFun;

class Redis implements LockInterface
{

    private RedisQuery $redis;

    private array $lockPool = [];

    public function __construct()
    {
        $this->redis = new RedisQuery();
    }

    /**
     * @param string $key
     * @param int $expire
     * @param string|null $val
     * @return bool
     */
    public function lock(string $key, int $expire = 3,string $val = null): bool
    {
        if (empty($val)) {
            $val = md5(time() . HelperFun::character(16));
        }
        $res = $this->redis->getLock($key,$expire,$val);
        if ($res) {
            $this->lockPool[$key] = $val;
        }
        return $res;
    }

    /**
     * @param string $key
     * @return bool
     * @throws \RedisClusterException
     */
    public function unlock(string $key): bool
    {
        $data = $this->redis->getLockVal($key);
        if (!$data) {
            return false;
        }
        if ($this->lockPool[$key] !== $data) {
            return false;
        }
        unset($this->lockPool[$key]);
        return $this->redis->delLock($key);
    }

    /**
     * @param string $key
     * @return string|null
     * @throws \RedisClusterException
     */
    public function getNowLockVal(string $key): ?string
    {
        $data = $this->redis->getLockVal($key,false);
        if (empty($data)) {
            return null;
        }
        return $data;
    }
}