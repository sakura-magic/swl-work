<?php
declare(strict_types=1);

namespace work\cor;


use work\Config;
use work\cor\redis\RedisConnect;
use work\HelperFun;

/**
 * redis 调用
 * Interface defining a client able to execute commands against Redis.
 *
 * All the commands exposed by the client generally have the same signature as
 * described by the Redis documentation, but some of them offer an additional
 * and more friendly interface to ease programming which is described in the
 * following list of methods:
 *
 * @method int         del(array|string $keys)
 * @method string|null dump($key)
 * @method int         exists($key)
 * @method int         expire($key, $seconds)
 * @method int         expireat($key, $timestamp)
 * @method array       keys($pattern)
 * @method int         move($key, $db)
 * @method mixed       object($subcommand, $key)
 * @method int         persist($key)
 * @method int         pexpire($key, $milliseconds)
 * @method int         pexpireat($key, $timestamp)
 * @method int         pttl($key)
 * @method string|null randomkey()
 * @method mixed       rename($key, $target)
 * @method int         renamenx($key, $target)
 * @method array       scan($cursor, array $options = null)
 * @method array       sort($key, array $options = null)
 * @method int         ttl($key)
 * @method mixed       type($key)
 * @method int         append($key, $value)
 * @method int         bitcount($key, $start = null, $end = null)
 * @method int         bitop($operation, $destkey, $key)
 * @method array|null  bitfield($key, $subcommand, ...$subcommandArg)
 * @method int         bitpos($key, $bit, $start = null, $end = null)
 * @method int         decr($key)
 * @method int         decrby($key, $decrement)
 * @method string|null get($key)
 * @method int         getBit($key, $offset)
 * @method string      getrange($key, $start, $end)
 * @method string|null getset($key, $value)
 * @method int         incr($key)
 * @method int         incrby($key, $increment)
 * @method string      incrbyfloat($key, $increment)
 * @method array       mget(array $keys)
 * @method mixed       mset(array $dictionary)
 * @method int         msetnx(array $dictionary)
 * @method mixed       psetex($key, $milliseconds, $value)
 * @method mixed       set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
 * @method int         setBit($key, $offset, $value)
 * @method int         setEx($key, $seconds, $value)
 * @method int         setNx($key, $value)
 * @method int         setrange($key, $offset, $value)
 * @method int         strlen($key)
 * @method int         hdel($key, array $fields)
 * @method int         hexists($key, $field)
 * @method string|null hget($key, $field)
 * @method array       hgetall($key)
 * @method int         hincrby($key, $field, $increment)
 * @method string      hincrbyfloat($key, $field, $increment)
 * @method array       hkeys($key)
 * @method int         hlen($key)
 * @method array       hmget($key, array $fields)
 * @method mixed       hmset($key, array $dictionary)
 * @method array       hscan($key, $cursor, array $options = null)
 * @method int         hset($key, $field, $value)
 * @method int         hsetnx($key, $field, $value)
 * @method array       hvals($key)
 * @method int         hstrlen($key, $field)
 * @method array|null  blpop(array|string $keys, $timeout)
 * @method array|null  brpop(array|string $keys, $timeout)
 * @method string|null brpoplpush($source, $destination, $timeout)
 * @method string|null lindex($key, $index)
 * @method int         linsert($key, $whence, $pivot, $value)
 * @method int         llen($key)
 * @method string|null lpop($key)
 * @method int         lPush($key, ...$value1)
 * @method int         lpushx($key, array $values)
 * @method array       lrange($key, $start, $stop)
 * @method int         lrem($key, $count, $value)
 * @method mixed       lset($key, $index, $value)
 * @method mixed       ltrim($key, $start, $stop)
 * @method string|null rPop($key)
 * @method string|null rpoplpush($source, $destination)
 * @method int         rpush($key, array $values)
 * @method int         rpushx($key, array $values)
 * @method int         sadd($key, array $members)
 * @method int         scard($key)
 * @method array       sdiff(array|string $keys)
 * @method int         sdiffstore($destination, array|string $keys)
 * @method array       sinter(array|string $keys)
 * @method int         sinterstore($destination, array|string $keys)
 * @method int         sismember($key, $member)
 * @method array       smembers($key)
 * @method int         smove($source, $destination, $member)
 * @method string|null spop($key, $count = null)
 * @method string|null sRandMember($key, $count = null)
 * @method int         srem($key, $member)
 * @method array       sscan($key, $cursor, array $options = null)
 * @method array       sunion(array|string $keys)
 * @method int         sunionstore($destination, array|string $keys)
 * @method int         zAdd($key, $options, $score1, $value1 = null, $score2 = null, $value2 = null, $scoreN = null, $valueN = null)
 * @method int         zCard($key)
 * @method string      zcount($key, $min, $max)
 * @method string      zIncrBy($key, $increment, $member)
 * @method int         zinterstore($destination, array|string $keys, array $options = null)
 * @method array       zrange($key, $start, $stop, array $options = null)
 * @method array       zrangebyscore($key, $min, $max, array $options = null)
 * @method int|null    zrank($key, $member)
 * @method int         zrem($key, $member)
 * @method int         zRemRangeByRank($key, $start, $stop)
 * @method int         zremrangebyscore($key, $min, $max)
 * @method array       zRevRange($key, $start, $stop, array $options = null)
 * @method array       zrevrangebyscore($key, $max, $min, array $options = null)
 * @method int|null    zrevrank($key, $member)
 * @method int         zunionstore($destination, array|string $keys, array $options = null)
 * @method string|null zScore($key, $member)
 * @method array       zscan($key, $cursor, array $options = null)
 * @method array       zrangebylex($key, $start, $stop, array $options = null)
 * @method array       zrevrangebylex($key, $start, $stop, array $options = null)
 * @method int         zremrangebylex($key, $min, $max)
 * @method int         zlexcount($key, $min, $max)
 * @method int         pfadd($key, array $elements)
 * @method mixed       pfmerge($destinationKey, array|string $sourceKeys)
 * @method int         pfcount(array|string $keys)
 * @method mixed       pubsub($subcommand, $argument)
 * @method int         publish($channel, $message)
 * @method mixed       discard()
 * @method array|null  exec()
 * @method mixed       multi()
 * @method mixed       unwatch()
 * @method mixed       watch($key)
 * @method mixed       eval($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method mixed       evalsha($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method mixed       script($subcommand, $argument = null)
 * @method mixed       auth($password)
 * @method string      echo ($message)
 * @method mixed       ping($message = null)
 * @method mixed       select($database)
 * @method mixed       bgrewriteaof()
 * @method mixed       bgsave()
 * @method mixed       client($subcommand, $argument = null)
 * @method mixed       config($subcommand, $argument = null)
 * @method int         dbsize()
 * @method mixed       flushall()
 * @method mixed       flushdb()
 * @method array       info($section = null)
 * @method int         lastsave()
 * @method mixed       save()
 * @method mixed       slaveof($host, $port)
 * @method mixed       slowlog($subcommand, $argument = null)
 * @method array       time()
 * @method array       command()
 * @method int         geoadd($key, $longitude, $latitude, $member)
 * @method array       geohash($key, array $members)
 * @method array       geopos($key, array $members)
 * @method string|null geodist($key, $member1, $member2, $unit = null)
 * @method array       georadius($key, $longitude, $latitude, $radius, $unit, array $options = null)
 * @method array       georadiusbymember($key, $member, $radius, $unit, array $options = null)
 * @method int       getDbNum()
 * @method RedisQuery pipeline()
 * @author Daniele Alessandri <>
 */
class RedisQuery
{
    //库名
    private int $redisIndex = -2;


    private \work\cor\Log $logObj;
    //记录日志
    private bool $recordLog = false;

    protected ?string $optionRedisKey = 'default';

    protected ?RedisConnect $redisConn = null;
    //请求标志
    protected bool $queryFlag = false;

    private bool $autoRetFlag = false;

    public function __construct(?string $key = 'default', array $conf = [])
    {
        $this->autoRetFlag = (bool) Config::getInstance()->get("redis.autoRet",false);
        $this->logObj = new \work\cor\Log();
        if (!array_key_exists('recordLog', $conf)) {
            $this->recordLog = (bool)Config::getInstance()->get("redis.recordLog", $this->recordLog);
        } else {
            $this->recordLog = (bool)$conf['recordLog'];
        }
        $this->optionRedisKey = $key;
    }


    /**
     * 设置连接实例
     * @param RedisConnect $redisCon
     * @throws \RedisClusterException
     */
    public function setRedisConnect(RedisConnect $redisCon)
    {
        if ($this->optionRedisKey !== null) {
            throw new \RedisClusterException('redis key not equal to NULL');
        }
        $this->redisConn = $redisCon;
    }

    /**
     * 获取连接实例
     * @return RedisConnect|null
     * @throws \RedisClusterException
     */
    protected function getRedisConnectInfo(): ?RedisConnect
    {
        if ($this->redisConn instanceof RedisConnect) {
            return $this->redisConn;
        }
        if ($this->optionRedisKey === null) {
            throw new \RedisClusterException("no set option redis key");
        }
        $this->redisConn = new RedisConnect($this->optionRedisKey);
        return $this->redisConn;
    }

    /**
     * @param bool $autoFlag
     * @return $this
     */
    public function setAutoRet(bool $autoFlag) :self
    {
        $this->autoRetFlag = $autoFlag;
        return $this;
    }


    /**
     * 调用redis方法
     * @throws \RedisClusterException
     */
    public function __call($method, $args)
    {
        if ($this->queryFlag) {
            return null;
        }
        $methodName = strtolower($method);
        if ($methodName == 'select') {
            if (!isset($args[0]) || !is_numeric($args[0]) || count($args) > 1) {
                return false;
            }
            $this->redisIndex = $args[0] >= 0 && $args[0] < 16 ? $args[0] : -1;
        }
        $this->queryFlag = true;
        $result = call_user_func_array([$this->getRedisConnectInfo(), $method], $args);
        if ($this->recordLog) {
            $this->logObj->info("redis:{$method} : agr:" . var_export($args, true) . ' >>> result : ' . var_export($result, true));
        }
        $this->queryFlag = false;
        $this->doneRun();
        if (in_array($methodName,['multi','pipeline'])) {
            return $this;
        }
        return $result;
    }

    /**
     * 获取锁
     * @param string $key
     * @param int $lockTime
     * @param $value
     * @throws \RedisClusterException
     */
    public function getLock(string $key = 'lock', int $lockTime = 3, $value = null)
    {
        $value = $value === null ? time() : $value;
        $res = $this->getRedisConnectInfo()->set($key, $value, ['nx', 'ex' => $lockTime]);
        $this->doneRun();
        return $res;
    }

    /**
     * 释放锁
     * @param string $key
     * @param bool $opt
     * @return bool|int
     * @throws \RedisClusterException
     */
    public function delLock(string $key = 'lock', bool $opt = false)
    {
        //判断锁是否存在
        if ($opt && !$this->getRedisConnectInfo()->exists($key)) {
            return true;
        }
        $iLoop = 3;
        do {
            $result = $this->getRedisConnectInfo()->del($key);
        } while ((--$iLoop) && !$result);
        $this->doneRun();
        return $result;
    }

    /**
     * 判断锁是否存在
     * @param string $key
     * @throws \RedisClusterException
     */
    public function LockExists(string $key = 'lock')
    {
        $res = $this->getRedisConnectInfo()->exists($key);
        $this->doneRun();
        return $res;
    }

    /**
     * 获取锁的内容
     * @param string $key
     * @param bool $opt
     * @return false|string
     * @throws \RedisClusterException
     */
    public function getLockVal(string $key = 'lock', bool $opt = true)
    {
        //判断锁是否存在
        if ($opt && !$this->getRedisConnectInfo()->exists($key)) {
            return false;
        }
        $res = $this->getRedisConnectInfo()->get($key);
        $this->doneRun();
        return $res;
    }

    /**
     * 获取下标值
     * @return int
     */
    public function getIndex(): int
    {
        return $this->redisIndex;
    }

    /**
     * 限流方法
     */
    public function limitCurrent(string $key, int $sec = 1, int $limit = 1): bool
    {
        $auto = $this->autoRetFlag;
        $this->setAutoRet(false);
        if (empty($key) || $sec < 1 || $limit < 1) {
            return false;
        }
        $number = $this->incr($key);
        if ($number === false) { //如果服务失败返回false
            return false;
        }
        if ($number > $limit) {
            $ttl = $this->ttl($key);
            if ($ttl == -1) {
                $this->expire($key, $sec);
            }
            return false;
        }
        if ($number == 1) {
            $this->expire($key, $sec);
        }
        $this->setAutoRet($auto);
        $this->doneRun();
        return true;
    }

    /**
     * 调用结束
     */
    public function doneRun()
    {
        if ($this->autoRetFlag) {
            $this->getRedisConnectInfo()->recycleLink();
        }
    }
}