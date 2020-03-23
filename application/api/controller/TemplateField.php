<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Template;
use app\api\model\TemplateDataDetail;
use app\api\model\TemplateField as FieldModel;
use think\Exception;

class TemplateField extends Controller
{

    /**
     * 保存模板字段
     */
    function save()
    {
        $tid = (int)input('t_id');
        if($tid < 1){
            return errorReturn('非法访问');
        }
        $name = input('name');
        $date_type = input('dataType');
        $options = input('options');
        $sort = (int)input('sort',20);
        $is_title = (int)input('isTitle',0);
        $is_require = (int)input('isRequire',0);
        $is_filter = (int)input('isFilter',0);
        $is_sort = (int)input('isSort',0);
        $remark = input('remark');
        $model = new FieldModel();
        $data = [
            'temp_id'=>$tid,
            'name' => $name, 
            'data_type' =>$date_type,
            'options' =>$options,
            'sort'=>$sort,
            'user_id'=>$this->user_id,
            'is_title' => $is_title,
            'is_require'=>$is_require,
            'is_filter'=>$is_filter,
            'is_sort'=>$is_sort,
            'remark' => $remark
        ];
        if(in_array($date_type,[DT_SELECT,DT_RADIO]) && !$options){
            return errorReturn('请填写可选值');
        }
        $rule = [
            'name' => 'require|max:32|regex:/[-\w\x{4e00}-\x{9fa5}]+/iu',
            'sort' => 'number|>:0',
            'remark' => 'max:255'
        ];
        $msg = [
            'name.require' => '名称不能为空',
            'name.max' => '名称不超过32个字符',
            'name' => '名称由汉字、字母、数字、下划线及横线组成',
            'sort'=>'排序值只能为正整数',
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
            
            $count = $model->where(['temp_id'=>$tid])->count('id');
            if($count > 0){
                $template = new Template();
                $template->update(['field_count'=>$count],['id'=>$tid]);
            }
            return sucReturn('保存成功', $data);
        } catch (Exception $e) {
            return errorReturn('保存失败(' . $e->getMessage());
        }
    }

    /**
     * 编辑模板字段
     */
    function updateTemplateField(){

    }
    function del(){
        $id = (int)input('id');
        $tid = (int)input('t_id');
        $model = new FieldModel();
        $model->where(['id' => $id])->delete();

        $count = $model->where(['temp_id'=>$tid])->count('id');
        if($count > 0){
            $template = new Template();
            $template->update(['field_count'=>$count],['id'=>$tid]);
        }
        $modelData = new TemplateDataDetail();
        $modelData->where(['flow_id'=>$tid,'field_id'=>$id])->delete();
        return sucReturn('删除成功');
    }
}
