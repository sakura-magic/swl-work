<?php
namespace work\cor\lock;
interface LockInterface
{
    /**
     * 加锁
     * @param string $key
     * @param int $expire
     * @return mixed
     */
    public function lock(string $key,int $expire = 3,string $val = null):bool;

    /**
     * 解锁
     * @param string $key
     * @return mixed
     */
    public function unlock(string $key):bool;

    /**
     * @param string $key
     */
    public function getNowLockVal(string $key) : ?string;
}