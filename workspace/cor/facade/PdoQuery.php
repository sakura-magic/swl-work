<?php
declare(strict_types=1);

namespace work\cor\facade;

/**
 * @method \work\cor\PdoQuery name(string $table = '') static 设定当前的语言
 * @method \work\cor\PdoQuery table(string $table = '') static 设定当前的语言
 * @method void getParseSql() static 设定当前的语言
 * @method  getLastInsertId() static 设定当前的语言
 * @method bool beginTransaction(string $key = 'exec') static 设定当前的语言
 * @method bool commit(string $key = 'exec') static 设定当前的语言
 * @method bool rollback(string $key = 'exec') static 设定当前的语言
 * @method bool manualReturnPdo(string $key, bool $tran) static 设定当前的语言
 * @method bool manualReturnPdoAll(bool $tran = false) static 设定当前的语言
 * @method \work\cor\PdoQuery setAutoRet(bool $autoFlag) static 设定当前的语言
 */
class PdoQuery extends Facade
{
    protected static bool $instance = true;

    public static function initCreate(...$arg): ?\work\cor\PdoQuery
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
        return \work\cor\PdoQuery::class;
    }
}
