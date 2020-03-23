<?php
use think\Route;
Route::get('login$','api/Index/login');
Route::post('login$','api/Index/login');
Route::get('center$','api/Index/center');
Route::group('api',function(){
    Route::group('v1',function(){
        Route::get('home','api/Index/home');
        Route::get('/template$','api/Template/index');
        Route::get('/template/:id','api/Template/read');
        Route::put('/template/:id','api/Template/update');
        Route::post('/template','api/Template/save');
        Route::delete('/template','api/Template/del');
        Route::get('/template/design/:id','api/Template/getDetail');

        Route::post('/templateField','api/TemplateField/save');
        Route::delete('/templateField','api/TemplateField/del');

        Route::get('/project$','api/Project/index');
        Route::get('/project/:id','api/Project/read');
        Route::put('/project/:id','api/Project/update');
        Route::post('/project$','api/Project/save');
        Route::delete('/project$','api/Project/updateStatus');
        Route::get('/project/design/:id$','api/Project/getDetail');
        Route::post('/project/flow$','api/Project/addFlow');
        Route::delete('/project/flow$','api/Project/delFlow');
        

        Route::get('/data$', 'api/ProjectData/index');
        Route::delete('/data$', 'api/ProjectData/del');
        Route::get('/down/:id', 'api/ProjectData/down');

        Route::get('/role$','api/Role/index');
        Route::get('/role/:id','api/Role/read');
        Route::put('/role/:id','api/Role/update');
        Route::post('/role','api/Role/save');
        Route::delete('/role','api/Role/del');

        Route::get('/admin$','api/Admin/index');
        Route::get('/admin/:uuid','api/Admin/read');
        Route::put('/admin/:uuid','api/Admin/update');
        Route::post('/admin','api/Admin/save');
        Route::delete('/admin','api/Admin/del');

        Route::get('/setting','api/Setting/index');
        Route::post('/setting','api/Setting/save');

        //查看数据详情
        Route::get('/data/view/:id$', 'api/ProjectData/view');
        //添加数据
        Route::get('/data/add$', 'api/ProjectData/add');
        Route::get('/data/edit/:id$', 'api/ProjectData/add?did=:id');
        //保存数据
        Route::post('/data/add$', 'api/ProjectData/save');
        
        //检测更新模板字段数
        Route::get('/checkField','api/Check/checkFields');

    });
});
Route::group('msg',function(){
    Route::group('v1',function(){
        Route::get('msg','msg/Msg/list');
        Route::post('msg','msg/Msg/save');
    });
});

Route::get('/time',function(){
    return time();
});

Route::get('/:any','api/Index/Index');

return [
    '__pattern__' => [
        'name' => '/[\x{4e00}-\x{9fa5}\w]+/u',
        'id'=>'/^[1-9]\d*?$/',
        'ids'=>'/^[1-9]\d*?(,[1-9]\d*?)*$/',
        'uuid'=>'/^[-\da-f]+$/',
        'any'=>'/^.*/',
    ],
//    '[hello]'     => [
//        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
//        ':name' => ['index/hello', ['method' => 'post']],
//    ],

];
