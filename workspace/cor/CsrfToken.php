<?php
namespace work\cor;
use work\cor\facade\Request;
use work\cor\facade\Response;
use work\cor\facade\Session;
use work\cor\facade\RedisQuery;
use work\HelperFun;

class CsrfToken
{
    private string $key;

    private int $opt = 0;//0-session,1-redis

    public function __construct(string $key = "csrf_token_info",int $opt = 0)
    {
        $this->key = $key;
        $this->opt = $opt;
    }

    /**
     * 校验token
     * @return bool
     * @throws \Exception
     */
    public function verifyToken(): bool
    {
        $requestToken = Request::header("X-XSRF-TOKEN");
        if (!$requestToken) {
            return false;
        }
        $result = false;
        $res = "";
        switch ($this->opt) {
            case 0 : $res = Session::get($this->key); break;
            case 1 : $res = RedisQuery::get("csrf_token_{$this->key}"); break;
            default : throw new \Exception("error opt");
        }
        if (!$res) {
            return false;
        }
        if (strcmp($requestToken, $res) === 0) {
            $result = true;
        }
        switch ($this->opt) {
            case 0 : Session::del($this->key); break;
            case 1 : RedisQuery::del("csrf_token_{$this->key}"); break;
        }
        return $result;
    }

    /**
     * 生成token
     * @return string
     * @throws \Exception
     */
    public function buildToken(): string
    {
        $token = HelperFun::character(16);
        Response::setHeader("XSRF-TOKEN",$token);
        switch ($this->opt){
            case 0 : Session::set($this->key,$token); break;
            case 1 : RedisQuery::set("csrf_token_{$this->key}",$token,2 * 3600); break;
            default : throw new \Exception("error opt");
        }
        return $token;
    }
 }