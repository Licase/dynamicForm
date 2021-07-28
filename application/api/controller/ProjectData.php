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

class ProjectData extends Controller
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

        $tpl = 'projectData/list';
        $data['proList'] = '';
        $baseUrl = Cache::get('baseUrl');
        $list = $modelProject->where($where)->field('id,name,total,status,uuid')
                    ->paginate(['list_rows' => $pagesize, 'page' => $page, 'path' => $baseUrl. '/api/v1/data']);
        $paginate = $list->render();
        $data['paginate'] = $paginate;
        $data['isOwn'] = 0;
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
        $baseUrl = Cache::get('baseUrl');
        $detailModel = new ProjectDataDetail();
        $list = $modelData->alias('a')
                        ->join('tab_user_data_check c','c.p_id = a.p_id and c.data_id = a.id and c.step = 1','LEFT')
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
        $proInfo = (new Project())->getProInfo($p_id,1);
        $data['proInfo'] = $proInfo;

        $tempInfo = (new Template())->where(['id'=>['in',$proInfo['temps']]])->field('id,name')->select();
        $tempInfo = array_column($tempInfo->toArray(),null,'id');

        $where = ['p_id'=>$p_id];
        $flows = (new ProjectFlow())->where($where)->field('id,temps,step')->order('step')->select();
        $tempInfos = [];
        foreach($flows as $flow){
            if(!$flow['temps']){
                continue;
            }
            $t = explode(',',$flow['temps']);
            foreach($t as $tid){
                $tempInfos[$flow['step']][] = $tempInfo[$tid];
            }
        }
        $checkInfo = (new ProjectDataCheck())->alias('a')->join('tab_admin b','a.check_user = b.id')->where(['p_id'=>$p_id,'data_id'=>$id])->field('step,b.name,b.nickname,check_status,check_time,a.remark')->select();
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

        $data['has_check'] = $pInfo['roles'] && $flowInfo[0]['has_check'] ? true : false;
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
        
        $data['auditUser'] =  (new Admin())->getAuthUsers($pInfo['admin_roles']);
        $data['userId'] = $this->user_id;
        $tpl = 'projectData/add';
        return $this->view->fetch($tpl, $data);
    }
    //用户数据保存
    function save($uuid){
        if(!$uuid){
            return errorReturn('非法访问');
        }
        $fieldData = input('field/a');
        $check_user = (int)input('check_user',0);
        $fromcode = input('code','0');
        $cur_step = input('curStep',1);
        $dataId = input('dataId',0);
        
        $modelProject = new Project();
        $pInfo = $modelProject->getProInfo($uuid);
        $p_id = $pInfo['id'];
        
        //获取流程信息，模板信息，验证数据有效
        $modelFlow = new ProjectFlow();
        $flowInfo = $modelFlow->where(['p_id'=>$p_id,'step'=>$cur_step])->field('temps,has_check')->find();
        if($flowInfo['has_check'] && $check_user < 1){
            return errorReturn('请选择审批人');
        }

        $tempFields = (new TemplateField())->where(['temp_id'=>['in',$flowInfo['temps']]])
                            ->field('id,name,temp_id,data_type as dataType,options,is_require,is_sort')
                            ->select();
        $fields = array_column( $tempFields->toArray() ,null,'id');

        //检查各字段数据
        $error = '';
        $field_files = [];
        $sortId = 0;
        foreach($fields as $field){
            $field_id = $field['id'];
            if($field['dataType'] == DT_FILE){ //上传文件字段单独判断
                $field_files[$field_id] = $field;
                continue;
            }
             //拿到排序字段
             if($field['is_sort'] && $sortId < $field_id){
                $sortId = $field_id;
            }
            $r = $field['is_require'] ? true : false;

            if(!isset($fieldData[$field_id]) ){
                if($r){
                    return errorReturn($field['name'].'不能为空');
                }else{
                    continue;
                }
            }
            $item = $fieldData[$field_id];
            
            if($item === '' || $item === null){
                if(!$r){
                    continue;
                }
                $error = $field['name'].'不能为空';
                break;
            }
            switch($field['dataType']){
                case 'date':
                    if($r && !preg_match('#^\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12][0-9]|3[01])$#',trim($item))){
                        $error = $field['name'].'-日期格式错误';
                    }
                break;
                case 'number':
                    if(!preg_match('#^-?\d+$#',$item)){
                        $error = $field['name'].'-数字格式错误';
                    }
                break;
                case 'radio':case 'select':
                    $vals = explode('|',$field['options']);
                    if(!in_array($item,$vals)){
                        $error = $field['name'].'-未知的选项值('.$item.')';
                    }
                    break;
                case 'checkbox':
                    $vals = explode('|',$field['options']);
                    foreach($item as $v){
                        if(!in_array($v,$vals)){
                            $error = $field['name'].'-未知的选项值('.$v.')';
                            break;
                        }
                    }
                    $fieldData[$field_id] = implode('|-|',$item);
                break;
            }
            
        }
        if($error){
            return errorReturn($error);
        }
        $file = isset($_FILES['field']) ? $_FILES['field'] : '';
        
        $uploadPath = UPLOAD_DIR.$this->getFileDir($uuid);
        if(!file_exists($uploadPath)){
            mkdir($uploadPath,0755,true);
        }
        $error = '';
        
        foreach($field_files as $fid => $field){
            if($field['is_require']){
                if(!$file || !isset($file['name'][$fid]) || $file['size'][$fid] < 1  ){
                    $error = $field['name'].'-文件不能为空';
                    break;
                }
            }
            $origin_name = isset($file['name'][$fid]) ? trim($file['name'][$fid]) : '';
            if(!$origin_name){
                unset($field_files[$fid]);
                continue;
            }
            if($file['size'][$fid] > 1*1024*1024*1024){
                $error = $field['name'].'-单个文件不能超过1G';
                break;
            }
        
            $ext = pathinfo($origin_name,PATHINFO_EXTENSION);

            $filename = $p_id.$fid.getUUid().'.'.$ext;
            
            if(move_uploaded_file($file['tmp_name'][$fid],$uploadPath.'/'.$filename) !== false){
                $field_files[$fid]['saved_name'] = $filename;
                $field_files[$fid]['origin_name'] = $origin_name;
            }
        }
        if($error){
            return errorReturn($error);
        }
        $time = date('Y-m-d H:i:s');
        $modelData = new ModelProjectData();
        $user_id = $this->user_id ?: 0;
        $isComplete = $pInfo['steps'] <= $cur_step ? 1 : 0;
        if($dataId < 1){
            $sortData = '';
            if($sortId && isset($fieldData[$sortId])){
                $sortData = $fieldData[$sortId];
            }
            $savedData = [
            'p_id'=>$p_id,
            'user_id'=>$user_id,
            'cur_step'=> $isComplete ? $cur_step : $cur_step+1,
            'sorts' => $sortData,
            'cur_day' => date('Y-m-d'),
            'from_code' => $fromcode,
            'is_complete'=>$isComplete,
            'create_time'=>$time,
            'update_time'=>$time
            ];
            $dataId = $modelData->insertGetid($savedData);
        }else{
            $savedData = [
                'cur_step'=> $isComplete ? $cur_step : $cur_step+1,
                'cur_day' => date('Y-m-d'),
                'is_complete'=> $isComplete,
                'update_time'=>$time
            ];
            $modelData->where(['id'=>$dataId])->update($savedData);
        }
        if($dataId > 0){
            
            $modelDetail = new ProjectDataDetail();
            if($fieldData){
                foreach($fieldData as $fid => $val){
                    if(!is_array($val) && ($val === '' || $val === null) ){
                        continue;
                    }
                    $tmp = [
                        'p_id' => $p_id,
                        'data_id' => $dataId,
                        'user_id' => $user_id,
                        'step' => $cur_step,
                        'temp_id' => $fields[$fid]['temp_id'],
                        'field_id' => $fid,
                        'val' => $val
                    ];
                    $modelDetail->create($tmp);
                }
            }
          
            foreach($field_files as $fid => $item){
                $tmp = [
                    'p_id' => $p_id,
                    'data_id' => $dataId,
                    'user_id' => $user_id,
                    'step' => $cur_step,
                    'field_id' => $fid,
                    'temp_id' => $fields[$fid]['temp_id'],
                    'val' => $item['origin_name'],
                    'remark' => $item['saved_name'],
                ];
                $modelDetail->create($tmp);
            }
            $modelCheck = new ProjectDataCheck();
            if($cur_step > 1){ //通过时,把上一步改成审批通过
                $where = ['p_id'=>$p_id,'data_id'=>$dataId,'step'=>$cur_step-1];
                $modelCheck->where($where)->update(['check_status'=>1,'check_time'=>date('Y-m-d H:i:s')]);
                ProjectDataCheckStat::decStat($p_id,$this->user_id);
            }
            if($flowInfo['has_check'] && !$isComplete){
                $where = ['p_id'=>$p_id,'data_id'=>$dataId,'step'=>$cur_step];
                $info = $modelCheck->where($where)->value('id');
                if($info){
                    $modelCheck->where(['id'=>$info])->update(['check_status'=>0,'check_user'=>$check_user,'check_time'=>null]);
                }else{
                    $modelCheck->create(['p_id'=>$p_id,'data_id'=>$dataId,'step'=>$cur_step,'user_id'=>$this->user_id,'check_user'=>$check_user]);
                }
                ProjectDataCheckStat::incStat($p_id,$check_user);
            }
            
        }else{
            return errorReturn('保存失败,请重试');
        }
        return sucReturn('保存成功');
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

    function getFileDir($uuid){
        return 'temp_'.$uuid.'/'.date('Ym').'/';
    }
}
