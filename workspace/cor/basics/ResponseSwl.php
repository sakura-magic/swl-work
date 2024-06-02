<?php
declare(strict_types=1);

namespace work\cor\basics;

use swoole\http\Response;
use work\cor\basics\face\ResponseManageInterface;

class ResponseSwl implements ResponseManageInterface
{
    private ?Response $response;

    private int $autoEnd = 0;

    /**
     * ResponseSwl constructor.
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * 结束发送
     * @param string $data
     * @return bool
     */
    public function end(string $data = ''): bool
    {
        if ($this->autoEnd != 0 && $this->autoEnd != 200) {
            return false;
        }
        if (!$this->response->isWritable()) {
            return false;
        }
        $this->autoEnd = 100;
        return $this->response->end($data);
    }

    /**
     * 302跳转
     * @param string $url
     * @param int $code
     * @return bool
     */
    public function redirect(string $url, int $code = 302): bool
    {
        if ($this->autoEnd != 0 || !in_array($code, [301, 302])) {
            return false;
        }
        $this->autoEnd = $code;
        return $this->redirect($url, $code);
    }

    /**
     * 分段写
     * @param string $data
     * @return bool
     */
    public function write(string $data): bool
    {
        if ($this->autoEnd != 0 && $this->autoEnd != 200) {
            return false;
        }
        $this->autoEnd = 200;
        return $this->response->write($data);
    }

    /**
     * 设置cookie信息
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @param string $samesite
     * @return bool
     */
    public function setCookie(string $name, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = ''): bool
    {
        if (!in_array($this->autoEnd, [0, 200])) {
            return false;
        }
        return $this->response->cookie($name, $value, $expire, $path, $domain, $secure, $httponly, $samesite);
    }

    /**
     * 与setCookie类型不进行 urlencode
     */
    public function setRawCookie(string $name, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = ''): bool
    {
        if (!in_array($this->autoEnd, [0, 200])) {
            return false;
        }
        return $this->response->rawcookie($name, $value, $expire, $path, $domain, $secure, $httponly, $samesite);
    }

    /**
     * 设置header信息
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function setHeader(string $key, string $value, bool $format = true): bool
    {
        if (!in_array($this->autoEnd, [0, 200])) {
            return false;
        }
        return $this->response->header($key, $value, $format);
    }

    /**
     * @param int $code
     * @param string $reason
     * @return bool
     */
    public function status(int $code, string $reason = ''): bool
    {
        if (!in_array($this->autoEnd, [0, 200])) {
            return false;
        }
        return $this->response->status($code, $reason);
    }

    /**
     * 分片发送文件信息
     * @param string $filename
     * @param int $offset
     * @param int $length
     * @return bool
     */
    public function sendfile(string $filename, int $offset = 0, int $length = 0): bool
    {
        if ($this->autoEnd != 0) {
            return false;
        }
        return $this->response->sendfile($filename, $offset, $length);
    }

    /**
     * 分离响应对象
     * @return bool
     */
    public function detach(): bool
    {
        if ($this->autoEnd != 0) {
            return false;
        }
        $this->autoEnd = 400;
        return $this->response->detach();
    }

    /**
     * 实例销毁前调用
     */
    public function __destruct()
    {
        $this->end('');
    }

    /**
     * 调用其他方法
     */
    public function __call($method, $args)
    {
        if (!in_array($this->autoEnd, [0, 200])) {
            return false;
        }
        return call_user_func_array([$this->response, $method], $args);
    }
}