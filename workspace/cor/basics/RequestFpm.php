<?php
declare(strict_types=1);

namespace work\cor\basics;

use work\cor\basics\face\RequestManageInterface;

class RequestFpm implements RequestManageInterface
{
    /**
     * 获取服务
     * @param string $name
     * @return array|mixed|null
     */
    public function getSever(string $name = '')
    {
        if (empty($name)) {
            return $_SERVER;
        }
        return $_SERVER[$name] ?? null;
    }

    /**
     * 获取cookie
     * @param string $name
     * @return array|mixed|null
     */
    public function getCookie(string $name = '')
    {
        if (empty($name)) {
            return $_COOKIE;
        }
        return $_COOKIE[$name] ?? null;
    }

    /**
     * @param string $name
     * @return mixed|void
     */
    public function getHeader(string $name = '')
    {
        if (empty($name)) {
            return $this->headerInfo();
        }
        $headers = $this->headerInfo();
        return $headers[$name] ?? null;
    }

    /**
     * 获取get
     * @param string $name
     * @return mixed|void
     */
    public function getGet(string $name = '')
    {
        if (empty($name)) {
            return $_GET;
        }
        return $_GET[$name] ?? null;
    }

    /**
     * 获取post
     * @param string $name
     * @return array|mixed|null
     */
    public function getPost(string $name = '')
    {
        if (empty($name)) {
            return $_POST;
        }
        return $_POST[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getRequestAll(): array
    {
        return [
            "fd" => $this->getFd(),
            "server" => $this->getSever(),
            "header" => $this->getHeader(),
            "get" => $this->getGet(),
            "post" => $this->getPost(),
            "cookie" => $this->getCookie(),
            "rawContent" => $this->getRawContent(),
            "getMethod" => $_SERVER['REQUEST_METHOD']
        ];
    }

    /**
     * @param string $name
     * @return array|mixed|null
     */
    public function getFiles(string $name = '')
    {
        if (empty($name)) {
            return $_FILES;
        }
        return $_FILES[$name] ?? null;
    }

    /**
     * 获取rawContent
     * @return false|string
     */
    public function getRawContent()
    {
        return file_get_contents('php://input');
    }

    /**
     * @return array
     */
    public function headerInfo(): array
    {
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if ('HTTP_' == substr($key, 0, 5)) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            }
            if (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['AUTHORIZATION'] = $_SERVER['PHP_AUTH_DIGEST'];
            } elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
                $headers['AUTHORIZATION'] = base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
            }
            if (isset($_SERVER['CONTENT_LENGTH'])) {
                $headers['CONTENT-LENGTH'] = $_SERVER['CONTENT_LENGTH'];
            }
            if (isset($_SERVER['CONTENT_TYPE'])) {
                $headers['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
            }
        }
        return $headers;
    }

    /**
     * 设置get参数
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setGet(string $name, $value): bool
    {
        $_GET[$name] = $value;
        return true;
    }

    /**
     * 设置header数据
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setHeader(string $name, $value): bool
    {
        $name = str_replace('-', '_', $name);
        $name = "HTTP_" . strtoupper($name);
        $_SERVER[$name] = $value;
        return true;
    }

    /**
     * 设置server
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setSever(string $name, $value): bool
    {
        $name = strtolower($name);
        $_SERVER[$name] = $value;
        return true;
    }

    /**
     * 设置cookie值
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setCookie(string $name, $value): bool
    {
        $_COOKIE[$name] = $value;
        return true;
    }

    /**
     * 设置file
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setFiles(string $name, $value): bool
    {
        $_FILES[$name] = $value;
        return true;
    }

    /**
     * 设置post/
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setPost(string $name, $value): bool
    {
        $_POST[$name] = $value;
        return true;
    }

    /**
     * @return int
     */
    public function getFd(): int
    {
        return -1;
    }

    /**
     * @return string
     */
    public function getRouteUri(): string
    {
        $info = $this->getSever('REQUEST_URI');
        if ($info !== null) {
            $info = parse_url($info, PHP_URL_PATH);
            return preg_replace("/(\W).*index.php/", "", $info);
        }
        return empty($info) ? strip_tags($_GET['s'] ?? '') : $info;
    }

    /**
     * 获取客户端ip
     */
    public function getClientIp()
    {
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
            $ip = getenv("REMOTE_ADDR");
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = "unknown";
        return ($ip);
    }
}