<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------W

header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept,Authorization');
header("Access-Control-Allow-Methods:GET,POST,PUT,OPTIONS,DELETE,HEAD");
if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
    exit;
}


// [ 应用入口文件 ]
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
