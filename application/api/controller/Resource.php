<?php
namespace app\api\controller;

use app\api\model\AdminSupporter;
use app\api\model\AdminSupporterStat;
use app\api\model\Setting;
use app\api\model\User;
use Exception;
use think\Cache;
use think\Controller;

class Resource extends Controller
{
    function __construct()
    {
        parent::__construct();
        $this->view->assign('baseUrl',Cache::get('baseUrl'));
    }
   // 更新客服统计信息
    function supportStat(){
        $uuid = input('uuid');
        $act = input('act');
        
        if(!$uuid || !$act){
            return errorReturn('非法访问');
        }
        $model = new AdminSupporter();
        $modelStat = new AdminSupporterStat();
        $day = date('Y-m-d');
        switch($act){
            case 'ticker':
                $modelStat->incTime($uuid);
            break;
            case 'user':
                $admin = input('admin');
                if(!$admin){
                    return '';
                }
                $modelUser = new User();
                $info = $modelUser->where(['uuid'=>$uuid])->field('id,server_uuid,access_time')->find();
                if(!$info){
                    return '';
                }
                $f = 0;
                if(!$info['server_uuid']){
                    $modelUser->where(['uuid'=>$uuid])->update(['server_uuid'=>$admin]);
                    $modelStat->incUser($admin);
                    $f = 1;
                }elseif( $info['server_uuid'] != $admin ){
                    $modelUser->where(['uuid'=>$uuid])->update(['server_uuid'=>$admin]);
                    $modelStat->incUser($admin);
                    $f= 2;
                }elseif( ( time() - $info['access_time'] ) > 1800 ){
                    $modelStat->incUser($admin);
                    $f= 3;
                }
                return $f;
                break;
            case 'online':
                $model->where(['uuid'=>$uuid])->update(['is_online'=>1]);
                $res = $modelStat->where(['uuid'=>$uuid,'cur_day'=>$day])->find();
                if(!$res){
                    $modelStat->create(['uuid'=>$uuid,'cur_day'=>$day]);
                }
            break;
            case 'offline':
                $model->where(['uuid'=>$uuid])->update(['is_online'=>0]);
            break;
        }
        return 1;
    }
    function support(){
        $model = new Setting();
        $info = $model->where(['category'=>'server'])->field('setting,values')->select();
        $info = $info ? $info->toArray() : [];
        $data = array_column($info,'values','setting') ;

        return $this->view->fetch('/admin',$data);
    }
    function guest(){
        $model = new Setting();
        $info = $model->where(['category'=>'server'])->field('setting,values')->select();
        $info = $info ? $info->toArray() : [];
        $data = array_column($info,'values','setting') ;
        
        return $this->view->fetch('/client',$data);
    }
    function guestSave(){
        $name = input('name');
        $mobile = input('mobile');
        $gender = input('gender');
        if(!$mobile){
            return errorReturn('非法访问');
        }
        if(!preg_match('#^1\d{10}$#',$mobile)){
            return errorReturn('手机格式错误');
        }
        $model = new User();
        $info = $model->where(['mobile'=>$mobile])->field('id,name,uuid')->find();
        if($info){
            $model->where(['id'=>$info['id']])->update(['access_time'=>time()]);
            return sucReturn('ok',['uuid'=>$info['uuid'],'name'=>$info['name']]);
        }else{
            try{
            $uuid = getUUid();
            $data = [
                'name'=>$name,
                'nickname'=>$name,
                'uuid' => $uuid,
                'password'=> password_hash(substr($mobile,-4),PASSWORD_BCRYPT),
                'mobile'=>$mobile,
                'gender'=>$gender,
                'access_time'=>time(),
                'create_time' =>date('Y-m-d H:i:s'),
            ];
            $t = $model->isUpdate(false)->save($data);
            if($t){
                return sucReturn('ok',['uuid'=>$uuid,'name'=>$name]);
            }
            }catch(Exception $e){
                myLog($e->getMessage().$e->getTraceAsString());
                return errorReturn('服务器异常,请重试');
            }
        }
    }
}

