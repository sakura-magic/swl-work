<?php
namespace app\socket\controller;
use server\Table;
use work\CoLifeVariable;
use work\Config;
use work\cor\facade\PdoQuery;
use work\cor\facade\Wsl;
use work\cor\Log;
use work\GlobalVariable;
use work\jwt\anomaly\BeforeValidException;
use work\jwt\anomaly\ExpiredException;
use work\jwt\Jwt;
use work\jwt\Key;

class Test
{
    /**
     * @mapping {"route":"socket/test","mode":"WS"}
     * @return array
     */
    public function test()
    {
        $data = [
            "worker" =>  GlobalVariable::getManageVariable('_sys_')->get('workerId',0),
            "fd" => Wsl::getFrame()->fd,
            "fds" => [],
            'tableInfo' => Table::getTable('wsUserInfo')->get('user_' . Wsl::getFrame()->fd)
        ];
        foreach(Wsl::getWsServer()->connections as $fd){
            $data['fds'][] = $fd;
        }
        return ["code" => 0,"msg" => "测试控制器成功","data" => $data,"mode" => "json"];
    }

    /**
     * 登录指令
     * @param string $token mode=WS
     * @param int $force mode=WS
     * @mapping {"route":"socket/login","mode":"WS"}
     * @verifier {"mode":"json","extend":{"code":101,"msg":"{$err}"},"rule":{"token|令牌":"require|min:5|max:2048","force|强制登录":"integer|between:0,1"}}
     * @return array|null|bool
     */
    public function login(string $token,int $force = 0)
    {
        $key = Config::getInstance()->get('app.jwtConf.lockKey');
        $userData = [];
        try{
            $result = JWT::decode($token, new Key($key,'HS256'),60); //HS256方式，这里要和签发的时候对应
            if($result->exp <= time()){
                return ["code" => 4999,'msg' => "无效请求",'data' => []];
            }
            $userData = [
                'usr' => $result->userInfo->usr ?? null,
                'accessToken' => $result->userInfo->accessToken ?? null
            ];
        }catch(BeforeValidException $e) {  // 签名在某个时间点之后才能用
            return ['code' => 4441,'msg' => '签名在某个时间点之后才能用','data' => []];
        }catch(ExpiredException $e) {  // token过期
            return ['code' => 4442 , 'msg' => 'token已过期','data' => []];
        }catch(\Exception $e) {  //其他错误
            return ['code' => 4444,'msg' => '程序错误','data' => []];
        }
        $fdInfo = Table::getTable('wsUserInfo')->get('user_' . Wsl::getFrame()->fd);
        if (empty($fdInfo)) {
            return ['code' => -999,'msg' => "登录失效",'data' => []];
        }
        if (empty($userData['usr'])) {
            return ['code' => -997,'msg' => "用户信息不存在",'data' => ['jwtRes' => var_export($result,true)]];
        }
        $user = Table::getTable('userMapInfo')->get($userData['usr']);
        if (!$force && !empty($user['fd'])) {
            if ($user['fd'] != Wsl::getFrame()->fd) {
                return ['code' => -996,'msg' => "用户已在其他端登录了",'data' => []];
            }
            return ['code' => 1001,'msg' => "已登录过了，无需重复登录",'data' => []];
        }
       $userInfo =  PdoQuery::name('user')->where('email','=',$userData['usr'])->fields('email,access_token,nickname')->find();
       if (empty($userInfo)) {
           return ['code' => -990,'msg' => "未找到用户","data" => []];
       }
       if (empty($userData['accessToken']) || empty($userInfo['access_token']) || $userInfo['access_token'] !== $userData['accessToken']) {
           return ['code' => -994,'msg' => "令牌校验失败","data" => []];
       }
        $table = Table::getTable('userMapInfo')->set($userInfo['email'],["fd" => Wsl::getFrame()->fd,"infoData" => json_encode([
            "nk" => $userInfo['nickname']
        ])]);
       if (!$table) {
           return  ["code" => 4001,"msg" => "登录失败","data" => [],"mode" => "json"];
       }
       $table = Table::getTable('wsUserInfo')->set('user_' . Wsl::getFrame()->fd,['fd' => Wsl::getFrame()->fd,'status' => 1,'ptime' => time(),'userId' => $userInfo['email']]);
       if(!$table){
          return  ["code" => 4000,"msg" => "登录失败","data" => [],"mode" => "json"];
       }
       $upRes = PdoQuery::name('user')->where('email','=',$userData['usr'])->update(['socket_status' => 1,'update_time' => date('Y-m-d H:i:s')]);
       if (!$upRes) {
           return ['code' => 4090,'msg' => "系统繁忙"];
       }
       Wsl::pushSelf(Wsl::jsonPackData('onInit',0,'初始化成功',[]));
        if (empty($userData) || empty($userData['usr'])) {
            return ['code' => 405,'msg' => '无用户信息'];
        }
        $friendData = PdoQuery::name('friends')
            ->where('user_id','=',$userData['usr'])
            ->fields('id,friend_id,create_time')
            ->limit(50)
            ->select();
        if (empty($friendData)) {
            return ['code' => 0,'msg' => "好友列表",'data' => ['friends' => $friendData]];
        }
        $friendIds = [];
        foreach ($friendData as $key => $value) {
            if (!in_array($value['friend_id'],$friendIds)) {
                $friendIds[] = $value['friend_id'];
            }
        }
        $friendList = PdoQuery::name('user')
            ->where('email','in',$friendIds)
            ->fields('email,nickname,icon,socket_status')
            ->select();
        $list = [];
        $workerId = GlobalVariable::getManageVariable('_sys_')->get('workerId',-1);
        foreach ($friendData as $key => $value) {
            $row = [];
            foreach ($friendList as $val) {
                if ($value['friend_id'] === $val['email']) {
                    $row = [
                        'account' => $value['friend_id'],
                        'nickname' => $val['nickname'],
                        'icon' => $val['icon'],
                        'status' => $val['socket_status'],
                        'create_time' => $value['create_time']
                    ];
                    $mapInfo = Table::getTable('userMapInfo')->get($val['email']);
                    if (empty($mapInfo['fd'])) {
                        break;
                    }
                    $socketInfo = Table::getTable('wsUserInfo')->get('user_' . $mapInfo['fd']);
                    if ($workerId != $socketInfo['worker']) {
                        $jsonStr = Wsl::jsonPackData('online',0,"登录成功",[
                            "userId" => $userData['usr'],
                            "day" => date('Y-m-d')
                        ]);
                        $sendMsgBody = json_encode([
                            "method" => "sendMessage",
                            "fd" => $mapInfo['fd'],
                            "sendData" => $jsonStr
                        ]);
                        Wsl::getWsServer()->sendMessage($sendMsgBody,$socketInfo['worker']);
                        break;
                    }
                    Wsl::push($mapInfo['fd'],Wsl::jsonPackData('online',0,"登录成功",[
                        "userId" => $userData['usr'],
                        "day" => date('Y-m-d')
                    ]));
                    break;
                }
            }
            $list[] = $row;
        }
       return ["code" => 0,"msg" => "初始化信息","data" => ['friends' => $list],"mode" => "json"];
    }

    /**
     * 发送给他人
     * @middleware ["\\app\\socket\\middleware\\CheckLogin"]
     * @mapping {"route":"send_to_others","mode":"WS"}
     */
    public function sendInfo(\work\cor\Wsl $wsl)
    {
        $params = $wsl->parseJson('param','error');
        $wslResult = $wsl->jsonPackData('chat',0,'接受他发送的消息',['info' => $params]);
        $wsl->excludePush([$wsl->getFrame()->fd],$wslResult);
        $data = [];
        $list =  Table::getTable('wsUserInfo')->tableEach(function($val) use(&$data){
            $workerId =  GlobalVariable::getManageVariable('_sys_')->get('workerId',-1);
            if($workerId != -1 && $workerId != $val['worker']){
                $data[$val['worker']][] = $val["fd"];
            }
            return $val;
        });
        foreach($data as $key => $val){
            $jsonData = json_encode([
                "method" => "sendMessage",
                "fd" => $val,
                "sendData" => $wslResult
            ]);
            $wsl->getWsServer()->sendMessage($jsonData,$key);
        }
        return ["code" => 0,"msg" => "当前table信息","data" => ['list' => $list,'data' => $data],"mode" => "json"];
    }


    /**
     * 发送消息
     * @param \work\cor\Wsl $wsl
     */
    public function sendMsg(\work\cor\Wsl $wsl,string $fromUser,string $toUser,string $sendInfo): array
    {
        $myInfo = Table::getTable('userMapInfo')->get($fromUser);
        if (empty($myInfo) || empty($myInfo['fd']) || $myInfo['fd'] != $wsl->getFrame()->fd) {
            return ["code" => -9999,"msg" => "信息错误"];
        }
        $userInfo = Table::getTable('userMapInfo')->get($toUser);
        if (empty($userInfo) || empty($userInfo['fd'])) {
            return ["code" => 2003,"msg" => "发送的用户不存在"];
        }
        $fdInfo = Table::getTable('wsUserInfo')->get('user_' . $userInfo['fd']);
        if (empty($fdInfo) || empty($fdInfo['userId']) || $fdInfo['userId'] !== $toUser || $fdInfo['status'] <= 0) {
            return ["code" => 2004,"msg" => "发送的用户信息错误"];
        }
        $sendBody = [
            "formUser" => $fromUser,
            "toUser" => $toUser,
            "sendInfo" => $sendInfo
        ];
        $workerId =  GlobalVariable::getManageVariable('_sys_')->get('workerId',-1);
        $fds = [$myInfo['fd']];
        if ($fdInfo['worker'] != $workerId) {
            $jsonStr = $wsl->jsonPackData('send_msg',0,"接受他人发送的消息d",$sendBody);
            $sendMsgBody = json_encode([
                "method" => "sendMessage",
                "fd" => $userInfo['fd'],
                "sendData" => $jsonStr
            ]);
            $wsl->getWsServer()->sendMessage($sendMsgBody,$fdInfo['worker']);
        }else {
            $fds[] = $userInfo['fd'];
        }
        return ["fd" => $fds,"code" => 0,"msg" => "接受他人发送的消息","data" => [
            "formUser" => $fromUser,
            "toUser" => $toUser,
            "sendInfo" => $sendInfo
        ]];
    }
}