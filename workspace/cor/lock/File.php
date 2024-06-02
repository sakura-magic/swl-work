<?php
namespace work\cor\lock;
use work\Config;
use work\cor\FileSystem;
use work\HelperFun;

class File implements LockInterface
{
    private ?FileSystem $fileOption;

    private array $lockPool = [];

    public function __construct()
    {
        $this->fileOption = new FileSystem();
        $this->fileOption->setCoCycle(0);
        $this->fileOption->setFileCycle(0);
        $this->fileOption->setSpinMark(false);
    }

    /**
     * 文件锁
     * @param string $key
     * @param int $expire
     * @param string|null $val
     * @return bool
     */
    public function lock(string $key,int $expire = 5,string $val = null) : bool
    {
        $data = $this->getFileVal($key);
        if ($data === null) {
            return false;
        }
        $data = unserialize($data);
        if (!empty($data) && is_array($data) && isset($data['timestamp'])) {
            if ($data['timestamp'] > time()) {
                return false;
            }
        }
        if (empty($val)) {
            $val = md5(time() . HelperFun::character(16));
        }
        $res = $this->fileOption->write($this->getPathInfo($key),serialize([
            'value' => $val,
             'timestamp' => (time() + $expire)
        ]),true);
        if ($res) {
            $this->lockPool[$key] = $val;
        }
        return (bool) $res;
    }


    /**
     * 释放锁
     * @param string $key
     * @return bool
     */
    public function unlock(string $key) :bool
    {
        $data = $this->getFileVal($key);
        if (empty($data)) {
           return false;
        }
        $data = unserialize($data);
        if (!isset($data['value']) || !isset($data['timestamp']) || !isset($this->lockPool[$key])) {
            return false;
        }
        if ($data['value'] !== $this->lockPool[$key] && $data['timestamp'] > time()) {
            return false;
        }
        unset($this->lockPool[$key]);
        return unlink($this->getPathInfo($key));
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getNowLockVal(string $key): ?string
    {
       if (isset($this->lockPool[$key])) {
           return $this->lockPool[$key];
       }
       return null;
    }

    /**
     * 获取信息
     * @param string $key
     * @return array|null
     */
    private function getFileVal(string $key) :?string
    {
        $fileName = $this->getPathInfo($key);
        if (!file_exists($fileName)) {
            return '';
        }
        $data = $this->fileOption->read($fileName,true);
        if (empty($data) || !is_string($data)) {
            return null;
        }
        return $data;
    }

    /**
     * 获取地址
     * @param string $key
     * @return string
     */
    private function getPathInfo(string $key): string
    {
        $confPatch = (string)Config::getInstance()->get('other.lockFilePath', ROOT_PATH . DS . 'logs' . DS . 'lock_info');
        $endStr = substr($confPatch,-1,1);
        $fileName = $confPatch . ($endStr === DS ? $key : DS . $key);
        if (!is_dir($confPatch)) {
            mkdir($confPatch, 0744, true);
        }
        return $fileName . ".lock";
    }
}