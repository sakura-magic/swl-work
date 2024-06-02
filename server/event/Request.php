<?php
declare(strict_types=1);

namespace server\event;

use work\Config;
use Swoole\Http\Response;
use Swoole\Http\Request as SwRequest;
use work\cor\basics\RequestSwl;
use work\cor\basics\ResponseSwl;
use work\cor\facade\Log;
use work\HelperFun;
use work\Hook;

final class Request
{
    /**
     * 入口调用方法
     * @return void
     */
    public function access(SwRequest $request, Response $response)
    {
        $hookRunResult = Hook::getInstance('http')->runHook('request', [$request, $response]);
        if ($hookRunResult === HOOK_RESULT_INFO['skipRun']) {
            return;
        }
        if ($hookRunResult !== HOOK_RESULT_INFO['skipHttpIcoCheck']) {
            if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
                $response->status(HTTP_SERVER_ERROR["requestIco"]["code"] ?? 404);
                $response->end(HTTP_SERVER_ERROR["requestIco"]["msg"] ?? '404 not found');
                return;
            }
            if (empty($request->server['request_uri'])) {
                $response->status(HTTP_SERVER_ERROR["emptyUri"]["code"] ?? 404);
                $response->end(HTTP_SERVER_ERROR["emptyUri"]["msg"] ?? 'uri is empty');
                return;
            }
        }
        $requestSwl = new RequestSwl($request);
        $responseSwl = new ResponseSwl($response);
        $obj = null;
        try {
            $obj = new \box\event\http\Request();
            $obj->access($requestSwl, $responseSwl);
            if (method_exists($obj,'done')) {
                $obj->done();
            }
            unset($obj);
        } catch (\Exception | \PDOException | \RedisException | \Error | \ErrorException | \TypeError | \ParseError | \Throwable $throwable) {
            $hookRunResult = Hook::getInstance('http')->runHook('requestError', [$throwable, $request, $response]);
            if ($hookRunResult !== HOOK_RESULT_INFO['skipRun']) {
                $errorInfo = HelperFun::outErrorInfo($throwable);
                Log::error("http request error : \n" . $errorInfo);
                $responseSwl->status(HTTP_SERVER_ERROR["logicError"]["code"] ?? 500);
                if (Config::getInstance()->get('other.debug')) {
                    $errorInfo = HelperFun::outErrorInfo($throwable, "<br/>");
                    $responseSwl->end($errorInfo);
                } else {
                    $responseSwl->end(HTTP_SERVER_ERROR["logicError"]["msg"] ?? 'The system is busy,please try again later');
                }
            }
            if ($obj instanceof \box\event\http\Request && method_exists($obj,'error')) {
                try {
                    $obj->error($throwable);
                }catch (\Throwable | \Error $e) {
                    Log::error("http request error 2: \n" . var_export($e,true));
                }
            }
        } finally {
            Hook::getInstance('http')->runHook('requestFinally');
            HelperFun::flushCo();
        }
    }
}