<?php
namespace app\admin\controller;


use app\task\TestContext;
use app\task\TestTask;
use work\cor\CsrfToken;
use box\task\SwlTask;
use server\other\ServerTool;
use server\Table;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Server;
use work\cor\BloomFilterRedis;
use work\cor\Controller;
use work\cor\facade\Log;
use work\cor\facade\PdoQuery;
use work\cor\facade\RedisQuery;
use work\cor\facade\Request;
use work\cor\facade\Response;
use work\cor\File;
use work\cor\http\req\BatchCurl;
use work\cor\http\swl\CoRequest;
use work\cor\HttpBatchRequest;
use work\cor\HttpRequest;
use work\cor\Session;
use work\cor\Template;
use work\GlobalVariable;
use work\HelperFun;

/**
 * @mapping {"route":"test/"}
 */
class Test extends Controller
{

    /**
     * @mapping {"route":"admin/test","mode":"ANY"}
     * @return void
     */
    public function test(\work\cor\Request $request)
    {
//        return Request::get('test',null,"\\work\\HelperFun::xssFilter");
//        return date('Y-m-d H:i:s');
//        Response::dump(1111);
        Response::dump(str_pad("*",1080,"*"));
        sleep(1);
        Response::dump(PHP_OUTPUT_HANDLER_REMOVABLE);
        sleep(1);
        Response::dump((new \work\cor\Request())->get('kkl'));
        return ;
//        return SwlBase::getRunCoroutineNum();
    }

    /**
     * @mapping {"route":"admin/index","mode":"GET"}
     * #fusing {"opt":"process","conf":{"timeOut":3,"failThreshold":20,"successThreshold":5},"fallback":"\\app\\admin\\controller\\Test@test"}
     * @return array
     */
    public function index()
    {
//        Request::param();
//        GlobalVariable::corGet()->set('test','index');
        $result =  PdoQuery::name('menu_admin')->where('id','in',[1,2,3,4])->select();
        $redisData =  RedisQuery::get('test_901');
        $data = PdoQuery::name('user')->where('id','=',"' # OR 1")->limit(200)->select();
        return ['con' => 'index','result' => $result,'limit' => $data,'data' => $redisData];
    }

    /**
     * 协程并发
     * @return array
     */
    public function channalTest()
    {
        $start = microtime(true);
        $channal = new Channel(4);
        go(function() use($channal){
//            $this->response()->dump('redis start');
            try{
                $redisData =  (new \work\cor\RedisQuery())->get('test_901');
            }catch (\Throwable $e) {
                $redisData = $e->getMessage();
            }
//            $this->response()->dump('redis end');
            $channal->push($redisData);
        });
        go(function()use($channal){
//            $this->response()->dump('mysql start');
            try{
//                Coroutine::sleep(0.1);
//                $this->response()->dump('sleep done');
//                $pdoData = (new \work\cor\PdoQuery())->name('menu_admin')->where('id','in',[1,2,3,4])->select();
                $pdoData = PdoQuery::name('menu_admin')->where('id','in',[1,2,3,4])->select();
//                $co = HelperFun::getContainer();
////                $context = Coroutine::getContext();
////                $context->co = new Container();
////                $co = $context->co;
//                $pdo = $co->make(\work\cor\PdoQuery::class);
//                if (!$co->isShared(\work\cor\PdoQuery::class)) {
//                    $co->instance(\work\cor\PdoQuery::class,$pdo);
//                }
//                $pdoData = $pdo->name('menu_admin')->where('id','in',[1,2,3,4])->select();
            }catch (\Throwable $e) {
                $pdoData = $e->getMessage();
            }
//            finally {
//                HelperFun::flushCo();
//            }
//            $this->response()->dump('mysql end');
            $channal->push($pdoData);
        });
        go(function() use($channal){
//            $this->response()->dump('redis2 start');
            try{
                $redisData = (new \work\cor\RedisQuery())->get('test_902');
//                $redisData = RedisQuery::get('test_902');
//                $redisData =  (new \work\cor\RedisQuery())->get('test_902');
            }catch (\Throwable $e) {
                $redisData = $e->getMessage();
            }
//            HelperFun::getContainer()->flush();
//            $this->response()->dump('redis2 end');
            $channal->push($redisData);
        });
        go(function()use($channal){
//            $this->response()->dump('mysql2 start');
            try{
                $pdoData = (new \work\cor\PdoQuery())->name('menu_admin')->where('id','=',2)->select();
            }catch (\Throwable $e) {
                $pdoData = $e->getMessage();
            }
//            $this->response()->dump('mysql2 end');
            $channal->push($pdoData);
        });

//        $this->response()->dump("done");

        $result = [];
        for($i = 4;$i--;){
            $result[] = $channal->pop();
         }
//        $result[] = 'op';
//        $result[] =  PdoQuery::name('menu_admin')->where('id','=',2)->select();
////        PdoQuery::manualReturnPdoAll();
//        $result[] =  RedisQuery::get('test_901');
//        RedisQuery::manualReturnRedisAll();
        $end = microtime(true);
        $executionTime = $end - $start;
        $result[] = [
          'time' => $executionTime
        ];
        return $result;
    }

    /**
     * @mapping {"route":"admin/poolTest","mode":"GET"}
     * @fusing {"opt":"process","conf":{"timeOut":1,"failThreshold":10,"successThreshold":5},"fallback":"\\app\\admin\\controller\\Test@test"}
     */
    public function poolTest()
    {
        $redisData = RedisQuery::get('test_902');
        $pdoData = PdoQuery::name('menu_admin')->where('id','=',2)->select();
        return [$redisData,$pdoData];
    }
    /**
     * @mapping {"route":"admin/log","mode":"GET"}
     * @return false|string|array
     */
    public function logTest()
    {
        Log::info('testinfo 协程id:');
//        Log::error('test 协程id:' . Coroutine::getCid());
//        Log::error('test 协程id2:' . Coroutine::getCid());
        Log::info('testinfo2 协程id:');
        return '调用成功';
    }



    /**
     * @mapping {"route":"admin/testFile","mode":"POST"}
     * @return string|array
     */
    public function testFile()
    {
        $filesInfo = Request::getFileInfo('txt');
        if(!$filesInfo){
            return ['code' => -5,'sMsg' => '缺少文件信息'];
        }
        $file = new File($filesInfo['tmp_name']);
        $file->setUploadInfo($filesInfo);
        $result = $file->move(ROOT_PATH . DS . 'public' . DS . 'upload');
        if($result === false){
            return ['code' => 401,'sMsg' => '保存失败'];
        }
        return ['code' => 0,'sMsg' => '保存成功'];
    }

    /**
     * @mapping {"route":"admin/tableTest","mode":"GET"}
     * @return string
     */
    public function tableTest()
    {
        Table::getTable()->set('1',['id' => 1,'name' => 'test']);
        return 'ok';
    }

    /**
     * @mapping {"route":"admin/tableGet","mode":"GET"}
     * @return array
     */
    public function tableGetTest()
    {
        return [
            'id' => Table::getTable()->get('1','id'),
            'name' => Table::getTable()->get('1','name'),
            'workerId' =>  GlobalVariable::getManageVariable('_sys_')->get('workerId',0)
        ];

    }

    /**
     * @mapping {"route":"admin/postJson","mode":"POST"}
     * @return array
     */
    public function postJson()
    {
        return [
            'test' => Request::postJson(),
            'get' => Request::postJson('kkl.jk')
        ];
    }

    /**
     * @mapping {"route":"admin/reloadTest","mode":"GET"}
     * @return array
     */
    public function reloadTest()
    {
        return  [
            'uri' => Request::server('request_uri')
        ];
    }

    /**
     * @mapping {"route":"admin/setRedis","mode":"GET"}
     * 设置redis
     * @return array
     */
    public function setRedis()
    {
        Response::dieRun(json_encode([
            'code' => -1,
            'res' => 'error'
        ]));
        $result =  RedisQuery::set('test_901','test-test',86400);
        return [
            'code' => 'ok',
            're' => $result
        ];
    }

    /**
     * 测试请求参数
     * @mapping {"route":"admin/testParam","mode":"POST"}
     * @return array
     */
    public function testParam()
    {
        $ad = Request::get('ad');
        $bd = Request::post('bd');
        $adGet =  GlobalVariable::corGet()->get('ad');
        $bdGet =  GlobalVariable::corGet()->get('bd');
        GlobalVariable::corGet()->set('ad',$ad);
        GlobalVariable::corGet()->set('bd',$bd);
        return [
            'ad' => $ad,
            'pd' => $bd,
            'adGet' => $adGet,
            'bdGet' => $bdGet,
            'sad' =>  GlobalVariable::corGet()->get('ad'),
            'spd' =>  GlobalVariable::corGet()->get('bd')
        ];

    }

    /**
     * 测试task进程
     * @param string $account mode=POST
     * @param string $password mode=POST
     * @mapping {"route":"admin/taskTask","mode":"GET"}
     */
    public function testTask(string $account,string $password)
    {
        $server = $this->getServer();
        if (!$server) {
            return ['code' => -1,'msg' => '获取服务失败'];
        }
        $sendInfo = serialize([
            'class' => 'TestTask',
            ''
        ]);
        $task = $server->task($sendInfo);
        return ['code' => 0,'msg' => '完成','task' => $task];
    }

    private function getServer() :?Server
    {
        return ServerTool::getServer()->getSever();
    }

    /**
     * 测试模板渲染
     * @mapping {"route":"admin/testView","mode":"GET"}
     */
    public function testView(\work\cor\Request $request)
    {
        $data = $request->get('test');
        $this->assign('data_test',$data);

        return $this->display('index');
    }

    /**
     * 测试session类
     * @mapping {"route":"admin/session","mode":"GET"}
     */
    public function testSession()
    {
        $res = [];
        $num = mt_rand(0,9999);
        $res[] = RedisQuery::set('test' . $num,5,10);
        $session = new Session();
        $res[] = $session->set('test' . $num,'ls');
        $res[] = $session->get('test' . $num);
        $res[] = RedisQuery::get('test12');
        $res[] = RedisQuery::set('test12',1,10);
        $res[] = $session->set('test' . $num,'lfdss');
        $res[] = $session->get('test' . $num);
        return ['code' => 0,'msg' => 'll','data' => $res];
    }

    /**
     * @mapping {"route":"admin/printInclude","mode":"GET"}
     */
    public function testPrintIncludeFile(CoRequest $cu)
    {
//        $includeInfo = GlobalVariable::getManageVariable('_sys_')->get('beforeWorkerStartIncludeFile');
//        Response::dump($includeInfo);
//        Response::dieRun();
          $baidu = $cu->get('http://www.baidu.com');
//          $bili = $cu->get('http://doc.csfullspeed.com');
          return $baidu;
//        return file_get_contents(ROOT_PATH . DS . 'app' . DS  . 'admin' . DS . 'view' . DS  . 'index' . DS . 'index.html');
    }

    /**
     * @mapping {"route":"admin/curlTwo","mode":"GET"}
     */
    public function testCurlTwo(BatchCurl $cu)
    {
        $cu->addGetRequest('http://www.baidu.com');
        $cu->addGetRequest('http://doc.csfullspeed.com');
        return $cu->execute();
    }





    /**
     * @mapping {"route":"admin/luaRedis","mode":"GET"}
     */
    public function luaRedisTest(): string
    {
        $script = <<<SCRIPT
            local res = 0;
            if (tonumber(redis.call('get','test_cut')) > 100) 
            then
                redis.call('set','test_cut',300);
                res = 1;
            end    
             return res;
           SCRIPT;
        return 'redis res' . RedisQuery::eval($script,[],0);
    }

    /**
     * @mapping {"route":"admin/resData","mode":"GET"}
     */
    public function resData()
    {
//        $script = <<<SCRIPT
//            local sore = redis.call("zscore",KEY[1],KEY[2]);
//            sore = sore == nil ? 0 : tonumber(sore);
//           SCRIPT;

        $lockKey = 'ls-lock-' . mt_rand(1,100);
        if (!RedisQuery::getLock($lockKey,30)) {
            return "失败";
        }
        RedisQuery::lPush("list-key-fet",serialize([
            "class" => "TestTask",
            "data" => [
                'rand' => mt_rand(0,123)
            ]
        ]));
//        (new \work\cor\RedisQuery('test'))->lPush("list-key-fet",serialize([
//            "class" => "TestTask",
//            "data" => [
//                'rand' => mt_rand(0,123)
//            ]
//        ]));
        RedisQuery::delLock($lockKey);
        return 'ok';
    }


    /**
     * @mapping {"route":"admin/testRead","mode":"GET"}
     */
    public function testRead()
    {
        $fileName = ROOT_PATH . DS . 'logs' . DS . 'session' . DS . 'session_id_6cd16ef4e3eeeac97a2900405ffb5241';
        $fp = fopen($fileName,'r+');
        if (!$fp) {
            return "文件打开失败";
        }
        $lo = flock($fp,LOCK_SH | LOCK_NB);
        if (!$lo) {
            fclose($fp);
            return "锁失败";
        }
        $str = fread($fp,filesize($fileName));
        fclose($fp);
        return $str;
    }

    /**
     * @mapping {"route":"admin/testWrite","mode":"GET"}
     */
    public function testWrite()
    {
        $fileName = ROOT_PATH . DS . 'logs' . DS . 'session' . DS . 'session_id_6cd16ef4e3eeeac97a2900405ffb5241';
        $fp = fopen($fileName,'w+');
        if (!$fp) {
            return "文件打开失败";
        }
        $lo = flock($fp,LOCK_EX);
        if (!$lo) {
            fclose($fp);
            return "锁失败";
        }
        for ($i = 10000; $i--;) {
            echo 1;
            sleep(1);
            fwrite($fp,1);
        }
        fclose($fp);
        return '结束';
    }

    /**
     * @mapping {"route":"admin/testContext","mode":"GET"}
     */
    public function testContext()
    {
        $context =  Coroutine::getContext();
        $context['test'] = 'testVar';
        $context->Con = new TestContext();
        $context->Con->run('main');
        go(function() {
            go(function () {
                $context = Coroutine::getContext();
                $cid = Coroutine::getCid();
                $this->response()->dump(['cid' => $cid,'test' => $context->serialize(),'va' => isset($context['test'])]);
                $context['test'] = '1231';
            });
            $context = Coroutine::getContext(Coroutine::getPcid());
            $cid = Coroutine::getCid();
            $this->response()->dump(['cid' => $cid,'test' => $context->serialize()]);
            Coroutine::sleep(0.2);
            $context->Con->run('c1');
            $context->test = '324';
        });
        Coroutine::sleep(0.02);
        $this->response()->dump('p:' . property_exists($context,'test'));
        return "结束";
    }

    /**
     * @mapping {"route":"admin/sendfile","mode":"GET"}
     */
    public function sendFile()
    {
//        $this->response()->write("fdsfds");
//        $this->response()->write("fsdsd");
//        $this->response()->write("sfd");
        $flag = $this->response()->sendDownloadFile('upload' . DS  . 'images' . DS . '20230908' . DS . '13e4a373f5ed4c603a4989765bff5b5c.jpg','test.png',1);
//        $flag = $this->response()->sendfile(ROOT_PATH . DS . 'public' . DS . 'upload' . DS  . 'images' . DS . '20230908' . DS . '13e4a373f5ed4c603a4989765bff5b5c.jpg');
        if (!$flag) {
            return "发送失败";
        }
    }


    /**
     * @mapping {"route":"admin/coRequestTest","mode":"GET"}
     * @return string
     */
    public function coRequestTest()
    {
        return (new HttpRequest(['ssl' => true]))->get('https://www.yhmgo.com/showp/12120.html')['data'] ?? '';
    }


    /**
     * @mapping {"route":"admin/coBatchRequest","mode":"GET"}
     */
    public function coBatchRequest()
    {
//        $batch = new \work\cor\http\req\BatchCurl();
//        $batch = new \work\cor\http\swl\CoBatchRequest(['ssl' => true]);
//        $batch = new \work\cor\http\req\BatchCurl();
        $batch = new HttpBatchRequest();
        $batch->addGetRequest('http://www.baidu.com');
        $batch->addGetRequest('http://www.baidu.com');
        return  $batch->execute();
    }



    /**
     * @mapping {"route":"admin/obTest","mode":"GET"}
     */
    public function obTest()
    {
        $data = [];
        $channel = new Channel(3);
        ob_start();
        echo "我是父协程\n";
        go(function() use($channel) {
            ob_start();
           echo "我是协程1\n";
           Coroutine\System::sleep(0.01);
           $s = ob_get_clean();
           $channel->push($s);
        });
        go(function() use($channel) {
            ob_start();
            echo "我是协程2\n";
            $s = ob_get_clean();
            $channel->push($s);
        });
        go(function() use($channel) {
            ob_start();
            echo "我是协程3\n";
            $s = ob_get_clean();
            $channel->push($s);
        });
        (function () {
            echo "父协程中的函数调用";
        })();
        for($i = 0; $i < 3; $i++) {
            $data[] = $channel->pop();
        }
        $data[] = ob_get_clean();
        return $data;
    }










    /**
     * 测试生成码
     * @mapping {"route":"admin/testCodeInfo","mode":"GET"}
     */
    public function testCodeInfo()
    {
        $num = 0;

        $list = [];
        for($i = 1;$i <= 2000000; $i++) {
            $info = HelperFun::inviteCode6($i,'0123456789abcdefghijklmnopqrstuvwxyz');
         $res = RedisQuery::sismember('test_invite_code',$info);
         if ($res === false) {
             $re = RedisQuery::sadd('test_invite_code',$info);
             if ($re != true) {
                 break;
             }
         }else {
             $list[] = $i;
             $num++;
         }
        }
        return [ 'number' => $num,'list' => $list,'res' =>$i];
//        return HelperFun::inviteCode6(1232132,'0123456789abcdefghijklmnopqrstuvwxyz');
//        $str = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
//        $bas = str_split($str,1);
//        shuffle($bas);
//        $bas = implode("",$bas);
//        $this->response()->dump($bas);
//        return 'ok';
    }

    /**
     * 测试生成码
     * @mapping {"route":"admin/testApiEcho","mode":"GET"}
     */
    public function testApiEcho()
    {

        $startTime = microtime(true);
        $batch = new HttpBatchRequest(['ssl' => false,'timeout' => 80]);
        for ($j = 0;$j < 50;$j++) {
            $batch->addGetRequest('http://www.baidu.com',[
//                'Connection: Keep-Alive',
//                'Keep-Alive: 300'
            ]);
        }
        $data = $batch->execute();
        Response::dump($data);
        $endTime = microtime(true);
        $costTime = round((($endTime - $startTime) * 1000), 3);
        return 'ok costTime:' . $costTime;
    }

    /**
     * redis lua test
     *  @mapping {"route":"admin/testRedisLua","mode":"GET"}
     */
    public function testRedisLua()
    {
        $csrf = new CsrfToken();
        if (!$csrf->verifyToken()) {
            return "kkc";
        }
        $bloom = new BloomFilterRedis("tess_bloom",["DEKHash","DJBHash"]);
        Response::dump($bloom->exists("haha"));
        $bloom->add("haha");
        return 'ok';
    }


    /**
     *  @mapping {"route":"admin/testTemplate","mode":"GET"}
     */
    public function testTemplate()
    {
        $template = new Template([
            "view_path" => ROOT_PATH . DS . "app" . DS . "admin"
        ]);
        $template->assign("readMessageInfo","哈哈错误了0");
        $template->assign("infoList",[
            ['id' => 1, 'name' => '张三0', 'age'=>18],
            ['id' => 2, 'name' => '李四0', 'age'=>23],
            ['id' => 3, 'name' => '王五0', 'age'=>27],
        ]);
         $template->display("view/error");
        $channel = new Channel(3);
        go(function() use($channel){
            $template = new Template([
                "view_path" => ROOT_PATH . DS . "app" . DS . "admin"
            ]);
            $template->assign("readMessageInfo","哈哈错误了");
            $template->assign("infoList",[
                ['id' => 1, 'name' => '张三', 'age'=>18],
                ['id' => 2, 'name' => '李四', 'age'=>23],
                ['id' => 3, 'name' => '王五', 'age'=>27],
            ]);
            $content = $template->display("view/error");
            $channel->push("push1:" . $content . "push1end");
        });
        go(function() use($channel){
            $template = new Template([
                "view_path" => ROOT_PATH . DS . "app" . DS . "admin"
            ]);
            $template->assign("readMessageInfo","哈哈错111");
            $template->assign("infoList",[
                ['id' => 1, 'name' => '张三1', 'age'=>18],
                ['id' => 2, 'name' => '李四1', 'age'=>23],
                ['id' => 3, 'name' => '王五1', 'age'=>27],
            ]);
            Coroutine::sleep(0.5);
            $content = $template->display("view/error");

            $channel->push("push2:" . $content . "push2end");
        });
        go(function() use($channel){
            $template = new Template([
                "view_path" => ROOT_PATH . DS . "app" . DS . "admin"
            ]);
            $template->assign("readMessageInfo","哈哈错222");
            $template->assign("infoList",[
                ['id' => 1, 'name' => '张三2', 'age'=>18],
                ['id' => 2, 'name' => '李四2', 'age'=>23],
                ['id' => 3, 'name' => '王五2', 'age'=>27],
            ]);
            $content = $template->display("view/error");
            $channel->push("push3:" . $content . "push3end");
        });
        $list = [];
        for($i = 0; $i < 3;$i++) {
            $list[] = $channel->pop();
        }
        return $list;
    }




    /**
     * task任务
     * @mapping {"route":"admin/taskDebug","mode":"ANY"}
     */
    public function taskDebug(): string
    {
        $flag = (new SwlTask(TestTask::class))
            ->init(1,2,3,4)
            ->param(6,7,8)
            ->then(function ($data) {
                echo "success";
                var_dump($data);
            })
            ->catch(function ($data) {
                echo "catch";
                var_dump($data);
            })
            ->asyncRun();
        Response::dump($flag);
        return 'ok';
    }
}