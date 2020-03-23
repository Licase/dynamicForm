<?php

namespace app\msg\controller;

use app\msg\model\Msg as ModelMsg;
use think\Controller;
use think\Log;

class Msg extends Controller
{
    //获取消息
    function list(){
        $uid = input('uid'); //客户uid;
        if(!$uid){
            return sucReturn('ok');
        }
        $model = new ModelMsg();
        $list = $model->field('role,from,to,msg,create_time,is_read')
        ->where(['role'=>'user','from'=>$uid])->whereOr(function($query)use($uid){
            $query->where(['role'=>'admin','to'=>$uid]);
        })->order('create_time desc')->limit(0,20)->select();
        return sucReturn('ok',$list);
    }
    //保存消息
    function save(){
        $from = input('from');
        $to = input('to');
        $role = input('role');
        $msg = input('msg');
        $date = date('Y-m-d');
        if(!$from || !$to || !$role || !$msg){
            return false;
        }
        $model = new ModelMsg();
        $data = ['from'=> $from,'to'=> $to,'role'=> $role,'msg'=> $msg,'curDay'=>$date,'create_time'=> date('Y-m-d H:i:s')];
        $model->data($data)->isUpdate(false)->save();
        return true;
    }
    
    
    
}
