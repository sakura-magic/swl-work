<?php
namespace app\socket\controller;
use server\other\ServerTool;
use server\Table;
use work\cor\facade\PdoQuery;
use work\cor\Wsl;
use work\GlobalVariable;

class Com
{
    public function heartCheck(Wsl $wsl): array
    {
        Table::getTable('wsUserInfo')->set('user_' . $wsl->getFrame()->fd,['ptime' => time()]);
        return ["code" => 0,"msg" => 'ok',"data" => []];
    }


    /**
     * 下线
     * @param string $email
     */
    public function tapeOut(string $email)
    {
        $flag = PdoQuery::name('user')->where('email','=',$email)->update(['socket_status' => 0,'update_time' => date('Y-m-d H:i:s')]);
        if (!$flag) {
            return ;
        }
        $workerId = GlobalVariable::getManageVariable('_sys_')->get('workerId',-1);
        $friendList = PdoQuery::name('friends')->where('user_id','=',$email)->fields('id,friend_id')->select();
        foreach ($friendList as $val) {
            $mapInfo = Table::getTable('userMapInfo')->get($val['friend_id']);
            if (!$mapInfo) {
                continue;
            }
            $jsonStr = json_encode([
                "event" => 'offline',
                "code" => 0,
                "msg" => "登录成功",
                "data" => [
                    "userId" => $email,
                    "day" => date('Y-m-d')
                ]
            ]);
            $usrData = Table::getTable('wsUserInfo')->get('user_' . $mapInfo['fd']);
            if (!$usrData) {
                continue;
            }
            if ($workerId != $usrData['worker']) {
                $sendMsgBody = json_encode([
                    "method" => "sendMessage",
                    "fd" => $mapInfo['fd'],
                    "sendData" => $jsonStr
                ]);
                ServerTool::getServer()->getSever()->sendMessage($sendMsgBody,$usrData['worker']);
                continue;
            }
            ServerTool::getServer()->getSever()->push($mapInfo['fd'],$jsonStr);
        }
    }
}