<?php
declare(strict_types=1);
const COMMON_MSG = [
    "sysError" => "The system is busy,please try again later"
];


//服务异常码
const SERVER_ERROR = [
    "success" => 0,
    "start" => -9000,
    "stop" => -9001,
    "reload" => -9002
];


//http服务
const HTTP_SERVER_ERROR = [
    "emptyUri" => [
        "code" => 403,
        "msg" => "uri is empty"
    ],
    "routeNotFound" => [
        "code" => 404,
        "msg" => "404 not found"
    ],
    "requestIco" => [
        "code" => 404,
        "msg" => "404 not found"
    ],
    "logicError" => [
        "code" => 500,
        "msg" => COMMON_MSG["sysError"]
    ],
    "routeModeMismatching" => [
        "code" => 404,
        "msg" => "404 not found"
    ],
    "routeAskMismatching" => [
        "code" => 404,
        "msg" => "404 not found"
    ]
];