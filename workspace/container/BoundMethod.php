<?php
declare(strict_types=1);

namespace work\container;

use http\Exception\InvalidArgumentException;

class BoundMethod
{
    /**
     *  解析方法
     */
    public static function resolveMethod(Container $container, $callback, array $parameters = [], ?string $defaultMethod = null)
    {
        if (is_string($callback) && !$defaultMethod && method_exists($callback, '__invoke')) {
            $defaultMethod = '__invoke';
        }

        if (static::isCallableWithAtSign($callback) || $defaultMethod) {
            return static::callClass($container, $callback, $parameters, $defaultMethod);
        }

        return static::callBoundMethod($container, $callback,
            fn() => $callback(...array_values(static::getMethodDependencies($container, $callback, $parameters))));
    }

    /**
     * @param Container $container
     * @param $callback
     * @param array $parameters
     */
    protected static function getMethodDependencies(Container $container, $callback, array $parameters = []): array
    {
        $dependencies = [];

        foreach (static::getCallReflector($callback)->getParameters() as $parameter) {
            static::addDependencyForCallParameter($container, $parameter, $parameters, $dependencies);
        }

        return array_merge($dependencies, array_values($parameters));
    }


    /**
     * 解析callback如果
     * @param $callback
     * @return \ReflectionFunction|\ReflectionMethod
     * @throws \ReflectionException
     */
    protected static function getCallReflector($callback)
    {
        if (is_string($callback) && strpos($callback, '::') !== false) {
            $callback = explode('::', $callback);
        } else if (is_object($callback) && !$callback instanceof \Closure) {
            $callback = [$callback, '__invoke'];
        }
        return is_array($callback) ?
            new \ReflectionMethod($callback[0], $callback[1])
            : new \ReflectionFunction($callback);
    }

    /**
     * @param Container $container
     * @param \ReflectionParameter $parameter
     * @param array $parameters
     * @param $dependencies
     */
    protected static function addDependencyForCallParameter(
        Container $container, \ReflectionParameter $parameter, array &$parameters, &$dependencies): void
    {
        $typeInfo = $parameter->getType();
        if (array_key_exists($paramName = $parameter->getName(), $parameters)) { //判断参数是否在param
            $paramVal = $parameters[$paramName];
            if ($typeInfo instanceof \ReflectionNamedType) {
                switch ($typeInfo->getName()) {
                    case "string" :
                        $paramVal = is_string($paramVal) ? $paramVal : (string) $paramVal;
                        break;
                    case "int" :
                        $paramVal = is_numeric($paramVal) ? (int) $paramVal : $paramVal;
                        break;
                    case "bool" :
                        $paramVal = is_bool($paramVal) ? $paramVal : (bool) $paramVal;
                        break;
                    case "float" :
                        $paramVal = is_numeric($paramVal) ? (float) $paramVal : $paramVal;
                        break;
                }
            }
            $dependencies[] = $paramVal;
        } else if (!is_null($className = static::getParameterClassName($parameter))) { //获取类名
            if (array_key_exists($className, $parameters)) {
                $dependencies[] = $parameters[$className];
                unset($parameters[$className]);
            } else {
                if ($parameter->isVariadic()) {
                    $variadicDependencies = $container->make($className);
                    $dependencies = array_merge($dependencies, is_array($variadicDependencies) ? $variadicDependencies : [$variadicDependencies]);
                } else {
                    $dependencies[] = $container->make($className);
                }
            }
        } else if ($parameter->isDefaultValueAvailable()) {
            $dependencies[] = $parameter->getDefaultValue();
        } else if (!$parameter->isOptional() && !array_key_exists($paramName, $parameters)) {
            $message = "Unable to resolve dependency [{$parameter}] in class {$parameter->getDeclaringClass()->getName()}";
            throw new \Exception($message);
        }

    }


    /**
     * 获取参数的类名称
     * @param \ReflectionParameter $parameter
     */
    public static function getParameterClassName(\ReflectionParameter $parameter): ?string
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
     * 调用方法
     * @param Container $container
     * @param \Closure|array $callback
     * @param $default
     */
    protected static function callBoundMethod(Container $container, $callback, $default)
    {
        if (!is_array($callback)) {
            return static::unwrapIfClosure($default);
        }
        $method = static::normalizeMethod($callback);

        if ($container->hasMethodBinding($method)) {
            return $container->callMethodBinding($method, $callback[0]);
        }
        return static::unwrapIfClosure($default);
    }

    /**
     * 返回给定值的默认值。
     * @param $value
     * @return mixed
     */
    public static function unwrapIfClosure($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }

    /**
     * 普通方法
     * @param $callback
     * @return string
     */
    protected static function normalizeMethod($callback): string
    {
        $class = is_string($callback[0]) ? $callback[0] : get_class($callback[0]);

        return "{$class}@{$callback[1]}";
    }

    /**
     * 判断调用是否为类方法
     * @param $callback
     * @return bool
     */
    protected static function isCallableWithAtSign($callback): bool
    {
        return is_string($callback) && strpos($callback, '@') !== false;
    }

    /**
     * 类方法调用
     * @param Container $container
     * @param string $target
     * @param array $parameters
     * @param string|null $defaultMethod
     * @return false|mixed
     */
    protected static function callClass(
        Container $container, string $target, array $parameters = [], ?string $defaultMethod = null)
    {
        $segments = explode('@', $target);

        $method = count($segments) === 2 ? $segments[1] : $defaultMethod;

        if (is_null($method)) {
            throw new InvalidArgumentException('Method not provided.');
        }
        return static::resolveMethod($container, [$container->make($segments[0]), $method], $parameters);
    }
}