<?php
declare(strict_types=1);

namespace server;
/**
 * 性能强悍，单线程每秒可读写 200 万次；
 * 应用代码无需加锁，Table 内置行锁自旋锁，所有操作均是多线程 / 多进程安全。用户层完全不需要考虑数据同步问题；
 * 支持多进程，Table 可以用于多进程之间共享数据；
 * 使用行锁，而不是全局锁，仅当 2 个进程在同一 CPU 时间，并发读取同一条数据才会进行发生抢锁。
 * set/get/del 是自带行锁，所以不需要调用 lock 加锁；
 */

/**
 * @method bool destroy()
 * @method bool set(string $key, array $value)
 * @method mixed get(string $key, ?string $field = null)
 * @method int count()
 * @method bool del(string $key)
 * @method bool delete(string $key)
 * @method bool exist(string $key)
 * @method int|float incr(string $key, string $column, int|float $incrby = 1)
 * @method int|float decr(string $key, string $column, int|float $incrby = 1)
 * @method array|false stats()
 */
class Table
{
    private static array $tablePool = [];
    //表对象
    private ?\Swoole\Table $table;

    private static bool $init = false;

    private function __construct($key)
    {
        $size = intval(SWOOLE_TABLE_CACHE[$key]['size']);
        $this->table = new \Swoole\Table($size);
        $columnData = SWOOLE_TABLE_CACHE[$key]['column'] ?? [];
        foreach ($columnData as $value) {
            $this->table->column($value['name'], $value['type'], $value['size']);
        }
        $this->table->create();
    }


    /**
     * 调用
     */
    public function __call($method, $args)
    {
        if ($this->table === null || !method_exists($this->table, $method)) {
            return false;
        }
        return call_user_func_array([$this->table, $method], $args);
    }

    /**
     * tableEach
     * @return array
     */
    public function tableEach(?\Closure $fun = null): array
    {
        $res = [];
        foreach ($this->table as $k => $val) {
            $res[$k] = $fun === null ? $val : $fun($val);
        }
        unset($fun);
        return $res;
    }

    /**
     * 筛选
     * @param \Closure|null $fun
     * @return array
     */
    public function tableEachFilter(?\Closure $fun = null): array
    {
        $res = [];
        foreach ($this->table as $k => $val) {
            $flag = $fun === null ? $val : $fun($val);
            if ($flag) {
                $res[$k] = $val;
            }
        }
        unset($fun);
        return $res;
    }

    /**
     * 初始化
     * @return bool
     */
    public static function initialize(): bool
    {
        if (self::$init) {
            return false;
        }
        $configData = SWOOLE_TABLE_CACHE;
        if (is_array($configData)) {
            foreach ($configData as $key => $value) {
                if (!isset($value['create']) || $value['create'] !== true) {
                    continue;
                }
                if (!isset(self::$tablePool[$key]) || !self::$tablePool[$key] instanceof self) {
                    self::$tablePool[$key] = new self($key);
                }
            }
        }
        self::$init = true;
        return true;
    }

    /**
     * 获取单例
     * @return $this|Table|null
     */
    public static function getTable(string $key = 'default'): ?Table
    {
        if (isset(self::$tablePool[$key]) && self::$tablePool[$key] instanceof self) {
            return self::$tablePool[$key];
        }
        return null;
    }

    /**
     * 获取实例
     */
    public function getInstance(): ?\Swoole\Table
    {
        return $this->table;
    }
}