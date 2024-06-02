<?php
declare(strict_types=1);

namespace work\cor\facade;
/**
 * @method void set(string $key, $value) static 设定当前的语言
 * @method mixed get(?string $key = null) static 设定当前的语言
 * @method mixed del(string $key) static 设定当前的语言
 * @method mixed cleanSession() static 设定当前的语言
 */
class Session extends Facade
{

    protected static bool $instance = true;

    public static function initCreate(...$arg): ?\work\cor\Session
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
        return \work\cor\Session::class;
    }
}