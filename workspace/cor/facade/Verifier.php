<?php
declare(strict_types=1);

namespace work\cor\facade;
/**
 * @method \work\cor\Verifier rule(array $rule) static 设定当前的语言
 * @method string getLastError() static 设定当前的语言
 */
class Verifier extends Facade
{
    protected static bool $instance = true;

    public static function initCreate(...$arg): ?\work\cor\Verifier
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
        return \work\cor\Verifier::class;
    }
}