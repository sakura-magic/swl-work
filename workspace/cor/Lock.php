<?php
namespace work\cor;
use work\Config;
use work\cor\lock\LockInterface;
use work\HelperFun;

/**
 * 流程锁
 * Class Lock
 * @package work\cor
 */
class Lock
{
    private ?LockInterface  $lockObj;

    private string $namespaceInfo = '\\work\\cor\\lock\\';

    public function __construct()
    {
        $optDrive = Config::getInstance()->get('other.lockDrive', 'File');
        $className = $this->namespaceInfo . $optDrive;
        if (!class_exists($className)) {
            throw new \Exception('lock ' . $className . ' drive is not found');
        }
        $class = HelperFun::getContainer()->make($className);
        $this->lockObj = $class;
    }

    /**
     * 加锁
     * @param string $key
     * @param int $expire
     * @param string|null $val
     * @return bool
     */
    public function lock(string $key,int $expire = 5,string $val = null) :bool
    {
        return $this->lockObj->lock($key,$expire,$val);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function unlock(string $key): bool
    {
        return $this->lockObj->unlock($key);
    }
}