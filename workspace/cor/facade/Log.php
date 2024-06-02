<?php
declare(strict_types=1);

namespace work\cor\facade;
/**
 * @method false|int error($str) static 设定当前的语言
 * @method bool info($str) static 设定当前的语言
 * @method false|int infoWrite($str) static 设定当前的语言
 */
class Log extends Facade
{
    protected static bool $instance = true;

    public static function initCreate(...$arg): ?\work\cor\Log
    {
        return static::createFacade(null, $arg);
    }

    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass(): ?string
    {
        return \work\cor\Log::class;
    }
}