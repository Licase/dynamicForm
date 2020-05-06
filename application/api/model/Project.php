<?php

/**
 * User: zhangshuaishuai
 * Date: 2018/07/03 17:35
 */

namespace app\api\model;

use think\Model;

class Project extends Model {
    protected $table = 'tab_project';

    function getProInfo($uuid,$isId=0){
        if(!$uuid){
            return [];
        }
        $where = $isId ? ['id'=>$uuid] : ['uuid'=>$uuid];
        $data = $this->where($where)->field('id,name,status,uuid,total,temps,roles,admin_roles,steps')->find();
        return $data ? $data->getData() : [];
    }
}
