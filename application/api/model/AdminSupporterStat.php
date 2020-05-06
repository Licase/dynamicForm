<?php

/**
 * User: zhangshuaishuai
 * Date: 2018/07/03 17:35
 */

namespace app\api\model;

use think\Db;
use think\Model;

class AdminSupporterStat extends Model {
    protected $table = 'tab_admin_supporter_stat';

    //服务人数+1
    function incUser($uuid){
        $day = date('Y-m-d');
        $uuid = trim($uuid,'"\'');
        $sql = "update tab_admin_supporter_stat set `served_num`= `served_num`+1 where uuid = '".$uuid."' and cur_day='".$day."'";
        Db::execute($sql);
        $sql = "update tab_admin_supporter set `served_total`= `served_total`+1,`served_today` = `served_today`+1 where uuid = '".$uuid."'";
        Db::execute($sql);
    }
    //增加服务时长
    function incTime($uuid,$time = 60){
        $day = date('Y-m-d');
        $uuid = trim($uuid,'"\'');

        $sql = "update tab_admin_supporter_stat set `online_time`= `online_time`+".$time." where uuid = '".$uuid."' and cur_day='".$day."'";
        Db::execute($sql);
        $sql = "update tab_admin_supporter set `online_total`= `online_total`+ ".$time.",`online_today` = `online_today`+".$time." where uuid = '".$uuid."'";
        Db::execute($sql);
    }

}
