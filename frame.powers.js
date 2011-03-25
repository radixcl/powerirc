// frame.js specific to foro.powers.cl

function isIE() {
    return /msie/i.test(navigator.userAgent) && !/opera/i.test(navigator.userAgent);
}

function _setCookie(cookieName,cookieValue,nDays) {
    var today = new Date();
    var expire = new Date();
    if (nDays==null || nDays==0) nDays=1;
    expire.setTime(today.getTime() + 3600000*24*nDays);
    document.cookie = cookieName+"="+escape(cookieValue)+ ";expires="+expire.toGMTString() + ";domain=powers.cl";
}

function _readCookie(cookieName) {
    var theCookie=""+document.cookie;
    var ind=theCookie.indexOf(cookieName+"=");
    if (ind==-1 || cookieName=="") return "";
    var ind1=theCookie.indexOf(";",ind);
    if (ind1==-1) ind1=theCookie.length; 
    return unescape(theCookie.substring(ind+cookieName.length+1,ind1));
}

var svrno;
if (Math.floor(Math.random()*10) > 5) {
    svrno = 1;
} else {
    svrno = 2;
}


if (isNaN(parseInt(_readCookie('chat_svr')))) {
    _setCookie('chat_srv', svrno, 2);
} else {
    svrno = _readCookie('chat_svr');
}

if (_readCookie('hidefpchat') != 'true') {
    if (isIE()) {
        document.write('<div id="chat_frame" style="clear: both; border: 1px dotted; position: fixed; __position: absolute; _top:expression(document.body.scrollTop+document.body.clientHeight-this.clientHeight); bottom: 0; background-color: aliceblue;">');
        document.write('<div id="chat_frame_click" style="clear: both;"><a style="color: chocolate; text-decoration: none; font-weight: bold; font-family: verdana; font-size: 1.5em;" onclick="chatClick(this)" href="javascript:void 0;">&nbsp;Chat! (beta)&nbsp;</a></div>');
        document.write('<div style="clear: both; display: none; width: 800px; height: 400px;" id="chat_frame_content"><iframe id="chat_frame_iframe" style="height: 100%; width: 100%;" src="http://svr-' + svrno + '.foro.powers.cl/chat/index.php"></iframe></div>');
        document.write('</div>');
    } else {
        document.write('<div id="chat_frame" style="height: 1.5em; border: 1px dotted; position: fixed; bottom: 0; background-color: aliceblue;">');
        document.write('<div id="chat_frame_click"><a style="color: chocolate; text-decoration: none; font-weight: bold; font-family: verdana; font-size: 1.5em;" onclick="chatClick(this)" href="javascript:void 0;">&nbsp;Chat!&nbsp;</a></div>');
        document.write('<div style="border: 0px solid; display: none; height: 100%; width: 100%;" id="chat_frame_content"><iframe id="chat_frame_iframe" style="height: 100%; width: 100%;" src="http://svr-' + svrno + '.foro.powers.cl/chat/index.php"></iframe></div>');
        document.write('</div>');
    }

}

var chatframe = document.getElementById('chat_frame');
var chatframe_content = document.getElementById('chat_frame_content');
var chatframe_click = document.getElementById('chat_frame_click');

var chatframe_width = chatframe.style.width;
var chatframe_height = chatframe.style.height;

function chatClick(obj) {

    if (chatframe_content.style.display == 'block') {
        chatframe_content.style.display = 'none';
        if (!isIE()) {
            chatframe.style.width = chatframe_width;
            chatframe.style.height = chatframe_height;
        }
    } else {
        chatframe_content.style.display = 'block';
        if (!isIE()) {
            chatframe.style.width = window.innerWidth + 'px';
            chatframe.style.height = (window.innerHeight / 1.5) + 'px';
        }
    }

}