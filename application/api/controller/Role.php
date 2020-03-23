<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Role as ModelRole;
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

        $data['list'] = $list;
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;
        $data['total'] = $total;
        $data['name'] = $name;

        return $this->view->fetch('list', $data);
    }
        /**
     * 获取角色属性
     */
    function read($id)
    {
        $model = new ModelRole();
        $list = $model->where(['id' => $id])->find();
        return sucReturn('ok', $list);
    }

    /**
     * 添加角色
     */
    function save()
    {
        $name = input('name');
        $remark = input('remark');
        $model = new ModelRole();
        $data = ['name' => $name, 'remark' => $remark];
        $rule = [
            'name' => 'require|max:32|regex:/[-\w\x{4e00}-\x{9fa5}]+/iu',
            'remark' => 'max:255'
        ];
        $msg = [
            'name.require' => '名称不能为空',
            'name.max' => '名称不超过32个字符',
            'name' => '名称由汉字、字母、数字、下划线及横线组成',
            'remark.max' => '备注不超过255个字符'
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
            $data['create_time'] = date('Y-m-d H:i:s');
            $id = $model->insertGetId($data);
            $data['id'] = $id;
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
        $model = new ModelRole();
        $data = ['name' => $name, 'remark' => $remark];
        $rule = [
            'name' => 'require|max:32|regex:/[-\w\x{4e00}-\x{9fa5}]+/iu',
            'remark' => 'max:255'
        ];
        $msg = [
            'name.require' => '名称不能为空',
            'name.max' => '名称不超过32个字符',
            'name' => '名称由汉字、字母、数字、下划线及横线组成',
            'remark.max' => '备注不超过255个字符'
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
            $model->save($data, ['id' => $id]);
            $data['id'] = $id;
            return sucReturn('ok', $data);
        } catch (Exception $e) {
            return errorReturn('保存失败(' . $e->getMessage());
        }
    }
    //隐藏或显示角色
    function del()
    {
        $id = input('id');
        $model = new ModelRole();
        $model->where(['id' => $id])->update(['status'=>0]);
        return sucReturn('删除成功');
    }
}
