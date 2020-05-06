<?php
// 不用验证的接口,全部写成小写
$list =  array (
    'passed'=>[ // 不检测token
        'get.api.index.login', // 登录
        'post.api.index.login', // 登录
        'post.api.index.logout', // 注销
    ],
    'unexpire'=>[   //不检测过期
      
    ],
    'uncheck'=>[    //不检测权限
        'post.api.admin.modifypwd', // 修改密码
        'get.api.home.index', // 修改密码
        'get.api.home.index', // 修改密码
    ]
);
return $list;