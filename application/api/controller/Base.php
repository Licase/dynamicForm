<?php
namespace app\api\controller;


use think\Controller;
use think\Request;

class Base extends Controller
{
    protected $user_id = 0;
    public function __construct(Request $request = null)
    {
        $this->user_id = 1;
        parent::__construct($request);

    }
    public function _empty($name){
        return $this->display('not exist action:'.$name);
    }

}

