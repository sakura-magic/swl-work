<?php
declare(strict_types=1);

namespace work\cor\facade;

use Swoole\Coroutine;
use work\CoLifeVariable;
use work\HelperFun;

class Facade
{

    protected static bool $instance = false;


    public static function initCreate(...$arg)
    {
        return static::createFacade(null, $arg);
    }

    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass(): ?string
    {
        return null;
    }

    /**
     * @param string $class
     * @param array $args
     * @return mixed|null
     */
    protected static function createFacade(?string $class = null, array $args = [])
    {
        $class = $class ?: static::class;

        $facadeClass = static::getFacadeClass();

        if ($facadeClass) {
            $class = $facadeClass;
        }
        $container = HelperFun::getContainer();
        $project = $container->make($class, $args);
        if (static::$instance === true && !$container->isShared($class)) {
            $container->instance($class,$project);
        }
        return $project;
    }


    // 调用实际类的方法
    public static function __callStatic($method, $params)
    {
        return call_user_func_array([static::createFacade(), $method], $params);
    }
}