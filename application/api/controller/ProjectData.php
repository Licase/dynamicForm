<?php

namespace app\api\controller;

use app\api\controller\Base as Controller;
use app\api\model\Project;
use app\api\model\Template;
use app\api\model\ProjectData as ModelProjectData;
use app\api\model\ProjectDataDetail;
use app\api\model\TemplateField;
use think\Exception;

class ProjectData extends Controller
{
      /**
     * 获取项目用户数据
     */
    function index()
    {
        $page = (int) input('page', 1);
        $page = max(1, $page);
        $pagesize = (int) input('pagesize', 15);
        $pagesize = max($pagesize, 1);

        $searchId = (int)input('pid');
        
        $list = [];
        $data = ['list' => [], 'paginate' => ''];


        $modelData= new ModelProjectData();        
        $where= ['step'=>1];
        if($searchId > 0){
            $where['a.p_id'] = $searchId;
        }
        $total = $modelData->alias('a')->where($where)->count('a.id');
        if($total > 0){
            $list = $modelData->alias('a')->join('tab_project b','a.p_id = b.id')
                            ->where($where)
                            ->field('a.id,b.name,a.create_time')
                            ->order('a.id desc')->paginate(['list_rows' => $pagesize, 'page' => $page, 'path' => '', 'fragment' => 'data']);
            $paginate = $list->render();
            $data['paginate'] = $paginate;
        }
       
        $proList = $this->getUserProList();
        $data['proList'] = $proList;

        $data['searchId'] = $searchId;
        $data['list'] = $list;
        $data['page'] = $page;
        $data['total'] = $total;
        $data['pagesize'] = $pagesize;

        return $this->view->fetch('projectData/list', $data);
    }

    function view($id){
        $searchId = (int)input('pid');

        $modelData = new ModelProjectData();
        $info = $modelData->alias('a')->join('tab_template b','a.flow_id = b.id','LEFT')->where(['a.id'=>$id])->field('title,flow_id,b.name')->find();
        
        $temp_id = (int)$info['flow_id'];

        $modelDetail = new ProjectDataDetail();
        $fieldDatas = $modelDetail->where(['data_id'=>$id,'flow_id'=>$temp_id])->field('id,field_id,val')->select();
        $fieldDatas = $fieldDatas ? $fieldDatas->toArray() : [];
        $fieldDatas = array_column($fieldDatas,null,'field_id');
        
        $modelField = new TemplateField();
        $fields = $modelField->where(['temp_id'=> $temp_id])
                            ->field('id,name,data_type as dataType')
                            ->order('sort asc,id asc')
                            ->select();
        $fields = $fields ? $fields->toArray() : [];
        foreach($fields as $key => $item){
            $fid = (int)$item['id'];
            $fields[$key]['val'] = '';
            $fields[$key]['detail_id'] = '';
            if(isset($fieldDatas[$fid])){
                $fields[$key]['val'] = $fieldDatas[$fid]['val'];
                $fields[$key]['detail_id'] = (int)$fieldDatas[$fid]['id'];
            }
        }
        $data= [];
        $data['fields'] = $fields;
        $data['info'] = $info;
        $data['searchId'] = $searchId;
        return $this->view->fetch('projectData/view',$data);

    }

    function add(){

        $pid = (int)input('pid',0);
        $data_id = (int)input('did',0);
        $data = [];
        $data['data_id'] = 0;
        if($data_id > 0){
            $modelData = new ModelProjectData();
            $dataInfo = $modelData->where(['id'=>$data_id])->find();
    
            $data['dataInfo'] = [];
            if($dataInfo){
                $pid = $dataInfo['p_id'];
                $data['dataInfo'] = $dataInfo;
            }
            $modelDetail = new ProjectDataDetail();
    
            $where =['data_id'=>$data_id];
            $detailInfo = $modelDetail->alias('a')->join('tab_template_field b','a.field_id = b.id')->field('a.val,a.remark,a.field_id,b.data_type As dataType')->where($where)->select();
            $detailInfo = $detailInfo ? $detailInfo->toArray() :[];
            $detailInfo = array_column($detailInfo,null,'field_id');
            $data['detailInfo'] = $detailInfo;
            $data['data_id'] = $data_id;
        }
       
        $data['field_require'] = [];
        if($pid > 0 ){
            $where = [];
            $where['id'] = $pid;
            $model = new Template();
            $tempInfo = $model->where($where)->field('id,name')->find();
            $data['tempInfo'] = $tempInfo;    
            $modelField = new TemplateField();
            $fields = $modelField->where(['temp_id'=> $pid])
                                ->field('id,name,data_type as dataType,options,is_require')
                                ->order('sort asc,id asc')
                                ->select();
            $data['fields'] = $fields;
            $field_require = [];
            foreach($fields as $v){
                if(!$v['is_require']){
                    continue;
                }
                $field_require[$v['id']] = [ 'dataType'=>$v['dataType'],'name'=>$v['name']];
            }
            $data['fieldRequire'] = $field_require;
        }
        $tempList= $this->getUserTempList();
        $data['tempList'] = $tempList;
        $data['pid'] = $pid;
        $temp = $pid > 0 ? 'projectData/add' : 'projectData/select';
        return $this->view->fetch($temp, $data);
    }
    //用户数据保存
    function save(){
        $fieldData = input('field/a');
        $title = input('title');
        $pid = (int)input('pid');
        $dataId = (int)input('did',0);
        
        if($pid < 1 || !$title){
            return errorReturn('参数错误');
        }
        $modelField = new TemplateField();
        $fields = $modelField->where(['temp_id'=> $pid])
                            ->field('id,name,data_type as dataType,options,is_require')
                            ->order('sort asc,id asc')
                            ->select();
        $fields = $fields ? $fields->toArray() : [];

        //检查各字段数据
        $error = '';
        $field_files = [];
        foreach($fields as $field){
            $field_id = $field['id'];
            $r = $field['is_require'] ? true : false;
            if(!isset($fieldData[$field_id])){
                if($field['dataType'] == DT_FILE){ //上传文件字段单独判断
                    $field_files[$field_id] = $field;
                    continue;
                }
                if($r){
                    return errorReturn($field['name'].'不能为空');
                }else{
                    continue;
                }
            }
            $item = $fieldData[$field_id];
            if( $r && !$item){
                $error = $field['name'].'不能为空';
                break;
            }
            if($item){
                switch($field['dataType']){
                    case 'date':
                        if( !preg_match('#^\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12][0-9]|3[01])$#',trim($item))){
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
                                $error = $field['name'].'-选项值('.$v.')不存在';
                                break;
                            }
                        }
                        $fieldData[$field_id] = implode('|-|',$item);
                    break;
                }
            }
        }
        if($error){
            return errorReturn($error);
        }
        $file = isset($_FILES['field']) ? $_FILES['field'] : '';
        
        $uploadPath = UPLOAD_DIR.$this->getFileDir($pid);
        if(!file_exists($uploadPath)){
            mkdir($uploadPath,0755,true);
        }
        $error = '';
        foreach($field_files as $fid => $field){
            if($field['is_require']){
                if(!$dataId && (!$file || !isset($file['name'][$fid]) || !$file['name'][$fid] || $file['size'][$fid] < 1 ) ){
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

            $filename = $pid.'_'.$fid.$this->user_id.'_'.time().'.'.$ext;
            
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
        $isEdit = $dataId ? 1 : 0;
        if($dataId){
            $modelData->where(['id'=>$dataId])->update(['title'=>$title,'update_time'=>$time]);
        }else{
            $dataId = $modelData->insertGetid(['flow_id'=>$pid,'user_id'=>$this->user_id,'title'=>$title,'create_time'=>$time,'update_time'=>$time]);
        }
       
        if($dataId > 1){
            $oldData = [];
            $modelDetail = new ProjectDataDetail();
            if($isEdit){
                $oldData = $modelDetail->where(['data_id'=>$dataId])->field('field_id,val')->select();
                $oldData = $oldData ? array_column($oldData->toArray(),null,'field_id') : [];
            }
            foreach($fieldData as $fid => $val){
                if(!is_array($val) && !trim($val)){
                    continue;
                }
                $tmp = [
                    'flow_id' => $pid,
                    'data_id' => $dataId,
                    'field_id' => $fid,
                    'val' => $val
                ];
                if($isEdit){
                    if(isset($oldData[$fid]) && $oldData[$fid]['val'] != $val){
                        $modelDetail->where(['data_id'=>$dataId,'field_id'=>$fid])->update(['val'=>$val]);
                    }
                }else{
                    $modelDetail->data($tmp)->isUpdate(false)->save();
                }
            }
            foreach($field_files as $fid => $item){
                $tmp = [
                    'flow_id' => $pid,
                    'data_id' => $dataId,
                    'field_id' => $fid,
                    'val' => $item['origin_name'],
                    'remark' => $item['saved_name'],
                ];
                if($isEdit){
                    $modelDetail->where(['data_id'=>$dataId,'field_id'=>$fid])->update(['val'=> $item['origin_name'],'remark' => $item['saved_name']]);
                }else{
                    $modelDetail->data($tmp)->isUpdate(false)->save();
                }
            }
        }else{
            return errorReturn('保存失败,请重试');
        }
        return sucReturn('保存成功');
    }
    //获取用户能用的模板列表
    function getUserProList(){
        //后期根据用户权限增加过滤
        $where = ['status'=>1,'steps'=>['>',0]];
        $model = new Project();
        $proList= $model->where($where)->field('id,name')->select();
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
    function getFileDir($flow_id){
        return 'temp_'.$flow_id.'/';
    }
}
