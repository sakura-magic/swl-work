<?php
namespace work\pool;
interface PoolItemInterface
{
    //获取最后使用时间
    public function getLastUseTime():int;
    //设置最后使用时间
    public function setLastUseTime(int $num):void;
    //设置hash值
    public function setObjHashInfo(string $str):void;
    //获取hash值
    public function getObjHashInfo():string;
    //是否可回收
    public function recoverable():bool;
}