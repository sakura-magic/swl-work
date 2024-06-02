<?php
declare(strict_types=1);

namespace work\cor\session;

use Swoole\Coroutine;
use work\Config;
use work\cor\facade\RedisQuery;
use work\SwlBase;

class Redis extends Bash implements SessionControlInterface
{
    /**
     * @var int 调用redis时读取库
     */
    private int $beforeIndex = -3;
    /**
     * @var string
     */
    private string $prefix = '';
    /**
     * @var int
     */
    private int $index = 0;

    /**
     * 生命周期
     * @var int
     */
    private int $life = 86400;
    /**
     * cid
     * @var int
     */
    private int $cid = -1;

    private ?\work\cor\RedisQuery $redis = null;


    public function __construct(string $id)
    {
        $redisIndex = Config::getInstance()->get('session.redisIndex');
        if (is_numeric($redisIndex)) {
            $this->index = intval($redisIndex);
        }
        $life = Config::getInstance()->get('session.sessionLife');
        if (is_numeric($life) && $life > 3600) {
            $this->life = $life;
        }
        $this->sessionId = $id;
        $prefix = Config::getInstance()->get('session.prefix', '');
        if (is_string($prefix)) {
            $this->prefix = $prefix;
        }
    }

    /**
     * 写入数据
     * @param string $key
     * @param $val
     * @return bool
     */
    public function write(string $key, $val): bool
    {
        return $this->saveInfo([$key => $val]);
    }

    /**
     * 获取数据信息
     * @param string $key
     * @return false|mixed|null
     */
    public function get(string $key = '')
    {
        $redisData = [];
        if (!$this->cutSelect()) {
            return false;
        }
        if (!empty($getRedis = RedisQuery::get($this->getKeyName()))) {
            $redisData = unserialize($getRedis);
        }
        $this->rollbackSelect();
        if (empty($key)) {
            return $redisData;
        }
        return $redisData[$key] ?? null;
    }

    /**
     * 删除的key
     * @param string $key
     * @return bool
     */
    public function del(string $key): bool
    {
        return $this->saveInfo([], [$key]);
    }

    /**
     * 清掉session
     * @return bool
     */
    public function cleanSession(): bool
    {
        if (!$this->cutSelect()) {
            return false;
        }
        $result = RedisQuery::del($this->getKeyName());
        $this->rollbackSelect();
        return (bool) $result;
    }

    /**
     * session数据
     * @param string $id
     */
    public function setSessionId(string $id): void
    {
        $this->sessionId = $id;
    }

    /**
     * data
     */
    private function saveInfo(array $data, array $delData = []): bool
    {
        $redisData = [];
        if (!$this->cutSelect()) {
            return false;
        }
        if (!empty($getRedis = RedisQuery::get($this->getKeyName()))) {
            $redisData = unserialize($getRedis);
        }
        $redisData = array_merge($redisData, $data);
        foreach ($delData as $val) {
            unset($redisData[$val]);
        }
        $result = RedisQuery::set($this->getKeyName(), serialize($redisData), $this->life);
        $this->rollbackSelect();
        return $result;
    }

    /**
     * @return \work\cor\RedisQuery|null
     */
    public function getRedis(): ?\work\cor\RedisQuery
    {
        $cid = SwlBase::inCoroutine() ? Coroutine::getCid() : -1;
        if ($cid !== $this->cid || $this->redis === null) {
            $this->redis = null;
            $this->redis = new \work\cor\RedisQuery();
            $this->redis->select($this->index);
        }
        return $this->redis;
    }

    /**
     * 切换库
     * @return bool
     */
    private function cutSelect(): bool
    {
        if ($this->beforeIndex === -3) {
            $this->beforeIndex = (int)RedisQuery::getDbNum();
        }
        if ($this->beforeIndex !== $this->index) {
            return RedisQuery::select($this->index);
        }
        return true;
    }

    /**
     * 回滚select
     * @return void
     */
    private function rollbackSelect(): void
    {
        if ($this->beforeIndex === -3) {
            return;
        }
        if ($this->beforeIndex != $this->index) {
            $index = $this->beforeIndex;
            $this->beforeIndex = -3;
            RedisQuery::select($index);
            return;
        }
    }

    private function getKeyName()
    {
        if (!empty($this->sessionId)) {
            return $this->prefix . $this->sessionId;
        }
        $number = 6;
        do {
            $sessionId = $this->buildSession();
            if (RedisQuery::getLock($this->prefix . $sessionId, $this->life, serialize([]))) {
                $this->setSessionId($sessionId);
                break;
            }
        } while ($number--);
        if (empty($this->sessionId)) {
            throw new \Exception('session build id error');
        }
        return $this->prefix . $this->sessionId;
    }
}