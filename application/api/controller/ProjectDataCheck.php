<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Admin;
use app\api\model\Project;
use app\api\model\Template;
use app\api\model\ProjectData ;
use app\api\model\ProjectDataCheck as ModelProjectDataCheck;
use app\api\model\ProjectDataCheckStat;
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

        $baseUrl = Cache::get('baseUrl');
        $list = $modelProject->alias('a')->join('tab_user_data_check_stat b','a.id = b.p_id')->where($where)->field('a.id,a.name,b.total,a.status,a.uuid')
                    ->paginate(['list_rows' => $pagesize, 'page' => $page, 'path' => $baseUrl.'/api/v1/mycheck']);
        $paginate = $list->render();
        $data['paginate'] = $paginate;
        $data['list'] = $list ? $list->toArray() : [];
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;

        $tpl = 'ProCheck/proList';
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
        
        $modelData= new ProjectData();
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
                    ->order('a.id desc')
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
        
    
        $data['isOwn'] = $isOwn;
        $data['titles'] = $titles;
 
        $data['list'] = $list ? $list->toArray() : [];
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;

        
        return $this->view->fetch('ProCheck/checkList', $data);
    }

    /**查看数据详情并审核
     * 1. 根据data_id拿到p_id后找出项目信息,流程信息,所有的模板信息
     * 2. 只有当前步骤被驳回的情况下提交人可以编辑，否则无法编辑
     * */
    function view($id){
        if($id < 1){
            return errorReturn('非法访问');
        }
        $modelData = new ProjectData();
        $info = $modelData->where(['id'=>$id])->field('p_id,cur_step,is_complete')->find();
        
        $data = [];
        
        $p_id = $info['p_id'];

        /**
          * 1.判断该条数据当前最新的一条审核数据，如果和当前步一样，且未通过审核，则可以重填 
          * 2. 如果通过审核，更新最新的当前步为下一步或者完成状态(没有后续时)
          * 3. 当前审核步不是最终步，且有模板可填,保存后自动默认审核通过
          * 4. 驳回后,当前步-1，上一步提交人可以继续修改
        */
        $curStep = $info['cur_step'];
        $data['curStep'] = $curStep;

        $proInfo = (new Project())->getProInfo($p_id,1);
        $data['proInfo'] = $proInfo;

        $tempInfo = (new Template())->where(['id'=>['in',$proInfo['temps']]])->field('id,name')->select();
        $tempInfo = array_column($tempInfo->toArray(),null,'id');

        $where = ['p_id'=>$p_id];
        $flows = (new ProjectFlow())->where($where)->field('id,temps,has_check,step')->order('step')->select();
        $tempInfos = [];

        $checkInfo = (new ModelProjectDataCheck())->alias('a')->join('tab_admin b','a.check_user = b.id')->where(['p_id'=>$p_id,'data_id'=>$id])->field('step,b.name,b.nickname,a.check_user,check_status,check_time,a.remark')->select();
        if($checkInfo){
            $checkInfo = $checkInfo->toArray();
            $checkInfo  = array_column( $checkInfo,null,'step');
        }else{
            $checkInfo = [];
        }
        $hasCheck = false;
        $editFlag = 0;//上一步的审批人是本人,可以驳回和提交
        if(!$info['is_complete']  && isset($checkInfo[$curStep-1]) && $checkInfo[$curStep-1]['check_user'] == $this->user_id ){
            $editFlag = $curStep;
        }
        foreach($flows as $flow){
            if(!$flow['temps']){
                continue;
            }
            if($editFlag && $curStep == $flow['step'] && $flow['has_check']){
                $hasCheck = true;
            }
            $t = explode(',',$flow['temps']);
            foreach($t as $tid){
                $tempInfos[$flow['step']][] = $tempInfo[$tid];
            }
        }
        $data['hasCheck'] = $hasCheck;
        $data['checkInfo'] = $checkInfo;
      
        $data['editFlag'] = $editFlag;
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

            if($item['is_require']){
                $field_require[$fid] = [ 'dataType'=>$item['dataType'],'name'=>$item['name']];
            }

            if(isset($fieldDatas[$fid])){
                $item['val'] = $fieldDatas[$fid]['val'];
                $item['remark'] = $fieldDatas[$fid]['remark'];
                $item['detail_id'] = $fieldDatas[$fid]['id'];
            }
            $tempFields[$item['temp_id']][] = $item;
        }
        $data['fieldRequire'] = $field_require;
        unset($fields);

        $data['auditUser'] =  (new Admin())->getAuthUsers($proInfo['admin_roles']);

        $data['dataId'] = $id;
        $data['user_id'] = $this->user_id;
        $data['tempInfos'] = $tempInfos;
        $data['tempFields'] = $tempFields;
        return $this->view->fetch('proCheck/view',$data);

    }
    //驳回数据
    function check($id){
        $reason = input('post.reason');
        $model = new ProjectData();
        $info = $model->where(['id'=>$id])->field('p_id,cur_step')->find();

        $model->where(['id'=>$id])->update(['cur_step'=>$info['cur_step']-1,'update_time'=>date('Y-m-d H:i:s')]);

        $modelCheck = new ModelProjectDataCheck();
        $modelCheck->where(['p_id'=>$info['p_id'],'data_id'=>$id,'step'=>$info['cur_step']-1 ])
        ->update(['check_user'=>$this->user_id,'check_status'=>2,'remark'=>$reason,'check_time'=>date('Y-m-d H:i:s')]);
        
        ProjectDataCheckStat::decStat($info['p_id'],$this->user_id);

        return sucReturn('驳回成功');
    }
}
