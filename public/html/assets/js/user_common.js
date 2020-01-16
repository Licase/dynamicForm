/**
 * 用户自定义函数
 */
function showError(obj,msg){
    $(obj).after(msg);
}
function showTipOk(msg){
    $('#user-tips').html(msg).addClass('tips-success').fadeIn().delay(500).fadeOut();
}
function showTipError(msg){
    $('#user-tips').html(msg).addClass('tips-warning').fadeIn().delay(1500).fadeOut();
}