<?php
declare(strict_types=1);

namespace work;

use Swoole\Coroutine;
use work\cor\ManageVariable;

class CoLifeVariable
{
    private static ?ManageVariable $manage = null;

    /**
     * 获取实例
     */
    public static function getManageVariable(?int $cid = null): ManageVariable
    {
        if (IS_SWOOLE_SERVER && SwlBase::inCoroutine()) { //如果处于协程内
            return static::getSwlVar($cid);
        }
        return static::getVar();
    }

    /**
     * 协程方式
     * @param int|null $cid
     * @return ManageVariable
     */
    protected static function getSwlVar(?int $cid = null): ManageVariable
    {
        if (is_null($cid) || $cid < 1) {
            $context = Coroutine::getContext();
        } else {
            $context = Coroutine::getContext($cid);
        }
        if (!property_exists($context, 'manageVariable')) {
            $context->manageVariable = new ManageVariable();
            $context->manageVariable->set('coLiftCidNumber', is_numeric($cid) ? $cid : Coroutine::getCid());
        }
        $cidNum = $context->manageVariable->get('coLiftCidNumber');
        $getCid = is_null($cid) ? $cidNum : $cid;
        if ($getCid != -1 && $getCid !== Coroutine::getCid()) {
            throw new \Exception("$getCid var is error" . Coroutine::getCid());
        }
        return $context->manageVariable;
    }

    /**
     * 非协程方式
     */
    protected static function getVar(): ManageVariable
    {
        if (is_null(static::$manage)) {
            static::$manage = new ManageVariable();
        }
        return static::$manage;
    }

    /**
     * 销毁
     */
    public static function flush(?int $cid = null): void
    {
        static::$manage = null;
        if (IS_SWOOLE_SERVER && SwlBase::inCoroutine()) {
            if ($cid === null) {
                $context = Coroutine::getContext();
            } else {
                $context = Coroutine::getContext($cid);
            }
            $context->manageVariable = null;
        }
    }


}