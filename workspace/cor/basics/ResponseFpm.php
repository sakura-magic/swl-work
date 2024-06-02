<?php
declare(strict_types=1);

namespace work\cor\basics;

use work\cor\basics\face\ResponseManageInterface;

class ResponseFpm implements ResponseManageInterface
{
    private int $autoEnd = 0;

    /**
     * 设置响应头
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function setHeader(string $key, string $value, bool $format = true): bool
    {
        header($key . ': ' . $value, $format);
        return true;
    }

    /**
     * 设置响应码
     * @param int $code
     * @param string $reason
     * @return bool
     */
    public function status(int $code, string $reason = ''): bool
    {
        if (!in_array($this->autoEnd, [0, 200])) {
            return false;
        }
        if (empty($reason)) {
            http_response_code($code);
        } else {
            header("status:{$code} {$reason}");
        }
        return true;
    }

    /**
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
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
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
        if (!file_exists($filename)) {
            return false;
        }
        $outFileSize = 1048576;
        $offset = max(0, $offset);
        $length = max(0, $length);
        $fp = fopen($filename, 'rb');
        $fpOut = fopen("php://output", 'wb');
        fseek($fp, $offset);
        $fileSize = filesize($filename);
        $length = $length === 0 ? $fileSize : min($length, $fileSize - $offset);
        $this->setHeader("Content-type", "application/octet-stream");
        $this->setHeader("Accept-Ranges", "bytes");
        $this->setHeader("Accept-Length", $length);
        $this->setHeader("Content-Disposition", "attachment;filename=" . basename($filename));
        $countNumber = ceil($length / $outFileSize);
        for ($i = 0; $i < $countNumber; $i++) {
            $info = fread($fp, min($length - $outFileSize * $i, $outFileSize));
            fwrite($fpOut, $info);
            flush();
            ob_flush();
        }
        fclose($fp);
        fclose($fpOut);
        return true;
    }

    /**
     * 写cookie
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
    public function setRawCookie(string $name, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = ''): bool
    {
        if (!in_array($this->autoEnd, [0, 200])) {
            return false;
        }
        return setrawcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * @param string $data
     * @return bool
     */
    public function write(string $data): bool
    {
        if ($this->autoEnd != 0 && $this->autoEnd != 200) {
            return false;
        }
        if ($this->autoEnd == 0) {
            ob_implicit_flush(1);
            $this->setHeader('Content-Type', 'text/plain');
            $this->setHeader('Transfer-Encoding', 'chunked');
        }
        $this->autoEnd = 200;
        $chunkSize = dechex(strlen($data));
        echo $chunkSize  . PHP_EOL;
        echo $data;
        echo PHP_EOL;
        ob_flush();
        flush();
        return true;
    }

    public function detach(): bool
    {
        return false;//fpm模式不存在切换
    }

    /**
     * 发送
     * @param string $data
     * @return bool
     */
    public function end(string $data = ''): bool
    {
        if ($this->autoEnd != 0 && $this->autoEnd != 200) {
            return false;
        }
        if ($this->autoEnd == 200) { //兼容swoole的调用方式
            echo "0" . PHP_EOL . PHP_EOL;
            ob_flush();
            flush();
            return true;
        }
        $this->autoEnd = 100;
        echo $data;
        return true;
    }

    /**
     * 重定向
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
        header("Location: {$url}", true, $code);
        return true;
    }

    /**
     * 结束
     */
    public function __destruct()
    {
        if ($this->autoEnd == 200) {
            $this->end();
        }
    }
}