var x_debug = function () {
    var self = this;
    this.messagelist = [];
    this.isInit = false;
    this.run();
    window.onload = function () {
        self.init();
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
 
    }
 
};
x_debug.prototype.config = {
    max_num: 50
}
x_debug.prototype.init = function () {
    //元素创建
    this.createInputElement();
    this.containerClsoe();
    this.isInit = true;
    this.pushMessage();
};
 
x_debug.prototype.run = function () {
 
    var self = this;
    //异常抛出
    window.onerror = function (message, url, line) {
        //  self.containerOpen();
        self.isOpen = true;
        if (self.isInit) {
            self.messageContainer.appendChild(self.createErrorText(message, url, line));
            self.nodeDelect();
        } else {
            self.messagelist.push({type: "error", message: message, url: url, line: line});
        }
    };
    //console截取
    console.log = function (_str) {
        var args = "";
        var _nbsp = "";
        if (arguments.length > 1) {
            for (var i = 0; i < arguments.length; i++) {
                var args_i = "";
                if (typeof arguments[i] == "object") {
                    args_i = JSON.stringify(arguments[i]);
                } else {
                    args_i = arguments[i];
                }
                args += _nbsp+"<font color='#a64f03'>[" + i + "]</font>&nbsp;" + args_i + "<br/>";
                _nbsp="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
            }
        }
        else {
 
            if (typeof _str == "object") {
                args = "<font color='#0808cd'>" + JSON.stringify(_str) + "</font>";
            } else {
                args = _str;
            }
        }
        if (self.isInit) {
            self.messageContainer.appendChild(self.createConsoleText(args));
            self.nodeDelect();
        } else {
            self.messagelist.push({type: "log", message: args});
        }
    };
 
 
};
/**
 *将在模块初始化前的错误添加到显示对象
 * **/
x_debug.prototype.pushMessage = function () {
 
 
    if (this.messagelist.length > 0) {
 
        for (var i = 0;i<this.messagelist.length;i++) {
            if (this.messagelist[i].type == "log") {
                this.messageContainer.appendChild(this.createConsoleText(this.messagelist[i].message));
 
            } else if (this.messagelist[i].type == "error") {
                this.messageContainer.appendChild(this.createErrorText(this.messagelist[i].message, this.messagelist[i].url, this.messagelist[i].line));
            }
        }
    }
}
 
/**
 * 条数过多进行删除
 * **/
x_debug.prototype.nodeDelect = function () {
    if (this.messageContainer.childNodes.length > this.config.max_num) {
        this.messageContainer.removeChild(this.messageContainer.childNodes[0]);
    }
};
/**
 * 开关
 * **/
x_debug.prototype.containerClsoe = function () {
    this.container.style.bottom = null;
    this.container.style.marginTop = ( window.innerHeight - (window.innerHeight * 0.06)) + "px";
    this.container.style.opacity = "0.5";
};
x_debug.prototype.containerOpen = function () {
    this.container.style.bottom = "0px";
    this.container.style.marginTop = null;//( window.innerHeight - 50) + "px";
    this.container.style.opacity = "1";
};
x_debug.prototype.createInputElement = function () {
    //外框容器
    this.container = document.createElement("div");
    this.container.id = "message_container";
    this.container.style.width = document.body.clientWidth + "px";
    this.container.style.height = window.innerHeight * .6 + "px";
    document.body.insertBefore(this.container, document.body.childNodes[0]);
    this.container.style.backgroundColor = "#f6f6f6";
    this.container.style.position = "fixed";
    this.container.style.bottom = "0px";
    this.container.style.border = "1px solid #ffffff";
    this.container.style.zIndex = 999;
    this.container.style.overflow = "hidden";
    this.ListButton = this.createlistButton();
    this.container.appendChild(this.ListButton);
    this.messageContainer = document.createElement("div");
    this.container.appendChild(this.messageContainer);
    this.messageContainer.style.position = "absolute";
    this.messageContainer.style.width = (document.body.clientWidth - 10) + "px";
    this.messageContainer.style.marginLeft = "6px";
    this.messageContainer.style.height = (window.innerHeight * .6 - (window.innerHeight * 0.06)) + "px";
    this.messageContainer.style.overflow = "scroll";
    this.messageContainer.style.backgroundColor = "#ffffff";
    this.messageContainer.style.border = "1px solid #e9e9e9";
    this.messageContainer.style.bottom = "0px";
    this.textSize = document.body.clientWidth * 0.03;
    this.isOpen = false;
    var touchEvent = [["touchend", TouchEnd], ["mouseup", TouchEnd]];
    for (var i in touchEvent) {
        this.ListButton.addEventListener(touchEvent[i][0], touchEvent[i][1]);
    }
    var self = this;
    function TouchEnd(e) {
        self.isOpen = !self.isOpen;
        if (self.isOpen) {
            self.containerOpen();
        } else {
            self.containerClsoe();
        }
    }
    this.containerClsoe();
    this.run();
};
/**
 * 错误信息文本
 * **/
x_debug.prototype.createErrorText = function (message, url, line) {
    var p = document.createElement("p");
    p.style.width = (document.body.clientWidth - 30) + "px";
    p.style.marginLeft = "15px";
    p.innerHTML = "<font color='#a60404'>[ERROR]</font>&nbsp;&nbsp;"
    + message + "<br/><font color='#a60404'>[TO]</font>&nbsp;&nbsp;"
    + url
    + "<br/><font color='#a60404'>[LINE]</font>&nbsp;&nbsp;" + line;
    p.style.fontSize = this.textSize + "px";
    p.style.color = "rgb(255, 0, 0)";
    p.style.lineHeight = "2";
    //   p.style.textShadow = "2px 2px 3px rgb( 0, 0, 0 )";
    p.style.fontFamily = "微软雅黑,Arial, Helvetica;";
    return p;
};
/**
 * consoel.log 信息文本
 * **/
x_debug.prototype.createConsoleText = function (_str) {
    var p = document.createElement("p");
    p.style.width = (document.body.clientWidth - 30) + "px";
    p.innerHTML = "<font color='#006c00'>[&nbsp;log&nbsp;]</font>&nbsp;&nbsp;" + _str;
    p.style.marginLeft = "15px";
    p.style.fontSize = this.textSize + "px";
    p.style.color = "#191919";
    p.style.lineHeight = "2";
    p.style.fontFamily = "微软雅黑,Arial, Helvetica;";
    return p;
};
/**
 * 创建一个按钮
 * **/
x_debug.prototype.createlistButton = function () {
    var button = document.createElement("div");
    button.id = "ListButton";
    button.style.position = "absolute";
    var width = document.body.clientWidth * 0.12;
    var height = 4 * document.body.clientWidth / 480;
    for (var i = 0; i < 3; i++) {
        var radius = document.createElement("div");
        radius.style.width = +width + "px";
        radius.style.height = height + "px";
        radius.style.backgroundColor = "#388bf2";
        radius.style.border = "border:1px solid #0069bb";
        radius.style.marginTop = height + "px";
        radius.style.borderRadius = "2.5px";
        radius.style.marginLeft = (document.body.clientWidth - width) / 2 + "px";
        button.appendChild(radius);
    }
    button.style.marginTop = "10px";
    return button;
};
if(localdebug != undefined && localdebug) {
    new x_debug();
}
