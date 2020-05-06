<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Admin as ModelAdmin;
use app\api\model\AdminRole;
use app\api\model\Role;
use think\Cache;
use think\Exception;
use think\Session;

class Admin extends Controller
{
    /**
     * 获取管理员列表
     */
    function index()
    {
        $page = (int) input('page', 1);
        $page = max(1, $page);
        $pagesize = (int) input('pagesize', 15);
        $pagesize = max($pagesize, 1);

        $name = input('name');

        $model = new ModelAdmin();
        $where = ['status'=>1];
        if ($name) {
            $where['name'] = ['like', "%{$name}%"];
        }

        $list = [];
        $data = ['list' => [], 'paginate' => ''];

        $modelRole = new AdminRole();
        $baseUrl = Cache::get('baseUrl');
        $list = $model->where($where)->order('id desc')
        ->paginate(['list_rows' => $pagesize, 'page' => $page, 'path' => $baseUrl.'/api/v1/admin'])
        ->each(function($item,$key)use($modelRole){
            $item['roles'] = $modelRole->alias('a')->join('tab_role b','a.role_id = b.id')->where(['a.admin_id'=>$item->id])->order('a.role_id asc')->column('b.name');
            return $item;
        });

        $paginate = $list->render();
        $data['paginate'] = $paginate;

        $model = new Role();
        $roles = $model->where(['status'=>1])->field('id,name')->order('name asc')->select();
        $data['roles'] = $roles ? $roles->toArray() : [];
        $data['list'] = $list ? $list->toArray():[];
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;

        $data['name'] = $name;

        return $this->view->fetch('list', $data);
    }
        /**
     * 获取管理员信息
     */
    function read($uuid)
    {
        $model = new ModelAdmin();
        $info = $model->where(['uuid' => $uuid])->find();
        $model = new AdminRole();
        $info['roles'] = $model->where(['admin_id'=>$info['id']])->column('role_id');
        return sucReturn('ok', $info);
    }

    /**
     * 添加管理员
     */
    function save()
    {
        $data = [];
        $data['account'] = input('account');
        $data['name'] = input('name');
        $data['nickname'] = input('nickname');
        $data['mobile'] = input('mobile');
        $data['email'] = input('email');
        $data['password'] = input('passwd');
        $data['repasswd'] = input('repasswd');
        $data['remark'] = input('remark');
        $roles = input('roles/a');
 
        if(!$roles){
            return errorReturn('请选择角色');
        }
        $model = new ModelAdmin();
       
        $rule = [
            'account'=>'require|max:32|alphaDash',
            'name' => 'require|max:32|regex:/[-\w\x{4e00}-\x{9fa5}]+/iu',
            'nickname' => 'max:32|regex:/[-\w\x{4e00}-\x{9fa5}]+/iu',
            'mobile'=>'regex:/^\d{11}$/',
            'email'=>'email',
            'password'=>'require|max:32|confirm:repasswd',
            'repasswd'=>'confirm:password',
            'remark' => 'max:255'
        ];
        $msg = [
            'account.require' => '账号不能为空',
            'account.max' => '账号不超过32个字符',
            'account.alphaDash' => '账号由字母、数字、下划线及横线组成',
            'name.require' => '姓名不能为空',
            'name.max' => '姓名不超过32个字符',
            'name' => '姓名由汉字、字母、数字、下划线及横线组成',
            'nickname.max' => '昵称不超过32个字符',
            'nickname' => '昵称由汉字、字母、数字、下划线及横线组成',
            'mobile' => '手机格式错误',
            'email' => '邮件格式错误',
            'password.require' => '密码不能为空',
            'password.max' => '密码不超过32个字符',
            'repasswd' => '两次密码不一致',
            'remark' => '备注不超过255个字符',
        ];
        $check = $this->validate($data, $rule, $msg);
        if ($check !== true) {
            return errorReturn($check);
        }
        unset($data['repasswd']);
        $data['password'] = getPwdHash($data['password']);
        $t = $model->where(['account' => $data['account'],'status'=>1 ])->value('id');
        if ($t) {
            return errorReturn('该账号已被使用');
        }
        try {
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['uuid'] = getUUid();
            $id = $model->insertGetId($data);
            $model = new AdminRole();
            foreach($roles as $role){
                if($role < 1){
                    continue;
                }
                $model->isUpdate(false)->data(['admin_id'=>$id,'role_id'=>(int)$role])->save();
            }
            $data['id'] = $id;
            return sucReturn('保存成功', $data);
        } catch (Exception $e) {
            return errorReturn('保存失败(' . $e->getMessage());
        }
    }

    /**
     * 编辑管理员
     */
    function update($uuid)
    {
        if (!$uuid) {
            return errorReturn('非法访问');
        }
        $data = [];
        $data['name'] = input('name');
        $data['nickname'] = input('nickname');
        $data['mobile'] = input('mobile');
        $data['email'] = input('email');       
        $data['remark'] = input('remark');
        $roles = input('roles/a');
 
        if(!$roles){
            return errorReturn('请选择角色');
        }
        $model = new ModelAdmin();
       $passwd =  input('passwd');
       $repasswd =  input('repasswd');
       if($passwd && $passwd != $repasswd){
            return errorReturn('两次密码不一致');
       }
        $rule = [
            'name' => 'require|max:32|regex:/[-\w\x{4e00}-\x{9fa5}]+/iu',
            'nickname' => 'max:32|regex:/[-\w\x{4e00}-\x{9fa5}]+/iu',
            'mobile'=>'regex:/^\d{11}$/',
            'email'=>'email',
            'password'=>'max:32|confirm:repasswd',
            'repasswd'=>'confirm:password',
            'remark' => 'max:255'
        ];
        $msg = [
            'name.require' => '姓名不能为空',
            'name.max' => '姓名不超过32个字符',
            'name' => '姓名由汉字、字母、数字、下划线及横线组成',
            'nickname.max' => '昵称不超过32个字符',
            'nickname' => '昵称由汉字、字母、数字、下划线及横线组成',
            'mobile' => '手机格式错误',
            'email' => '邮件格式错误',
            'password.max' => '密码不超过32个字符',
            'repasswd' => '两次密码不一致',
            'remark' => '备注不超过255个字符',
        ];
        $check = $this->validate($data, $rule, $msg);
        if ($check !== true) {
            return errorReturn($check);
        }
        $t = $model->where(['name' => $data['name'],'status'=>1 ])->order('id desc')->find();
        if ($t && $t['uuid'] != $uuid) {
            return errorReturn('该姓名已被使用');
        }
        try {
            if($passwd){
                $data['password'] = getPwdHash($passwd);
            }
            $model->save($data,['uuid'=>$uuid]);
            $modelRole = new AdminRole();
            $modelRole->where(['admin_id'=>$t['id']])->delete();
            foreach($roles as $role){
                if($role < 1){
                    continue;
                }
                $modelRole->isUpdate(false)->data(['admin_id'=>$t['id'],'role_id'=>(int)$role])->save();
            }
            return sucReturn('保存成功', $data);
        } catch (Exception $e) {
            return errorReturn('保存失败(' . $e->getMessage().$e->getTraceAsString());
        }
    }
    //删除管理员
    function del()
    {
        $uuid = input('uuid');
        if(!$uuid){
            return errorReturn('非法访问');
        }

        $model = new ModelAdmin();
        $model->where(['uuid' => $uuid])->update(['status'=>0]);

        (new AdminSupporter())->save(['status'=>0],['uuid'=>$uuid]);
        return sucReturn('删除成功');
    }

    function modifyPwd(){
        $old = input('old');
        $pwd = input('pwd');
        $repwd = input('repwd');
        if(!$old || !$pwd){
            return errorReturn('请输入旧密码或新密码');
        }
        if($pwd != $repwd){
            return errorReturn('2次密码不一致');
        }
        if(strlen($pwd) > 32){
            return errorReturn('密码最长32位');
        }
        $model = new ModelAdmin();
        $info = $model->where(['id'=>$this->user_id])->field('id,password')->find();
        if(!$info){
            return errorReturn('修改失败,请刷新页面重试');
        }
        if(!checkPwd($old,$info['password'])){
            return errorReturn('旧密码错误');
        }
        $new = getPwdHash($pwd);
        $model->where(['id'=>$this->user_id])->update(['password'=>$new]);
        Session::delete('userinfo');
        return sucReturn('修改成功');

    }
}
