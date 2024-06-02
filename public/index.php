<?php
declare(strict_types=1);

use work\Config;
use work\cor\facade\Log;
use work\GlobalVariable;
use work\HelperFun;

define("ROOT_PATH", dirname(__DIR__));
const DS = DIRECTORY_SEPARATOR;
const IS_CLI = PHP_SAPI == 'cli';
require_once '../vendor/autoload.php';
if (IS_CLI) {
    (function () {
        global $argc, $argv;
        for ($i = 1; $i < $argc; $i++) {
            if (empty($argv[$i]) || strpos($argv[$i], '=') <= 0) {
                continue;
            }
            [$k, $v] = explode("=", $argv[$i]);
            $_GET[$k] = $v;
        }
    })();
}
HelperFun::scanFolder('load' . DS . 'start' . DS . 'fpm');
HelperFun::scanFolder('load' . DS . 'start');
$work = new \box\event\WorkerStart();
$work->loadFile();
$work->initError();
$work->showError();
$work->initRoute();

$requestFpm = new \work\cor\basics\RequestFpm();
$responseFpm = new \work\cor\basics\ResponseFpm();
$request = null;
try {
    GlobalVariable::getManageVariable('_sys_')->set('currentCourse', 'worker', true);
    $request = new \box\event\http\Request();
    $request->access($requestFpm, $responseFpm);
    if (method_exists($request,'done')) {
        $request->done();
    }
    \box\LiftMethod::fpmDone();
} catch (\ErrorException | \TypeError | \ParseError | \Error | \Exception | \Throwable $throwable) {
    $errorInfo = HelperFun::outErrorInfo($throwable);
    Log::error("http request error : \n" . $errorInfo);
    $responseFpm->status(HTTP_SERVER_ERROR["logicError"]["code"] ?? 500);
    if (Config::getInstance()->get('other.debug')) {
        $errorInfo = HelperFun::outErrorInfo($throwable, "<br>");
        $responseFpm->end($errorInfo);
    } else {
        $responseFpm->end(COMMON_MSG['sysError'] ?? 'The system is busy,please try again later');
    }
    if ($request instanceof \box\event\http\Request && method_exists($request,'error')) {
        try {
            $request->error($throwable);
        }catch (\Throwable | \Error $e) {
            Log::error("http request error 2: \n" . var_export($e,true));
        }
    }
}
exit();//进程结束
