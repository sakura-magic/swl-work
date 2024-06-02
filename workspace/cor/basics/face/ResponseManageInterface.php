<?php
namespace work\cor\basics\face;
interface ResponseManageInterface
{
    /**
     * 响应头
     * @param string $key
     * @param string $value
     * @return mixed
     */
    public function setHeader(string $key,string $value,bool $format = true):bool;

    /**
     * 设置cookie
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @param string $samesite
     * @return mixed
     */
    public function setCookie(string $name, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = ''):bool;

    /**
     * 与setCookie类似不进行urlencode编码
    */
    public function setRawCookie(string $name, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = ''):bool;

    /**
     * 响应码
     * @param int $code
     * @param string $reason
     * @return bool
     */
    public function status(int $code,string $reason = ''):bool;

    /**
     * 重定向
     * @param string $url
     * @param int $code
     * @return bool
     */
    public function redirect(string $url, int $code = 302):bool;

    /**
     * 分段写
     * @param string $data
     * @return bool
     */
    public function write(string $data) :bool;

    /**
     * 结束调用
     * @param string $data
     * @return bool
     */
    public function end(string $data = '') :bool;

    /**
     * 分片发送文件
     * @param string $filename
     * @param int $offset
     * @param int $length
     * @return bool
     */
    public function sendfile(string $filename, int $offset = 0, int $length = 0):bool;

    /**
     * 响应分离，仅swoole可用
     * @return bool
     */
    public function detach():bool;
}