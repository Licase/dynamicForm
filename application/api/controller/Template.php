<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Template as TemplateModel;
use app\api\model\TemplateField;
use think\Config;
use think\Exception;

class Template extends Controller
{
    /**
     * 获取模板列表
     */
    function index()
    {
        $page = (int) input('page', 1);
        $page = max(1, $page);
        $pagesize = (int) input('pagesize', 15);
        $pagesize = max($pagesize, 1);

        $name = input('name');

        $model = new TemplateModel();
        $where = ['is_show' => 1];
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
            $list = $model->where($where)->order('id desc')->paginate(['list_rows' => $pagesize, 'page' => $page, 'path' => '', 'fragment' => 'template']);

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
     * 获取模板
     */
    function read($id)
    {
        $model = new TemplateModel();
        $list = $model->where(['id' => $id])->find();
        return sucReturn('ok', $list);
    }

    /**
     * 获取模板详情,用于编辑与预览
     */
    function getDetail($id)
    {
        if (!$id) {
            exit('非法访问');
        }
        $modelTemplate = new TemplateModel();
        $info = $modelTemplate->where(['id' => $id])->field('id,name,remark')->find();
        if (!$info) {
            exit('模板不存在');
        }

        $model = new TemplateField();
        $list = $model->where(['tempalte_id' => $id])->order('sort asc,id asc')->select();

        $data['dataType'] = Config::get('dataType');
        $data['info'] = $info;
        $data['fieldList'] = $list;
        return $this->view->fetch('design', $data);
    }

    /**
     * 保存模板
     */
    function save()
    {
        $name = input('name');
        $remark = input('remark');
        $model = new TemplateModel();
        $data = ['name' => $name, 'remark' => $remark];
        $rule = [
            'name' => 'require|max:32|regex:/[-\w\x4e00-\x9fa5]+/iU',
            'remark' => 'max:255'
        ];
        $msg = [
            'name.require' => '名称不能为空',
            'name.max' => '名称不超过32个字符',
            'name' => '名称由字母、数字和下划线构成',
            'remark.max' => '备注不超过255个字符'
        ];
        $check = $this->validate($data, $rule, $msg);
        if ($check !== true) {
            return errorReturn($check);
        }

        $t = $model->where(['name' => $name,'is_show'=>1])->field('id')->find();
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
     * 编辑模板属性
     */
    function update($id)
    {
        if ($id < 1) {
            return errorReturn('非法访问');
        }
        $name = input('name');
        $remark = input('remark');
        $model = new TemplateModel();
        $data = ['name' => $name, 'remark' => $remark];
        $rule = [
            'name' => 'require|max:32|regex:/[-\w\x4e00-\x9fa5]+/iU',
            'remark' => 'max:255'
        ];
        $msg = [
            'name.require' => '名称不能为空',
            'name.max' => '名称不超过32个字符',
            'name' => '名称由字母、数字和下划线构成',
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

    function del()
    {
        $id = input('id');
        $model = new TemplateModel();
        $model->update(['is_show' => 0], ['id' => $id]);
        return sucReturn('删除成功');
    }
}
