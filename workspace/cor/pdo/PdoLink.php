<?php
declare(strict_types=1);

namespace work\cor\pdo;

use server\other\Console;
use work\cor\anomaly\PdoCustomException;
use work\pool\PoolCheckInterface;
use work\pool\PoolItemInterface;

class PdoLink implements PoolItemInterface, PoolCheckInterface
{
    private ?\PDO $pdo = null;
    private string $hashInfo = '';
    private int $lastUseTime = 0;
    private bool $run = false;//是否在调用中
    private bool $runTransaction = false;//是否处于事务中
    private bool $restore = false;//恢复连接
    private int $lastInvokingTime = 0;//上次调用时时间

    private array $conf = [
        'db' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbName' => 'test',
        'charSet' => 'utf8',
        'userName' => 'test',
        'userPassword' => '123456',
        'timeout' => 3,
        'pingInterval' => 5//单位秒
    ];

    /**
     * @throws PdoCustomException
     */
    public function __construct(array $conf = [])
    {
        $this->conf = array_merge($this->conf, $conf);
        $this->instantiation();
    }

    /**
     * 获取pdo实例
     * @return \PDO
     * @throws PdoCustomException
     */
    private function instantiation(): \PDO
    {
        if (!class_exists('\PDO')) {
            throw new PdoCustomException('The pdo extension does not exist', -9999);
        }
        if ($this->pdo === null) {
            $pdoConnect = $this->conf['db'];
            $pdoConnect .= ':host=' . $this->conf['host'];
            $pdoConnect .= ';dbname=' . $this->conf['dbName'];
            $pdoConnect .= ';port=' . $this->conf['port'];
            $pdoConnect .= ';charset=' . $this->conf['charSet'];
            $this->pdo = new \PDO($pdoConnect, $this->conf['userName'], $this->conf['userPassword'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_TIMEOUT => $this->conf['timeout']
            ]);
        }
        return $this->pdo;
    }

    /**
     * 判断连接
     * @return bool
     */
    public function pdoPing(): bool
    {
        if ($this->pdo === null) {
            return false;
        }
        try {
            $this->instantiation()->getAttribute(\PDO::ATTR_SERVER_INFO);
            $this->lastInvokingTime = time();
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'MySQL server has gone away') !== false) {
                return false;
            }
        } catch (PdoCustomException $e) {
            return false;
        }
        return true;
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
        $this->pdo = null;
    }

    //不做操作
    public function objectRestore()
    {
        $this->restore = true;
        $this->run = false;
        if ($this->runTransaction || ($this->pdo !== null && $this->pdo->inTransaction())) {
            $this->rollback();
        }
        $this->restore = false;
    }

    //使用ping命令判断连接是否可用
    public function beforeUse(): ?bool
    {
        if ($this->runTransaction || $this->run || $this->restore) {
            return false;
        }
        if (time() - $this->lastInvokingTime < $this->conf['pingInterval']) {
            return true;
        }
        return $this->pdoPing();
    }

    /**
     * 是否可回收
     * @return bool
     */
    public function recoverable(): bool
    {
        if ($this->runTransaction || $this->run || $this->restore) {
            return false;
        }
        return true;
    }

    public function __destruct()
    {
        $this->gc();
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
        if ($this->run) {
            return null;
        }
        $methodName = strtolower($method);
        if ($this->restore && $methodName !== 'rollback') {
            return null;
        }
        $this->run = true;
        $pdo = $this->instantiation();
        $result = call_user_func_array([$pdo, $method], $args);
        if ($methodName === strtolower('beginTransaction')) {
            $this->runTransaction = true;
        }
        if (in_array($methodName, ['commit', 'rollback'])) {
            $this->runTransaction = false;
        }
        $this->run = false;
        return $result;
    }
}