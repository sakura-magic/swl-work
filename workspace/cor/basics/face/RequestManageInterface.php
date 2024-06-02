<?php
namespace work\cor\basics\face;
interface RequestManageInterface
{
    /**
     * 获取请求头
     * @return mixed
     */
    public function getHeader(string $name = '');

    /**
     * 获取sever信息
     * @return mixed
     */
    public function getSever(string $name = '');

    /**
     * 获取get信息
     * @return mixed
     */
    public function getGet(string $name = '');

    /**
     * 获取post信息
     * @return mixed
     */
    public function getPost(string $name = '');

    /**
     * 获取cookie值
     * @return mixed
     */
    public function getCookie(string $name = '');

    /**
     * 获取file
     * @return mixed
     */
    public function getFiles(string $name = '');

    /**
     * 获取fd描述符
     * @return mixed
     */
    public function getFd();

    /**
     * 获取rawContent
     * @return mixed
     */
    public function getRawContent();

    /**
     * 设置sever信息
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setSever(string $name,$value):bool;

    /**
     * 设置header信息
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setHeader(string $name,$value):bool;

    /**
     * 设置get参数
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setGet(string $name,$value):bool;

    /**
     * 设置post信息
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setPost(string $name,$value):bool;

    /**
     * 设置cookie
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setCookie(string $name,$value):bool;

    /**
     * 设置file信息
     * @param string $name
     * @param $value
     * @return bool
     */
    public function setFiles(string $name,$value):bool;


    /**
     * 获取request所有参数
     * @return array
     */
    public function getRequestAll():array;

    /**
     * 路由
     * @return string
     */
    public function getRouteUri():string;


    public function getClientIp();
}