<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Admin;
use app\api\model\Project;
use app\api\model\Template;
use app\api\model\ProjectData as ModelProjectData;
use app\api\model\ProjectDataCheck;
use app\api\model\ProjectDataCheckStat;
use app\api\model\ProjectDataDetail;
use app\api\model\ProjectFlow;
use app\api\model\ProjectRoles;
use app\api\model\TemplateField;
use think\Cache;
use think\Exception;

class MyData extends Controller
{
      /**
     * 获取用户项目数据
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
        $where = ['steps'=>['>',0]];

        $where['status'] =  1;
        $ids = (new ModelProjectData())->where(['user_id'=>$this->user_id])->distinct(true)->column('p_id');
        if($ids){
            $where['id'] = ['in',$ids];
        }else{
            $where['id'] = ['<',1];
        }
        $tpl = 'myData/mylist';
        $data['proList'] = $this->getUserProjectList();
        $baseUrl = Cache::get('baseUrl');
        $list = $modelProject->where($where)->field('id,name,total,status,uuid')
                    ->paginate(['list_rows' => $pagesize, 'page' => $page, 'path' => $baseUrl.'/api/v1/myData']);
        $paginate = $list->render();
        $data['paginate'] = $paginate;
        $data['isOwn'] = 1;
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

        
        $list = [];
        $data = ['list' => [], 'paginate' => ''];


        $modelProject = new Project();
        
        $modelData= new ModelProjectData();
        $where= ['a.status'=>1];
        $where['a.user_id'] = $this->user_id;
        
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
                        ->join('tab_user_data_check c','c.p_id = a.p_id and c.data_id = a.id and c.step = 1','LEFT')
                        ->join('tab_admin b','c.check_user = b.id','LEFT')
                        ->where($where)
                        ->field('a.id,a.p_id,cur_step,check_status,b.name,b.nickname,check_time,is_complete,from_code,a.create_time')
                        ->order('a.id desc')
                        ->paginate(['list_rows' => $pagesize, 'page' => $page, 'path' =>$baseUrl.'/api/v1/myData'])
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
                                    $item['check'] = '未审核';
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
        $data['isOwn'] = 1;
    
        $data['titles'] = $titles;
 
        $data['list'] = $list ? $list->toArray() : [];
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;
        return $this->view->fetch('projectData/prodata', $data);
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
        $curStep = $info['cur_step'];
        $data['curStep'] = $curStep;
        
        $proInfo = (new Project())->getProInfo($p_id,1);
        $data['proInfo'] = $proInfo;
        $data['userId'] = $this->user_id;
        $tempInfo = (new Template())->where(['id'=>['in',$proInfo['temps']]])->field('id,name')->select();
        $tempInfo = array_column($tempInfo->toArray(),null,'id');

        $where = ['p_id'=>$p_id];
        $flows = (new ProjectFlow())->where($where)->field('id,temps,has_check,step')->order('step')->select();
        $tempInfos = [];
        $hasCheck = false;
       
        foreach($flows as $flow){
            if($curStep == $flow['step'] && $flow['has_check']){
                $hasCheck = true;
            }
            $t = explode(',',$flow['temps']);
            foreach($t as $tid){
                $tempInfos[$flow['step']][] = $tempInfo[$tid];
            }
        }
        $data['hasCheck'] = $hasCheck;
       
        $checkInfo = (new ProjectDataCheck())->alias('a')->join('tab_admin b','a.check_user = b.id')->where(['p_id'=>$p_id,'data_id'=>$id])->field('step,b.name,b.nickname,user_id,a.check_user,check_status,check_time,a.remark')->select();
        if($checkInfo){
            $checkInfo = $checkInfo->toArray();
            $checkInfo  = array_column( $checkInfo,null,'step');
        }else{
            $checkInfo = [];
        }
        $editFlag = 0;
        // 如果上一步的审核人是自己,则可以编辑与驳回 || 当前步是自己提交的并且被驳回,可以重新编辑
        if(isset($checkInfo[$curStep-1]) && $checkInfo[$curStep-1]['check_user'] == $this->user_id && $checkInfo[$curStep-1]['check_status'] == 0
        || isset($checkInfo[$curStep]) && $checkInfo[$curStep]['user_id'] == $this->user_id && $checkInfo[$curStep]['check_status'] == 2 ){
            $editFlag = $curStep;
        }
        $data['editFlag'] = $editFlag;
        $data['checkInfo'] = $checkInfo;

        $modelDetail = new ProjectDataDetail();
        $fieldDatas = $modelDetail->where(['p_id'=>$p_id,'data_id'=>$id])->field('id,field_id,val,remark')->select();
        $fieldDatas = $fieldDatas ? $fieldDatas->toArray() : [];
        $fieldDatas = array_column($fieldDatas,null,'field_id');
        
        $modelField = new TemplateField();
        $fields = $modelField->where(['temp_id'=> ['in',$proInfo['temps']]])
                            ->field('id,temp_id,name,data_type as dataType,is_require,options')
                            ->order('temp_id,sort,id')
                            ->select();
        $fields = $fields ? $fields->toArray() : [];
        $tempFields = [];
        $field_require = [];
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
            if($item['is_require'] && ($item['dataType'] != DT_FILE || $item['detail_id'] < 1 ) ){
                $field_require[$fid] = [ 'dataType'=>$item['dataType'],'name'=>$item['name']];
            }
            $tempFields[$item['temp_id']][] = $item;
        }
        $data['fieldRequire'] = $field_require;
        unset($fields);
        $data['auditUser'] =  (new Admin())->getAuthUsers($proInfo['admin_roles']);
        $data['dataId'] = $id;
        $data['isOwn'] = 1;
        $data['tempInfos'] = $tempInfos;
        $data['tempFields'] = $tempFields;
        return $this->view->fetch('proCheck/view',$data);

    }

    //添加数据
    function add($uuid){
        
        if(!$uuid){
            return errorReturn('非法访问');
        }
        $data = [];
        $modelProject = new Project();
        $pInfo = $modelProject->getProInfo($uuid);
        $data['proInfo'] = $pInfo;
        $p_id = $pInfo['id'];
      
        
        $data['uuid'] = $uuid;
        
        $modelFlow = new ProjectFlow();
        $flowInfo = $modelFlow->where(['p_id'=>$p_id])->field('temps,step,has_check')->order('step')->select();

        $data['has_check'] = $flowInfo[0]['has_check'];
        $tempInfo = (new Template())->where(['id'=>['in',$pInfo['temps']]])->field('id,name')->select();
        $tempInfo = array_column($tempInfo->toArray(),null,'id');

        $temps = [];
        foreach($flowInfo as $flow){
            $t = explode(',',$flow['temps']);
            foreach($t as $tid){
                $temps[$flow['step']][] = $tempInfo[$tid];
            }
        }
        unset($tempInfo);
        $data['flowInfo'] = $temps;

        $tempFields = (new TemplateField())->where(['temp_id'=>['in',$pInfo['temps']]])
                            ->field('id,temp_id,name,data_type as dataType,options,is_require')
                            ->order('temp_id,sort,id')->select();
        $fields = [];
        $field_require = [];
        foreach($tempFields as $item){
            if($item['is_require']){
                $field_require[$item['id']] = [ 'dataType'=>$item['dataType'],'name'=>$item['name']];
            }
            $fields[$item['temp_id']][] = $item->getData();
        }
        $data['tempFields'] = $fields;
        $data['fieldRequire'] = $field_require;
        $data['userId'] = $this->user_id;
        
        $data['auditUser'] =  (new Admin())->getAuthUsers($pInfo['admin_roles']);
        $tpl = 'projectData/add';
        return $this->view->fetch($tpl, $data);
    }

    /** 
     * 获取用户参与的项目列表
     * 1. 所有人可参与的项目
     * 2. 用户所属角色符合项目参与角色的
     * 3. 需要用户审核的
     */
    function getUserProjectList(){
        
        $roles = $this->user_info['roles'];
        $allowed = [];
        $where = ['roles'=>0];
        if($roles){
            $model = new ProjectRoles();
            $allowed= $model->where(['role_id'=>['in',$roles]])->distinct(true)->column('p_id');
            if($allowed){
                $where['id'] = ['in',$allowed];
            }
        }
        $model = new Project();
        $model->where(['status'=>1,'steps'=>['>',0]]);
        $model->where(function($query)use($where){
            $query->whereOr($where);
        });
        $proList = $model->field('id,name,total,uuid')->select();
        return $proList ? $proList->toArray() : [];
    }
    function del(){
        $id = (int) input('did');
        if($id < 1){
            return errorReturn('非法访问');
        }
        $model = new ModelProjectData();
        $model->where(['id'=>$id])->update(['status'=>0]);
        return sucReturn('删除成功');
    }

    function down($id){
        $model = new ProjectDataDetail();
        $info = $model->where(['id'=>$id])->field('flow_id,remark')->find();
        if(!$info){
            return "文件不存在";
        }
        $filename = UPLOAD_DIR.$this->getFileDir($info['flow_id']).$info['remark'];
        downloadFile($filename);
        
    }
    function getFileDir($uuid){
        return 'temp_'.$uuid.'/'.date('Ym').'/';
    }
}
