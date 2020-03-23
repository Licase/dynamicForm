<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Admin;
use think\Request;
use think\Session;

class Index extends Controller
{
    function login(){
        if(Request::instance()->isAjax()){
            $account = input('user');
            $pwd = input('pwd');
            if(!$account || !$pwd){
               // return errorReturn('用户名或密码不能为空');
            }
            $info = (new Admin())->where(['account'=>$account])->field('id,name,nickname,password,mobile,uuid,email')->find();
            if(!password_verify($pwd,$info['password'])){
                return errorReturn('用户名或密码错误');
            }
            
            Session::set('userinfo',$info);
            return sucReturn('ok');
        }
        return $this->view->fetch('login');
    }
    function home(){
        $data = [];
        return $this->view->fetch('home',$data);
    }
}
