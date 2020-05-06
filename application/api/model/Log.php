<?php

/**
 * User: zhangshuaishuai
 * Date: 2018/07/03 17:35
 */

namespace app\api\model;

use think\Model;

class Log extends Model {
    protected $table = 'tab_log';

    function Add($act,$detail){
        if(!$this->user_id){
            return true;
        }
        $log = [
            'user_name' => $this->username,
            'user_id' => $this->user_id,
            'op_type' =>$act,
            'op_content' => $detail,
            'create_time'=>date('Y-m-d H:i:s'),
            'login_ip'=> request()->ip()
        ];
        $this->insert($log);
    }
}
