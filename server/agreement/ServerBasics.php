<?php
namespace server\agreement;
interface ServerBasics{

    /**
     * 运行方法
     * @return bool
     */
    public function start():bool;

    /**
     * 服务重载
     * @return bool
     */
    public function reload():bool;

    /**
     * 服务停止
     * @return bool
     */
    public function stop():bool;

    /**
     * 服务重启
     * @return bool
     */
    public function restart():bool;

    /**
     * 获取服务信息
     * @return mixed
     */
    public function getServerConfig(string $key = '',$default = null);
}