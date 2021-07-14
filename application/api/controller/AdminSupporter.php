<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Admin;
use app\api\model\AdminRole;
use app\api\model\AdminSupporter as ModelAdminSupporter;
use app\api\model\AdminSupporterStat;
use app\api\model\Role;
use think\Cache;
use think\Exception;

class AdminSupporter extends Controller
{
    /**
     * 获取客服列表
     */
    function index()
    {
        $page = (int) input('page', 1);
        $page = max(1, $page);
        $pagesize = (int) input('pagesize', 15);
        $pagesize = max($pagesize, 1);

        $name = input('name');

        $model = new ModelAdminSupporter();
        $where = ['a.status'=>1];
        $list = [];
        $data = ['list' => [], 'paginate' => ''];
        $baseUrl = Cache::get('baseUrl');
        $list = $model->alias('a')->join('tab_admin b','a.uuid = b.uuid')
        ->where($where)
        ->field('a.uuid,b.name,b.nickname,a.showname,b.mobile,a.online_total,a.online_today,served_total,served_today')
        ->paginate(['list_rows' => $pagesize, 'page' => $page, 'path' => $baseUrl.'/api/v1/supporter']);
        $paginate = $list->render();
        $data['paginate'] = $paginate;

        $admins = (new Admin())->where(['status'=>1])->field('uuid,name')->select();
        $data['admins'] = $admins ? $admins->toArray() : [];
        $data['list'] = $list ? $list->toArray():[];
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;

        $data['name'] = $name;

        return $this->view->fetch('Supporter/list', $data);
    }

    /**
     * 添加客服
     */
    function save()
    {
        $data = [];
        $data['showname'] = input('showname');
        $uuid = input('uuid');
        if(!$data['showname'] || !$uuid){
            return errorReturn('非法访问');
        }
        $model = new Admin();
        $admin_id = $model->where(['uuid'=>$uuid,'status'=>1])->value('id');
        if($admin_id < 1){
            return errorReturn('人员不存在');
        }
       $data['uuid'] = $uuid;
        try {
            $model = new ModelAdminSupporter();
            $t = $model->where(['uuid' => $data['uuid']])->value('uuid');
            if ($t) {
                $data['status'] =1;
                unset($data['uuid']);
                $model->save($data,['uuid'=>$uuid]);
            }else{
                $id = $model->insertGetId($data);
            }
            
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
        $data['showname'] = input('showname');

        try {
            $model = new ModelAdminSupporter();
            $model->save($data,['uuid'=>$uuid]);
         
            return sucReturn('保存成功', $data);
        } catch (Exception $e) {
            return errorReturn('保存失败(' . $e->getMessage().$e->getTraceAsString());
        }
    }
    //客服
    function del()
    {
        $uuid = input('uuid');
        if(!$uuid){
            return errorReturn('非法访问');
        }

        $model = new ModelAdminSupporter();
        $model->where(['uuid' => $uuid])->update(['status'=>0]);
        return sucReturn('删除成功');
    }
}
