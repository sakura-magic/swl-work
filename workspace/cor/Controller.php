<?php
declare(strict_types=1);

namespace work\cor;

use app\task\Template;
use server\other\ServerTool;
use Swoole\Coroutine;
use Swoole\Server;
use work\Config;
use work\Container;
use work\HelperFun;
use work\SwlBase;

class Controller
{
    private ?int $coroutineUid = null;
    protected ?string $class = null;
    protected ?string $viewPath = null;
    private array $viewArg = [];

    /**
     * 实例前操作
     */
    public function __construct()
    {
        $this->coroutineUid = SwlBase::inCoroutine() ? SwlBase::getCoroutineId() : -1;
        $this->class = get_class($this);
        $parse = preg_split("/\\\\/", $this->class);
        $pathArr = [];
        foreach ($parse as $value) {
            if (empty($value) || !is_string($value)) {
                continue;
            }
            $value = strtolower($value);
            if ($value == 'controller') {
                break;
            }
            $pathArr[] = $value;
        }
        $pathArr[] = 'view';
        $this->viewPath = implode(DS, $pathArr);
        $this->initialize();
    }

    /**
     * 获取request
     * @return Request
     */
    public function request(): ?Request
    {
        if (IS_SWOOLE_SERVER && SwlBase::inCoroutine() && Coroutine::getCid() != $this->coroutineUid && !SwlBase::parentCoroutineExist()) {
            return null;
        }
        $request = HelperFun::getContainer($this->coroutineUid)->make(Request::class);
        if (!HelperFun::getContainer($this->coroutineUid)->isShared(Request::class)) {
            HelperFun::getContainer($this->coroutineUid)->instance(Request::class, $request);
        }
        if (!$request instanceof Request) {
            return null;
        }
        return $request;
    }

    /**
     * 获取响应对象
     * @return Response
     */
    public function response(): ?Response
    {
        if (IS_SWOOLE_SERVER && SwlBase::inCoroutine() && Coroutine::getCid() != $this->coroutineUid && !SwlBase::parentCoroutineExist()) {
            return null;
        }
        $response = HelperFun::getContainer($this->coroutineUid)->make(Response::class);
        if (!HelperFun::getContainer($this->coroutineUid)->isShared(Response::class)) {
            HelperFun::getContainer($this->coroutineUid)->instance(Response::class, $response);
        }
        if (!$response instanceof Response) {
            return null;
        }
        return $response;
    }

    /**
     * @param array $data
     * @return null
     */
    public function json(array $data = [])
    {
        $this->response()->sendJson($data);
        return null;
    }

    /**
     * @param $message
     * @return null
     */
    public function dump($message)
    {
        $this->response()->dump($message);
        return null;
    }

    /**
     * 配置模板变量
     * @param string $key
     * @param mixed $data
     * @return void
     */
    public function assign(string $key, $data)
    {
        $this->viewArg[$key] = $data;
    }

    /**
     * 带参数模板渲染
     * @param string $dom
     * @param array $arg
     * @return false|string
     */
    public function fetch(string $dom, array $arg = [])
    {
        $this->viewArg = array_merge($this->viewArg, $arg);
        return $this->display($dom);
    }

    /**
     * 模板渲染
     * @param string $dom
     * @return false|string
     */
    public function display(string $dom)
    {
        $template = new \work\cor\Template([
            "view_path" => ROOT_PATH . DS . $this->viewPath,
            "default_filter" => "htmlspecialchars"
        ]);
        $data = Config::getInstance()->get('template.globalVariable');
        return $template->fetch($dom,array_merge($data,$this->viewArg));
    }

    /**
     * 获取服务实例
     * @return array|false|string
     */
    private function getServer(): Server
    {
        return ServerTool::getServer()->getSever();
    }

    //初始化调用
    public function initialize()
    {
    }
}