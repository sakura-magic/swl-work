<?php
declare(strict_types=1);
const ROOT_PATH = __DIR__;
const DS = DIRECTORY_SEPARATOR;
const IS_CLI = PHP_SAPI == 'cli';
require_once 'vendor/autoload.php';
$cmd = new \server\Initiator();
if (!$cmd->readCommand()) {
    throw new Exception('command exit');
}
$cmd->runCmd();
