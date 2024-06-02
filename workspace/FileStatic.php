<?php
declare(strict_types=1);

namespace work;

use Swoole\Coroutine\System;
use work\traits\StaticManage;

class FileStatic
{
    use StaticManage;

    private static array $fpArray = [];
    private static array $fpNumber = [];
    const MODE_MAP = ['r', 'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+', 'e'];

    public static function writeFile(string $id, string $data, string $mode = 'a+')
    {
        if (!in_array($mode, ['w', 'w+', 'a', 'a+'])) {
            return false;
        }
        $fp = static::getFp($id, $mode);
        if ($fp === false) {
            return false;
        }
        return fwrite($fp, $data);
    }

    /**
     * 进程单例fp
     * @param string $id
     * @param string $mode
     * @return resource|bool
     */
    public static function getFp(string $id, string $mode = 'w+')
    {
        if (!static::has($id)) {
            return false;
        }
        $index = array_search($mode, self::MODE_MAP);
        if ($index === false) {
            return false;
        }
        if (!isset(static::$fpArray[$id]) || !isset(static::$fpArray[$id][$index])) {
            $result = static::atomic($id, function () use ($id, $mode, $index) {
                if (isset(static::$fpArray[$id][$index])) {
                    return false;
                }
                try {
                    $dir = dirname(static::get($id));
                    if (!is_dir($dir)) {
                        mkdir($dir, 0733, true);
                    }
                    static::$fpArray[$id][$index] = fopen(static::get($id), $mode);
                    return true;
                } catch (\Exception | \Error | \Throwable $e) {
                    return false;
                }
            });
            if ($result === false) {
                return false;
            }
        }
        if (!isset(static::$fpNumber[$id])) {
            static::$fpNumber[$id] = [];
        }
        if (!isset(static::$fpNumber[$id][$index])) {
            static::$fpNumber[$id][$index] = [];
        }
        $cid = SwlBase::getCoroutineId();
        $cid = is_numeric($cid) && $cid > 0 ? $cid : 0;
        if (!isset(static::$fpNumber[$id][$index][$cid])) {
            static::$fpNumber[$id][$index][$cid] = 0;
        }
        static::$fpNumber[$id][$index][$cid]++;
        return static::$fpArray[$id][$index];
    }

    /**
     * 清掉fp
     * @param string $id
     * @param string|null $mode
     * @return void|false
     */
    public static function cleanFp(string $id, ?string $mode = null)
    {
        if (!static::has($id)) {
            return false;
        }
        $cid = SwlBase::getCoroutineId();
        if ($mode != null) {
            $index = array_search($mode, self::MODE_MAP);
            if ($index === false || !isset(static::$fpArray[$id][$index]) || !is_array(static::$fpNumber[$id][$index])) {
                return false;
            }
            if (count(static::$fpNumber[$id][$index]) <= 1) {
                return static::atomic($id, function () use ($id, $index) {
                    if (!isset(static::$fpArray[$id][$index])) {
                        return false;
                    }
                    fclose(static::$fpArray[$id][$index]);
                    unset(static::$fpArray[$id][$index]);
                    unset(static::$fpNumber[$id][$index]);
                    return true;
                });
            } else {
                unset(static::$fpNumber[$id][$index][$cid]);
            }
            return true;
        }
        foreach (static::$fpNumber[$id] ?? [] as $key => $val) {
            $number = is_array($val) ? count($val) : 0;
            if ($number <= 1 && isset(static::$fpArray[$id][$key])) {
                return static::atomic($id . '_' . $key, function () use ($id, $key) {
                    if (!isset(static::$fpArray[$id][$key])) {
                        return false;
                    }
                    fclose(static::$fpArray[$id][$key]);
                    unset(static::$fpArray[$id][$key]);
                    unset(static::$fpNumber[$id][$key]);
                    return true;
                });
            } else {
                unset(static::$fpNumber[$id][$key][$cid]);
            }
        }
        return true;
    }


    /**
     * 文件进程原子锁
     * @param $path
     * @param $callback
     * @return mixed
     */
    protected static function atomic($path, $callback)
    {
        if (SwlBase::inCoroutine()) {
            try {
                while (!SwlBase::lock($path)) {
                    if (IS_SWOOLE_SERVER && SwlBase::inCoroutine()) {
                        System::sleep(1);
                    } else {
                        usleep(1000 * 1000);
                    }
                }
                return $callback($path);
            } finally {
                SwlBase::unlock($path);
            }
        } else {
            return $callback($path);
        }
    }
}