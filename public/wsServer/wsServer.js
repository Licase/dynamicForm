// var w = document.documentElement.clientWidth / 6.4 + "px"
// document.documentElement.style.fontSize = w; 
// console.log(w);
var baseHost = window.location.origin;

function WsServer(dsn){
	this.status = 0;
	var ws = new WebSocket(dsn);
	//readyState属性返回实例对象的当前状态，共有四种。
	//CONNECTING：值为0，表示正在连接。
	//OPEN：值为1，表示连接成功，可以通信了。
	//CLOSING：值为2，表示连接正在关闭。
	//CLOSED：值为3，表示连接已经关闭，或者打开连接失败
	//例如：if (ws.readyState == WebSocket.CONNECTING) { }
	 
	//【用于指定连接成功后的回调函数】
	ws.onopen = function (evt) {
		this.status = 1;
		var msg = info;
        msg.action = 'init';
        ws.send(JSON.stringify(msg));
        if(info.role == 'user'){
            showMsg(0,'连接已建立',2,0);
        }
	};
	 
	//【用于指定收到服务器数据后的回调函数】
	//【服务器数据有可能是文本，也有可能是二进制数据，需要判断】
	ws.onmessage = function (event) {
        console.log("Received Message: " + event.data);
	    if (typeof event.data == 'string') {
            var c = JSON.parse(event.data);
            if(c && c.status ){
                switch(c.action){
                    case 'init':
                        switch(c.status){
                            case 200:
                                var userInfo = JSON.parse(c.msg);
                                if(info.role == 'user'){
                                    info.toUid = userInfo.toUid;
                                    info.adminName = userInfo.name;
                                    showMsg(userInfo.toUid,'连接成功!客服->'+userInfo.name+'为您服务',2);
                                    getHistory(info.uid);
                                }else{
                                    addClient(userInfo.toUid,userInfo.name);
                                    getHistory(userInfo.toUid);
                                    clientInfo[userInfo.toUid] = {name:userInfo.name};
                                }
                                this.status = 2;
                            break;
                            case 201:
                                //用户分配客服失败
                                showMsg(0,c.msg,2);
                            break;
                        }
                    break;
                    case 'msg':
                        var content = JSON.parse(c.msg);
                        var uid = info.role == 'user' ? 0 : content.uid;
                        statMsg(uid);
                        showMsg(uid,content.msg,0);
                    break;
                    case 'close':
                        userLeave(c.msg,1);
                        break;
                }   
            }else{
                if(info.role == 'user'){
                    showMsg(0,"客服正忙,请稍后再试",2);
                }else{

                }
            }
	    }
	};
	 
	//[【于指定连接关闭后的回调函数。】
	ws.onclose = function (evt) {
        // showMsg(0,'连接已断开',2);
	};
	ws.onerror = function(event){
		
	};
	return ws;
}

function getHistory(uid){
    $.ajax({
        url: baseHost+'/msg/v1/msg' ,
        data:{uid:uid},
        type:'get',
        dataType:'json',
        success:function(res){
            if(res && res.status == 200 && res.data){
                var len = res.data.length;
                for(var i = res.data.length-1 ;i >= 0;i--){
                    var obj = res.data[i];
                    var type,uid,msg;
                    var msg = obj.msg;
                    type = info.role == obj.role ? 1 : 0;
                    if(obj.role == 'user'){
                        uid = obj.from;
                    }else{
                        uid = obj.to;
                    }
                    if(info.role == 'user'){
                        uid = 0;
                    }
                    
                    showMsg(uid,obj.create_time,2,0);
                    showMsg(uid,msg,type,0,0);
                }
            }
        }
    }) ;
    
}

function userLeave(uid){
    
}
//更新消息统计数字
function statMsg(uid){
    if(info.role == 'user' || uid == curUid){
        return;
    }
    var num = parseInt($('#userbox-'+uid+' .num span').html());
    num++;
    if(num > 9){
        num = '9+';
    }
    $('#userbox-'+uid+' .num span').html(num);
    $('#userbox-'+uid+' .num').css({display:'inline-block'});
}

//切换用户列表
function changeUser(uid){
    if(uid == curUid){
        return ;
    }
    $('.user').removeClass('talking').attr({"data-status":0});
    $('#userbox-'+uid).addClass('talking');
    $('#userbox-'+uid).attr({"data-status":1});
    $('.msgbox-text').removeClass('talking').addClass('notalking');
    $('#msgbox-'+uid).removeClass('notalking').addClass('talking');
    $('#userbox-'+uid+' .num span').hide().html('0');
    $('#text').val('').focus();
    curUid = uid;
}
function addClient(uid,name){
    if($('#userbox-'+uid).length < 1){
        var userHtml ='<div id="userbox-'+uid+'" data-status="0" class="user"><div class="name" onclick="changeUser(\''+uid+'\')">'+name+'</div><div class="num"><span class="badge badge-important">0</span></div></div>';
        var msgHtml ='<div id="msgbox-'+uid+'" class="notalking msgbox-text"></div>';
        $('#msgbox').append(msgHtml);
        if($('.clientList-user .user').length > 0){
            $('.clientList-user .user:first').after($(userHtml));
        }else{
            $('.clientList-user').append($(userHtml));
            curUid = uid;
            $('#userbox-'+uid).addClass('talking');
            $('#msgbox-'+uid).removeClass('notalking').addClass('talking');
        }
    }
}
function setCookie(name,value,expire)
{
    var exp = new Date();
    expire = expire | 3600;
    exp.setTime(exp.getTime() + expire*1000);
     var t = name + "="+ escape (value) + ";";
    t += expire ? "expires=" + exp.toGMTString() : '';
    document.cookie = t;
}

//读取cookies
function getCookie(name)
{
    var arr,reg=new RegExp("(?:^| )"+name+"=([^;]*)(?:;|$)");
 
    if(arr=document.cookie.match(reg)){
        return (arr[1]);
    }
    return null;
}
//删除cookies
function delCookie(name)
{
    var exp = new Date();
    exp.setTime(exp.getTime() - 1);
    var cval=getCookie(name);
    if(cval!=null){
        document.cookie= name + "="+cval+";expires="+exp.toGMTString();
    }
}
function getUuid(){
	var uuid = getCookie('user_uuid');
	if(!uuid){
		var d = new Date().getTime();
		uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
				var r = (d + Math.random()*16)%16 | 0;
                d = Math.floor(d/16);
                var s =(c=='x' ? r : (r&0x3|0x8)).toString(16); 
				return s;
        });
        console.log(uuid)
		setCookie('user_uuid',uuid,3600000);
	}
	return uuid;
}
function onerror(event){
    
}
function getDateTime(){
    var myDate = new Date();
    return myDate.getFullYear()+'-'+ (myDate.getMonth() +1 )+'-'+ myDate.getDate()+' '+' '+myDate.getHours()+':'+myDate.getMinutes()+':'+ myDate.getSeconds();
}
function sendMsg(uid){
    var msg = $('#text').val();
	if(!msg || wsObj.status != 2){
        console.log(msg,wsObj.status);
		return ;
    }
    var t = formatMsg(msg);
    console.log('send message:'+t);
    wsObj.send(t);
    showMsg(uid,msg,1);
    $('#text').val('').focus();
}
/**
 * 显示消息,
 * @param {*} msg 
 * @param {*} isSelf 0：对方,1:自己，2：系统
 */
function showMsg(uid,msg,isSelf,isShowTime){
    var html = '';
    isShowTime = isShowTime == undefined ? 1 : isShowTime;
    if(isShowTime){
        html += '<div class="msg msg-center">'+(getDateTime())+'</div>';
    }
    var name = '';
    switch(isSelf){
        case 0:
            name = info.role == 'user' ? info.adminName : clientInfo[uid].name;
            html += '<div class="msg msg-left"><p class="my">'+name+'：</p><p class="text">'+msg+'</div>';
        break;
        case 1:
            name = info.name;
            html += '<div class="msg msg-right"><p class="my">'+name+'：</p><p class="text">'+msg+'</p></div>';
        break;
        case 2:
            html += '<div class="msg msg-center">'+msg+'</div>';
        break;
    }
    var obj = info.role == 'admin' ? $('#msgbox-'+uid) : $('#msgbox');
    obj.append(html);
    $('#msgbox').animate({scrollTop:$('#msgbox')[0].scrollHeight},10);
}
function formatMsg(msg){
    var to = info.role == 'user' ? info.uid : curUid;
	var t = {action:'msg',msg:msg,uid:to,role:info.role};
	return JSON.stringify(t);
}