<?php
declare(strict_types=1);

namespace work\cor\pdo;

use work\CoLifeVariable;
use work\Config;
use work\GlobalVariable;
use work\SwlBase;

class PdoConnect
{

    private ?PdoLink $pdo = null;//原生连接实例
    private array $config = [];//配置信息
    private ?string $key = null;//选择使用的配置key
    private float $timeout = 2.0;

    /**
     * 实例化
     * @param string|null $key
     * @param array $conf
     */
    public function __construct(?string $key = 'default', ?PdoLink $pdoLink = null)
    {
        $worker = GlobalVariable::getManageVariable('_sys_')->get('currentCourse', 'worker');
        if ($worker === null) {
            throw new \PDOException('get currentCourse error');
        }
        if (empty($key) && is_null($pdoLink)) {
            throw new \PDOException('pdo connect params error');
        }
        $configInfo = null;
        if ($key !== null) {
            $this->key = $key;
            $configInfo = Config::getInstance()->get("pdoDb.$worker.$key");
        } else {
            $configInfo = [];
            $configInfo['poolConnect'] = false;
            $this->pdo = $pdoLink;
        }
        if ($configInfo === null) {
            throw new \PDOException('config error');
        }
        $this->config = $configInfo;
    }


    /**
     * 连接
     * @return PdoLink
     * @throws \Throwable
     */
    private function connect(): PdoLink
    {
        if (is_null($this->pdo)) {
            if (IS_SWOOLE_SERVER && $this->config['poolConnect'] && SwlBase::inCoroutine()) {
                return PdoPoolGather::getInstanceObj()->getLink($this->key, $this->timeout); //防止被长期引用 该连接由pool管理器管理
            }  else {
                $obj = CoLifeVariable::getManageVariable()->has('sys_co_pdo_link_' . $this->key) ?
                    CoLifeVariable::getManageVariable()->get('sys_co_pdo_link_' . $this->key)
                    : null;
                if ($obj instanceof PdoLink && $obj->beforeUse()) {
                    $this->pdo = $obj;
                } else {
                    $this->pdo = new PdoLink($this->config);
                    CoLifeVariable::getManageVariable()->set('sys_co_pdo_link_' . $this->key, $this->pdo);
                }
            }
        }
        return $this->pdo;
    }

    /**
     * 回收
     */
    public function recycleLink()
    {
        if (IS_SWOOLE_SERVER && $this->config['poolConnect'] && SwlBase::inCoroutine()) {
            $pdo = $this->connect();
            PdoPoolGather::getInstanceObj()->pushLink($this->key,$pdo);
        }
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
        $pdo = $this->connect();
        return call_user_func_array([$pdo, $method], $args);
    }

}