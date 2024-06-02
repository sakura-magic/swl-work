<?php
declare(strict_types=1);

namespace work;

use server\other\Console;
use server\other\ServerTool;
use work\cor\Request;
use work\fusing\CircuitBreaker;
use work\fusing\FusingFace;
use work\traits\SingleInstance;

/**
 * @method null|Route get($routePath, $classPath) static 设定当前的语言
 * @method null|Route post($routePath, $classPath) static 设定当前的语言
 * @method null|Route any($routePath, $classPath) static 设定当前的语言
 * @method null|Route ws($routePath, $classPath) static 设定当前的语言
 * @method null|Route put($routePath, $classPath) static 设定当前的语言
 * @method null|Route delete($routePath, $classPath) static 设定当前的语言
 */
class Route
{
    use SingleInstance;

    private array $routeMessage = [];

    private array $tempRoute = [];
    //group调用栈
    private array $groupStack = [];

    private bool $init = false;

    /**
     * 路由初始化
     * @throws \ReflectionException
     */
    public function init()
    {
        if (!$this->init) {
            $this->init = true;
            $this->buildRoute();
        }
    }


    /**
     * 生成路由信息
     * @return array
     * @throws \ReflectionException
     */
    private function buildRoute(): array
    {
        $catchFlag = !IS_SWOOLE_SERVER && !Config::getInstance()->get('other.debug');
        $path = Config::getInstance()->get('other.routeInfo', '');
        $fileName = $path . 'cache.route';
        if ($catchFlag && ($fileInfo = ServerTool::readFileInfo($fileName,false))) {
            try{
                $fileInfo = unserialize($fileInfo);
            }catch (\Throwable $e) {
                $fileInfo = null;
            }
            if (is_array($fileInfo)) {
                $this->routeMessage = $fileInfo;
                return $this->routeMessage;
            }
        }
        foreach (Config::getInstance()->get('route.controllers', []) as $val) {
            $val = preg_replace("/(\/|\\\\)+/", DS, $val);
            $controllers = $this->everyFile(ROOT_PATH . DS . $val);
            $this->addList($controllers);
        }
        if ($catchFlag) {
            ServerTool::createDir($fileName);
            file_put_contents($fileName, serialize($this->routeMessage));
        }
        return $this->routeMessage;
    }

    /**
     * 打印路由信息
     * @return void
     */
    public function printInfo()
    {
        $header = ['a' => 'namespace', 'b' => 'method', 'c' => 'route', 'd' => 'model', 'e' => 'middleware'];
        $list = [];
        foreach ($this->routeMessage as $key => $value) {
            $middlewareArr = array_map(function ($val) {
                $splitInfo = preg_split('/\/|\\\\/', $val);
                return end($splitInfo);
            }, $value['rule']['middleware'] ?? []);

            $list[] = [
                'a' => $value['namespace'],
                'b' => $value['method'],
                'c' => $value['route'],
                'd' => $value['rule']['mapping']['mode'] ?? '',
                'e' => implode(',', $middlewareArr)
            ];
        }
        $consoleData = Console::tableDump($header, $list, true);
        $workerId = GlobalVariable::getManageVariable('_sys_')->get('workerId', 0);
        $routeInfo = Config::getInstance()->get('other.routeInfo');
        Console::dumpFile($routeInfo . 'route' . $workerId . '.txt', $consoleData);
        Console::dumpFile($routeInfo . 'routeInfo_' . $workerId . '.txt', $this->routeMessage);
    }

    /**
     * 遍历目录下的所有文件
     * @param $dir
     * @param array $list
     * @return array|mixed
     */
    private function everyFile($dir, array $list = [])
    {
        $handle = opendir($dir);
        while ($line = readdir($handle)) {
            if ($line != '.' && $line != '..') {
                if (is_dir($dir . DS . $line)) {
                    $list = $this->everyFile($dir . DS . $line, $list);
                } else {
                    $list[] = $dir . DS . $line;
                }
            }
        }
        // 关闭目录
        closedir($handle);
        return $list;
    }

    /**
     * 遍历指定目录下的注解
     * @param array $list 目录结构
     * @throws \ReflectionException|\Throwable
     */
    private function addList(array $list): void
    {
        foreach ($list as $path) {
            $fp = fopen($path, "r");
            $size = filesize($path);
            if ($size > 0) {
                $str = fread($fp, $size);
                $this->readFileInfo($str, $path);
            }
            fclose($fp);
        }
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function readFileInfo(string $str, string $path): bool
    {
        if (preg_match('/namespace(.*);/i', $str, $comment) === false) {
            return false;
        }
        if (!isset($comment[1])) {
            return false;
        }
        $suffix = substr(strrchr($path, '.'), 1);
        $result = basename($path, "." . $suffix);
        # 获得命名空间地址
        $namespace = trim($comment[1]) . '\\' . $result;
        # 使用注解
        $info = ServerTool::readFileInfo(WORKER_INFO_CONF['noThrowLastErrorFile']);
        if ($info && !empty($info = unserialize($info))) {
            if ($info['file'] === $path || stripos($path, $info['file']) !== false) {
                return false;
            }
        }
        $doc = DocParserFactory::getInstanceObj()->getNote($namespace);
        if (!is_array($doc)) {
            return false;
        }
        foreach ($doc as $key => $val) {
            if (!isset($val['mapping']) || !isset($val['mapping']['route'])) {
                continue;
            }
            $routeKey = $this->getRouteKey($val['mapping']['route']);
            if (!empty($val['fusing']) && is_array($val['fusing'])) {
                $info = $val['fusing'];
                unset($val['fusing']);
                $fallbackArr = explode('@',$info['fallback'] ?? '');
                if (count($fallbackArr) == 2 && (!isset($info['opt']) || in_array($info['opt'],['process']))) {
                    $fusingInfo = [
                        "conf" => $info['conf'] ?? [],
                        "class" => $fallbackArr[0],
                        "method" => $fallbackArr[1]
                    ];
                    $k = md5($namespace . '@' . $key);
                    $val['fusing'] = $this->getFusingOjb($k,(array) $fusingInfo['conf'],$info['opt'] ?? 'process');
                    $val['fusingInfo'] = $fusingInfo;
                }
            }
            $this->routeMessage[$routeKey] = [
                'namespace' => $namespace,
                'method' => $key,
                'route' => $val['mapping']['route'],
                'rule' => $val
            ];
        }
        return true;
    }


    /**
     * 查找路由信息
     * @param string $path
     * @return mixed|null
     */
    public function findRoute(string $path): ?array
    {
        $routeKey = $this->getRouteKey($path);
        if (!empty($routeKey) && isset($this->routeMessage[$routeKey])) {
            return $this->routeMessage[$routeKey];
        }
        $cutting = Config::getInstance()->get('route.cutting');
        $prefix = mb_substr($path, 0, 1);
        if ($cutting !== $prefix) {
            return null;
        }
        $routeKey = $this->getRouteKey(mb_substr($path, 1));
        if (!empty($routeKey) && isset($this->routeMessage[$routeKey])) {
            return $this->routeMessage[$routeKey];
        }
        return null;
    }

    /**
     *
     * @param Request $request
     * @param string $uri
     * @return array
     */
    public function parseUrl(Request $request, string $uri): array
    {
        $routeData = $this->findRoute($uri);
        if ($routeData === null) {
            return ['flag' => false, 'status' => HTTP_SERVER_ERROR["routeNotFound"]["code"] ?? 404, 'content' => HTTP_SERVER_ERROR["routeNotFound"]["msg"] ?? '404 not found'];
        }
        $ruleData = $routeData['rule'];
        //判断请求是否合法
        if (isset($ruleData['mapping']['mode']) && $ruleData['mapping']['mode'] !== 'ANY' && $request->method() != $ruleData['mapping']['mode']) {
            return ['flag' => false, 'status' => HTTP_SERVER_ERROR["routeModeMismatching"]["code"] ?? 404, 'content' => HTTP_SERVER_ERROR["routeModeMismatching"]["msg"] ?? '404 not found'];
        }
        if (isset($ruleData['mapping']['ask']) &&
            (($ruleData['mapping']['ask'] == 'AJAX' && !$request->isAjax()) || ($ruleData['mapping']['ask'] == 'WEB' && $request->isAjax()))
        ) {
            return ['flag' => false, 'status' => 404, 'content' => '404 not found'];
        }
        $requestArg = [];
        if (isset($ruleData['param'])) {
            foreach ($ruleData['param'] as $key => $val) {
                $mode = strtolower($val);
                if (method_exists($request, $mode)) {
                    $requestArg[$key] = $request->{$mode}($key);
                }
            }
        }
        return [
            'flag' => true,
            'class' => $routeData['namespace'],
            'method' => $routeData['method'],
            'middleware' => $ruleData['middleware'] ?? [],
            'verifier' => $ruleData['verifier'] ?? null,
            'argument' => $requestArg,
            'fusing' => $ruleData['fusing'] ?? null,
            'fusingInfo' => $ruleData['fusingInfo'] ?? []
        ];
    }

    /**
     * websocket解析
     * @return array
     */
    public function parseWsUrl(string $uri, array $arguments = []): array
    {
        $routeData = $this->findRoute($uri);
        if ($routeData === null) {
            return ['flag' => false, 'status' => 404, 'content' => '404 not found'];
        }
        $ruleData = $routeData['rule'];
        if ($ruleData['mapping']['mode'] !== 'WS') {
            return ['flag' => false, 'status' => 404, 'content' => '404 not found'];
        }
        return [
            'flag' => true,
            'class' => $routeData['namespace'],
            'method' => $routeData['method'],
            'middleware' => $ruleData['middleware'] ?? [],
            'verifier' => $ruleData['verifier'] ?? null,
            'argument' => $arguments,
            'uri' => $uri,
            'fusing' => $ruleData['fusing'] ?? null,
            'fusingInfo' => $ruleData['fusingInfo'] ?? []
        ];
    }


    /**
     * 设置路由
     * @return $this
     */
    public function routeDefinition(string $method, string $routPath, string $classPath): Route
    {
        $prefixStr = '';
        $namespaceStr = '';
        $groupMiddleware = [];
        $groupParams = [];
        foreach ($this->groupStack as $val) {
            $symbol = '';
            if (!empty($prefixStr) && !empty($val['prefix']) && !(preg_match('/^.*\/$/', $prefixStr) || preg_match('/^\/.*$/', $val['prefix']))) {
                $symbol = '/';
            }
            $prefixStr .= $symbol . $val['prefix'];
            $symbol = '';
            if (!empty($namespaceStr) && !empty($val['namespace']) && !(preg_match('/^.*\\\\$/', $namespaceStr) || preg_match('/^\\\\.*$/', $val['namespace']))) {
                $symbol = '\\';
            }
            $namespaceStr .= $symbol . $val['namespace'];
            $groupMiddleware = array_merge($groupMiddleware, $val['middleware'] ?? []);
            foreach ($val['param'] ?? [] as $k => $v) {
                if (!is_array($v)) {
                    continue;
                }
                foreach ($v as $n) {
                    if (!is_string($n)) {
                        continue;
                    }
                    $groupParams[$n] = strtoupper($k);
                }
            }
        }
        $dismantle = explode('@', $classPath);
        $namespaceParse = reset($dismantle);
        $symbol = '';
        if (!empty($prefixStr) && !empty($routPath) && !(preg_match('/^.*\/$/', $prefixStr) || preg_match('/^\/.*$/', $routPath))) {
            $symbol = '/';
        }
        $routInfo = $prefixStr . $symbol . $routPath;
        $symbol = '';
        if (!empty($namespaceStr) && !empty($namespaceParse) && !(preg_match('/^.*\\\\$/', $namespaceStr) || preg_match('/^\\\\.*$/', $namespaceParse))) {
            $symbol = '\\';
        }
        $namespaceInfo = $namespaceStr . $symbol . $namespaceParse;
        $data = [
            'namespace' => $namespaceInfo,
            'method' => end($dismantle),
            'route' => $routInfo,
            'rule' => [
                'mapping' => [
                    'route' => $routInfo,
                    'mode' => strtoupper($method)
                ]
            ]
        ];
        if (!empty($groupMiddleware)) {
            $data['rule']['middleware'] = $groupMiddleware;
        }
        if (!empty($groupParams)) {
            $data['rule']['param'] = $groupParams;
        }
        $this->tempRoute = $data;
        $this->done();
        return $this;
    }

    /**
     * 载入信息
     * @return void
     */
    private function done(): void
    {
        if (!empty($this->tempRoute)) {
            $routeKey = $this->getRouteKey($this->tempRoute['route']);
            $this->routeMessage[$routeKey] = $this->tempRoute;
        }
    }

    /**
     * 中间件
     * @throws
     */
    public function middleware(array $middleware): ?self
    {
        if (empty($this->tempRoute)) {
            throw new \Exception('route reload config middleware error');
        }
        $this->tempRoute['rule']['middleware'] = array_merge($this->tempRoute['rule']['middleware'] ?? [], $middleware);
        $this->done();
        return $this;
    }

    /**
     * 设定取值
     * @throws \Exception
     */
    public function param(string $mode = 'param', array $arg = []): ?self
    {
        if (empty($this->tempRoute)) {
            throw new \Exception('route reload config middleware error');
        }
        if (!in_array($mode, ['post', 'get', 'param', 'cookie', 'session'])) {
            throw new \Exception('get param mode error');
        }
        foreach ($arg as $value) {
            if (!is_string($value)) {
                continue;
            }
            $this->tempRoute['rule']['param'][$value] = strtoupper($mode);
        }
        $this->done();
        return $this;
    }

    /**
     * 载入验证器
     * @throws \Exception
     */
    public function verifier(array $rule, array $extend = [], ?int $status = null, string $mode = 'json', ?string $event = null): ?self
    {
        if (empty($this->tempRoute)) {
            throw new \Exception('route reload config middleware error');
        }
        $row = ['mode' => $mode, 'extend' => $extend, 'rule' => $rule];
        if (!is_null($status)) {
            $row['status'] = $status;
        }
        if (!is_null($event)) {
            $row['event'] = $event;
        }
        $this->tempRoute['rule']['verifier'] = $row;
        $this->done();
        return $this;
    }

    /**
     * 熔断器
     * @throws \Exception
     */
    public function fusing(string $fallback, int $failThreshold = 1,int $successThreshold = 1,int $timeOut = 1,string $opt = 'process'): Route
    {
        if (empty($this->tempRoute) || empty($this->tempRoute['namespace']) || empty($this->tempRoute['method'])) {
            throw new \Exception('route reload config method error');
        }
        $info = explode('@',$fallback);
        if (count($info) != 2) {
            throw new \Exception('fallback error');
        }
        $conf = [
            "timeOut" => $timeOut,
            "failThreshold" => $failThreshold,
            "successThreshold" => $successThreshold
        ];
        $key = md5($this->tempRoute['namespace'] . '@' . $this->tempRoute['method']);
        $this->tempRoute['rule']['fusingInfo'] = [
            'conf' => $conf,
            'class' => $info[0],
            'method' => $info[1]
        ];
        $this->tempRoute['rule']['fusing'] = $this->getFusingOjb($key,$conf,$opt);
        $this->done();
        return $this;
    }

    /**
     * 获取熔断器
     * @param string $key
     * @param array $conf
     */
    private function getFusingOjb(string $key,array $conf,string $opt = 'process') : FusingFace
    {
        $obj = null;
        switch ($opt) {
            case 'process' : $obj = new CircuitBreaker($key,$conf);break;
            default :
                throw new \Exception('fusing opt argument error');
        }
        return $obj;
    }


    /**
     * 静态调用
     * @param $name
     * @param $arguments
     * @return null|Route
     */
    public static function __callStatic($name, $arguments): ?self
    {
        if (!in_array($name, ['get', 'post', 'any', 'ws', 'put', 'delete'])) {
            return null;
        }
        return self::getInstance()->routeDefinition($name, $arguments[0], $arguments[1]);
    }

    /**
     * 构建前置条件
     * @param array $rule
     * @param \Closure $fun
     * @return void
     */
    public static function group(array $rule, \Closure $fun)
    {
        self::getInstance()->groupInfo($rule, $fun);
    }

    /**
     * group信息
     * @return void
     */
    public function groupInfo(array $rule, \Closure $fun): void
    {
        $data = [
            'prefix' => strval($rule['prefix'] ?? ''),
            'namespace' => strval($rule['namespace'] ?? ''),
            'middleware' => [],
            'param' => isset($rule['param']) && is_array($rule['param']) ? $rule['param'] : []
        ];
        if (!empty($rule['middleware'])) {
            $data['middleware'] = is_array($rule['middleware']) ? $rule['middleware'] : [$rule['middleware']];
        }
        $this->groupStack[] = $data;
        $fun();
        array_pop($this->groupStack);
    }

    /**
     * @param string $uri
     * @return string
     */
    public function getRouteKey(string $uri): string
    {
        if (empty($uri)) {
            return $uri;
        }
        return (string)preg_replace('/\/|\\\\/', '@', $uri);
    }
}