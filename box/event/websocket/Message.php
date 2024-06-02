<?php
declare(strict_types=1);

namespace box\event\websocket;

use box\LiftMethod;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use work\container\BoundMethod;
use work\cor\facade\Verifier;
use work\cor\facade\Wsl;
use work\cor\PipeLine;
use work\fusing\FusingFace;
use work\HelperFun;
use work\Route;

class Message
{
    /**
     * 熔断器
     * @var FusingFace|null
     */
    private ?FusingFace $fuse = null;

    public function access(Server $server, Frame $frame): int
    {
        $wsData = Wsl::jsonUnpackData();
        if ($wsData === null) {
            return -1;
        }
        $isStartOk = LiftMethod::checkRunOk();
        if (!$isStartOk) {
            $server->push($frame->fd,Wsl::jsonPackData('system_error', -500, "server error", ["route" => $wsData["route"]]));
            return -1;
        }
        if (empty($wsData['route'])) {
            Wsl::pushSelf(Wsl::jsonPackData('system_error', -101, "route not exist"));
            return -2;
        }
        $param = isset($wsData['param']) && is_array($wsData['param']) ? $wsData['param'] : [];
        $routeParse = Route::getInstance()->parseWsUrl($wsData['route'], $param);
        if (!isset($routeParse['flag']) || !$routeParse['flag']) {
            Wsl::pushSelf(Wsl::jsonPackData('system_error', -404, "404 not found", ["route" => $wsData["route"]]));
            return -3;
        }
        $flag = true;
        $data = null;
        if (isset($routeParse['verifier']) && isset($routeParse['verifier']['rule']) && is_array($routeParse['verifier']['rule'])) {
            $verifier = Verifier::initCreate();
            $argument = $routeParse['argument'];
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
                    'code' => $extendArr['code'] ?? -9001,
                    'msg' => $extendArr['msg'] ?? "参数错误",
                    'data' => $extendArr['data'] ?? [],
                    'event' => $routeParse['verifier']['event'] ?? $routeParse['uri']
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
                ->send(Wsl::initCreate())
                ->through($routeParse['middleware'])
                ->then(function () use ($routeParse) {
                    return BoundMethod::resolveMethod(HelperFun::getContainer(), "{$routeParse['class']}@{$routeParse['method']}", $routeParse['argument']);
                });
        }
        if ($data === null | $data === false) {
            return 0;
        }
        if (is_string($data)) {
            Wsl::pushSelf($data);
            return 1;
        }
        $data["mode"] = $data["mode"] ?? "json";
        $data["data"] = $data["data"] ?? [];
        if (!$this->checkResultRule($data)) {
            Wsl::pushSelf(Wsl::jsonPackData('system_error', -501, "系统繁忙，请稍后再试"));
            return 2;
        }
        if (isset($data["fd"])) {
            if (is_array($data["fd"])) {
                Wsl::includePush($data["fd"], Wsl::jsonPackData($data['event'] ?? $wsData['route'], $data["code"], $data['msg'], $data['data']));
            } else {
                Wsl::push($data["fd"], Wsl::jsonPackData($data['event'] ?? $wsData['route'], $data["code"], $data['msg'], $data['data']));
            }
            return 3;
        }
        Wsl::pushSelf(Wsl::jsonPackData($data['event'] ?? $wsData['route'], $data["code"], $data['msg'], $data['data']));
        return 4;
    }

    /**
     * @return bool
     */
    public function checkResultRule(array $data): bool
    {
        $rule = ["code", "msg", "mode", "data"];
        $mode = ["json"];
        foreach ($rule as $val) {
            if (!isset($data[$val])) {
                return false;
            }
        }
        if (isset($data["fd"]) && !(is_array($data["fd"]) || (is_numeric($data["fd"]) && $data["fd"] > 0))) {
            return false;
        }
        if (!is_string($data['msg']) || !is_array($data['data'])) {
            return false;
        }
        if (!in_array($data["mode"], $mode)) {
            return false;
        }
        return true;
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