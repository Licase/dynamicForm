<?php
namespace app\api\controller;

use think\Config;
use think\Controller;
use think\Request;
use think\Session;

class Base extends Controller
{
    protected $user_id = 0;
    protected $role_id = 1;
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $route = $request->routeInfo();

        $userinfo = Session::get('userinfo');
        $passed=  Config::get('permission.pass');
        if(in_array(trim($route['rule'][0],'/'),$passed)){
            return ;
        }
        if( !$userinfo || $userinfo['id'] < 1 ){
            $this->redirect('/login');
        }
        $this->user_id = $userinfo->id;
        $menu = isset($route['rule'][2]) ? $route['rule'][2] : $route['rule'][0];
        $this->view->assign('curMenu',$menu);

    }
    public function _empty($name){
        return $this->display('not exist action:'.$name);
    }

}

