<?php

/**
 * User: zhangshuaishuai
 * Date: 2018/07/03 17:35
 */

namespace app\api\model;

use think\Db;
use think\Model;

class ProjectDataCheckStat extends Model {
    protected $table = 'tab_user_data_check_stat';
    static function incStat($pid,$userid){
        $check =Db::table('tab_user_data_check_stat')->where(['p_id'=>$pid,'user_id'=>$userid])->field('id,total')->find();
        if($check && $check['id']){
            $sql = "update tab_user_data_check_stat set total = total +1 where user_id = ".$userid." and p_id=".$pid;
        }else{
            $sql = "insert into tab_user_data_check_stat (p_id,user_id,total ) values (".$pid.",".$userid.",1);";
        }
        Db::execute($sql);
        return true;
    }
    static function decStat($pid,$userid){
        $check =Db::table('tab_user_data_check_stat')->where(['p_id'=>$pid,'user_id'=>$userid])->field('id,total')->find();
        if(!$check || !$check['id'] || $check['total'] < 1){
            return true;
        }
        $sql = "update tab_user_data_check_stat set total = total-1 where user_id = ".$userid." and p_id=".$pid;
        Db::execute($sql);
        return true;
    }
}
