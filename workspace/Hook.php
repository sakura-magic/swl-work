<?php
declare(strict_types=1);

namespace work;

use work\traits\SingleInstance;

/**
 * hook类禁止乱用
 * 1.可能会导致内存溢出
 * 2.协程http隔离问题需要key + 协程id
 * 3.注意回收
 */
class Hook
{
    use SingleInstance;

    private array $hookContainer;
    private static array $hookSingle = [];

    /**
     * 设置hook位
     * @param string $name
     * @param \Closure $callBackFunction
     * @return void
     */
    public function setHook(string $name, \Closure $callBackFunction)
    {
        $this->hookContainer[$name] = $callBackFunction;
    }

    /**
     * 判断hook是否存在
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        return isset($this->hookContainer[$name]);
    }

    /**
     * 销毁hook回调
     * @param string $name
     * @return void
     */
    public function destroyHook(string $name)
    {
        if ($this->exists($name)) {
            unset($this->hookContainer[$name]);
        }

    }

    /**
     * 运行hook
     * @param string $name
     * @param array $arrData
     * @return null|int
     */
    public function runHook(string $name, array $arrData = []): ?int
    {
        if (!$this->exists($name)) {
            return HOOK_RESULT_INFO['none'];
        }
        $method = $this->hookContainer[$name];
        return call_user_func_array($method, $arrData);
    }


}