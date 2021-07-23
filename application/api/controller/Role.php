<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\AdminRole;
use app\api\model\Permission;
use app\api\model\Role as ModelRole;
use app\api\model\RolePerm;
use think\Cache;
use think\Config;
use think\Exception;

class Role extends Controller
{
    /**
     * 获取角色列表
     */
    function index()
    {
        $page = (int) input('page', 1);
        $page = max(1, $page);
        $pagesize = (int) input('pagesize', 15);
        $pagesize = max($pagesize, 1);

        $name = input('name');

        $model = new ModelRole();
        $where = ['status'=> 1];
        if ($name) {
            $where['name'] = ['like', "%{$name}%"];
        }
      
        $list = [];
        $data = ['list' => [], 'paginate' => ''];
    
        $baseUrl = Cache::get('baseUrl'); 
        $list = $model->where($where)->order('id desc')->paginate(['list_rows' => $pagesize, 'page' => $page, 'path' => $baseUrl.'/api/v1/role']);

        $paginate = $list->render();
        $data['paginate'] = $paginate;
        

        $data['list'] = $list ? $list->toArray() : [];
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;
        $data['name'] = $name;

        $data['permsAll'] = json_encode( $this->getPerms() );
        return $this->view->fetch('list', $data);
    }

    function getPerms($ids = []){
        $perms = (new Permission())->field('id,pid,name as text,type')->order('pid asc,sorts asc,id asc')->select()->toArray();
        $perms = Permission::formatMenu($perms,'id','pid','nodes');
        $flag = $ids && is_array($ids) ? true : false;
        $data = [];
        foreach($perms as &$menu){
            $menu['selectable'] = true;
            $menu['state']['checked'] = false;
            $menu['state']['expanded'] = true;
            
            $id = $menu['id'];
            unset($menu['pid']);
            
            if($flag && in_array($id,$ids)){
                $menu['state']['checked'] = true;
                $menu['state']['selected'] = true;
            }

            if(isset($menu['nodes'])){
                foreach($menu['nodes'] as &$item){
                    unset($item['pid']);
                    
                    $item['state']['checked'] = false;
                    if($flag && in_array($item['id'],$ids)){
                        $item['state']['checked'] = true;
                        $item['state']['selected'] = true;
                    }
                    
                }
            }
        }
        return $perms;
    }
        /**
     * 获取角色属性
     */
    function read($id)
    {
        $model = new ModelRole();
        $list = $model->where(['id' => $id])->find();
        $curPerms = (new RolePerm())->where(['role_id'=>$id])->column('p_id');
        $list['permsAll'] = $this->getPerms($curPerms);
        return sucReturn('ok', $list);
    }

    /**
     * 添加角色
     */
    function save()
    {
        $name = input('name');
        $remark = input('remark');
        $perms = input('perms/a');
        
        $model = new ModelRole();
        $data = ['name' => $name, 'remark' => $remark,'perms'=>$perms];
        $rule = [
            'name' => 'require|max:32|regex:/[-\w\x{4e00}-\x{9fa5}]+/iu',
            'perms' => 'require',
            'remark' => 'max:255'
        ];
        $msg = [
            'name.require' => '名称不能为空',
            'name.max' => '名称不超过32个字符',
            'name' => '名称由汉字、字母、数字、下划线及横线组成',
            'remark.max' => '备注不超过255个字符',
            'perms'=>'请选择权限'
        ];
        $check = $this->validate($data, $rule, $msg);
        if ($check !== true) {
            return errorReturn($check);
        }

        $t = $model->where(['name' => $name])->field('id')->find();
        if ($t) {
            return errorReturn('该名称已被使用');
        }
        try {
            unset($data['perms']);
            $data['create_time'] = date('Y-m-d H:i:s');
            $id = $model->insertGetId($data);
            $data['id'] = $id;
            foreach($perms as $pid){
                RolePerm::create(['role_id'=>$id,'p_id'=>$pid]);
            }
            return sucReturn('保存成功', $data);
        } catch (Exception $e) {
            return errorReturn('保存失败(' . $e->getMessage());
        }
    }

    /**
     * 编辑角色
     */
    function update($id)
    {
        if ($id < 1) {
            return errorReturn('非法访问');
        }
        $name = input('name');
        $remark = input('remark');
        $perms = input('perms/a');
        
        $model = new ModelRole();
        $data = ['name' => $name, 'remark' => $remark,'perms'=>$perms];
        $rule = [
            'name' => 'require|max:32|regex:/[-\w\x{4e00}-\x{9fa5}]+/iu',
            'perms' => 'require',
            'remark' => 'max:255'
        ];
        $msg = [
            'name.require' => '名称不能为空',
            'name.max' => '名称不超过32个字符',
            'name' => '名称由汉字、字母、数字、下划线及横线组成',
            'remark.max' => '备注不超过255个字符',
            'perms'=>'请选择权限'
        ];
        $check = $this->validate($data, $rule, $msg);
        if ($check !== true) {
            return errorReturn($check);
        }

        $t = $model->where(['name' => $name])->field('id')->find();
        if ($t && $t->id != $id) {
            return errorReturn('该名称已被使用');
        }
        try {
            unset($data['perms']);
            $model->save($data, ['id' => $id]);
            $model = new RolePerm();
            $model->where(['role_id'=>$id])->delete();
            foreach($perms as $pid){
                RolePerm::create(['role_id'=>$id,'p_id'=>$pid]);
            }
            $data['id'] = $id;
            return sucReturn('ok', $data);
        } catch (Exception $e) {
            return errorReturn('保存失败(' . $e->getMessage().$e->getTraceAsString());
        }
    }
    //隐藏或显示角色
    function del()
    {
        $id = input('id');
        if($id < 1){
            return errorReturn('非法访问');
        }
        $model = new ModelRole();
        $model->where(['id' => $id])->update(['status'=>0]);
        return sucReturn('删除成功');
    }
}
