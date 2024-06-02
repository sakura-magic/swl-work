<?php
declare(strict_types=1);

namespace work\traits;
trait SingleInstance
{
    protected static array $singlePool = [];

    protected function __construct()
    {

    }

    protected function __clone()
    {

    }

    /**
     * 获取实例
     * @param string $key
     * @return static
     */
    public static function getInstance(string $key = 'default'): self
    {
        if (!isset(self::$singlePool[$key]) || !self::$singlePool[$key] instanceof self) {
            self::$singlePool[$key] = new self();
        }
        return self::$singlePool[$key];
    }


    /**
     * 销毁实例
     * @param array $keys
     * @return void
     */
    public static function destroyKey(array $keys)
    {
        foreach ($keys as $k) {
            if (!isset(self::$singlePool[$k])) {
                continue;
            }
            self::$singlePool[$k] = null;
            unset(self::$singlePool[$k]);
        }
    }
}