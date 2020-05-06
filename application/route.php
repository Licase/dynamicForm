<?php
use think\Route;
Route::get('login$','api/Index/login');
Route::post('login$','api/Index/login');
Route::post('logout$','api/Index/logout');
Route::get('center$','api/Index/center');
Route::group('api',function(){
    Route::group('v1',function(){
        Route::get('home$','api/Home/index');

        Route::post('modifyPwd$','api/Admin/modifyPwd');

        //模板管理
        Route::get('/template$','api/Template/index');
        Route::get('/template/:id','api/Template/read');
        Route::put('/template/:id','api/Template/update');
        Route::post('/template','api/Template/save');
        Route::delete('/template','api/Template/del');
        Route::get('/template/design/:id','api/Template/getDetail');
        Route::get('/templateField/:id$','api/TemplateField/read');
        Route::post('/templateField$','api/TemplateField/save');
        Route::put('/templateField/:id$','api/TemplateField/update');
        Route::delete('/templateField$','api/TemplateField/del');

        //项目管理
        Route::get('/project$','api/Project/index');
        Route::get('/project/:uuid$','api/Project/read');
        Route::put('/project/:uuid','api/Project/update');
        Route::post('/project$','api/Project/save');
        Route::delete('/project$','api/Project/updateStatus');
        Route::get('/project/design/:uuid$','api/Project/getDetail');
        Route::post('/project/flow$','api/Project/addFlow');
        Route::delete('/project/flow$','api/Project/delFlow');

        //我提交的->
        //项目列表
        Route::get('/myData$', 'api/MyData/index');
        //查看项目
        Route::get('/myData/:uuid$', 'api/MyData/viewPro');
        //添加数据
        Route::get('/myData/add/:uuid$', 'api/MyData/add');
        Route::get('/myData/view/:id$', 'api/MyData/view');
        
        Route::get('/mycheck$', 'api/ProjectDataCheck/index');
        Route::get('/mycheck/:uuid$', 'api/ProjectDataCheck/viewPro');
        Route::get('/mycheck/view/:id$', 'api/ProjectDataCheck/view');

        //项目管理
        //数据列表
        Route::get('/data$', 'api/ProjectData/index');
        //查看项目
        Route::get('/data/:uuid$', 'api/ProjectData/viewPro');
        //删除数据
        Route::delete('/data$', 'api/ProjectData/del');
        //查看数据详情
        Route::get('/data/view/:id$', 'api/ProjectData/view');
        
        //添加数据
        Route::get('/data/add/:uuid$', 'api/ProjectData/add');
        //编辑数据
        Route::get('/data/edit/:id$', 'api/ProjectData/add?did=:id');
        //保存数据
        Route::post('/data/add/:uuid$', 'api/ProjectData/save');

        Route::get('/down/:id', 'api/ProjectPublic/down');

        //角色管理
        Route::get('/role$','api/Role/index');
        Route::get('/role/:id','api/Role/read');
        Route::put('/role/:id','api/Role/update');
        Route::post('/role','api/Role/save');
        Route::delete('/role','api/Role/del');

        //管理员管理
        Route::get('/admin$','api/Admin/index');
        Route::get('/admin/:uuid','api/Admin/read');
        Route::put('/admin/:uuid','api/Admin/update');
        Route::post('/admin','api/Admin/save');
        Route::delete('/admin','api/Admin/del');

        //客服管理
        Route::get('supporter$','api/AdminSupporter/index');
        Route::post('supporter$','api/AdminSupporter/save');
        Route::put('supporter/:uuid$','api/AdminSupporter/update');
        Route::delete('supporter$','api/AdminSupporter/del');

        //设置
        Route::get('/settingBase','api/Setting/base');
        Route::post('/settingBase','api/Setting/saveBase');

        Route::get('/settingServer','api/Setting/server');
        Route::post('/settingServer','api/Setting/saveServer');

        //检测更新模板字段数
        Route::get('/checkField','api/Check/checkFields');

    });
});
Route::group('ask',function(){
    route::post('supportStat','api/Resource/supportStat');
    route::post('guest','api/Resource/guestSave');
    route::get('guest','api/Resource/guest');
    route::get('support','api/Resource/support');
});

Route::group('form',function(){
     //公开项目数据展示和提交
     Route::get('show/:uuid','api/ProjectPublic/view');
     Route::get(':uuid$','api/ProjectPublic/add');
     Route::post(':uuid$','api/ProjectPublic/save');
     
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
        'uuid'=>'/^[-\da-z]{10,}$/',
        'any'=>'/^.*/',
    ],
//    '[hello]'     => [
//        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
//        ':name' => ['index/hello', ['method' => 'post']],
//    ],

];
