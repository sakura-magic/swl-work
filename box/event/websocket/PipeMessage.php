<?php
declare(strict_types=1);

namespace box\event\websocket;

use box\LiftMethod;
use Swoole\websocket\Server;
use work\cor\Log;
use work\HelperFun;

class PipeMessage
{
    //入口
    public function access(Server $server, $src_worker_id, $data)
    {
        $isStartOk = LiftMethod::checkRunOk();
        if (!$isStartOk) {
            //todo wokerStart启动未完成
            return -1;
        }
        HelperFun::getContainer()->bind(get_class($server), fn() => $server);
        $parseJson = json_decode($data, true);
        if ($parseJson === false) {
            return -1;
        }
        if (empty($parseJson['method']) || !method_exists($this, $parseJson['method'])) {
            return -2;
        }
        (new Log())->info("接受到消息:" . var_export($parseJson, true));
        return $this->{$parseJson['method']}($server, $src_worker_id, $parseJson);
    }

    /**
     * 直接发送消息
     * @return int
     */
    public function sendMessage(Server $server, $src_worker_id, $data): int
    {
        if (empty($data["fd"]) || !(is_numeric($data['fd']) || is_array($data["fd"]))) {
            return -11;
        }
        if (empty($data['sendData'])) {
            return -12;
        }
        $data['fd'] = is_numeric($data['fd']) ? [$data['fd']] : $data['fd'];
        foreach ($data['fd'] as $val) {
            if (empty($val) || !is_numeric($val) || $val < 0) {
                continue;
            }
            if (!$server->isEstablished($val)) {
                return -22;
            }
            $server->push($val, is_array($data["sendData"]) ? json_encode($data["sendData"]) : (string)$data["sendData"]);
        }
        return 11;
    }
}