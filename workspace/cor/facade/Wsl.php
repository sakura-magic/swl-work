<?php
declare(strict_types=1);

namespace work\cor\facade;
/**
 * @method array|int|string parseJson(string $name = '', $default = null) static 设定当前的语言
 * @method string getFrameData() static 设定当前的语言
 * @method bool  pushSelf(string $msg) static 设定当前的语言
 * @method null|array jsonUnpackData()  static 设定当前的语言
 * @method string jsonPackData(string $event, int $code = 0, string $msg = "", array $data = []) static 设定当前的语言
 * @method bool|array includePush(array $inFd = [], $msg = '') static 设定当前的语言
 * @method bool push(int $fd, $msg = '') static 设定当前的语言
 * @method \Swoole\WebSocket\Frame getFrame() static 设定当前的语言
 * @method \Swoole\WebSocket\Server getWsServer() static 设定当前的语言
 * @method array|null excludePush(array $exFd = [], $msg = '')  static 设定当前的语言
 */
class Wsl extends Facade
{
    protected static bool $instance = true;

    public static function initCreate(...$arg): ?\work\cor\Wsl
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
        return \work\cor\Wsl::class;
    }
}