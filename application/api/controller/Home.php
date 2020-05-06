<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Admin;

class Home extends Controller
{
    
    function index(){
        $data = [];
        $modelAdmin = new Admin();
        $info = $modelAdmin->alias('a')->join('tab_admin_supporter b','a.uuid = b.uuid')
        ->field('b.showname,a.name,b.uuid,b.online_total,b.online_today,b.served_today,b.served_total')
        ->where(['id'=>$this->user_id])
        ->find();
        
        setcookie('admin_'.$info['uuid'],json_encode(['name'=>$info['showname'],'uid'=>$info['uuid'] ]),0,'/');
        $data['myInfo'] = $info;
        return $this->view->fetch('home',$data);
    }
}
