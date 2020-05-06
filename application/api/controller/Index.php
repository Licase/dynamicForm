<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Admin;
use app\api\model\AdminRole;
use app\api\model\Permission;
use think\Cache;
use think\Request;
use think\Session;

class Index extends Controller
{
    function login(){
        if(Request::instance()->isAjax()){
            $account = input('user');
            $pwd = input('pwd');
            if(!$account || !$pwd){
                return errorReturn('用户名或密码不能为空');
            }
            $info = (new Admin())->where(['account'=>$account,'status'=>1])->field('id,name,nickname,password,mobile,uuid,email')->find();
            if(!$info || !checkPwd($pwd,$info['password'])){
                return errorReturn('用户名或密码错误');
            }
            $info = $info->getData();
            unset($info['password']);
            $id = $info['id'];
            
            $role_ids = (new AdminRole())->where('admin_id',$id)->field('role_id')->order('role_id asc')->column('role_id');
            $info['roles'] = $role_ids;

            $permisson = Permission::getUserPermissions($id,$role_ids);
            $info['menus'] = Permission::formatMenu($permisson);
            $info['permAll'] = $permisson;
            Session::set('userinfo',$info);
            return sucReturn('ok');
        }
        
        $baseUrl = Cache::get('baseUrl');
        $baseUrl = '';
        if(!$baseUrl){
            $baseUrl = rtrim(substr( rtrim($_SERVER['REQUEST_URI'],'/'),0,-5),'/');
            Cache::set('baseUrl',$baseUrl);
        }
        
        return $this->view->fetch('/login',['baseUrl'=>$baseUrl]);
    }

    function logout(){
        Session::delete('userinfo');
        return sucReturn('ok');
    }
    function home(){
        $data = [];
        return $this->view->fetch('home',$data);
    }
}
