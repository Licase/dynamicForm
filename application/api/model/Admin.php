<?php

/**
 * User: zhangshuaishuai
 * Date: 2018/07/03 17:35
 */

namespace app\api\model;

use think\Model;

class Admin extends Model {
    protected $table = 'tab_admin';

    //获取项目的审核人列表
    function getAuthUsers($roles){
        $return = [];
        if(!$roles){
            return $return;
        }
        $admins = $this->alias('a')
            ->join('tab_admin_role b','a.id = b.admin_id')
            ->where(['b.role_id'=>['in',$roles ],'status'=>1 ])
            ->field('a.id,a.name,a.nickname')
            ->distinct(true)
            ->order('name')
            ->select();
        if($admins){
            $return = $admins->toArray();
        }
        return $return;
    }
}
