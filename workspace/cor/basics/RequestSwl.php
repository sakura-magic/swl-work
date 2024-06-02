<?php
declare(strict_types=1);

namespace work\cor\basics;

use server\other\ServerTool;
use work\cor\basics\face\RequestManageInterface;

class RequestSwl implements RequestManageInterface
{
    private \swoole\http\Request $request;

    /**
     * RequestSwl constructor.
     */
    public function __construct(\swoole\http\Request $request)
    {
        $this->request = $request;
    }

    /**
     * 获取fd
     * @return int
     */
    public function getFd(): ?int
    {
        return $this->request->fd;
    }

    /**
     * 获取sever
     * @param string $name
     * @return mixed|null
     */
    public function getSever(string $name = '')
    {
        if (empty($name)) {
            return (array)$this->request->server;
        }
        $name = $this->changeName($name);
        return $this->request->server[$name] ?? null;
    }

    /**
     * 获取cookie
     * @param string $name
     * @return mixed|null
     */
    public function getCookie(string $name = '')
    {
        if (empty($name)) {
            return (array)$this->request->cookie;
        }
        return $this->request->cookie[$name] ?? null;
    }

    /**
     * 获取file信息
     * @param string $name
     * @return mixed|null
     */
    public function getFiles(string $name = '')
    {
        if (empty($name)) {
            return (array)$this->request->files;
        }
        return $this->request->files[$name] ?? null;
    }

    /**
     * 获取get参数
     * @param string $name
     * @return mixed
     */
    public function getGet(string $name = '')
    {
        if (empty($name)) {
            return (array)$this->request->get;
        }
        return $this->request->get[$name] ?? null;
    }

    /**
     * 获取请求头信息
     * @param string $name
     * @return mixed|null
     */
    public function getHeader(string $name = '')
    {
        if (empty($name)) {
            return (array)$this->request->header;
        }
        $name = $this->changeName($name);
        return $this->request->header[$name] ?? null;
    }

    /**
     * 获取post信息
     * @param string $name
     * @return mixed|void
     */
    public function getPost(string $name = '')
    {
        if (empty($name)) {
            return (array)$this->request->post;
        }
        return $this->request->post[$name] ?? null;
    }

    /**
     * 获取请求所有的信息
     * @return array
     */
    public function getRequestAll(): array
    {
        return [
            "fd" => $this->request->fd,
            "server" => $this->request->server,
            "header" => $this->request->header,
            "get" => $this->request->get,
            "post" => $this->request->post,
            "cookie" => $this->request->cookie,
            "rawContent" => $this->request->rawContent(),
            "getMethod" => $this->request->getMethod()
        ];
    }

    /**
     * 设置cookie
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setCookie(string $name, $value): bool
    {
        $name = $this->changeName($name);
        $this->request->cookie[$name] = $value;
        if ($value === null) {
            unset($this->request->cookie[$name]);
        }
        return true;
    }

    /**
     * 设置files
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setFiles(string $name, $value): bool
    {
        $name = $this->changeName($name);
        $this->request->files[$name] = $value;
        if ($value === null) {
            unset($this->request->files[$name]);
        }
        return true;
    }

    /**
     * 设置get信息
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setGet(string $name, $value): bool
    {
        $this->request->get[$name] = $value;
        if ($value === null) {
            unset($this->request->get[$name]);
        }
        return true;
    }

    /**
     * 设置header信息
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setHeader(string $name, $value): bool
    {
        $name = $this->changeName($name);
        $this->request->header[$name] = $value;
        if ($value === null) {
            unset($this->request->header[$name]);
        }
        return true;
    }

    /**
     * 设置post
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setPost(string $name, $value): bool
    {
        $this->request->post[$name] = $value;
        if ($value === null) {
            unset($this->request->post[$name]);
        }
        return true;
    }

    /**
     * 获取信息
     * @return false|string
     */
    public function getRawContent()
    {
        return $this->request->rawContent();
    }

    /**
     * 设置server
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setSever(string $name, $value): bool
    {
        $name = $this->changeName($name);
        $this->request->server[$name] = $value;
        if ($value === null) {
            unset($this->request->server[$name]);
        }
        return true;
    }

    /**
     * name转换
     * @param string $name
     */
    private function changeName(string $name): string
    {
        return strtolower($name);
    }

    /**
     * @return string
     */
    public function getRouteUri(): string
    {
        $uri = $this->getSever('request_uri');
        if ($uri === null) {
            return '';
        }
        return parse_url($uri, PHP_URL_PATH);
    }

    /**
     * 获取客户端ip
     */
    public function getClientIp()
    {
        if ($ip = $this->getSever('x_forwarded_for')) {
            return $ip;
        }
        if ($ip = $this->getHeader('x-forwarded-for')) {
            return $ip;
        }
        if ($ip = $this->getHeader('x-real-ip')) {
            return $ip;
        }
        if ($ip = $this->getSever('remote_addr')) {
            return $ip;
        }
        if ($ip = $this->getHeader('remote-addr')) {
            return $ip;
        }
        $list = ServerTool::getServer()->getSever()->getClientInfo($this->getFd());
        if (isset($list['remote_ip'])) {
            return $list['remote_ip'];
        }
        return null;
    }
}