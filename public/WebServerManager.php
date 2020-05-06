
<?php
define('APP_PATH', __DIR__ . '/../application/');
// 加载框架引导文件
require_once __DIR__ . '/../thinkphp/base.php';
require_once dirname(__FILE__) . '/../vendor/Workerman/Autoloader.php';
require_once './WebServer.php';

use app\api\model\Setting;
use think\App;
App::initCommon();
$model = new Setting();
$apiHost = '';

$port = $model->where(['setting'=>'service_port'])->value('values');
$port = $port ?: 8090;

$ip = "0.0.0.0";
function init(){
    global $apiHost,$model;
    $apiHost = $model->where(['setting'=>'tab_host'])->value('values');
    $apiHost = rtrim($apiHost,'/').'/';
    serverLog('apihost:'.$apiHost);
}
function serverLog($log){
    $file = 'wsServer_'.date('Y_m_d').'.log';
    
    file_put_contents(LOG_PATH.$file,"time:".date('Y-m-d H:i:s').'--log'.$log."\r\n",FILE_APPEND);
}
function feedBackMsg($data){
    global $apiHost;
    if(!$apiHost){
        return false;
    }
    if(substr($apiHost,0,7) != 'http://'){
        $apiHost =  'http://'.$apiHost;
    }
    url_get_contents($apiHost.'msg/v1/msg',$data,'POST',[],1);
}
$ws_worker = new WsServer("websocket://0.0.0.0:".$port);
WsServer::runAll();