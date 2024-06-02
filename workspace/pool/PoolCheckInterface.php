<?php
namespace work\pool;
interface PoolCheckInterface
{
    //unset 的时候执行
    public function gc();
    //使用后,free的时候会执行
    public function objectRestore();
    //使用前调用,当返回true，表示该对象可用。返回false，该对象失效，需要回收
    public function beforeUse():?bool;
    //是否可回收
    public function recoverable():bool;
}