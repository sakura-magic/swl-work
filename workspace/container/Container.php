<?php
declare(strict_types=1);

namespace work\container;
class Container
{
    /**
     * 别名信息
     * @var array
     */
    protected array $aliases = [];

    /**
     * build 栈
     * @var array
     */
    protected array $buildStack = [];

    /**
     * 绑定上下文映射
     * @var array
     */
    protected array $contextual = [];

    /**
     * 由抽象名注册的别名
     * @var array
     */
    protected array $abstractAliases = [];
    /**
     * 单例
     * @var array
     */
    protected array $instances = [];
    /**
     * 关于
     * @var array
     */
    protected array $with = [];

    /**
     * 容器绑定
     * @var array
     */
    protected array $bindings = [];
    /**
     * 绑定回调
     * @var array
     */
    protected array $reboundCallbacks = [];

    /**
     * 方法绑定
     * @var array
     */
    protected array $methodBindings = [];

    /**
     * 记录创建顺序栈，用于有序销毁
     * @var array
     */
    protected array $buildInstancesStack = [];

    /**
     * 从容器中解析给定的类型
     * @param $abstract
     * @param array $parameters
     * @throws \Exception
     */
    public function make($abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * 解依赖
     * @param $abstract
     * @param array $parameters
     * @return mixed|object
     * @throws \Exception
     */
    protected function resolve($abstract, array $parameters = [])
    {
        $concrete = $this->getContextualConcrete($abstract);
        $needsContextualBuild = !empty($parameters) || !is_null($concrete);
        if (isset($this->instances[$abstract]) && !$needsContextualBuild) { //在单例中并且无上下文无参数
            return $this->instances[$abstract];
        }

        $this->with[] = $parameters;

        if (is_null($concrete)) {
            $concrete = $this->getConcrete($abstract);
        }


        if ($this->isBuildable($concrete, $abstract)) {

            $object = $this->build($concrete);

        } else {
            $object = $this->make($concrete);
        }

        if ($this->isShared($abstract) && !$needsContextualBuild) {
            $this->instances[$abstract] = $object; //设置单例
            if (!in_array($abstract,$this->buildInstancesStack)) {
                $this->buildInstancesStack[] = $abstract;
            }
        }
        array_pop($this->with);
        return $object;
    }

    /**
     * 判断是否为共享
     * @param string $abstract
     */
    public function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared'] === true);
    }


    /**
     *  实例化
     * @param $concrete
     * @throws \Exception
     */
    public function build($concrete)
    {
        if ($concrete instanceof \Closure) { //如果是函数直接调用
            return $concrete($this, $this->getLastParameterOverride());
        }
        try {
            $reflector = new \ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new \Exception("Target class [$concrete] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) { //如果类不可实例化
            $this->notInstantiable($concrete);
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor(); // 获取构造函数
        if (is_null($constructor)) { //如果没有构造函数
            array_pop($this->buildStack);
            return new $concrete;//直接new
        }
        $dependencies = $constructor->getParameters(); //获取构造函数的参数
        try {
            $instances = $this->resolveDependencies($dependencies);
        } catch (\Exception $e) {
            array_pop($this->buildStack);
            throw $e;
        }
        array_pop($this->buildStack);
        return $reflector->newInstanceArgs($instances);
    }

    /**
     * 解析依赖参数
     * @param array $dependencies
     */
    protected function resolveDependencies(array $dependencies): array
    {
        $results = [];
        foreach ($dependencies as $dependency) {
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);
                continue;
            }
            $result = is_null($this->getParameterClassName($dependency)) ? $this->resolvePrimitive($dependency) : $this->resolveClass($dependency);

            if ($dependency->isVariadic()) {
                $results = array_merge($results, $result);
            } else {
                $results[] = $result;
            }
        }
        return $results;
    }

    /**
     * 解析类
     * @param \ReflectionParameter $parameter
     * @throws \ReflectionException
     */
    protected function resolveClass(\ReflectionParameter $parameter)
    {
        try {
            return $parameter->isVariadic() ? $this->resolveVariadicClass($parameter) : $this->make($this->getParameterClassName($parameter));
        } catch (\Exception $e) {
            if ($parameter->isDefaultValueAvailable()) {
                array_pop($this->with);
                return $parameter->getDefaultValue();
            }
            if ($parameter->isVariadic()) {
                array_pop($this->with);
                return [];
            }
            throw $e;
        }
    }


    /**
     * 解析可变的依赖类
     * @param \ReflectionParameter $parameter
     */
    protected function resolveVariadicClass(\ReflectionParameter $parameter)
    {
        $className = $this->getParameterClassName($parameter);

        $abstract = $this->getAlias($className);

        if (!is_array($concrete = $this->getContextualConcrete($abstract))) {
            return $this->make($className);
        }
        return array_map(fn($abstract) => $this->resolve($abstract), $concrete);
    }

    /**
     * 解析原生参数
     * @param \ReflectionParameter $parameter
     */
    protected function resolvePrimitive(\ReflectionParameter $parameter)
    {
        if (!is_null($concrete = $this->getContextualConcrete('$' . $parameter->getName()))) {
            return $concrete instanceof \Closure ? $concrete($this) : $concrete;
        }

        if ($parameter->isDefaultValueAvailable()) { //如果有默认值
            return $parameter->getDefaultValue();//获取默认值
        }

        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new \Exception($message);
    }


    /**
     * 获取参数的类名称
     * @param \ReflectionParameter $parameter
     */
    public function getParameterClassName(\ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) { //$type有问题或如果是php内置类型
            return null;
        }
        $name = $type->getName(); //获取名称

        if (!is_null($class = $parameter->getDeclaringClass())) { //获取反射类名
            if ($name === 'self') { //是自身
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) { //如果是父类获取父类
                return $parent->getName();
            }
        }
        return $name;
    }


    /**
     * 判断依赖参数是否存在传入的参数
     * @param \ReflectionParameter $dependency
     */
    protected function hasParameterOverride(\ReflectionParameter $dependency): bool
    {
        return array_key_exists(
            $dependency->name,
            $this->getLastParameterOverride()
        );
    }

    /**
     * 获取依赖参数
     * @param \ReflectionParameter $dependency
     * @return mixed
     */
    protected function getParameterOverride(\ReflectionParameter $dependency)
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }

    /**
     * 抛出异常类不可实例化
     * @param $concrete
     * @throws \Exception
     */
    protected function notInstantiable($concrete)
    {
        if (!empty($this->buildStack)) {
            $previous = implode(', ', $this->buildStack);
            $message = "Target [$concrete] is not instantiable while building [$previous].";
        } else {
            $message = "Target [$concrete] is not instantiable.";
        }
        throw new \Exception($message);
    }


    /**
     * 获取当前最后参数
     * @return array
     */
    protected function getLastParameterOverride(): array
    {
        return count($this->with) ? end($this->with) : [];
    }


    /**
     * 是否可构建
     * @param $concrete
     * @param $abstract
     * @return bool
     */
    protected function isBuildable($concrete, $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof \Closure;
    }

    /**
     * 返回类的绑定关联
     * @param $abstract
     * @return mixed
     */
    protected function getConcrete($abstract)
    {
        if (isset($this->bindings[$abstract])) { //判断绑定容器是否存在
            $concrete = $this->bindings[$abstract]['concrete'];

            if (!$concrete instanceof \Closure) {
                if (!is_string($concrete)) {
                    throw new \TypeError(self::class . '::bind(): Argument #2 ($concrete) must be of type Closure|string|null');
                }
                $concrete = $this->getClosure($abstract, $concrete);
            }
            return $concrete;
        }
        return $abstract;
    }


    /**
     * 具体上下文的绑定
     * @param $abstract
     */
    protected function getContextualConcrete($abstract)
    {
        if (!is_null($binding = $this->findInContextualBindings($abstract))) {
            return $binding;
        }
        /**
         * 如果为空返回null
         */
        if (empty($this->abstractAliases[$abstract])) {
            return null;
        }

        foreach ($this->abstractAliases[$abstract] as $alias) {
            if (!is_null($binding = $this->findInContextualBindings($alias))) {
                return $binding;
            }
        }
        return null;
    }

    /**
     * 栈中最后一个内容的绑定映射是否存在$abstract返回
     * @param  $abstract
     * @return mixed|null
     */
    protected function findInContextualBindings($abstract)
    {
        return $this->contextual[end($this->buildStack)][$abstract] ?? null;
    }

    /**
     * 获取别名信息
     * @param $abstract
     * @return mixed
     */
    public function getAlias($abstract)
    {
        return isset($this->aliases[$abstract])
            ? $this->getAlias($this->aliases[$abstract])
            : $abstract;
    }

    /**
     * 注册单例
     * @param string $abstract
     * @param Object $instance
     * @return object
     */
    public function instance(string $abstract, object $instance): object
    {
        $this->removeAbstractAlias($abstract);

        $isBound = $this->bound($abstract);

        unset($this->aliases[$abstract]);

        $this->instances[$abstract] = $instance;//会导致内存泄漏
        if ($isBound) {
            $this->rebound($abstract);
        }
        if (!in_array($abstract,$this->buildInstancesStack)) {
            $this->buildInstancesStack[] = $abstract;
        }
        return $instance;
    }

    /**
     * 从上下文绑定别名缓存中删除别名。
     * @param string $searched
     */
    protected function removeAbstractAlias(string $searched): void
    {
        if (!isset($this->aliases[$searched])) {
            return;
        }
        foreach ($this->abstractAliases as $abstract => $aliases) {
            foreach ($aliases as $index => $alias) {
                if ($alias == $searched) {
                    unset($this->abstractAliases[$abstract][$index]);
                }
            }
        }
    }

    /**
     * 确定给定的抽象类型是否已被绑定。
     * @param string $abstract
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]) || isset($this->aliases[$abstract]);
    }

    /**
     * 获取别名
     * @param string $name
     */
    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }

    /**
     * 绑定
     * @param string $abstract
     * @param null $concrete
     * @param false $shared
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (!$concrete instanceof \Closure) {
            if (!is_string($concrete)) {
                throw new \TypeError(self::class . '::bind(): Argument #2 ($concrete) must be of type Closure|string|null');
            }
            /**
             * todo 容器实例被存储到manage类成员,记录到swoole Coroutine::Coroutine::getContext()()作为根
             * todo 经过压测发现在容器内的回调方法被引用后无法被gc掉
             */
//            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');

        if ($this->resolved($abstract)) {
            $this->rebound($abstract);
        }

    }

    /**
     * 生成闭包函数体
     * @param string $abstract
     * @param string $concrete
     * @return \Closure
     */
    protected function getClosure(string $abstract, string $concrete): \Closure
    {
        return function (Container $container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete) {
                return $container->build($concrete);
            }
            return $container->resolve(
                $concrete, $parameters
            );
        };
    }

    /**
     * @param string $abstract
     * @return bool
     */
    public function resolved(string $abstract): bool
    {
        if ($this->isAlias($abstract)) {
            $abstract = $this->getAlias($abstract);
        }
        return isset($this->instances[$abstract]);
    }

    /**
     * 绑定
     * @param $abstract
     */
    protected function rebound($abstract)
    {
        $instance = $this->make($abstract);

        foreach ($this->getReboundCallbacks($abstract) as $callback) {
            call_user_func($callback, $this, $instance);
        }
    }

    /**
     * 绑定回调
     * @param $abstract
     * @return array|mixed
     */
    protected function getReboundCallbacks($abstract)
    {
        return $this->reboundCallbacks[$abstract] ?? [];
    }

    /**
     * 销毁内容
     */
    public function flush()
    {
        $this->removeInstance();
        $this->buildInstancesStack = [];
        $this->aliases = [];
        $this->bindings = [];
        $this->instances = [];
        $this->abstractAliases = [];
    }

    /**
     * 容器释放
     */
    protected function removeInstance(int $num = 3)
    {
        if ($num <= 0) {
            return;
        }
        try {
            foreach (array_reverse($this->buildInstancesStack) as $val) {
                if (isset($this->instances[$val])) {
                    unset($this->instances[$val]);
                }
            }
        } catch (\Error | \Exception | \Throwable $e) { //防止释放时有析构函数的处理
            $this->removeInstance($num - 1);
        }
    }

    /**
     * 判断是否有方法绑定
     * @param string $method
     * @return bool
     */
    public function hasMethodBinding(string $method): bool
    {
        return isset($this->methodBindings[$method]);
    }

    /**
     * 绑定方法
     * @param string|array $method
     * @param \Closure $callback
     */
    public function bindMethod($method, \Closure $callback)
    {
        $this->methodBindings[$this->parseBindMethod($method)] = $callback;
    }

    /**
     * 解析绑定方法
     * @param $method
     * @return string
     */
    protected function parseBindMethod($method): string
    {
        if (is_array($method)) {
            return $method[0] . '@' . $method[1];
        }
        return $method;
    }

    /**
     * 调用绑定的方法
     * @param string $method
     * @param Object $instance
     */
    public function callMethodBinding(string $method, object $instance)
    {
        return call_user_func($this->methodBindings[$method], $instance, $this);
    }

    /**
     * 向容器添加上下文绑定。
     * @param string $concrete
     * @param string $abstract
     * @param \Closure|string $implementation
     * @return void
     */
    public function addContextualBinding(string $concrete, string $abstract, $implementation)
    {
        $this->contextual[$concrete][$this->getAlias($abstract)] = $implementation;
    }

    /**
     * 别名
     * @param $abstract
     * @param $alias
     * @throws \Exception
     */
    public function alias($abstract, $alias)
    {
        if ($alias === $abstract) {
            throw new \Exception("[{$abstract}] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;

        $this->abstractAliases[$abstract][] = $alias;
    }

    /**
     * 释放时调用
     */
    public function __destruct()
    {
        $this->flush();
    }
}