<?php
declare(strict_types=1);

namespace work\traits;
trait SingleObject
{
    protected static ?self $instanceObj = null;

    /**
     * @return static
     * @throws \Throwable
     */
    public static function getInstanceObj(): self
    {
        if (self::$instanceObj === null) {
            self::$instanceObj = new self();
            self::$instanceObj->initAfter();
        }
        return self::$instanceObj;
    }

    /**
     * 实例化后调用
     */
    public function initAfter()
    {
    }

    /**
     * 清实例
     */
    public function unsetBefore()
    {
    }

    /**
     * 销毁实例
     * @return bool
     */
    public static function unsetObj(): bool
    {
        if (self::$instanceObj === null) {
            return true;
        }
        self::$instanceObj->unsetBefore();
        self::$instanceObj = null;
        return false;
    }
}