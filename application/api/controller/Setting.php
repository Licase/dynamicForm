<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Setting as ModelSetting;

class Setting extends Controller
{
    /**
     * 获取设置项
     */
    function index()
    {
        $model = new ModelSetting();
        $data = [];
        return $this->view->fetch('setting',$data);
    }

    /**
     * 保存设置项
     */
    function save()
    {
        
    }

    
}
