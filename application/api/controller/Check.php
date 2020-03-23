<?php

namespace app\api\controller;

use app\api\model\Template;
use app\api\model\TemplateField;
use think\Controller;
use think\Exception;

class Check extends Controller
{
    /**
     * 检测模板字段数
     */
    function checkFields()
    {
        $sql = "select count(id) as count,temp_id from tab_";
        $model = new TemplateField();
        $stat=  $model->field('count(id) as num,temp_id')->group('temp_id')->select();
        $stat = $stat ? $stat->toArray() : [];
        $model = new Template();
        foreach($stat as $item){
            if($item['num'] < 1){
                continue;
            }
            $model->update(['field_count'=>$item['num']],['id'=>$item['temp_id']]);
        }
        return 'ok';
    }
}
