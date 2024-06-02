<?php
declare(strict_types=1);

namespace work\cor\redis;

use work\cor\anomaly\RedisCustomException;
use work\pool\PoolCheckInterface;
use work\pool\PoolItemInterface;

class RedisLink implements PoolItemInterface, PoolCheckInterface
{
    private ?\Redis $redis = null;
    private string $hashInfo = '';
    private int $lastUseTime = 0;
    private bool $run = false;//是否在调用中
    private bool $runTransaction = false;//是否处于事务中
    private bool $restore = false;//恢复中
    private int $lastInvokingTime = 0;//上次调用时时间
    private int $selectNum = 0;
    private bool $runPipeline = false;//执行pipeline命令


    /**
     * redis信息配置
     * @var array
     */
    private array $conf = [
        "host" => "127.0.0.1",
        "port" => 6379,
        "auth" => '',
        "index" => 0,
        "timeOut" => 3.0,
        'pingInterval' => 5//单位秒
    ];


    /**
     * @throws RedisCustomException
     */
    public function __construct(array $conf = [])
    {

        $this->conf = array_merge($this->conf, $conf);
        $this->instantiation();
    }

    /**
     * 连接
     * @return \Redis
     */
    private function instantiation(): \Redis
    {
        if (!class_exists('\Redis')) {
            throw new RedisCustomException('The redis extension does not exist', -9999);
        }
        if (is_null($this->redis)) {
            $this->redis = new \Redis();
            $res = $this->redis->connect($this->conf['host'], $this->conf['port'], $this->conf['timeOut'] ?? 3.0);
            if ($res === false) {
                throw new RedisCustomException('redis connect error', -1000);
            }
            $auth = $this->conf['auth'];
            if (!empty($auth)) {
                $resAuth = $this->redis->auth($auth);
                if (!$resAuth) {
                    throw new RedisCustomException('redis auth error',-1000);
                }
            }
            $res = $this->redis->select($this->conf['index']);
            $this->selectNum = $this->conf['index'];
            if ($res === false) {
                throw new RedisCustomException('redis select error', -1000);
            }
        }
        return $this->redis;
    }

    public function getObjHashInfo(): string
    {
        return $this->hashInfo;
    }

    public function setObjHashInfo(string $str): void
    {
        $this->hashInfo = $str;
    }

    public function getLastUseTime(): int
    {
        return $this->lastUseTime;
    }

    public function setLastUseTime(int $num): void
    {
        $this->lastUseTime = $num;
    }

    //关闭连接
    public function gc()
    {
        $this->redis->close();
        $this->redis = null;
    }

    /**
     * 是否可回收
     * @return bool
     */
    public function recoverable(): bool
    {
        if ($this->runTransaction || $this->runPipeline || $this->run || $this->restore || $this->conf['index'] != $this->selectNum) {
            return false;
        }
        return true;
    }

    //不做操作
    public function objectRestore()
    {
        $this->restore = true;
        $this->run = false;
        if ($this->runTransaction || $this->runPipeline) {
            $this->discard();
        }
        if ($this->selectNum != $this->conf['index']) {
            $this->select($this->conf['index']);
        }
        $this->restore = false;
    }

    //使用ping命令判断连接是否可用
    public function beforeUse(): ?bool
    {
        if ($this->runTransaction || $this->runPipeline || $this->run || $this->restore) {
            return false;
        }
        if ($this->selectNum != $this->conf['index']) {
            $this->select($this->conf['index']);
        }
        if (time() - $this->lastInvokingTime < $this->conf['pingInterval']) {
            return true;
        }
        return $this->ping();
    }

    /**
     * @return bool
     */
    public function ping(): bool
    {
        if ($this->redis === null) {
            return false;
        }
        try {
            $redis = $this->instantiation();
            $result = $redis->ping();
            if ($result === true) {
                $this->lastInvokingTime = time();
                return true;
            }
        } catch (RedisCustomException | \RedisException $e) {

        }
        return false;
    }

    /**
     * 魔术调用
     * @param $method
     * @param $args
     * @return void
     * @throws RedisCustomException
     */
    public function __call($method, $args)
    {
        if ($this->run) {
            return null;
        }
        $methodName = strtolower($method);
        if ($this->restore && $this->runTransaction && $methodName !== 'discard') {
            return null;
        }
        $this->run = true;
        $redis = $this->instantiation();
        $result = call_user_func_array([$redis, $method], $args);
        if ($methodName === 'multi') {
            $this->runTransaction = true;
        }
        if ($methodName === 'pipeline') {
            $this->runPipeline = true;
        }
        if (in_array($methodName, ["exec", "discard"])) {
            $this->runTransaction = false;
            $this->runPipeline = false;
        }
        if ($methodName === 'select' && $result) {
            $this->selectNum = intval($args[0] ?? 0);
        }
        $this->run = false;
        return $result;
    }
}