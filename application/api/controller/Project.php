<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Project as ModelProject;
use app\api\model\ProjectFlow;
use app\api\model\ProjectRoles;
use app\api\model\Role;
use app\api\model\Template;
use app\api\model\TemplateField;
use think\Cache;
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
        $where = [];
        if ($name) {
            $where['name'] = ['like', "%{$name}%"];
        }

        $list = [];
        $data = ['list' => [], 'paginate' => ''];
        $baseUrl = Cache::get('baseUrl');
        $list = $model->where($where)->order('id desc')->paginate(['list_rows' => $pagesize, 'page' => $page, 'path' => $baseUrl.'/api/v1/project']);

        $paginate = $list->render();
        $data['paginate'] = $paginate;
        
        $data['temps'] = (new Template())->where(['field_count'=>['>',0]])->field('id,name')->order('name asc')->select();
        $data['roles'] = (new Role())->where(['status'=>1])->field('id,name')->order('name asc')->select();
        $data['list'] = $list ? $list->toArray() : [];
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;
        $data['name'] = $name;

        return $this->view->fetch('list', $data);
    }
        /**
     * 获取项目属性
     */
    function read($uuid)
    {
        $model = new ModelProject();
        $list = $model->where(['uuid' => $uuid])->find();
        return sucReturn('ok', $list);
    }


    /**
     * 获取项目详情,用于设计项目流程
     */
    function getDetail($uuid)
    {
        if (!$uuid) {
            exit('非法访问');
        }
        $modelProject = new ModelProject();

        $projectInfo = $modelProject->where(['uuid' => $uuid])->find();
        if (!$projectInfo) {
            exit('项目不存在');
        }

        $id = $projectInfo->id;
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
    
        $modelTemplateField = new TemplateField();
        $hasCheck = 0;
        foreach($flows as $k => $flow){
            $t = explode(',',$flow['temps']);
            if(!$t){
                continue;
            }
            $flows[$k]['temps'] = $t;
            $hasCheck = (int)$flow['has_check'];
            
            $usedTemps = array_merge($usedTemps,$t);
        }
        $data = ['has_check'=>$hasCheck];
        $allTemps = explode(',',$projectInfo['temps']);
        $unused = explode(',',$projectInfo['temps']);
        if($usedTemps){
            $unused = array_diff($allTemps,$usedTemps);
        }
        $data['used'] = $usedTemps;
        $data['unused'] = $unused;
        $tempsInfo = (new Template())->where(['field_count' => ['>',0],'id'=>['in',$allTemps]])->field('id,name')->select();
        $tempsInfo = array_column($tempsInfo->toArray(),null,'id');
        $data['tempList'] = $tempsInfo;

        $tempFields = $modelTemplateField->where(['temp_id'=>['in',$allTemps]])->field('id,temp_id,name,data_type,options,is_require')->order('temp_id asc,sort asc,id asc')->select();
    
        $fields = [];
        foreach($tempFields as $item){
            $fields[$item['temp_id']][] = $item->getData();
        }
        $data['tempFields'] = $fields;
        $data['usedRoles'] = '';
        if($projectInfo['roles']){
            $data['usedRoles'] = (new Role())->where(['status'=>1,'id'=>['in',$projectInfo['roles'] ] ])->field('id,name')->select();
        }
        $data['adminRoles'] = '';
        if($projectInfo['admin_roles']){
            $data['adminRoles'] = (new Role())->where(['status'=>1,'id'=>['in',$projectInfo['admin_roles'] ] ])->field('id,name')->select();
        }
        $data['proInfo'] = $projectInfo;
        $data['p_id'] = $id;
        $data['flows'] = $flows ;
        return $this->view->fetch('design', $data);
    }
    //增加项目流程
    function addFlow(){
        $pid = (int) input('p_id');
        $step = (int) input('step');
        $temps = input('temps');
        $ischeck = (int)input('ischeck',0);
        $data = [
            'p_id' => $pid, 
            'step' => $step,
            'temps' => $temps,
            'has_check' => $ischeck,
            ];
        $rule = [
            'temps'=>'require',
        ];
        $msg = [
            'temps' =>'请选择模板',
        ];
        if($step == 1){
            $check = $this->validate($data, $rule, $msg);
            if ($check !== true) {
                return errorReturn($check);
            }
        }
        
        $model = new ProjectFlow();
        $id = $model->insertGetId($data);
        if($id){
            $model = new ModelProject();

            $model->where(['id' => $pid])->update(['steps'=>$step]);
            return sucReturn('保存成功');
        }
    }
    //减少项目流程
    function delFlow(){
        $pid = (int)input('p_id');
        $step = (int)input('step');
        if($pid < 1 || $step < 1){
            return errorReturn('非法访问');
        }
        $model = new ProjectFlow();
        $model->where(['p_id'=>$pid,'step'=>$step])->delete();

        (new ModelProject())->where(['id'=>$pid])->setDec('steps');
        return sucReturn('删除成功');
    }

    /**
     * 添加项目
     */
    function save()
    {
        $name = input('name');
        $temps = input('temps');
        $roles = input('roles');
        $onlyone = input('onlyone',0);
        if(!$roles){
            $roles = 0;
        }
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
            $data['onlyone'] = $onlyone;
            $data['uuid'] = getUUid();
            $data['create_time'] = date('Y-m-d H:i:s');
            $id = $model->insertGetId($data);
            $data['id'] = $id;
            if($id > 0 && $roles){
                $roles = explode(',',$data['roles']);
                $ProRoles = new ProjectRoles();
                foreach($roles as $role_id){
                    $ProRoles->isUpdate(false)->save(['p_id'=>$id,'role_id'=>$role_id] );
                }
            }
            return sucReturn('保存成功', $data);
        } catch (Exception $e) {
            return errorReturn('保存失败(' . $e->getMessage(),$e->getTrace());
        }
    }

    /**
     * 编辑项目
     */
    function update($uuid)
    {
        if (!$uuid) {
            return errorReturn('非法访问');
        }
        $name = input('name');
        $temps = input('temps');
        $roles = input('roles');
        $onlyone = input('onlyone',0);
        if(!$roles){
            $roles = 0;
        }
        $admin_roles = input('admin_roles');
        $remark = input('remark');
        
        $data = [
                'name' => $name, 
                'temps' => $temps,
                'roles' => $roles,
                'onlyone'=>$onlyone,
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
        $pInfo = $model->getProInfo($uuid);
        $t = $model->where(['name' => $name,'status'=>1])->field('uuid,id')->find();
        if ($t && $t['uuid'] != $uuid) {
            return errorReturn('该名称已被使用');
        }
        try {
            $model->save($data, ['uuid' => $uuid]);
            $ProRoles = new ProjectRoles();
            $p_id = $pInfo['id'];
            $ProRoles->where(['p_id' => $p_id])->delete();
            if($roles){
                $roles = explode(',',$data['roles']);
                foreach($roles as $role_id){
                    $ProRoles->isUpdate(false)->save(['p_id'=>$p_id,'role_id'=>$role_id],[]);
                }
            }
            return sucReturn('ok', $data);
        } catch (Exception $e) {
            return errorReturn('保存失败(' . $e->getMessage().$e->getTraceAsString());
        }
    }
    //隐藏或显示项目
    function updateStatus()
    {
        $uuid = input('uuid');
        $status = (int)input('status',1);
        $status = $status ? 1 : 0;
        $model = new ModelProject();
        $model->where(['uuid' => $uuid])->update(['status'=>$status]);
        return sucReturn('隐藏成功');
    }
}
