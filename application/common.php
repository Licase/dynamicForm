<?php
// 应用公共文件
/**
 * restful api ，通用错误返回
 */
use Think\Log;
function errorReturn($msg, $data = null, $code = 400)
{
    $back = array(
        "msg" => $msg,
        "status" => $code,
        "data" => $data
    );
    return json($back);
}

/**
 * restful api ，通用成功返回
 */
function sucReturn($msg, $data = null, $code = 200)
{
    $back = array(
        "msg" => $msg,
        "status" => $code,
        "data" => $data
    );
    return json($back);
}

/**
 * 验证批量删除id字符串的格式
 * @param $ids
 * @return bool
 */
function checkIds($ids)
{
    if (preg_match('#^[1-9]\d*?(,[1-9]\d*?)*$#', $ids)) {
        return true;
    }
    return false;
}

/**
 * 获取日期是当年的第几周
 * @param $date
 * @param $weekstart 每周第一天,1:星期一,0:星期日
 */
function getWeek($date, $weekstart = 1)
{
    if (preg_match('#^\d+$#', $date)) {
        $curDayTime = $date;
    } else {
        $curDayTime = strtotime($date);
    }


    if ($weekstart == 1) {
        return (int) date('W', $curDayTime);
    }

    $firstDayTime = strtotime(date('Y', $curDayTime) . '-01-01');
    $weekday = date('w', $firstDayTime);

    $firstWeekEndTime = $firstDayTime + (7 - $weekday) * 86400 - 1;

    if ($curDayTime < $firstWeekEndTime) {
        return 1;
    } else {
        $pastedWeeks = floor(($curDayTime - $firstWeekEndTime)   / (86400 * 7));
        $curWeek = $pastedWeeks + 1 + 1;
        return $curWeek;
    }
}

function getEncryptedPassword($password, $salt)
{
    return md5(md5($password) . $salt);
}
// 密码入库加密
function pwdSha256($str)
{
    return hash_hmac('sha256', $str, 'lonbon');
}

/**
 * sha256加密
 */
function sha256($password, $username)
{
    return hash_hmac('sha256', $password, $username);
}


function url_get_contents($url, $data = null, $method = 'GET', $header = [], $timeout = 10)
{
    $urlarr = parse_url($url);
    if ($header && !is_array($header)) {
        $header = [$header];
    }
    $method = strtoupper($method);
    $header[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; …) Gecko/20100101 Firefox/61.0";
    if ($data && $method == 'GET') {
        if (is_array($data)) {
            ksort($data);
            $data = http_build_query($data);
        }
        if (false === strpos($url, '?')) {
            $url .= '?' . $data;
        } else {
            $url .= '&' . $data;
        }
        $data = null;
    }

    $curlCheck = function_exists('curl_init');
    if (!$curlCheck) {
        $opts = [
            'http' => [
                'method' => $method,
                'timeout' => $timeout,
            ]
        ];
        if ($data && $method != 'GET') {
            if (is_array($data)) {
                ksort($data);
                $content = http_build_query($data);
                $content_length = strlen($content);
                $opts['http']['content'] = $content;
            } else {
                $content_length = strlen($data);
                $opts['http']['content'] = $data;
            }
            $header[] = "Content-Type: application/x-www-form-urlencoded";
            $header[] = "Content-Length: {$content_length}";
        }
        if ($header) {
            $opts['http']['header'] = is_array($header) ? implode("\r\n", $header) : $header;
        }
        $context = stream_context_create($opts);
        $output = file_get_contents($url, false, $context);
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
        if (strtolower($urlarr['scheme']) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
        }
        if (isset($urlarr['port']) && $urlarr['port']) {
            curl_setopt($ch, CURLOPT_PORT, $urlarr['port']);
        }
        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            default:
                $header[] = "application/x-www-form-urlencoded;charset=UTF-8";
                $data = http_build_query($data);
                $header[] = 'Content-length: ' . strlen($data);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //设置请求体，提交数据包
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLINFO_HEADER_OUT, false); // 打开时获取请求头,通过curl_getinfo();
        curl_setopt($ch, CURLOPT_HEADER, 0); //打开时显示响应头信息
        $output = curl_exec($ch);
        curl_close($ch);
        \think\Log::write('url:' . $url, 'curl', true);
    }
    return $output;
}

// 检测服务器磁盘空间可用率
function checkDisk()
{
    $totalSpace = disk_total_space(__DIR__);
    $totalSpace = round($totalSpace / (1024 * 1024 * 1024), 2);

    $freeSpace = disk_free_space(__DIR__);
    $freeSpace = round($freeSpace / (1024 * 1024 * 1024), 2);

    $rate = round($freeSpace / $totalSpace * 100, 2);
    $stat = ['total' => $totalSpace, 'free' => $freeSpace, 'rate' => $rate . "%", 'unit' => 'G'];
    return $stat;
}

//下载文件，文件名为真实路径
function downloadFile($file_name,$showName = '')
{
    if (!file_exists($file_name)) {
        echo '文件不存在';
        exit();
    }
    if(!$showName){
        $showName = pathinfo($file_name,PATHINFO_BASENAME);
    }
    $file = fopen($file_name, "r"); // 打开文件
    // 输入文件标签
    Header("Content-Type: application/force-download");
    Header("Content-type: application/octet-stream");
    header('Content-Transfer-Encoding:binary');
    Header("Accept-Ranges: bytes");
    header('Expires:0');
    Header("Accept-Length: " . filesize($file_name));
    Header("Content-Disposition: attachment; filename=" . $showName);
    // 输出文件内容
    echo fread($file, filesize($file_name));
    fclose($file);
    exit;
    
}
//获取唯一码
function getUUid(){
     $d = time()*1000;
     $uuid = preg_replace_callback('/[xyz]/',function($c)use($d){
        $r = ($d + rand(0,15))%16 | 0;
        $d = floor($d/16);
        switch($c[0]){
            case 'y':
                $res = dechex($r&0x3|0x8);
            break;
            case 'z':
                $res = dechex(rand(1,9));
            break;
            default:
            $res = dechex($r);
        }
        return $res;
     },'xxxzxxxyxxxx');
     return $uuid;

}

function translateSecond($second){
    if($second < 1){
        return '-';
    }
    $day = floor($second/86400);
    $t = $second%86400;
    $hour = floor($t/3600);
    $t = $second%3600;
    $minute = floor($t/60);
    $r = '';
    if($day){
        $r .= $day.'天';
    }
    if($hour){
        $r.= $hour.'小时';
    }
    if($minute){
        $r .= $minute.'分';
    }
    return $r;
}
function myLog($log, $type='log') {
    $log = var_export($log,true);
    Log::write($log,$type,true);
}

function getPwdHash($pwd){
    return password_hash($pwd,PASSWORD_BCRYPT);
}

function checkPwd($pwd,$hash){
    return password_verify($pwd,$hash);
}


if(!function_exists('array_column')){
    function array_column($array,$column,$index_key){
        $new = array();
        if(!$array){
            return $new;
        }
        foreach($array as $val){
            $new[$val[$index_key]] = $column === NULL ? $val : $val[$column];
        }
        return $new;
    }
}