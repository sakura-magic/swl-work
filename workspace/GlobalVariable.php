<?php
declare(strict_types=1);

namespace work;

use Swoole\Coroutine;
use work\cor\ManageVariable;

class GlobalVariable
{
    /**
     * @var array
     */
    private static array $variableList = [];

    private const  PLIE = 6;

    private const NOT_CLEAN_KEY = ['_sys_'];

    private const SET_KEYS = ['_sys_', '_temp_'];

    /**
     * 获取变量管理实例
     * @param string $key
     * @return ManageVariable
     */
    public static function getManageVariable(string $key = ''): ManageVariable
    {
        if (!(preg_match('/^cor_[0-9]{1,32}$/', $key) || in_array($key, self::SET_KEYS))) {
            throw new \Exception("key not in accordance with the rules");
        }
        if (!isset(self::$variableList[$key])) {
            if (!in_array($key, self::SET_KEYS)) {
                $arr = explode('_', $key);
                $cid = intval(end($arr));
                if (!SwlBase::coroutineExists($cid)) {
                    throw new \Exception("Coroutines do not exist");
                }
            }
            self::$variableList[$key] = new ManageVariable(self::PLIE);
        }
        return self::$variableList[$key];
    }

    /**
     * 协程隔离方式获取
     * @return ManageVariable
     */
    public static function corGet(): ManageVariable
    {
        $key = '_temp_';
        if (IS_SWOOLE_SERVER && SwlBase::inCoroutine()) {
            $key = 'cor_' . SwlBase::getCoroutineId(); //挂载父级协程
        }
        return self::getManageVariable($key);
    }

    /**
     * 清掉当前协程
     */
    public static function cleanCorManage(): bool
    {
        $key = '_temp_';
        if (IS_SWOOLE_SERVER && SwlBase::inCoroutine()) {
            $cid = SwlBase::getCoroutineId();
            if ($cid !== Coroutine::getCid()) {
                return false;
            }
            $key = 'cor_' . $cid;
        }
        return self::cleanManage($key);
    }

    /**
     * 清掉管理实例
     * @param string $key
     */
    public static function cleanManage(string $key): bool
    {
        if (in_array($key, self::NOT_CLEAN_KEY, true)) {
            return false;
        }
        if (isset(self::$variableList[$key])) {
            unset(self::$variableList[$key]);
        }
        return true;
    }
}