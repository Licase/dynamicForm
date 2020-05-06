<?php 
require_once '../vendor/phpqrcode/qrlib.php';
$code = $_GET['code'];
if(!$code){
    exit(0);
}
QRcode::png($code,false,QR_ECLEVEL_H,10,3);