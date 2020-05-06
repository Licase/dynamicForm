<?php

use Workerman\Worker;
class WsServer extends Worker{
    public $name = 'wm_pro_service';
    public $count = 1;
    public $clientInfo = [];//客户端连接
    public $adminsInfo = [];//客服端连接
    public $userInfo = []; // 所有用户的信息

    function __construct($dsn)
    {
        parent::__construct($dsn);
        $this->onWorkerStart = function(){
            init();
            serverLog('work start');
        };
        $this->onConnect = function($connect){
            $connect->send( $this->format('ok',200,'connected') );
        };
        $this->onMessage = function($connect,$msg)
        {
            $data = json_decode($msg,true);
            if(!$data || !isset($data['action']) || !isset($data['uid']) || !isset($data['role']) ) {
                serverLog('illegal msg：'.$msg);
                return $connect->send('illegal msg:'. $msg);
            }
            serverLog($msg);
            switch($data['action']){
                case 'init':
                    $uid = $data['uid']; //客服和用户自己的uid
                    
                    $name = $data['name'];
                    $this->userInfo[$uid] = ['name'=> $name];
                    $role = $data['role'];

                    $connect->role = $role;
                    $connect->uid = $uid;
                    if($role == 'user'){
                        $admin = $this->assignAdmin();
                        if(!$this->adminsInfo || !$admin){
                            $connect->send($this->format('暂无客服在线,请稍后再试',201,'init') );
                            $connect->toUid = '';
                            break;
                        }
                        $this->clientInfo[$uid] = $connect->id;
                        //保存连接对应的信息
                        $connect->toUid = $admin['uid']; 
                        //客服服务人数+1
                        $this->adminsInfo[$admin['uid']]['num'] = $this->adminsInfo[$admin['uid']]['num']++; 

                        //把分配的客服信息发回用户
                        $this->adminsInfo[$admin['uid']]['client'][$uid] = 1;
                        $connect->send($this->format( json_encode(['toUid'=>$admin['uid'],'name'=> $admin['name'] ]),200,'init') );

                        //把分配的用户信息发给客服
                        $this->connections[$admin['connect']]->send( $this->format( json_encode(['toUid'=>$uid,'name'=> $this->userInfo[$uid]['name'] ]),200,'init') );
                        
                    }elseif($role == 'admin'){
                        $connect->toUid = ''; 
                        $this->adminsInfo[$uid]['connect'] = $connect->id;
                        $this->adminsInfo[$uid]['status']= 1;
                        $this->adminsInfo[$uid]['name']= $name;
                        if(!isset($this->adminsInfo[$uid]['num'])){
                            $this->adminsInfo[$uid]['num'] = 0;
                        }
                        //把之前的用户重新推送
                        if(!isset($this->adminsInfo[$uid]['client'])){
                            $this->adminsInfo[$uid]['client'] = [];
                        }else{
                            foreach($this->adminsInfo[$uid]['client'] as $k=>$u){
                                $connect->send( $this->format(json_encode( ['toUid'=>$k,'name'=> $this->userInfo[$k]['name'] ] ),200,'init') );
                            }
                        }     
                    }
                break;
                case 'msg':
                    $role = $connect->role;
                    $uid = $data['uid']; // 用户uid;
                    $msg = $data['msg'];
                    $apiData = ['role'=>$role,'msg'=>$msg];
                    
                    if($role == 'user'){
                        $toUid = $connect->toUid;
                        if(isset($this->adminsInfo[$toUid]) ){
                            $this->connections[$this->adminsInfo[$toUid]['connect']]->send($this->format(json_encode(['msg'=>$msg,'uid'=>$uid])));
                        }
                        $apiData['from']= $uid;
                        $apiData['to'] = $toUid;
                    }elseif($role == 'admin'){
                        if(isset($this->clientInfo[$uid])){
                            $this->connections[$this->clientInfo[$uid]]->send($this->format(json_encode(['msg'=>$msg])));
                        }
                        $apiData['from']= $connect->uid;
                        $apiData['to']=$uid;
                    }
                    feedBackMsg($apiData);
                break;
            case 'ping':
                break;
                case 'close':
                break;
            }

        };
        $this->onClose = function($connect)
        {
            $role = isset($connect->role) ?$connect->role:'';
            $uid = isset($connect->uid) ?$connect->uid:0;
            if(!$role || !$uid){
                return false;
            }
            if($role == 'user'){
                unset($this->clientInfo[$uid]);
                $toUid = $connect->toUid;
                if(!$toUid || !isset($this->adminsInfo[$toUid])){
                    return false;
                }
                $this->adminsInfo[$toUid]['num']--;
                if(isset($this->connections[$this->adminsInfo[$toUid]['connect']])){
                    $this->connections[$this->adminsInfo[$toUid]['connect']] ->send($this->format($uid,200,'close'));
                }
                unset($this->adminsInfo[$toUid]['client'][$uid]);
            }else if($role == 'admin'){
                $this->adminsInfo[$uid]['status']= 0;
            }
            
        };
        $this->onError = function($connect,$code,$msg)
        {
            $msg = 'error occured, code:'.$code.'--msg:'.$msg;
            $msg .= '--data:'.json_encode(['role'=>$connect->role,'uid'=>$connect->uid]);
            serverLog($msg);
        };
    }
    /** 
     * 封装发送的消息
     * 
    */
    protected function format($msg,$status = 200,$action ="msg"){
        return json_encode(['msg'=>$msg,'status'=>$status,'action'=>$action]);
    }

    //为用户分配客服
    protected function assignAdmin(){
        $tmpUid =  null;
        $curNum = 0;
        foreach($this->adminsInfo as $uid => $info){
            if(!$info['status']){
                continue;
            }
            if($info['num'] < 1){
                $tmpUid = $uid;
                break;
            }
            if($curNum == 0){
                $tmpUid = $uid;
                $curNum = $info['num'];
            }else if($curNum > $info['num']){
                $tmpUid = $uid;
                $curNum = $info['num'];
            }
        }
        $info = $tmpUid ? $this->adminsInfo[$tmpUid] : [];
        $info['uid'] = $tmpUid;
        return $info;
    }
}