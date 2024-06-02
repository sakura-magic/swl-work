<?php
declare(strict_types=1);

namespace box\event\http;

use box\LiftMethod;
use work\container\BoundMethod;
use work\cor\anomaly\HttpResponseDie;
use work\cor\basics\face\RequestManageInterface;
use work\cor\basics\face\ResponseManageInterface;
use \work\cor\facade\Request as WorkRequest;
use \work\cor\facade\Response as WorkResponse;
use work\cor\PipeLine;
use work\fusing\FusingFace;
use work\HelperFun;
use work\Hook;
use work\Route;

class Request
{
    /**
     * 熔断器
     * @var FusingFace|null
     */
    private ?FusingFace $fuse = null;
    /**
     * 入口调用
     * @return int
     * @throws \Exception
     */
    public function access(RequestManageInterface $request, ResponseManageInterface $response): int
    {
        $isStartOk = LiftMethod::checkRunOk();
        if (!$isStartOk) {
            $response->status(500);
            $response->end("server error");
            return -1;
        }
        $hookRunResult = Hook::getInstance('http')->runHook('boxRequest', [$request, $response]);
        if ($hookRunResult === HOOK_RESULT_INFO['skipRun']) {
            return -9;
        }
        HelperFun::getContainer()->bind(RequestManageInterface::class, fn() => $request);
        HelperFun::getContainer()->bind(ResponseManageInterface::class, fn() => $response);
        $routeParse = Route::getInstance()->parseUrl(WorkRequest::initCreate(), WorkRequest::getRouteUri());
        if (!isset($routeParse['flag']) || !$routeParse['flag']) {
            WorkResponse::status($routeParse['status'] ?? HTTP_SERVER_ERROR["routeNotFound"]["code"]);
            WorkResponse::sendStr($routeParse['content'] ?? HTTP_SERVER_ERROR["routeNotFound"]["msg"]);
            return -1;
        }
        $hookRunResult = Hook::getInstance('http')->runHook('boxRequestRouteParseSuccess',[WorkRequest::initCreate(),WorkResponse::initCreate()]);
        if ($hookRunResult === HOOK_RESULT_INFO['skipRun']) {
            return -9;
        }
        try {
            $flag = true;
            $data = null;
            if (isset($routeParse['verifier']) && isset($routeParse['verifier']['rule']) && is_array($routeParse['verifier']['rule'])) {
                $verifier = \work\cor\facade\Verifier::initCreate();
                $argument = array_merge(WorkRequest::param(), $routeParse['argument']);
                $argument = array_filter($argument, function ($val) {
                    return !is_null($val);
                });
                $flag = $verifier->rule($routeParse['verifier']['rule'])->check($argument);
                if (!$flag) {
                    $extendArr = $routeParse['verifier']['extend'] ?? [];
                    foreach ($extendArr as $key => $val) {
                        if (is_string($val) && preg_match('/\{\$err}/', $val)) {
                            $extendArr[$key] = preg_replace('/\{\$err}/', $verifier->getLastError(), $val);
                        }
                    }
                    $data = [
                        'mode' => $routeParse['verifier']['mode'] ?? 'json',
                        'status' => $routeParse['verifier']['status'] ?? 200,
                        'data' => $extendArr
                    ];
                }
            }
            if ($flag) {
                if (isset($routeParse['fusing']) && $routeParse['fusing'] instanceof FusingFace) {
                    $this->fuse = $routeParse['fusing'];
                    if (!$this->fuse->allowRequest()) { //发生熔断
                        $routeParse['class'] = $routeParse['fusingInfo']['class'] ?? '';
                        $routeParse['method'] = $routeParse['fusingInfo']['method'] ?? '';
                        $this->fuse = null; //关闭引用
                    }
                }
                $data = (new PipeLine())
                    ->send(WorkRequest::initCreate())
                    ->through($routeParse['middleware'])
                    ->then(function () use ($routeParse) {
                        return BoundMethod::resolveMethod(HelperFun::getContainer(), "{$routeParse['class']}@{$routeParse['method']}", $routeParse['argument']);
                    });
            }
            if ($data !== null || $data !== false) {
                if (is_string($data) || is_numeric($data)) {
                    WorkResponse::sendStr($data);
                } else if (is_array($data) && isset($data['mode']) && $data['mode'] == 'json' && isset($data['data'])) {
                    WorkResponse::status($data['status'] ?? 200);
                    WorkResponse::sendJson(is_array($data['data']) ? $data['data'] : []);
                } else if (is_array($data)) {
                    WorkResponse::sendJson($data);
                }
            }
        } catch (HttpResponseDie $e) {
            return -2;
        }
        return 0;
    }

    /**
     * 出现错误
     */
    public function error(\Throwable $throwable)
    {
        if ($this->fuse instanceof FusingFace) {
            $this->fuse->recordFailure();
        }
    }

    /**
     * 完成回调
     */
    public function done()
    {
        if ($this->fuse instanceof FusingFace) {
            $this->fuse->recordSuccess();
        }
    }
}