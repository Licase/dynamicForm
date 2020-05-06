<?php

/**
 * User: zhangshuaishuai
 * Date: 2018/07/03 17:35
 */

namespace app\api\model;

use think\Model;

class ProjectDataCheckStat extends Model {
    protected $table = 'tab_user_data_check_stat';
    static function incStat($pid,$userid){
        $check = $this->where(['p_id'=>$pid,'user_id'=>$userid]);

    }
    static function decStat($pid,$userid){
        
    }
}
