<?php
declare(strict_types=1);

namespace work\cor;

use Swoole\Coroutine;
use work\SwlBase;

/**
 * 对文件加锁
 * Class FileSystem
 * @package work\cor
 */
class FileSystem
{

    private float $coLockSleep = 0.200; //当前协程未获得锁，睡眠多长时间醒来

    private float $fileLockSleep = 1; //当前协程未成功获取文件锁，睡眠多长时间醒来

    private int $error = 0;//失败码

    private int $coCycle = -1;//协程锁循环次数

    private int $fileCycle = -1;//文件锁循环次数

    private bool $spinMark = true; //协程自旋锁

    /**
     * 文件进程原子锁
     * @param $path
     * @param $callback
     * @return mixed
     */
    public function atomic($path, $callback)
    {
        if (SwlBase::inCoroutine()) {
            try {
                $number = $this->coCycle === -1 ? 1 : $this->coCycle;
                $flag = SwlBase::lock($path,$this->spinMark);
                while ($number > 0 && !$flag) {
                    $this->error = -1;
                    if ($this->coCycle !== -1) {
                        $number--;
                    }
                    Coroutine::sleep($this->coLockSleep);
                    $flag = SwlBase::lock($path,$this->spinMark);
                }
                if (!$flag) {
                    $this->error = -2;
                    return false;
                }
                $this->error = 0;
                return $callback($path);
            } finally {
                SwlBase::unlock($path);
            }
        } else {
            return $callback($path);
        }
    }

    /**
     * Write the contents of a file.
     * @param string $contents
     * @return bool|int
     */
    public function put(string $path, string $contents, bool $lock = false)
    {
        if ($lock) {
            return $this->lockFileHandle($path, 'a+', fn($fp) => fwrite($fp, $contents));
        }
        if (SwlBase::inCoroutine()) {
            return Coroutine\System::writeFile($path, $contents, FILE_APPEND);
        }
        return file_put_contents($path, $contents, FILE_APPEND);
    }

    /**
     * 写内容
     * @param string $path
     * @param string $contents
     * @param bool $lock
     * @return false|int
     */
    public function write(string $path, string $contents, bool $lock = false)
    {
        $fileDir = dirname($path);
        if (!is_dir($fileDir)) {
            if (SwlBase::inCoroutine()) {
                $this->atomic($fileDir,function() use($fileDir) {
                    mkdir($fileDir);
                });
            } else {
                mkdir($fileDir);
            }
        }
        if ($lock) {
            return $this->lockFileHandle($path, 'w+', fn($fp) => fwrite($fp, $contents));
        }
        if (SwlBase::inCoroutine()) {
            return Coroutine\System::writeFile($path, $contents);
        }
        return file_put_contents($path, $contents);
    }

    /**
     * @param string $path
     * @param bool $lock
     */
    public function read(string $path, bool $lock = false)
    {
        if (!file_exists($path)) {
            return false;
        }
        if (!is_readable($path)) {
            return false;
        }
        if ($lock) {
            return $this->lockFileHandle($path, 'r+', function ($fp) use ($path) {
                if (!is_readable($path)) {
                    return false;
                }
                $num = filesize(rtrim($path));
                if ($num === false || $num < 1) {
                    return false;
                }
                return fread($fp, $num);
            }, LOCK_SH);
        }
        if (SwlBase::inCoroutine()) {
            return Coroutine\System::readFile($path);
        }
        return file_get_contents($path);
    }

    /**
     * lockFile
     * @param string $path
     * @param string $op
     * @param \Closure $func
     * @return false|mixed
     */
    public function lockFileHandle(string $path, string $op, \Closure $func, int $lock = LOCK_EX)
    {
        return $this->atomic($path, function ($path) use ($op, $func, $lock) {
            clearstatcache(true,$path);
            $handle = fopen($path, $op);
            if (!$handle) {
                $this->error = -3;
                return false;
            }
            $wouldBlock = true;
            $lock |= LOCK_NB;
            flock($handle, $lock, $wouldBlock);
            $number = $this->fileCycle === -1 ? 1 : $this->fileCycle;
            while ($number > 0 && $wouldBlock) {
                $this->error = -4;
                if ($this->fileCycle !== -1) {
                    $number--;
                }
                if (SwlBase::inCoroutine()) {
                    Coroutine::sleep($this->fileLockSleep);
                } else {
                    usleep(intval($this->fileLockSleep * 1000 * 1000));
                }
                flock($handle, $lock, $wouldBlock);
            }
            if ($wouldBlock) {
                $this->error = -5;
                fclose($handle);
                return false;
            }
            $this->error = 0;
            $result = false;
            try {
                $result = $func($handle);
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
            return $result;
        });
    }

    /**
     * 设置协程锁睡眠时间
     */
    public function setCoLockSleep(float $number): bool
    {
        if ($number < 100) {
            return false;
        }
        $this->coLockSleep = $number;
        return true;
    }

    /**
     * @param float $number
     * @return bool
     */
    public function setFileLockSleep(float $number): bool
    {
        if ($number < 100) {
            return false;
        }
        $this->fileLockSleep = $number;
        return true;
    }

    /**
     * @param int $number
     * @return bool
     */
    public function setCoCycle(int $number): bool
    {
        if ($number < 0) {
            return false;
        }
        $this->coCycle = $number;
        return true;
    }

    /**
     * @param int $number
     * @return bool
     */
    public function setFileCycle(int $number): bool
    {
        if ($number < 0) {
            return false;
        }
        $this->fileCycle = $number;
        return true;
    }

    /**
     * 设置协程锁是否为自旋方式
     * @param bool $spinMark
     */
    public function setSpinMark(bool $spinMark)
    {
        $this->spinMark = $spinMark;
    }

    /**
     * 获取错误码
     * @return int
     */
    public function getLastError(): int
    {
        return $this->error;
    }

}