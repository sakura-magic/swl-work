<?php
declare(strict_types=1);

namespace work\cor;

use Closure;
use work\container\Container;
use work\HelperFun;

/**
 * 流水线调用
 * Class PipeLine
 * @package work\cor
 */
class PipeLine
{
    /**
     * @var string|array|object
     */
    protected $passable;
    //中间件执行载入匿名函数
    protected array $pipes = [];
    //中间件执行方法
    protected string $method = 'handle';

    protected ?Container $container;


    public function __construct(?Container $container = null)
    {
        if (is_null($container)) {
            $container = HelperFun::getContainer();
        }
        $this->container = $container;
    }

    //请求参数
    public function send($passable): self
    {
        $this->passable = $passable;
        return $this;
    }

    //载入匿名函数内容就是中间件的内容
    public function through(?array $pipes): self
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();
        return $this;
    }

    public function invokMethod($method): self
    {
        $this->method = $method;
        return $this;
    }

    //执行then方法
    public function then(Closure $destination)
    {

        $pipeline = array_reduce(
        //迭代数组
            array_reverse($this->pipes),
            //迭代执行方法
            $this->carry(),
            //initial初始参数
            function ($passable) use ($destination) {
                //$passable是接受的形参，而destination是上文的参数
                return $destination($passable);//执行传入then中的函数
            });
        return $pipeline($this->passable);
    }


    protected function carry(): \Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if ($pipe instanceof Closure) {
                    return $pipe($passable, $stack);
                } else if (!is_object($pipe)) {
                    list($name, $parameters) = $this->parsePipeString($pipe);
                    $pipe = $this->container->make($name);
                    $parameters = array_merge([$passable, $stack], $parameters);
                } else {
                    $parameters = [$passable, $stack];
                }
                return $pipe->{$this->method}(...$parameters);
            };
        };
    }

    /**
     * 获取中间件对象
     *
     * @param string $pipe
     * @return array
     */
    protected function parsePipeString(string $pipe): array
    {
        list($name, $parameters) = array_pad(explode(':', $pipe, 2), 2, []);
        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }
        return [$name, $parameters];
    }


}