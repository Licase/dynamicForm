<?php

/**
 * User: zhangshuaishuai
 * Date: 2018/07/03 17:35
 */

namespace app\api\model;

use \Think\Model;

class TemplateField extends Model {
    protected $table = 'tab_template_field';
    protected $create_time = false;
    protected $update_time = false;
    function __construct($data = [])
    {
        parent::__construct($data);
    }
}
