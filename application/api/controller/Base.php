<?php
namespace app\api\controller;

use app\api\model\Log;
use app\api\model\Setting;
use think\Cache;
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
        
        $method = $request->method();
        $module = $request->module();
        $controller = $request->controller();
        $action = $request->action();
        $passedActions = config('permission.passed');
        $needle = strtolower($method.'.'.$module.'.'.$controller.'.'.$action);

        $needCheck = true; //是否需要检测权限，其它任意接口需要获取用户信息
        if(in_array( $needle,$passedActions)){
            $needCheck = false;
        }
        
        $this->log = new Log();
        $baseUrl = Cache::get('baseUrl');
        $userinfo = Session::get('userinfo');
        $this->user_id = $userinfo['id'];
        $this->user_info = $userinfo;
        //跳过的不作检测
        if($needCheck && !in_array($needle,config('permission.unexpire'))) {
            if( !$userinfo || $userinfo['id'] < 1 ){
                if($request->isAjax()){
                    return errorReturn('登录过期',null,301);
                }else{
                    $this->redirect($baseUrl.'/login');
                }
            }
        }
        $model = new Setting();
        $title = $model->where(['setting'=>SYS_TITLE])->value('values');
        $this->view->assign('title',$title);
        $this->view->assign('baseUrl',$baseUrl);


        $route = $request->routeInfo();
        $tmp = '';
        for($i = 0;$i < 3; $i++){
            if(! isset($route['rule'][$i])){
            break;
            }
            $tmp .=  '/'.$route['rule'][$i];
        }
        $tmp = strtolower($tmp);
        if($needCheck){
            $this->view->assign('menus',$userinfo['menus']);
            
            $allPerm = $userinfo['permAll'];
            $pid = 0;
            $curMenuId = 0;
            foreach($allPerm as $t){
                if( strtolower($t['code']) == $tmp){
                    $pid = $t['pid'];
                    $curMenuId = $t['id'];
                }
            }
            unset($allPerm);
        }else{
            $pid = 0;
            $curMenuId = 0;
        }
        
        unset($userinfo['menus'],$userinfo['permAll']);
        $curMenu = isset($route['rule'][2]) ? $route['rule'][2] : $route['rule'][0];
        $this->view->assign('menu_pid',$pid);
        $this->view->assign('curMenuId',$curMenuId);
        $this->view->assign('curMenu',$curMenu);
        $this->view->assign('adminname',$userinfo['name']);

    }

    private function exitJson($msg,$code= 400){
        $a= errorReturn($msg,$code);
        header('Content-type:application/json');
        echo $a->getContent();
        exit;
    }

    public function _empty($name){
        return $this->display('not exist action:'.$name);
    }
}

