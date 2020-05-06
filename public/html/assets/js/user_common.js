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
        if(haystack.hasOwnProperty(i) && needle == haystack[i]){
            return true;
        }
    }
    return false;
}

function logOut(){
    $.ajax({
        'url':'/logout',
        'type':'post',
        'dataType':'json',
        success:function(res){
            if(res && res.status == 200){
                window.location.href = '/login';
            }
        }
    })
    return false;
}
function checkLoginExpire(data){
    if(!data || !data.status){
        return ;
    }
    switch(data.status){
        case 301:
            window.location.href='/login';
    }
    return ;
}