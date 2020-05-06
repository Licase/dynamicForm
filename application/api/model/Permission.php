<?php

/**
 * User: zhangshuaishuai
 * Date: 2018/07/03 17:35
 */

namespace app\api\model;

use think\Db;
use think\Model;

class Permission extends Model {
    protected $table = 'tab_permission';

    static function getUserPermissions($user_id,$role_ids=false){
        if($user_id < 1){
            return [];
        }
        if(!$role_ids){
            $role_ids = Db::table('tab_admin_role a')->where('admin_id',$user_id)->field('role_id')->order('role_id asc')->column('role_id');
        }
        $isSuper = false;
        $where = [];

        $where['type'] = ['in',['menu','button']];

        if($user_id === 1){
            $isSuper = true; 
        }elseif($role_ids){
            if(array_search(1,$role_ids) !== false){
                $isSuper = true;
            }else{
                $where['role_id'] = ['in',$role_ids];
            }
        }else{
            return [];
        }
        $field = 'a.id,a.name,a.pid,a.code,a.method,a.type,a.sorts';
        $sort = 'pid asc,sorts asc,id asc';
        if($isSuper){ // 系统管理员直接获取所有权限，避免新增权限遗漏
            $list = Db::table('tab_permission a')->where($where)->field($field)->order($sort)->select()->toArray();
        }else{
            $list = Db::table('tab_permission a')
                ->join('tab_role_perm b','a.id = b.p_id')
                ->field($field)
                ->order($sort)
                ->where($where)->select()->toArray();
        }
        
        return $list;
    }

    // 按层级分类权限
    static public function formatMenu($data,$idFlag = 'id',$pidFlag = 'pid',$childFlag = 'child',$rootIndex = 0){
        if(!$data){
            return [];
        }
        $tmp = [];
        foreach ($data as $item){
            $tmp[ $item[$idFlag] ] = $item;
        }

        foreach ($tmp as $val){
            $tmp[$val[$pidFlag]][$childFlag][] = &$tmp[$val[$idFlag]];
        }
        $list = array_values($tmp[$rootIndex][$childFlag]);
        unset($tmp);
        foreach ($list as &$v){
            if(!isset($v[$childFlag])){
                continue;
            }
            if(!isset($v['sorts'])){
                break;
            }
            $tmp = [];
            foreach ($v[$childFlag] as $val){
                $tmp[] = $val['sorts'];
            }
            array_multisort($tmp,SORT_ASC,$v[$childFlag]);
        }
        return $list;
    }
}
