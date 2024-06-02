<?php
declare(strict_types=1);

namespace work\cor\pdo;

use server\other\Console;
use work\Config;
use work\GlobalVariable;
use work\pool\Conf;
use work\traits\SingleInstance;
use work\traits\SingleObject;

class PdoPoolGather
{
    use SingleObject;

    private array $poolGather = []; //pool集

    private bool $init = false;

    /**
     * 连接池初始化必须是workerStart中调用禁止在协程中初始化
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function initialize()
    {
        if ($this->init) {
            return ;
        }
        $this->init = true;
        $name = GlobalVariable::getManageVariable('_sys_')->get('currentCourse', 'worker');
        $mysqlConfig = Config::getInstance()->get('pdoDb.' . $name,[]);
        $printHeard = ['mysqlConnect', 'option', 'host', 'port', 'dbName', 'charSet', 'userName', 'userPassword', 'initSize'];
        $printData = [];
        foreach ($mysqlConfig as $key => $val) {
            if (isset($val['poolConnect']) && $val['poolConnect']) {
                if (!isset($this->poolGather[$key]) || !$this->poolGather[$key] instanceof PdoPoolObj) {
                    $printRow = [($name . ':' . GlobalVariable::getManageVariable('_sys_')->get('workerId', 0)), $key];
                    foreach ($printHeard as $v) {
                        if (!in_array($v, ['mysqlConnect', 'option','initSize']) && isset($val[$v])) {
                            $printRow[] = $val[$v] ?? '';
                        }
                    }
                    $poolConf = null;
                    if (!empty($val['poolConfListInfo']) && is_array($val['poolConfListInfo'])) {
                        $poolConf = new Conf($val['poolConfListInfo']);
                    }
                    $this->poolGather[$key] = new PdoPoolObj($poolConf);
                    $setConf = $this->poolGather[$key]->setConfigInfo($val);//设置连接配置
                    if ($setConf === true) {
                        $printRow[] = $this->poolGather[$key]->keepMin();//创建初始连接池
                    } else {
                        $printRow[] = 'error';
                    }
                    $printData[] = $printRow;
                }
            }
        }
        if (!empty($printData))
            Console::tableDump($printHeard, $printData, true);
    }

    /**
     * 获取链接
     * @throws \Throwable
     */
    public function getLink(string $key = '', float $timeOut = 1.5): ?PdoLink
    {
        if (!isset($this->poolGather[$key]) || !$this->poolGather[$key] instanceof PdoPoolObj) {
            return null;
        }
        return $this->poolGather[$key]->getLink($timeOut);
    }

    /**
     * 主动回收给pool管理器
     * @param string $key
     * @param PdoLink $item
     * @throws \Throwable
     */
    public function pushLink(string $key, PdoLink $item): bool
    {
        if (!isset($this->poolGather[$key]) || !$this->poolGather[$key] instanceof PdoPoolObj) {
            return false;
        }
        return $this->poolGather[$key]->pushLink($item);
    }

    /**
     * 销毁前
     * @throws \Throwable
     */
    protected function unsetBefore()
    {
        foreach ($this->poolGather as $key => $value) {
            if ($value instanceof PdoPoolObj) {
                $value->destroy();
            }
            unset($this->poolGather[$key]);
        }
    }
}