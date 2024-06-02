<?php
namespace work\cor;
use work\HashLib;

/**
 * 布隆过滤器
 * Class BloomFilterRedis
 * @package work\cor
 */
class BloomFilterRedis
{
    /**
     * 需要使用一个方法来定义bucket的名字
     */
    protected string $bucket;

    protected array $hashFunction;

    protected RedisQuery $redis;

    public function __construct(string $bucket,array $hashFunction)
    {
        $this->bucket = $bucket;
        $this->hashFunction = $hashFunction;
        if (!$this->bucket || !$this->hashFunction) {
            throw new \Exception("需要定义bucket和hashFunction", 1);
        }
        $this->redis = new RedisQuery(); //假设这里你已经连接好了
    }

    /**
     * 添加到集合中
     */
    public function add($string)
    {
        $pipe = $this->redis->multi();
        foreach ($this->hashFunction as $function) {
            $hash =  HashLib::$function($string);
            $pipe->setBit($this->bucket, $hash, 1);
        }
        return $this->redis->exec();
    }

    /**
     * 查询是否存在
     */
    public function exists($string): bool
    {
        $pipe = $this->redis->multi();
        $len = strlen($string);
        foreach ($this->hashFunction as $function) {
            $hash = HashLib::$function($string, $len);
            $pipe->getBit($this->bucket, $hash);
        }
        $res = $this->redis->exec();
        foreach ($res as $bit) {
            if ($bit == 0) {
                return false;
            }
        }
        return true;
    }
}