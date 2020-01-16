<?php
use think\Route;
Route::group('api',function(){
    Route::group('v1',function(){
        Route::get('/template$','api/Template/index');
        Route::get('/template/:id','api/Template/read');
        Route::put('/template/:id','api/Template/update');
        Route::post('/template','api/Template/save');
        Route::delete('/template','api/Template/del');
        Route::get('/template/design/:id','api/Template/getDetail');

        Route::get('/data',function(){
                return "Helel";
        });

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
        'any'=>'/^.*/',
    ],
//    '[hello]'     => [
//        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
//        ':name' => ['index/hello', ['method' => 'post']],
//    ],

];
