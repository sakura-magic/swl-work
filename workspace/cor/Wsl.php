<?php
declare(strict_types=1);

namespace work\cor;

use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use work\HelperFun;

/**
 * websocket服务应用
 * Class Wsl
 * @package work\cor
 */
class Wsl
{
    private ?Server $swl;//server
    private ?Frame $frame;//frame

    public function __construct(?Server $server = null, ?Frame $frame = null)
    {
        if (is_null($server)) {
            $server = HelperFun::getContainer()->make(Server::class);
        }
        if (is_null($frame)) {
            $frame = HelperFun::getContainer()->make(Frame::class);
        }
        $this->swl = $server;
        $this->frame = $frame;
    }


    /**
     * 获取frame
     * @return Frame
     */
    public function getFrame(): Frame
    {
        return $this->frame;
    }

    /**
     * 获取swl
     * @return Server
     */
    public function getWsServer(): Server
    {
        return $this->swl;
    }

    /**
     * 发给自己
     * @param string $msg
     * @return bool
     */
    public function pushSelf(string $msg): bool
    {
        return $this->push($this->getFrame()->fd, $msg);
    }

    /**
     * push消息
     * @param int $fd
     * @return bool
     */
    public function push(int $fd, string $msg = ''): bool
    {
        if (!$this->getWsServer()->isEstablished($fd)) {
            return false;
        }
        return $this->getWsServer()->push($fd, $msg);
    }

    /**
     * 指定排除的fd,向其他fd发送
     * @param array $exFd
     * @return array
     */
    public function excludePush(array $exFd = [], string $msg = ''): array
    {
        $connes = $this->getWsServer()->connections;
        $fail = [];
        foreach ($connes as $fd) {
            if (in_array($fd, $exFd)) {
                continue;
            }
            $res = $this->push($fd, $msg);
            if ($res === false) {
                $fail[] = $fd;
            }
        }
        return $fail;
    }


    /**
     * 指定fd发送
     * @param array $inFd
     * @return bool|array
     */
    public function includePush(array $inFd = [], string $msg = '')
    {
        if (empty($inFd)) {
            return false;
        }
        $fail = [];
        foreach ($inFd as $fd) {
            $res = $this->push($fd, $msg);
            if ($res === false) {
                $fail[] = $fd;
            }
        }
        return $fail;
    }

    /**
     * 主动关闭连接
     * @return bool
     */
    public function disConnect(int $fd = -1): bool
    {
        if ($fd === -1) {
            $fd = $this->getFrame()->fd;
        }
        return $this->getWsServer()->disconnect($fd);
    }

    /**
     * 发送ping帧
     * @param int $fd
     * @return bool|array
     */
    public function ping(int $fd = -1)
    {
        $pingFrame = new Frame;
        $pingFrame->opcode = WEBSOCKET_OPCODE_PING;
        if ($fd !== -1) {
            return $this->getWsServer()->push($fd, $pingFrame);
        }
        return $this->getWsServer()->push($this->getFrame()->fd,$pingFrame);
    }

    /**
     * 获取frameData数据
     */
    public function getFrameData(): string
    {
        return $this->getFrame()->data;
    }

    /**
     * 解析json数据
     */
    public function parseJson(string $name = '', $default = null)
    {
        $data = $this->getFrame()->data;
        $parseJson = json_decode($data, true);
        if ($parseJson === false) {
            return $default;
        }
        if (empty($name)) {
            return $parseJson;
        }
        $names = explode('.', $name);
        foreach ($names as $val) {
            if (!isset($parseJson[$val])) {
                return $default;
            }
            $parseJson = $parseJson[$val];
        }
        return $parseJson;
    }

    /**
     * 打包json数据
     * @param string $event //前端事件，可以理解为标识让前端知道是要处理那里的信息
     * @param int $code //状态码
     * @param string $msg //信息
     * @param array $data //data
     * @return string
     */
    public function jsonPackData(string $event, int $code = 0, string $msg = "", array $data = []): string
    {
        $result = [
            "code" => $code,
            "msg" => $msg,
            "data" => $data,
            "event" => $event
        ];
        return json_encode($result);
    }

    /**
     * 如果不改动调用点，必须符合以下条件
     * 解包数据必须符合route定向否则返回错误
     * 解包失败可以返回null,调用点判断为null终止向下执行
     * @return null|array
     */
    public function jsonUnpackData(): ?array
    {
        $wsData = $this->parseJson();
        return [
            "route" => $wsData["route"] ?? "",
            "param" => $wsData["param"] ?? []
        ];
    }

}