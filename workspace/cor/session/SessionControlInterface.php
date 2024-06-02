<?php
namespace work\cor\session;
interface SessionControlInterface
{
    /**
     * 写入数据
     * @param string $key
     * @param $val
     * @return bool
     */
    public function write(string $key, $val):bool;

    /**
     * 读取数据
     * @param string $key
     * @return mixed
     */
    public function get(string $key = '');

    /**
     * 删数据
     * @param string $key
     * @return bool
     */
    public function del(string $key):bool;

    /**
     * 设置sessionId
     * @param string $id
     */
    public function setSessionId(string $id):void;

    /**
     * 获取sessionId
     * @return string
     */
    public function getSessionId() :string;

    /**
     * 清除session
     * @return bool
     */
    public function cleanSession():bool;
}