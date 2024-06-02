<?php
declare(strict_types=1);
/**
 * 获取配置实例
 */
if (!function_exists('config')) {
    function config($str = null): \work\Config
    {
        return \work\Config::getInstance($str);
    }
}

/**
 * 获取evns实例
 */
if (!function_exists('envs')) {
    function envs(string $str = 'default'): \work\Env
    {
        return \work\Env::getInstance($str);
    }
}