<?php
declare(strict_types=1);

namespace work\cor\facade;
/**
 * @method void sendJson(array $data) static 设定当前的语言
 * @method bool dump($data) static 设定当前的语言
 * @method void sendStr(string $str = '') static 设定当前的语言
 * @method bool setHeader(string $key, $value, bool $format = true) static 设定当前的语言
 * @method void status(int $number) static 设定当前的语言
 * @method bool setCookie(string $name, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = false, string $samesite = '') static 设定当前的语言
 * @method void dieRun(string $str = '') static 设定当前的语言
 * @method bool redirect(string $location, int $http_code = 302)
 * @method bool sendDownloadFile(string $filepath, string $fileName = '', int $speed = -1, string $prefix = 'public') static 设定当前的语言
 */
class Response extends Facade
{

    protected static bool $instance = true;

    public static function initCreate(...$arg): ?\work\cor\Response
    {
        return static::createFacade(null, $arg);
    }

    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass(): ?string
    {
        return \work\cor\Response::class;
    }
}