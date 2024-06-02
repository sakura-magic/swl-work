<?php
declare(strict_types=1);

namespace work\cor\redis;

use Swoole\Database\RedisPool;
use work\CoLifeVariable;
use work\Config;
use work\GlobalVariable;
use work\SwlBase;

/**
 * @method mixed       set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
 * @method string|null get($key)
 * @method int         del(array|string $keys)
 * @method int|bool         exists($key)
 */
class RedisConnect
{

    private ?RedisLink $redis = null;//原生连接实例
    private array $config = [];//配置信息
    private ?string $key = null;//选择使用的配置key
    private float $timeout = 2.0;


    /**
     * 实例化
     * @param string|null $key
     * @param array $conf
     */
    public function __construct(?string $key = 'default', ?RedisLink $redisLink = null)
    {
        $worker = GlobalVariable::getManageVariable('_sys_')->get('currentCourse', 'worker');
        if ($worker === null) {
            throw new \RedisException('get currentCourse error');
        }
        if (empty($key) && is_null($redisLink)) {
            throw new \RedisException('redis connect params error');
        }
        $configInfo = null;
        if ($key !== null) {
            $this->key = $key;
            $configInfo = Config::getInstance()->get("redis.$worker.$key");
        } else {
            $configInfo = [];
            $configInfo['poolConnect'] = false;
            $this->redis = $redisLink;
        }
        if ($configInfo === null) {
            throw new \PDOException('config error');
        }
        $this->config = $configInfo;
    }



    /**
     * 回收
     */
    public function recycleLink()
    {
        if (IS_SWOOLE_SERVER && $this->config['poolConnect'] && SwlBase::inCoroutine()) {
            $redis = $this->connect();
            RedisPoolGather::getInstanceObj()->pushLink($this->key,$redis);
        }
    }

    /**
     * 连接
     * @return RedisLink
     * @throws \Throwable
     */
    private function connect(): RedisLink
    {
        if (is_null($this->redis)) {
            if (IS_SWOOLE_SERVER && $this->config['poolConnect'] && SwlBase::inCoroutine()) {
                return RedisPoolGather::getInstanceObj()->getLink($this->key, $this->timeout);//防止被长期引用 该连接由pool管理器管理
            } else {
                $redisObj = CoLifeVariable::getManageVariable()->has('sys_co_redis_link_' . $this->key)
                ? CoLifeVariable::getManageVariable()->get('sys_co_redis_link_' . $this->key) :
                null;
                if ($redisObj instanceof RedisLink && $redisObj->beforeUse()) {
                    $this->redis = $redisObj;
                } else {
                    $this->redis = new RedisLink($this->config);
                    CoLifeVariable::getManageVariable()->set('sys_co_redis_link_' . $this->key,$this->redis);
                }
            }
        }
        return $this->redis;
    }

    /**
     * 魔术调用
     * @param $method
     * @param $args
     * @return void
     * @throws \Throwable
     */
    public function __call($method, $args)
    {
        $redis = $this->connect();
        return call_user_func_array([$redis, $method], $args);
    }

}