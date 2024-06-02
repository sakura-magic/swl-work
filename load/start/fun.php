<?php
declare(strict_types=1);
/**
 * 设置进程名
 */
if (!function_exists('processName')) {
    function processName($name): bool
    {
        if (!function_exists('cli_set_process_title')) {
            return swoole_set_process_name($name);
        }
        return cli_set_process_title($name);
    }
}