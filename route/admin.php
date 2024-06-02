<?php
use work\Route;
Route::get('admin/hl','app\admin\controller\Test@index');
Route::get('admin/channel','app\admin\controller\Test@channalTest')
    ->fusing(\app\admin\controller\Test::class . '@' . 'test',2,3,2);

Route::group(['prefix' => 'admin','namespace' => 'app\admin\controller','middleware' => 'app\api\middleware\Test','param' => ['get' => ['id','idcore']]],function(){
    Route::get('lls','Test@wwe')
        ->param('post',['ui'])
//        ->middleware(['app\task\TaskTask'])
        ->param('post',['lo'])
        ->verifier(['id' => 'require|number']);
});
//Route::get('admin/channelkk','app\admin\controller\Test@channalTest');

Route::get('admin/redisLuaTest','app\admin\controller\Test@testRedisLua');