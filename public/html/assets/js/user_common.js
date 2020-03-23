/**
 * 用户自定义函数
 */

function showTipOk(msg){
    $('#user-tips').html(msg).addClass('tips-success').fadeIn().delay(500).fadeOut();
}
function showTipError(msg){
    $('#user-tips').html(msg).addClass('tips-warning').fadeIn().delay(1500).fadeOut();
}
function in_array(needle,haystack){
    if(typeof(haystack) != 'object'){
        console.log('not object');
        return false;
    }
    var len = haystack.length;
    for(var i = 0;i<len;i++){
        if(needle == haystack[i]){
            return true;
        }
    }
    return false;
}