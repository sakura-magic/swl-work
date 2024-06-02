<?php
declare(strict_types=1);
namespace work\cor\steam;

class SteamTcp implements SteamInterface
{
    private  $socket;

    private bool $async = false;

    private int $packageLength = 2 * 1024 * 1024;
    /**
     * SteamTcp constructor.
     * @param string $path
     * @param int $port
     */
    public function __construct(string $path,int $port,int $timeout = 30,int $flags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT)
    {
        $errno = null;
        $errnoMessage = null;
        $this->socket = stream_socket_client("tcp://{$path}:{$port}",$errno,$errnoMessage,$timeout,$flags);
        if (!$this->socket) {
            throw new \Exception($errnoMessage,$errno);
        }
    }

    /**
     * 开启异步
     */
    public function async(bool $async = true): bool
    {
        if ($this->async === $async) {
            return true;
        }
        $this->async = $async;
        return stream_set_blocking($this->socket,!$async);
    }

    /**
     * 发送信息
     * @param string $data
     */
    public function send(string $data)
    {
        return fwrite($this->socket,$data);
    }

    /**
     * 读取数据
     * @return false|string
     */
    public function recv()
    {
        return fread($this->socket,$this->packageLength);
    }

    /**
     * 获取资源
     */
    public function getSteam()
    {
        return $this->socket;
    }

    /**
     * 销毁实例
     */
    public function __destruct()
    {
       fclose($this->socket);
    }
}