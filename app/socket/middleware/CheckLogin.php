<?php
namespace app\socket\middleware;
use server\Table;
use work\cor\Wsl;

class CheckLogin
{
    public function handle(Wsl $wsl,\Closure $next)
    {
        $fdInfo = Table::getTable('wsUserInfo')->get('user_' . $wsl->getFrame()->fd);
        if (empty($fdInfo)) {
            return ['code' => -9999,'msg' => "登录失效",'data' => []];
        }
        if (empty($fdInfo['status']) || $fdInfo['status'] <= 0 || empty($fdInfo['userId'])) {
            return ['code' => -9998,'msg' => "登录失效",'data' => []];
        }
        $data = Table::getTable('userMapInfo')->get($fdInfo['userId']);
        if (empty($data) || empty($data['fd'])) {
            return ['code' => -9997,'msg' => "登录失效",'data' => []];
        }
        if ($data['fd'] != $fdInfo['fd']) {
            return ['code' => -9996,'msg' => "已在其他设备上登录",'data' => []];
        }
        return $next($wsl);
    }
}