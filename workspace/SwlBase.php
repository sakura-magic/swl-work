<?php
declare(strict_types=1);

namespace work;

use Swoole\Coroutine;
use work\traits\StaticManage;

class SwlBase
{

    use StaticManage;

    /**
     * @var array
     */
    protected static array $container = [];

    /**
     * 添加信息
     * @param $key
     * @param $id
     */
    public static function add($key, $id): void
    {
        self::$container[$key][] = $id;
    }

    /**
     * 清除信息
     * @param $key
     */
    public static function clear($key): void
    {
        unset(self::$container[$key]);
    }

    /**
     * 协程锁
     * @param $key
     * @return bool
     */
    public static function lock($key,bool $demise = false): bool
    {
        if (!self::has($key)) {
            self::add($key, 0);
            return true;
        }
        if (!$demise) {
            return false;
        }
        self::add($key, Coroutine::getCid());
        Coroutine::suspend();
        return false;
    }

    /**
     * 解除冻结的协程
     * @param $key
     */
    public static function unlock($key): void
    {
        if (self::has($key)) {
            $ids = self::get($key);
            foreach ($ids as $id) {
                if ($id > 0 && Coroutine::exists($id)) {
                    Coroutine::resume($id);
                }
            }
            self::clear($key);
        }
    }

    /**
     * 是否处于协程
     * @return bool
     */
    public static function inCoroutine(): bool
    {
        return class_exists('\Swoole\Coroutine') && Coroutine::getCid() > 0;
    }

    /**
     * 协程是否存在
     * @param int $cid
     * @return bool
     */
    public static function coroutineExists(int $cid): bool
    {
        return Coroutine::exists($cid);
    }


    /**
     * 获取顶级id
     * @param $cid
     * @return false|int
     */
    public static function getCoroutineId($cid = null)
    {
        if ($cid === false) {
            return false;
        }
        if ($cid === null) {
            $cid = Coroutine::getCid();
        }
        $pCid = Coroutine::getPcid($cid);
        if ($pCid < 0) {
            return $cid;
        }
        return self::getCoroutineId($pCid);
    }

    /**
     * 判断父协程是否存在
     * @return bool
     */
    public static function parentCoroutineExist(): bool
    {
        if (!self::inCoroutine()) {
            return false;
        }
        $pCid = Coroutine::getPcid();
        if ($pCid === -1) {
            return false;
        }
        return true;
    }

    /**
     * 获取运行协程数量
     */
    public static function getRunCoroutineNum(): int
    {
       $info = Coroutine::stats();
       return intval($info['coroutine_num'] ?? 0);
    }
}
