<?php
declare(strict_types=1);
namespace box;
use server\other\ServerTool;
use work\Config;
use work\cor\FileGc;
use work\GlobalVariable;
use work\SwlBase;

abstract class LiftMethod
{
    /**
     * fpm运行完成，要处理的后续操作
     */
    public static function fpmDone()
    {
        try {
            if (!IS_CLI) {
                $rand = mt_rand(1, 100);
                if ($rand <= 30) { //30%的概率
                    $confPatch = (string)Config::getInstance()->get('session.sessionDir', ROOT_PATH . DS . 'logs' . DS . 'session');
                    $life = Config::getInstance()->get('session.sessionLife',86400);
                    $life = is_numeric($life) ? intval($life) : 0;
                    $life = max($life,3600);
                    $gcObj = new FileGc($confPatch,$life);
                    $gcObj->gc();
                }
            }
        }catch (\Throwable | \Exception $e) {

        }
    }


    /**
     * 检查是否可允许
     */
    public static function checkRunOk(): bool
    {
        if (!IS_SWOOLE_SERVER || !SwlBase::inCoroutine()) {
            return true;
        }
        $isStartOk = GlobalVariable::getManageVariable('_sys_')->get('workerStartDone');
        if (!$isStartOk) {
            return false;
        }
        $coroutineNum = ServerTool::getServer()->getServerConfig("server.setConfig.max_coroutine", false);
        if ($coroutineNum == false) {
            $coroutineNum = ServerTool::getServer()->getServerConfig("server.setConfig.max_coro_num",1024);
        }
        $coroutineNum = intval(round($coroutineNum * 0.8));
        if (SwlBase::getRunCoroutineNum() >= $coroutineNum) {
            return false;
        }
        return true;
    }
}