<?php
use work\Route;
Route::group(["namespace" => "\app\socket\controller","middleware" => \app\socket\middleware\CheckLogin::class],function (){
    Route::ws('heart_check',"\Com@heartCheck");
    Route::ws("send_msg","\Test@sendMsg")
        ->param('param',['fromUser','toUser','sendInfo'])
        ->verifier([
            "fromUser|发送者" => "require|min:2|max:64",
            "toUser|接受者" => "require|min:2|max:64",
            "sendInfo|消息" => "require|min:1|max:512"
        ],[
            "code" => -1003,
            "msg" => '{$err}'
        ]);
});
Route::ws('heart_check','\app\socket\controller\Com@heartCheck')->middleware([\app\socket\middleware\CheckLogin::class]);