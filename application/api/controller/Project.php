<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Project as ModelProject;
use app\api\model\ProjectFlow;
use app\api\model\Role;
use app\api\model\Template;
use app\api\model\TemplateField;
use think\Config;
use think\Exception;

class Project extends Controller
{
    /**
     * 获取项目列表
     */
    function index()
    {
        $page = (int) input('page', 1);
        $page = max(1, $page);
        $pagesize = (int) input('pagesize', 15);
        $pagesize = max($pagesize, 1);

        $name = input('name');

        $model = new ModelProject();
        $where = ['status'=>1];
        if ($name) {
            $where['name'] = ['like', "%{$name}%"];
        }
        $total = $model->where($where)->count('id');
        $list = [];
        $data = ['list' => [], 'paginate' => ''];
        if ($total > 0) {
            if ($page > 1 && $total <= ($page - 1) * $pagesize) {
                $page = ceil($total / $pagesize);
            }
            $list = $model->where($where)->order('id desc')->paginate(['list_rows' => $pagesize, 'page' => $page, 'path' => '/api/v1/project']);

            $paginate = $list->render();
            $data['paginate'] = $paginate;
        }
        $data['temps'] = (new Template())->where(['field_count'=>['>',0]])->field('id,name')->order('name asc')->select();
        $data['roles'] = (new Role())->where(['status'=>1])->field('id,name')->order('name asc')->select();
        $data['list'] = $list;
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;
        $data['total'] = $total;
        $data['name'] = $name;

        return $this->view->fetch('list', $data);
    }
        /**
     * 获取项目属性
     */
    function read($id)
    {
        $model = new ModelProject();
        $list = $model->where(['id' => $id])->find();
        return sucReturn('ok', $list);
    }


    /**
     * 获取项目详情,用于设计项目流程
     */
    function getDetail($id)
    {
        if (!$id) {
            exit('非法访问');
        }
        $modelProject = new ModelProject();
        $info = $modelProject->where(['id' => $id])->find();
        if (!$info) {
            exit('项目不存在');
        }

        /**
         * 项目设计流程，
         * 1. 添加步骤，
         * 2. 选择所用模板，
         * 3. 选择参与角色
         */
        $model = new ProjectFlow();
        $flows = $model->where(['p_id' => $id])->order('step asc')->select();
        $flows = $flows ? $flows->toArray() : [];
        $usedTemps = [];
        $hasCheck = 1;
        $modelTem = new Template();
        $modelTemplateField = new TemplateField();
        foreach($flows as $flow){
            $hasCheck = $flow['has_check'];
            $t = explode(',',$flow['temps']);
            if(!$t){
                continue;
            }
            $usedTemps = array_merge($usedTemps,$t);
            $temps = explode(',',$flow['temps']);

        }
        $data = [];
        $data['has_check'] = $hasCheck;
        $all = explode(',',$info['temps']);
        $unused = $info['temps'];
        if($usedTemps){
            $unused = array_diff($all,$usedTemps);
        }
        $data['tempList'] = [];
        if($unused){
            $data['tempList'] = (new Template())->where(['field_count' => ['>',0],'id'=>['in',$unused]])->field('id,name')->select();
        }
        $data['roles'] = '';
        if($info['roles']){
            $data['roles'] = (new Role())->where(['status'=>1,'id'=>['in',$info['roles'] ] ])->field('id,name')->select();
        }
        
        $data['info'] = $info;
        $data['pid'] = $id;
        $data['flows'] = $flows ;
        return $this->view->fetch('design', $data);
    }
    //增加项目流程
    function addFlow(){
        $pid = (int) input('p_id');
        $step = (int) input('step');
        $temps = input('temps');
        $roles = input('roles');
        $ischeck = (int)input('ischeck',0);
        $data = [
            'p_id' => $pid, 
            'step' => $step,
            'temps' => $temps,
            'roles' => $roles,
            'has_check' => $ischeck,
            ];
        $rule = [
            'temps'=>'require',
        ];
        $msg = [
            'temps' =>'请选择模板',
        ];
        $check = $this->validate($data, $rule, $msg);
        if ($check !== true) {
            return errorReturn($check);
        }
        $model = new ProjectFlow();
        $id = $model->insertGetId($data);
        if($id){
            $model = new ModelProject();

            $model->where(['id'])->update(['steps'=>$step]);
            return sucReturn('保存成功');
        }
    }
    //减少项目流程
    function delFlow(){

    }

    /**
     * 添加项目
     */
    function save()
    {
        $name = input('name');
        $temps = input('temps');
        $roles = input('roles');
        $admin_roles = input('admin_roles');
        $remark = input('remark');
        
        $data = [
                'name' => $name, 
                'temps' => $temps,
                'roles' => $roles,
                'admin_roles' => $admin_roles,
                'remark' => $remark,
                ];
        $rule = [
            'name' => 'require|max:32|regex:/[-\w\x4e00-\x9fa5]+/iu',
            'admin_roles'=>'require',
            'temps'=>'require',
            'remark' => 'max:255'
        ];
        $msg = [
            'name.require' => '名称不能为空',
            'name.max' => '名称不超过32个字符',
            'name' => '名称由汉字、字母、数字、下划线及横线组成',
            'temps' =>'请选择模板',
            'admin_roles' =>'请选择管理角色',
            'remark.max' => '备注不超过255个字符'
        ];
        $check = $this->validate($data, $rule, $msg);
        if ($check !== true) {
            return errorReturn($check);
        }
        $model = new ModelProject();
        $t = $model->where(['name' => $name])->value('id');
        if ($t) {
            return errorReturn('该名称已被使用');
        }
        try {
            $data['create_time'] = date('Y-m-d H:i:s');
            $id = $model->insertGetId($data);
            $data['id'] = $id;
            return sucReturn('保存成功', $data);
        } catch (Exception $e) {
            return errorReturn('保存失败(' . $e->getMessage());
        }
    }

    /**
     * 编辑项目
     */
    function update($id)
    {
        if ($id < 1) {
            return errorReturn('非法访问');
        }
        $name = input('name');
        $temps = input('temps');
        $roles = input('roles');
        $admin_roles = input('admin_roles');
        $remark = input('remark');
        
        $data = [
                'name' => $name, 
                'temps' => $temps,
                'roles' => $roles,
                'admin_roles' => $admin_roles,
                'remark' => $remark,
                ];
        $rule = [
            'name' => 'require|max:32|regex:/[-\w\x4e00-\x9fa5]+/iu',
            'admin_roles'=>'require',
            'temps'=>'require',
            'remark' => 'max:255'
        ];
        $msg = [
            'name.require' => '名称不能为空',
            'name.max' => '名称不超过32个字符',
            'name' => '名称由汉字、字母、数字、下划线及横线组成',
            'temps' =>'请选择模板',
            'admin_roles' =>'请选择管理角色',
            'remark.max' => '备注不超过255个字符'
        ];
        $check = $this->validate($data, $rule, $msg);
        if ($check !== true) {
            return errorReturn($check);
        }
        $model = new ModelProject();
        $t = $model->where(['name' => $name,'status'=>1])->value('id');
        if ($t && $t != $id) {
            return errorReturn('该名称已被使用');
        }
        try {
            $model->save($data, ['id' => $id]);
            $data['id'] = $id;
            return sucReturn('ok', $data);
        } catch (Exception $e) {
            return errorReturn('保存失败(' . $e->getMessage());
        }
    }
    //隐藏或显示项目
    function updateStatus()
    {
        $id = input('id');
        $status = (int)input('status',1);
        $status = $status ? 1 : 0;
        $model = new ModelProject();
        $model->where(['id' => $id])->update(['status'=>$status]);
        return sucReturn('删除成功');
    }
}
