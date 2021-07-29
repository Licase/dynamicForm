<?php

namespace app\api\controller;

use think\Controller;
use app\api\model\Project;
use app\api\model\Template;
use app\api\model\ProjectData as ModelProjectData;
use app\api\model\ProjectDataDetail;
use app\api\model\ProjectFlow;
use app\api\model\TemplateField;
use think\Cache;
use think\Exception;

/**
 * 对公提交和展示项目数据
 */
class ProjectPublic extends Controller
{
    function __construct()
    {
        parent::__construct();
        $this->view->assign('baseUrl',Cache::get('baseUrl'));
    }
    //查看具体数据
    function view($uuid){
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
    
        $pInfo = $modelProject->getProInfo($uuid);
        $data['proInfo'] = $pInfo;
        $p_id = $pInfo['id'];
        $where['a.p_id'] = $p_id;
    
        $tempInfo = (new TemplateField())->where(['temp_id'=>['in',$pInfo['temps']]])->order('temp_id asc,sort asc')->select();
        $titles = [];
        $firstTitle = [];
        foreach($tempInfo as $item ){
            if($item['is_title']){
                if(!$firstTitle){
                    $firstTitle = [ 'name'=> $item['name'],'id'=>$item['id']];
                }
            }else{
                $titles[$item['id']] = $item->getData();
            }
        }
        unset($tempInfo);
        $detailModel = new ProjectDataDetail();
        $list = $modelData->alias('a')
                        ->join('tab_user_data_check c','c.p_id = a.p_id and c.data_id = a.id and c.step = 1','LEFT')
                        ->join('tab_admin b','c.check_user = b.id','LEFT')
                        ->where($where)
                        ->field('a.id,a.p_id,cur_step,check_status,b.name,b.nickname,check_time,is_complete,from_code,a.create_time')
                        ->order('sorts,id asc')
                        ->limit(30)->select();
        $list = $list ? $list->toArray() : [];
        foreach($list as $key =>$item){
            $where =['p_id'=>$item['p_id'],'data_id'=>$item['id']];
            $tmps = $detailModel->where($where)->field('field_id,val,remark')->select();
            $tmp = [];

            if($tmps){
                foreach($tmps as $t){
                    $tmp[ $t['field_id'] ] = $t->getData();
                }
            }
            if(!$tmp){
                unset($list[$key]);
                continue;
            }
            $list[$key]['userData'] = $tmp;
        }
        $data['titles'] = $titles;
        $data['firstTitle'] = $firstTitle;
        $data['list'] = $list;
        $data['page'] = $page;
        $data['pagesize'] = $pagesize;
        
        return $this->view->fetch('public/list', $data);
    }

    //添加数据
    function add($uuid){
        
        if(!$uuid){
            return errorReturn('访问出错');
        }
        $data = [];
        $modelProject = new Project();
        $pInfo = $modelProject->getProInfo($uuid);
        $data['proInfo'] = $pInfo;
        $p_id = $pInfo['id'];
      
        $data['uuid'] = $uuid;
        $data['has_check'] = false;

        $data['flowInfo'] = (new ProjectFlow())->getProjectTemps($p_id,$pInfo['temps']);

        $tempFields = (new TemplateField())->where(['temp_id'=>['in',$pInfo['temps']]])
                            ->field('id,temp_id,name,data_type as dataType,options,is_require')
                            ->order('temp_id,sort,id')->select();
        $fields = [];
        foreach($tempFields as $item){
            if($item['is_require']){
                $field_require[$item['id']] = [ 'dataType'=>$item['dataType'],'name'=>$item['name']];
            }
            $fields[$item['temp_id']][] = $item->getData();
        }
        $data['tempFields'] = $fields;
        $field_require = [];
        foreach($tempFields as $v){
            if(!$v['is_require']){
                continue;
            }
            $field_require[$v['id']] = [ 'dataType'=>$v['dataType'],'name'=>$v['name']];
        }
        $data['fieldRequire'] = $field_require;
        
        $tpl = 'public/add';
        return $this->view->fetch($tpl, $data);
    }
    //用户数据保存
    function save($uuid){
        if(!$uuid){
            return errorReturn('非法访问');
        }
        $fieldData = input('field/a');
        $fromcode = input('code','0');
        
        $modelProject = new Project();
        $pInfo = $modelProject->getProInfo($uuid);
        $p_id = $pInfo['id'];
        
        $cur_step = 1;
        
        //获取流程信息，模板信息，验证数据有效
        $modelFlow = new ProjectFlow();
        $flowInfo = $modelFlow->where(['p_id'=>$p_id,'step'=>$cur_step])->field('temps')->find();

        $tempFields = (new TemplateField())->where(['temp_id'=>['in',$flowInfo['temps']]])
                            ->field('id,temp_id,name,data_type as dataType,options,is_require,is_sort')
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
        
        $sortData = '';
        if($sortId && isset($fieldData[$sortId])){
            $sortData = $fieldData[$sortId];
        }
        $savedData = [
        'p_id'=>$p_id,
        'user_id'=>0,
        'cur_step'=> $cur_step,
        'sorts' => $sortData,
        'cur_day' => date('Y-m-d'),
        'from_code' => $fromcode,
        'is_complete'=> 0,
        'create_time'=>$time,
        'update_time'=>$time];
        $dataId = $modelData->insertGetid($savedData);
       
        if($dataId > 0){
            
            $modelDetail = new ProjectDataDetail();
            
            foreach($fieldData as $fid => $val){
                if(!is_array($val) && ($val === '' || $val === null) ){
                    continue;
                }
                $tmp = [
                    'p_id' => $p_id,
                    'data_id' => $dataId,
                    'user_id' => 0,
                    'step' => $cur_step,
                    'field_id' => $fid,
                    'temp_id' => $fields[$fid]['temp_id'],
                    'val' => $val
                ];
                $modelDetail->create($tmp);
            }
            foreach($field_files as $fid => $item){
                $tmp = [
                    'p_id' => $p_id,
                    'data_id' => $dataId,
                    'user_id' => 0,
                    'step' => $cur_step,
                    'field_id' => $fid,
                    'temp_id' => $fields[$fid]['temp_id'],
                    'val' => $item['origin_name'],
                    'remark' => $item['saved_name'],
                ];
                $modelDetail->create($tmp);
            }
            $modelProject->where(['id'=>$p_id])->setInc('total');
        }else{
            return errorReturn('保存失败,请重试');
        }
        return sucReturn('保存成功');
    }
    

    function down($id){
        $model = new ProjectDataDetail();
        $info = $model->where(['id'=>$id])->field('p_id,remark')->find();
        if(!$info){
            return "文件不存在";
        }
        $uuid = (new Project())->where('id',$info['p_id'])->value('uuid');
        $filename = UPLOAD_DIR.$this->getFileDir($uuid).$info['remark'];
        downloadFile($filename);
        
    }
    function getFileDir($uuid){
        return 'temp_'.$uuid.'/'.date('Ym').'/';
    }
}
