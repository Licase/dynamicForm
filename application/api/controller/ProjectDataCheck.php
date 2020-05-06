<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Project;
use app\api\model\Template;
use app\api\model\ProjectData as ModelProjectData;
use app\api\model\ProjectDataCheck as ModelProjectDataCheck;
use app\api\model\ProjectDataDetail;
use app\api\model\ProjectFlow;
use app\api\model\TemplateField;
use think\Cache;
use think\Exception;

class ProjectDataCheck extends Controller
{
      /**
     * 获取审核的项目列表
     */
    function index()
    {
        $page = (int) input('page', 1);
        $page = max(1, $page);
        $pagesize = (int) input('pagesize', 30);
        $pagesize = max($pagesize, 1);

        $list = [];
        $data = ['list' => [], 'paginate' => ''];

        $modelProject = new Project();
        $where = ['b.user_id'=>$this->user_id,'b.total'=>['>',0]];

        $tpl = 'proCheck/proList';
        $baseUrl = Cache::get('baseUrl');
        $list = $modelProject->alias('a')->join('tab_user_data_check_stat b','a.id = b.p_id')->where($where)->field('a.id,a.name,b.total,a.status,a.uuid')
                    ->paginate(['list_rows' => $pagesize, 'page' => $page, 'path' => $baseUrl.'/api/v1/mycheck']);
        $paginate = $list->render();
        $data['paginate'] = $paginate;
        $data['list'] = $list ? $list->toArray() : [];
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;

        return $this->view->fetch($tpl, $data);
    }

    //查看具体项目数据
    function viewPro($uuid){
        $page = (int) input('page', 1);
        $page = max(1, $page);
        $pagesize = (int) input('pagesize', 30);
        $pagesize = max($pagesize, 1);

        $isOwn = input('isOwn',0);
        
        $list = [];
        $data = ['list' => [], 'paginate' => ''];


        $modelProject = new Project();
        
        $modelData= new ModelProjectData();
        $where= ['a.status'=>1];
 
        $where['c.check_user'] = $this->user_id;
        $where['c.check_status'] = 0;
    
        $p_id = 0;
        $titles = [];
        $filters = [];
    
        $pInfo = $modelProject->getProInfo($uuid);
        $data['proInfo'] = $pInfo;
        $p_id = $pInfo['id'];
        $where['a.p_id'] = $p_id;
    
        $tempInfo = (new TemplateField())->where(['temp_id'=>['in',$pInfo['temps']]])->order('temp_id asc,sort asc')->select();
        
        foreach($tempInfo as $item ){
            if($item['is_title']){
                $titles[$item['id']] = $item->getData();
            }
            if($item['is_filter']){
                $filters[$item['id']] = $item->getData();
            }
        }
        unset($tempInfo);
    $detailModel = new ProjectDataDetail();
    $baseUrl = Cache::get('baseUrl');
    $list = $modelData->alias('a')
                    ->join('tab_user_data_check c','c.p_id = a.p_id and c.data_id = a.id','LEFT')
                    ->join('tab_admin b','c.check_user = b.id','LEFT')
                    ->where($where)
                    ->field('a.id,a.p_id,cur_step,check_status,b.name,b.nickname,check_time,is_complete,from_code,a.create_time')
                    ->order('sorts,id desc')
                    ->paginate(['list_rows' => $pagesize, 'page' => $page, 'path' => $baseUrl.'/api/v1/data'])
                    ->each(function(&$item,$key)use($detailModel,$titles){
                        $where =['p_id'=>$item['p_id'],'data_id'=>$item['id']];
                        if($item['cur_step']  == 1){
                            $where['step'] = $item['cur_step'];
                        }else{
                            $where['step'] = ['<=',$item['cur_step']];
                        }
                        
                        $tmps = $detailModel->where($where)->field('field_id,val,remark')->select();
                        $item = $item->getData();
                        $data = [];
                        switch($item['check_status']){
                            case 1:
                                $item['check'] = '通过';
                            break;
                            case 2:
                                $item['check'] = '驳回';
                            break;
                            default:
                                $item['check'] = '-';
                        }
                        if($tmps){
                            foreach($tmps as $t){
                                if(isset($titles[$t['field_id']])){
                                    $data[ $t['field_id'] ] = $t->getData();
                                }    
                            }
                        }
                        $item['userData'] = $data;
                        return $item;
                    });
            $paginate = $list->render();
            $data['paginate'] = $paginate;
        
    
        $data['isOwn'] = $isOwn;
        $data['titles'] = $titles;
 
        $data['list'] = $list ? $list->toArray() : [];
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;

        
        return $this->view->fetch('proCheck/checkList', $data);
    }

    /**查看数据详情
     * 1. 根据data_id拿到p_id后找出项目信息,流程信息,所有的模板信息
     * 2. 只有当前步骤被驳回的情况下提交人可以编辑，否则无法编辑
     * */
    function view($id){
        if($id < 1){
            return errorReturn('非法访问');
        }
        $modelData = new ModelProjectData();
        $info = $modelData->where(['id'=>$id])->field('p_id,cur_step,is_complete')->find();
        
        $data = [];
        
        $p_id = $info['p_id'];
        $proInfo = (new Project())->getProInfo($p_id,1);
        $data['proInfo'] = $proInfo;

        $tempInfo = (new Template())->where(['id'=>['in',$proInfo['temps']]])->field('id,name')->select();
        $tempInfo = array_column($tempInfo->toArray(),null,'id');

        $where = ['p_id'=>$p_id];
        $flows = (new ProjectFlow())->where($where)->field('id,temps,step')->order('step')->select();
        $tempInfos = [];
        foreach($flows as $flow){
            $t = explode(',',$flow['temps']);
            foreach($t as $tid){
                $tempInfos[$flow['step']][] = $tempInfo[$tid];
            }
        }
        $checkInfo = (new ModelProjectDataCheck())->alias('a')->join('tab_admin b','a.check_user = b.id')->where(['p_id'=>$p_id,'data_id'=>$id])->field('step,b.name,b.nickname,check_status,check_time,a.remark')->select();
        $data['checkInfo'] = array_column( ($checkInfo ? $checkInfo->toArray() : []),null,'step');
        $modelDetail = new ProjectDataDetail();
        $fieldDatas = $modelDetail->where(['p_id'=>$p_id,'data_id'=>$id])->field('id,field_id,val,remark')->select();
        $fieldDatas = $fieldDatas ? $fieldDatas->toArray() : [];
        $fieldDatas = array_column($fieldDatas,null,'field_id');
        
        $modelField = new TemplateField();
        $fields = $modelField->where(['temp_id'=> ['in',$proInfo['temps']]])
                            ->field('id,temp_id,name,data_type as dataType')
                            ->order('temp_id,sort,id')
                            ->select();
        $fields = $fields ? $fields->toArray() : [];
        $tempFields = [];
        foreach($fields as $item){
            $fid = (int)$item['id'];
            $item['val'] = '';
            $item['remark'] = '';
            $item['detail_id'] = 0;
            if(isset($fieldDatas[$fid])){
                $item['val'] = $fieldDatas[$fid]['val'];
                $item['remark'] = $fieldDatas[$fid]['remark'];
                $item['detail_id'] = $fieldDatas[$fid]['id'];
            }
            $tempFields[$item['temp_id']][] = $item;
        }
        unset($fields);
        $data['data_id'] = $id;
        $data['tempInfos'] = $tempInfos;
        $data['tempFields'] = $tempFields;
        return $this->view->fetch('projectData/view',$data);

    }
}
