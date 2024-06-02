<?php
declare(strict_types=1);

namespace work\cor\facade;
/**
 * Interface defining a client able to execute commands against Redis.
 *
 * All the commands exposed by the client generally have the same signature as
 * described by the Redis documentation, but some of them offer an additional
 * and more friendly interface to ease programming which is described in the
 * following list of methods:
 *
 * @method int         del(array|string $keys) static 设定当前的语言
 * @method string|null dump($key) static 设定当前的语言
 * @method int         exists($key) static 设定当前的语言
 * @method int         expire($key, $seconds) static 设定当前的语言
 * @method int         expireat($key, $timestamp) static 设定当前的语言
 * @method array       keys($pattern) static 设定当前的语言
 * @method int         move($key, $db) static 设定当前的语言
 * @method mixed       object($subcommand, $key) static 设定当前的语言
 * @method int         persist($key) static 设定当前的语言
 * @method int         pexpire($key, $milliseconds) static 设定当前的语言
 * @method int         pexpireat($key, $timestamp) static 设定当前的语言
 * @method int         pttl($key) static 设定当前的语言
 * @method string|null randomkey() static 设定当前的语言
 * @method mixed       rename($key, $target) static 设定当前的语言
 * @method int         renamenx($key, $target) static 设定当前的语言
 * @method array       scan($cursor, array $options = null) static 设定当前的语言
 * @method array       sort($key, array $options = null) static 设定当前的语言
 * @method int         ttl($key) static 设定当前的语言
 * @method mixed       type($key) static 设定当前的语言
 * @method int         append($key, $value) static 设定当前的语言
 * @method int         bitcount($key, $start = null, $end = null) static 设定当前的语言
 * @method int         bitop($operation, $destkey, $key) static 设定当前的语言
 * @method array|null  bitfield($key, $subcommand, ...$subcommandArg) static 设定当前的语言
 * @method int         bitpos($key, $bit, $start = null, $end = null) static 设定当前的语言
 * @method int         decr($key) static 设定当前的语言
 * @method int         decrby($key, $decrement) static 设定当前的语言
 * @method string|null get($key) static 设定当前的语言
 * @method int         getBit($key, $offset) static 设定当前的语言
 * @method string      getrange($key, $start, $end) static 设定当前的语言
 * @method string|null getset($key, $value) static 设定当前的语言
 * @method int         incr($key) static 设定当前的语言
 * @method int         incrby($key, $increment) static 设定当前的语言
 * @method string      incrbyfloat($key, $increment) static 设定当前的语言
 * @method array       mget(array $keys) static 设定当前的语言
 * @method mixed       mset(array $dictionary) static 设定当前的语言
 * @method int         msetnx(array $dictionary) static 设定当前的语言
 * @method mixed       psetex($key, $milliseconds, $value) static 设定当前的语言
 * @method mixed       set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null) static 设定当前的语言
 * @method int         setBit($key, $offset, $value) static 设定当前的语言
 * @method int         setEx($key, $seconds, $value) static 设定当前的语言
 * @method int         setNx($key, $value) static 设定当前的语言
 * @method int         setrange($key, $offset, $value) static 设定当前的语言
 * @method int         strlen($key) static 设定当前的语言
 * @method int         hdel($key, array $fields) static 设定当前的语言
 * @method int         hexists($key, $field) static 设定当前的语言
 * @method string|null hget($key, $field) static 设定当前的语言
 * @method array       hgetall($key) static 设定当前的语言
 * @method int         hincrby($key, $field, $increment) static 设定当前的语言
 * @method string      hincrbyfloat($key, $field, $increment) static 设定当前的语言
 * @method array       hkeys($key) static 设定当前的语言
 * @method int         hlen($key) static 设定当前的语言
 * @method array       hmget($key, array $fields) static 设定当前的语言
 * @method mixed       hmset($key, array $dictionary) static 设定当前的语言
 * @method array       hscan($key, $cursor, array $options = null) static 设定当前的语言
 * @method int         hset($key, $field, $value) static 设定当前的语言
 * @method int         hsetnx($key, $field, $value) static 设定当前的语言
 * @method array       hvals($key) static 设定当前的语言
 * @method int         hstrlen($key, $field) static 设定当前的语言
 * @method array|null  blpop(array|string $keys, $timeout) static 设定当前的语言
 * @method array|null  brpop(array|string $keys, $timeout) static 设定当前的语言
 * @method string|null brpoplpush($source, $destination, $timeout) static 设定当前的语言
 * @method string|null lindex($key, $index) static 设定当前的语言
 * @method int         linsert($key, $whence, $pivot, $value) static 设定当前的语言
 * @method int         llen($key) static 设定当前的语言
 * @method string|null lpop($key) static 设定当前的语言
 * @method int         lPush($key, ...$value1) static 设定当前的语言
 * @method int         lpushx($key, array $values) static 设定当前的语言
 * @method array       lrange($key, $start, $stop) static 设定当前的语言
 * @method int         lrem($key, $count, $value) static 设定当前的语言
 * @method mixed       lset($key, $index, $value) static 设定当前的语言
 * @method mixed       ltrim($key, $start, $stop) static 设定当前的语言
 * @method string|null rPop($key) static 设定当前的语言
 * @method string|null rpoplpush($source, $destination) static 设定当前的语言
 * @method int         rpush($key, array $values) static 设定当前的语言
 * @method int         rpushx($key, array $values) static 设定当前的语言
 * @method int         sadd($key, array|string $members) static 设定当前的语言
 * @method int         scard($key) static 设定当前的语言
 * @method array       sdiff(array|string $keys) static 设定当前的语言
 * @method int         sdiffstore($destination, array|string $keys) static 设定当前的语言
 * @method array       sinter(array|string $keys) static 设定当前的语言
 * @method int         sinterstore($destination, array|string $keys) static 设定当前的语言
 * @method int         sismember($key, $member) static 设定当前的语言
 * @method array       smembers($key) static 设定当前的语言
 * @method int         smove($source, $destination, $member) static 设定当前的语言
 * @method string|null spop($key, $count = null) static 设定当前的语言
 * @method string|null sRandMember($key, $count = null) static 设定当前的语言
 * @method int         srem($key, $member) static 设定当前的语言
 * @method array       sscan($key, $cursor, array $options = null) static 设定当前的语言
 * @method array       sunion(array|string $keys) static 设定当前的语言
 * @method int         sunionstore($destination, array|string $keys) static 设定当前的语言
 * @method int         zAdd($key, $options, $score1, $value1 = null, $score2 = null, $value2 = null, $scoreN = null, $valueN = null) static 设定当前的语言
 * @method int         zCard($key) static 设定当前的语言
 * @method string      zcount($key, $min, $max) static 设定当前的语言
 * @method string      zIncrBy($key, $increment, $member) static 设定当前的语言
 * @method int         zinterstore($destination, array|string $keys, array $options = null) static 设定当前的语言
 * @method array       zrange($key, $start, $stop, array $options = null) static 设定当前的语言
 * @method array       zrangebyscore($key, $min, $max, array $options = null) static 设定当前的语言
 * @method int|null    zrank($key, $member) static 设定当前的语言
 * @method int         zrem($key, $member) static 设定当前的语言
 * @method int         zRemRangeByRank($key, $start, $stop) static 设定当前的语言
 * @method int         zremrangebyscore($key, $min, $max) static 设定当前的语言
 * @method array       zRevRange($key, $start, $stop, array $options = null) static 设定当前的语言
 * @method array       zrevrangebyscore($key, $max, $min, array $options = null) static 设定当前的语言
 * @method int|null    zrevrank($key, $member) static 设定当前的语言
 * @method int         zunionstore($destination, array|string $keys, array $options = null) static 设定当前的语言
 * @method string|null zScore($key, $member) static 设定当前的语言
 * @method array       zscan($key, $cursor, array $options = null) static 设定当前的语言
 * @method array       zrangebylex($key, $start, $stop, array $options = null) static 设定当前的语言
 * @method array       zrevrangebylex($key, $start, $stop, array $options = null) static 设定当前的语言
 * @method int         zremrangebylex($key, $min, $max) static 设定当前的语言
 * @method int         zlexcount($key, $min, $max) static 设定当前的语言
 * @method int         pfadd($key, array $elements) static 设定当前的语言
 * @method mixed       pfmerge($destinationKey, array|string $sourceKeys) static 设定当前的语言
 * @method int         pfcount(array|string $keys) static 设定当前的语言
 * @method mixed       pubsub($subcommand, $argument) static 设定当前的语言
 * @method int         publish($channel, $message) static 设定当前的语言
 * @method mixed       discard() static 设定当前的语言
 * @method array|null  exec() static 设定当前的语言
 * @method mixed       multi() static 设定当前的语言
 * @method mixed       unwatch() static 设定当前的语言
 * @method mixed       watch($key) static 设定当前的语言
 * @method mixed       eval($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null) static 设定当前的语言
 * @method mixed       evalsha($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null) static 设定当前的语言
 * @method mixed       script($subcommand, $argument = null) static 设定当前的语言
 * @method mixed       auth($password) static 设定当前的语言
 * @method string      echo ($message) static 设定当前的语言
 * @method mixed       ping($message = null) static 设定当前的语言
 * @method mixed       select($database) static 设定当前的语言
 * @method mixed       bgrewriteaof() static 设定当前的语言
 * @method mixed       bgsave() static 设定当前的语言
 * @method mixed       client($subcommand, $argument = null) static 设定当前的语言
 * @method mixed       config($subcommand, $argument = null) static 设定当前的语言
 * @method int         dbsize() static 设定当前的语言
 * @method mixed       flushall() static 设定当前的语言
 * @method mixed       flushdb() static 设定当前的语言
 * @method array       info($section = null) static 设定当前的语言
 * @method int         lastsave() static 设定当前的语言
 * @method mixed       save() static 设定当前的语言
 * @method mixed       slaveof($host, $port) static 设定当前的语言
 * @method mixed       slowlog($subcommand, $argument = null) static 设定当前的语言
 * @method array       time() static 设定当前的语言
 * @method array       command() static 设定当前的语言
 * @method int         geoadd($key, $longitude, $latitude, $member) static 设定当前的语言
 * @method array       geohash($key, array $members) static 设定当前的语言
 * @method array       geopos($key, array $members) static 设定当前的语言
 * @method string|null geodist($key, $member1, $member2, $unit = null) static 设定当前的语言
 * @method array       georadius($key, $longitude, $latitude, $radius, $unit, array $options = null) static 设定当前的语言
 * @method array       georadiusbymember($key, $member, $radius, $unit, array $options = null) static 设定当前的语言
 * @method int         getIndex() static 设定当前的语言
 * @method bool manualReturnRedis(string $key, bool $tran) static 设定当前的语言
 * @method bool manualReturnRedisAll(bool $tran = false) static 设定当前的语言
 * @method mixed getLock(string $key = 'lock', int $lockTime = 3, $value = null) static 设定当前的语言
 * @method mixed delLock(string $key = 'lock', bool $opt = false) static 设定当前的语言
 * @method mixed LockExists(string $key = 'lock') static 设定当前的语言
 * @method mixed getLockVal(string $key = 'lock', bool $opt = true) static 设定当前的语言
 * @method int   getDbNum() static 设定当前的语言
 * @method \work\cor\RedisQuery setAutoRet(bool $autoFlag) static 设定当前的语言
 * @author Daniele Alessandri <>
 */
class RedisQuery extends Facade
{
    protected static bool $instance = true;

    public static function initCreate(...$arg): ?\work\cor\RedisQuery
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
        return \work\cor\RedisQuery::class;
    }
}