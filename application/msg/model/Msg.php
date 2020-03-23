<?php

/**
 * User: zhangshuaishuai
 * Date: 2018/07/03 17:35
 */

namespace app\msg\model;

use think\Model;

class Msg extends Model {
    protected $table = 'tab_msg';
    protected $update_time = false;
    function __construct($data = [])
    {
        parent::__construct($data);
    }
}
