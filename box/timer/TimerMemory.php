<?php
declare(strict_types=1);

namespace box\timer;

use Swoole\Timer;
use work\cor\facade\Log;
use work\HelperFun;

class TimerMemory
{
    private static array $timers = [];

    /**
     * 周期性定时器
     * @param int $sec
     * @param \Closure $fun
     * @param ...$params
     * @return int
     */
    public static function addTick(int $sec, \Closure $fun, ...$params): int
    {
        $id = Timer::tick($sec, self::run($fun, 1), ...$params);
        self::$timers[$id] = [
            "create" => time(),
            "lastTime" => -1,
            "run" => 0
        ];
        return $id;
    }

    /**
     * 一次性定时器
     * @param int $sec
     * @param \Closure $fun
     * @param ...$params
     * @return int
     */
    public static function addAfter(int $sec, \Closure $fun, ...$params): int
    {
        return Timer::after($sec, self::run($fun), ...$params);
    }


    /**
     * 函数运行
     */
    private static function run(\Closure $fun, int $type = 0): \Closure
    {
        return function (...$params) use ($fun, $type) {
            try {
                if ($type === 1 && is_numeric($params[0]) && isset(self::$timers[$params[0]])) {
                    self::$timers[$params[0]]['run']++;
                    self::$timers[$params[0]]['lastTime'] = time();
                }
                if (!empty($params)) {
                    $fun(...$params);
                } else {
                    $fun();
                }
            } catch (\Throwable | \PDOException | \RedisException | \Error | \Exception | \ErrorException | \TypeError | \ParseError $throwable) {
                $errorInfo = HelperFun::outErrorInfo($throwable);
                $runInfo = $type === 1 ? var_export(self::$timers[$params[0]] ?? "empty", true) : 'nonTimerId';
                Log::error("[timer] : " . $runInfo . " error catch info:" . $errorInfo);
            } finally {
                HelperFun::flushCo();
                if ($type === 1 && is_numeric($params[0]) && isset(self::$timers[$params[0]])) {
                    unset(self::$timers[$params[0]]);
                }
            }
        };
    }

    /**
     * 清除定时器
     * @param int $timerId
     * @return bool
     */
    public static function cleanTimer(int $timerId): bool
    {
        $res = Timer::clear($timerId);
        if (isset(self::$timers[$timerId])) {
            unset(self::$timers[$timerId]);
        }
        return $res;
    }

    /**
     * 清除所有定时器
     * @return bool
     */
    public static function cleanAllTimer(): bool
    {
        $res = Timer::clearAll();
        self::$timers = [];
        return $res;
    }

    /**
     * 获取定时器是否存在
     * @param int $timerId
     * @return bool
     */
    public static function exists(int $timerId): bool
    {
        return Timer::exists($timerId);
    }
}