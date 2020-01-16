<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Template;
use app\api\model\TemplateField;
use think\Exception;

class TemplateData extends Controller
{
    function templateList()
    {
        $isshow = input('isshow');
        $model = new Template();
        $where = [];
        if ($isshow) {
            $where['is_show'] = $isshow;
        }
        $list = $model->where($where)->select();
        return sucReturn('ok', $list);
    }

    function saveTemplate()
    {
        $name = input('name');
        $remark = input('remark');
        $model = new Template();
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
        if ($t) {
            return errorReturn('该名称已被使用');
        }
        try {
            $data['create_time'] = date('Y-m-d H:i:s');
            $id = $model->insertGetId($data);
            $data['id'] = $id;
            return sucReturn('ok', $data);
        } catch (Exception $e) {
            return errorReturn('保存失败(' . $e->getMessage());
        }
    }

    function updateTemplate($id)
    {
        if ($id < 1) {
            return errorReturn('非法访问');
        }
        $name = input('name');
        $remark = input('remark');
        $model = new Template();
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

    function delTemplate()
    {
        $ids = input('ids');
        $model = new Template();
        $model->update(['is_show' => 0], ['id' => ['in', $ids]]);
        return sucReturn('ok');
    }

    function getTemplateFields($id)
    {
        $model = new TemplateField();
        $list = $model->where(['tempalte_id'=>$id])->order('sort asc')->select();

    }

    function saveTemplateField()
    {
        $template_id = (int)input('id');
        $name = input('name');
        $remark = input('remark');
        $model = new Template();
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
        if ($t) {
            return errorReturn('该名称已被使用');
        }
        try {
            $data['create_time'] = date('Y-m-d H:i:s');
            $id = $model->insertGetId($data);
            $data['id'] = $id;
            return sucReturn('ok', $data);
        } catch (Exception $e) {
            return errorReturn('保存失败(' . $e->getMessage());
        }
    }

    function updateTemplateField(){

    }
}
