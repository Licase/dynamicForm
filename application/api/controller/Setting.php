<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Setting as ModelSetting;

class Setting extends Controller
{
    /**
     * 获取基本设置项
     */
    function base()
    {
        $model = new ModelSetting();
        $setting = $model->where(['category'=>'base'])->field('setting,values')->select();
        $data = [];
        if($setting){
            foreach($setting as $item){
                $data[$item['setting']] = $item['values'];
            }
        }
        return $this->view->fetch('base',$data);
    }

    /**
     * 保存设置项
     */
    function saveBase()
    {
        $title = input(SYS_TITLE);
        $model = new ModelSetting();
        $time = date('Y-m-d H:i:s');
        if($title){
            if(mb_strlen($title) > 20){
                $title = substr($title,0,20);
            }
            $model->where(['setting'=>SYS_TITLE,'category'=>'base'])->update(['values'=>$title,'update_time'=>$time]);
        }
        return sucReturn('保存成功');
    }

    //客服设置
    function server(){
        $model = new ModelSetting();
        $setting = $model->where(['category'=>'server'])->field('setting,values')->select();
        $data = [
            'tab_host'=>'',
            'service_port'=>8090,
            'user_disconnect_second'=>300,
            'wel_word'=>'',
            ];
        if($setting){
            foreach($setting as $item){
                $data[$item['setting']] = $item['values'];
            }
        }
        $cmd = "ps aux | grep wm_pro_service | wc -l";
        return $this->view->fetch('server',$data);
    }
    /**
     * 保存设置项
     */
    function saveServer()
    {
        $tab_host = input('tab_host');
        $service_port = input('service_port','8090');
        $user_disconnect_second = input('user_disconnect_second',300);
        $wel_word = input('wel_word');
        $data = [
            'tab_host'=>$tab_host,
            'service_port'=> $service_port,
            'user_disconnect_second'=>$user_disconnect_second,
            'wel_word'=>$wel_word,
        ];
        if($tab_host){
            if(!preg_match('#^http(s)?\://[a-zA-Z\d]+?(\.[a-zA-Z\d]+){1,3}(\:\d{1,5})?(/.*)?$#',$tab_host)){
                return errorReturn('网页管理地址格式错误');
            }
        }
        if( $service_port < 1 || $service_port > 65535){
            return errorReturn('端口错误');
        }
        if($user_disconnect_second < 120 || $user_disconnect_second > 600){
            return errorReturn('自动断开时间超出范围');
        }
        
        if($wel_word && mb_strlen($wel_word) > 50){
            return errorReturn('欢迎辞过长');
        }

        $model = new ModelSetting();
        
        $setting = $model->where(['category'=>'server'])->field('setting,values')->select();
        $old = [];
        if($setting){
            foreach($setting as $item){
                $old[$item['setting']] = $item['values'];
            }
        }

        $time = date('Y-m-d H:i:s');
        foreach($data as $key => $val){
            if(!$val || isset($old[$key]) && $old[$key] == $val ){
                continue;
            }
            $model->where(['setting'=>$key,'category'=>'server'])->update(['values'=>$val,'update_time'=>$time]);
        }
        
        return sucReturn('保存成功');
        
    }

    function startService(){

    }
}
