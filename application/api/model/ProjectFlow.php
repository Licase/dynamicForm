<?php

/**
 * User: zhangshuaishuai
 * Date: 2018/07/03 17:35
 */

namespace app\api\model;

use think\Db;
use think\Model;

class ProjectFlow extends Model {
    protected $table = 'tab_project_flow';

    function getProjectTemps($proId,$tempIds){
        $tempInfo = Db::table('tab_template')->where(['id'=>['in',$tempIds]])->field('id,name')->select();
        $tempInfo = array_column($tempInfo->toArray(),null,'id');
        $info = $this->where(['p_id'=>$proId])->field('temps,step')->order('step')->select();
        $temps = [];
        foreach($info as $flow){
            $t = explode(',',$flow['temps']);
            foreach($t as $tid){
                $temps[$flow['step']][] = $tempInfo[$tid];
            }
        }
        return $temps;
    }
}
