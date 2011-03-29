<html>
    <head>
	<title>Chat</title>
	<link type="text/css" href="css/redmond/jquery-ui-1.8.10.custom.css" rel="stylesheet" />	
	<link type="text/css" href="css/style.css" rel="stylesheet" />	
	<script type="text/javascript" src="js/jquery-1.4.4.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-1.8.10.custom.min.js"></script>
	<script type="text/javascript" src="js/jquery.ba-resize.min.js"></script>
	<script type="text/javascript" src="js/jquery.cookie.js"></script>
	<script type="text/javascript">
	<!--
	
	var MyNick;
	var interval;
	var id

	String.prototype.htmlEntities = function () {
	   return this.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
	};

	function urldecode(str) {
	    str = unescape(str);
	    str = str.replace(/\+/g, ' ');
	    return(str);
	}
	
	function isNumeric(expression) {
	    return (String(expression).search(/^\d+$/) != -1);
	}
	
	function getVal(name) {
	    name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
	    var regexS = "[\\?&]"+name+"=([^&#]*)";
	    var regex = new RegExp( regexS );
	    var results = regex.exec( window.location.href );
	    if( results == null )
		return "";
	    else
		return results[1];
	}
	
	function getCmdNick(str) {
	    var tmp = str.split(' ');
	    var test = tmp[0].indexOf('!');
	    var nick;
	    if (test == -1) {
		nick = str.split(':')[1];
		nick = nick.split(' ')[0];
	    } else {
		nick = str.split(':')[1].split('!')[0];
	    }
	    return(nick);
	}
	
	function redrawTab(chan) {
	    var offset;
	    $("#tabs").height( $(window).height() - 10 );
	    //$("#chan_"+chan+"-content").height( $("#chan_"+chan).height() - 250);
	    //$("#chan_"+chan+"-users").height( $("#chan_"+chan).height() - 250 );
	    $("#"+chan).height( $(window).height() - 55 );
	    $("#"+chan+"-content").css( 'height', ($("#"+chan).height() - 50) + 'px');
	    $("#"+chan+"-users").css( 'height', ($("#"+chan).height() - 50) + 'px');
	    $("#"+chan+"-content").scrollTop(99999);

	}
	
	function createChannelTab(channel) {
	    var chan = channel.split("#")[1];
	    if ( typeof( $("#chan_"+chan+"-content")[0] ) != 'undefined' ) {
		return;
	    }
	    $("#tabs").tabs("add","#chan_" + chan, channel + ' <a href="#" class="closetablink" id="chan_' + chan + '-btnclose">[x]</a>');
	    $('#chan_'+chan).html('<div style="">\
				    <div id="chan_'+chan+'-content" style="overflow: auto; border: 0px solid; float: left; width: 80%; height: 200px;"></div>\
				    <div id="chan_'+chan+'-users-div" style="overflow: none; border: 0px solid; float: left; width: 20%; min-width: 20%;">\
					<select size="2" id="chan_'+chan+'-users" style="width: 100%; height: 200px; min-width: 100%;">\
					</select>\
				    </div>\
				    <div id="chan_'+chan+'-input-div" style="clear: both; width: 100%; border: 1px solid;">\
				      <input type="text" id="chan_'+chan+'-input" style="width: 100%; border: 0px;"></input>\
				    </div>\
				</div>');
	    	    
	    $('#chan_'+chan+'-input').keypress(function(e){
		if(e.which == 13){
		    //alert( $('#chan_'+chan+'-input')[0].value );
		    if ($('#chan_'+chan+'-input')[0].value == '') {
			return false;
		    }
		    procChanMessage(chan, $('#chan_'+chan+'-input')[0].value);
		    e.preventDefault();
		    $('#chan_'+chan+'-input')[0].value = '';
		    return false;
		}
	    });
	    
	    $('#chan_'+chan+'-btnclose').click( function(idx, obj) {
		var selectedTab = $('#tabs').tabs('option', 'selected');
		$('#tabs').tabs('select', selectedTab - 1)
		hideTab('chan_' + chan);
		$.get("index.php?op=irccmd&id=" + id + "&cmd=" + escape("PART #" + chan));
	    });
	    
	    $('#chan_'+chan+'-users').dblclick(function(obj) {
		// open query window on double click a nick
		var target = $('#chan_'+chan+'-users option:selected').attr('value');
		if (typeof(target) != 'undefined') {
		    createMsgTab(target);
		    $.get("index.php?op=putbuf&id=" + id + "&buf=" + escape(':' + MyNick + ' OPENMSGTAB ' + target));
		}

	    });
	    
	    selectTab('chan_' + chan);
	    
	}

	function createMsgTab(nick) {
	    if ( typeof( $("#priv_"+nick+"-content")[0] ) != 'undefined' ) {
		return;
	    }
	    $("#tabs").tabs("add","#priv_" + nick, nick + ' <a href="#" class="closetablink" id="priv_' + nick + '-btnclose">[x]</a>');
	    $('#priv_'+nick).html('<div style="">\
				    <div id="priv_'+nick+'-content" style="overflow: auto; border: 0px solid; float: left; width: 85%; height: 200px;"></div>\
				    <div id="priv_'+nick+'-input-div" style="clear: both; width: 100%; border: 1px solid;">\
				      <input type="text" id="priv_'+nick+'-input" style="width: 100%; border: 0px;"></input>\
				    </div>\
				</div>');

	    $('#priv_'+nick+'-input').keypress(function(e){
		if(e.which == 13){
		    if ($('#priv_'+nick+'-input')[0].value == '') {
			return false;
		    }
		    procPrivMessage(nick, $('#priv_'+nick+'-input')[0].value);
		    e.preventDefault();
		    $('#priv_'+nick+'-input')[0].value = '';
		    return false;
		}
	    });
	    
	    $('#priv_'+nick+'-btnclose').click( function(idx, obj) {
		var selectedTab = $('#tabs').tabs('option', 'selected');
		$('#tabs').tabs('select', selectedTab - 1)
		hideTab('priv_' + nick);
		$.get("index.php?op=putbuf&id=" + id + "&buf=" + escape(':' + MyNick + ' CLOSETAB priv_' + nick));
	    });

	    selectTab('priv_' + nick);
	}


	function procChanMessage(chan, message) {
	    var rawirc = '';
	    var cmds;
	    if (message.indexOf('/') == 0) {
		// command
		cmds = message.split('/');
		procCommands(cmds[1]);
	    } else {
		// channel message
		rawirc = 'PRIVMSG #' + chan + ' :' + message;
		$.get("index.php?op=irccmd&id=" + id + "&cmd=" + escape(rawirc));
		$.get("index.php?op=putbuf&id=" + id + "&buf=" + escape(':' + MyNick + ' ' + rawirc));
	    }
	}

	function procPrivMessage(dest, message) {
	    var rawirc = '';
	    var cmds;
	    if (message.indexOf('/') == 0) {
		// command
		cmds = message.split('/');
		procCommands(cmds[1]);
	    } else {
		// private message
		rawirc = 'PRIVMSG ' + dest + ' :' + message;
		$.get("index.php?op=irccmd&id=" + id + "&cmd=" + escape(rawirc));
		$.get("index.php?op=putbuf&id=" + id + "&buf=" + escape(':' + MyNick + ' ' + rawirc));
	    }
	}

	function procCommands(cmds) {
	    cmds = cmds.replace(/^[Jj] [^.]/g, 'JOIN ');
	    $.get("index.php?op=irccmd&id=" + id + "&cmd=" + escape(cmds));
	}
	
	function getNewBuf() {
	    var ts = new Date().getTime();
	    $.get("index.php?op=getbuf&id=" + id + '&__nocache=' + ts, function(data){
		if (data != '' || data != '\n') parseBuf(data, true);
	    });
	}
	
	function addList(chan, nick) {
	    var list = $('#chan_'+chan+'-users');
	    var i;
	    var c = false;
	    var w;
	    $('#chan_' + chan + '-users option').each( function(idx, obj){
		if (obj.value == nick) c = true;
	    } );
	    
	    if (c == true) return;
	    
	    $('#chan_'+chan+'-users').append('<option value="' + nick + '">' + nick + '</option>');
	}
	
	function removeList(chan, nick, message) {
	    var list = $('#chan_'+chan+'-users');
	    var i;
	    var c = false;
	    $('#chan_' + chan + '-users option').each( function(idx, obj){
		if (obj.value == nick) {
		    $(obj).remove();
		    if (message != '') {
			writeTab("#chan_"+chan+"-content", message);
		    }
		}
	    } );
	}
	
	function updateLists(oldnick, newnick, message) {
	    var chan;
	    $("#tabs .ui-tabs-panel").each(function(idx, obj){
		if (obj.id.indexOf('chan_') == 0)
		    chan = obj.id.substring(5, obj.id.length);
		else
		    chan = obj.id;
		updateList(chan, oldnick, newnick, message);
		//alert(chan + ' ' + oldnick + ' ' + newnick);
	    });
	}
	
	function removeLists(nick, message) {
	    var chan;
	    $("#tabs .ui-tabs-panel").each(function(idx, obj){
		if (obj.id.indexOf('chan_') == 0)
		    chan = obj.id.substring(5, obj.id.length);
		else
		    chan = obj.id;
		removeList(chan, nick, message);
	    });
	}
	
	function updateList(chan, oldnick, newnick, message) {
	    var list = $('#chan_'+chan+'-users');
	    var i;
	    var c = false;
	    $('#chan_' + chan + '-users option').each( function(idx, obj){
		if (obj.value == oldnick) {
		    obj.value = newnick;
		    obj.text = newnick;
		    writeTab("#chan_"+chan+"-content", message);
		}
	    } );
	}

	function removeTab(id) {
	    id = '#' + id;
	    $('#tabs li a').each( function(idx, obj){
		if ($(obj).attr('href') == id) {
		    $(obj).parent().remove();
		    $(id).remove();
		}
	    });
	}
	
	function hideTab(id) {
	    id = '#' + id;
	    $('#tabs li a').each( function(idx, obj){
		if ($(obj).attr('href') == id) {
		    $(obj).parent().hide();
		}
	    });
	}

	function showTab(id) {
	    id = '#' + id;
	    $('#tabs li a').each( function(idx, obj){
		if ($(obj).attr('href') == id) {
		    $(obj).parent().show();
		}
	    });
	}

	function selectTab(id) {
	    $('#tabs').tabs('select', id);
	}
	
	function getSelectedTab() {
	    var selected;
	    selected = $('#tabs .ui-state-active a').attr('href');
	    if (typeof(selected) == 'undefined')
		return(0);
	    selected = selected.split('#');
	    return(selected[1]);
	}

	
	function parseBuf(allbuf, processping) {
	    buf = allbuf.split("\n");
	    var i = 0;
	    var ii = 0;
	    var cmds;
	    var nick;
	    var channel;
	    var target;
	    var rawmessage;
	    var message;
	    var tmp1;
	    var tmp2;
	    var tmpnick;
	    var chan;
	    var curtab;

	    for (i = 0; i < buf.length; i++) {
		cmds = buf[i].split(' ');
		if (buf[i] == '') continue;

		if (cmds[0] == 'S_ERROR') {
		    //alert('Disconnected from IRC Server ');
		    //$("#servertab-content").append('<span class="servererror">' + buf + '</span><br>');
		    clearInterval(interval);
		    $.cookie('irc_connected', 0);
		    setTimeout(function(){
			window.location.href = 'index.php?error=disconnected';
		    }, 1000);
		    continue;
		}
		
		if (cmds[0] == 'PING' && processping == true) {
		    $.get("index.php?op=irccmd&id=" + id + "&cmd=PONG " + cmds[1]);
		    continue;
		} else if (cmds[0] == 'PING' && processping == false) {
		    cmds = '';
		    continue;
		}
		
		if (cmds[0] == 'ERROR') {
		    $("#servertab-content").append('<span class="servererror">' + buf + '</span><br>');
		    continue;
		}
		
		if (cmds[1] == 'JOIN') {
		    nick = getCmdNick(buf[i]);
		    channel = cmds[2].split(':')[1];
		    chan = channel.split("#")[1];
		    createChannelTab(channel);
		    writeTab("#chan_"+chan+"-content", '<span class="joinmsg">--&gt; ' + nick + ' has joined channel ' + channel + '</span>');
		    addList(chan, nick);
		
		} else if (cmds[1] == 'PONG') {
		    // nothing
		    
		} else if (cmds[1] == 'PRIVMSG') {
		    nick = getCmdNick(buf[i]);
		    target = cmds[2];
		    if (target.indexOf('#') == 0) {
			// channel message
			chan = target.split("#")[1];
			tmp1 = buf[i].split(':');
			tmp1.shift();tmp1.shift();
			message = tmp1.join(":"); 
			channelMessage(target, nick, message.htmlEntities());
		    } else {
			// private message
			tmp1 = buf[i].split(':');
			tmp1.shift();tmp1.shift();
			message = tmp1.join(":"); 
			if (nick == MyNick) {
			    // sent private message
			    sentPrivateMessage(nick, target, message.htmlEntities());
			} else {
			    privateMessage(nick, message.htmlEntities());
			}
		    }
		    
		} else if (cmds[1] == 'NOTICE') {
		    nick = getCmdNick(buf[i]);
		    target = cmds[2];
		    if (target.indexOf('#') == 0) {
			// channel notice
			chan = target.split("#")[1];
			tmp1 = buf[i].split(':');
			tmp1.shift();tmp1.shift();
			message = tmp1.join(":"); 
			writeTab("#chan_"+chan+"-content", '<span class="noticemsg">-<span class="noticemsgnick">' + nick + '/' + target + '</span>-  ' + message.htmlEntities() + '</span>');
		    } else {
			curtab = getSelectedTab();
			tmp1 = buf[i].split(':');
			tmp1.shift();tmp1.shift();
			message = tmp1.join(":"); 
			writeTab("#"+curtab+"-content", '<span class="noticemsg">-<span class="noticemsgnick">' + nick + '</span>-  ' + message.htmlEntities() + '</span>');
		    }
		
		} else if (cmds[1] == 'CLOSETAB') {
		    removeTab(cmds[2]);
		} else if (cmds[1] == 'OPENMSGTAB') {
		    createMsgTab(cmds[2]);
		    
		} else if (cmds[1] == 'PART') {
		    nick = getCmdNick(buf[i]);
		    channel = cmds[2];
		    chan = channel.split("#")[1];
		    writeTab("#chan_"+chan+"-content", '<span class="partmsg">&lt;-- ' + nick + ' has left channel ' + channel + '</span>');
		    removeList(chan, nick, '');
		    if (nick == MyNick) {
			removeTab("chan_" + chan);
		    }

		} else if (cmds[1] == 'KICK') {
		    nick = getCmdNick(buf[i]);
		    channel = cmds[2];
		    chan = channel.split("#")[1];
		    writeTab("#chan_"+chan+"-content", '<span class="kickmsg">&lt;-- ' + nick + ' has kicked  ' + cmds[3] + ' from ' + channel + '</span>');
		    removeList(chan, cmds[3], '');
		    if (cmds[3] == MyNick) {
			// I was kicked!
			removeTab("chan_" + chan);
			writeTab("#servertab-content", '<span class="kickmsg">!!! You were kicked from ' + channel + '</span>');
			if ($.cookie('irc_hideservertab') == 1 && processping == true) {
			    alert("!!! You were kicked from " + channel);
			}
		    }
		    
		} else if (cmds[1] == 'QUIT') {
		    nick = getCmdNick(buf[i]);
		    removeLists(nick, '<span class="quitmsg">&lt;-- ' + nick + ' has left IRC</span>');
		
		} else if (cmds[1] == 'NICK') {
		    var oldnick = getCmdNick(buf[i]);
		    var newnick = cmds[2].split(':')[1];

		    setTimeout( function() {
			       updateLists(oldnick, newnick, '<span class="newnickmsg">--- ' + oldnick + ' is now known as ' + newnick + '</span>');
		    }, 250);
		    
		    if (oldnick == MyNick) {
			MyNick = newnick;
		    }
		} else if (cmds[1] == 'MODE') {
		    nick = getCmdNick(buf[i]);
		    channel = cmds[2];
		    if (channel.indexOf('#') == 0) {
			// channel mode
			chan = channel.split("#")[1];
			tmp1 = buf[i].split(' ');
			tmp1.shift();tmp1.shift();
			message = tmp1.shift() + " " + tmp1.join(" "); 
			writeTab("#chan_"+chan+"-content", '<span class="modechange">--- ' + nick + ' sets mode [' +  message.htmlEntities() +  ']</span>');
		    } else {
			// user mode
			$("#servertab-content").append('<span class="modechange">' + buf[i] + '</span><br>');
		    }
		    
		} else if (cmds[1] == '001') {
		    MyNick = cmds[2];
		    $.cookie('irc_connected', 1);
		} else if (cmds[1] == '353') {
		    // who is in?
		    channel = cmds[4]
		    chan = channel.split("#")[1];
		    tmp1 = buf[i].split(':');
		    tmp2 = String(tmp1).split(' ');
		    for (ii = 5 ; ii < tmp2.length; ii++) {
			tmpnick = tmp2[ii].replace('@', '');
			tmpnick = tmpnick.replace('+', '');
			tmpnick = tmpnick.replace('%', '');
			tmpnick = tmpnick.replace(',', '');
			addList(chan, tmpnick)
		    }
		} else if (cmds[1] == '004') {
		    // first commands contained in the firstCmds cookie
		    if ($.cookie('firstCmds') != null) {
			var firstcmds = urldecode($.cookie('firstCmds')).split("\n")
			for (c = 0; c < firstcmds.length; c++) {
			    $.get("index.php?op=irccmd&id=" + id + "&cmd=" + escape(firstcmds[c]));
			}
		    }
		    $.cookie('firstCmds', null);
		    
		} else if (cmds[1] == '372') {
		    tmp1 = buf[i].split(' ');
		    tmp1.shift();tmp1.shift();tmp1.shift();
		    message = tmp1.join(' '); 
		    $("#servertab-content").append('<pre>' + message + "</pre>");
		} else {
		    if (isNumeric(cmds[1])) {
			tmp1 = buf[i].split(' ');
			tmp1.shift();tmp1.shift();tmp1.shift();
			message = tmp1.shift() + " " + tmp1.join(' '); 
			$("#servertab-content").append('<span class="servermsg">' + message + '</span><br>');
		    } else {
			$("#servertab-content").append('<span class="servermsg">' + buf[i] + '</span><br>');
		    }
		    // FIXME: arreglar esto de forma mas elegante
		    $("#servertab-content").scrollTop(99999);
		}
	    }
	    
	    //$("#tabs").tabs("add","#newtabtemplate","New Tab")
	}
	
	function writeTab(tab, message) {
	    $(tab).append(message + "<br>");
	    $(tab).scrollTop(99999);
	}
	
	function channelMessage(channel, nick, message) {
	    var chan = channel.split("#")[1];
	    createChannelTab(channel) ;
	    $("#chan_"+chan+"-content").append('<span class="channelmsg"><span class="channelmsglt">&lt;</span><span class="nickmsg">'+nick+'</span><span class="channelmsggt">&gt;</span> ' + message + "</span><br>");
	    $("#chan_"+chan+"-content").scrollTop(99999);
	}
	
	function privateMessage(nick, message) {
	    createMsgTab(nick);
	    $("#priv_"+nick+"-content").append('<span class="privmsg"><span class="privmsglt">&lt;</span><span class="nickprivmsg">'+nick+'</span><span class="">&gt;</span> ' + message + "</span><br>");
	    $("#priv_"+nick+"-content").scrollTop(99999);
	}
	
	function sentPrivateMessage(nick, target, message) {
	    createMsgTab(target);
	    $("#priv_"+target+"-content").append('<span class="privmsg"><span class="privmsglt">&lt;</span><span class="nickprivmsg">'+nick+'</span><span class="">&gt;</span> ' + message + "</span><br>");
	    $("#priv_"+target+"-content").scrollTop(99999);	    
	}
	

	$(function(){
	    var c = 0;
	    if ($.cookie('irc_hideservertab') == 1)
		hideTab('servertab');
	    
	    id = $.cookie('irc_id');
	    if (id == '') {
		window.location.href = 'index.php?error=noid'
		return;
	    }

	    // Tabs
	    //$('#tabs').tabs();
	    $('#tabs').tabs({
		select: function(event, ui) {
		    $("#tabs .ui-tabs-panel").each(function(idx, obj){
			setTimeout( function() { redrawTab(obj.id); }, 100);
		    });
		}
		
	    });
	    $('#servertab').html('<div id="servertab-content" style="overflow: auto; border: 1px solid; width: 100%; height: 70%;"></div> <input type="text" id="serverinput" style="width: 100%; border: 1px solid;">');

	    $('#serverinput').keypress(function(e){
		var cmd;
		if(e.which == 13){
		    if ($('#serverinput')[0].value == '') {
			return false;
		    }
		    
		    if ($('#serverinput')[0].value.indexOf('/') == 0) {
			cmds = $('#serverinput')[0].value.split('/');
			$.get("index.php?op=irccmd&id=" + id + "&cmd=" + escape(cmds[1]));
		    } else {
			alert('No channel joined, try /join #<channel>');
		    }
		    
		    e.preventDefault();
		    $('#serverinput')[0].value = '';
		    return false;
		}
	    });

	    
	    var allbuf;
	    var ts = new Date().getTime();
	    $.get("index.php?op=getallbuf&id=" + id + '&__nocache=' + ts, function(data){
		parseBuf(data, false);
		$.get("index.php?op=irccmd&id=" + id + "&cmd=" + escape('PING :' + MyNick));
	        if ($.cookie('irc_selectedtab') != '') {
		    selectTab( parseInt($.cookie('irc_selectedtab')) );
		}

	    });
	    
	    interval = setInterval('getNewBuf()', 1000);
	    
	    $(window).bind('resize', function(){
		$("#tabs .ui-tabs-panel").each(function(idx, obj){
		    setTimeout( function() { redrawTab(obj.id); }, 100);
		});
	    });
	    
	});
	
	$(window).unload(function() {
	    var selectedTab = $('#tabs').tabs('option', 'selected');
	    $.cookie('irc_selectedtab', selectedTab);
	});
	
	
	-->
	</script>
	
	<style type="text/css" media="screen">
	<!--
	body { 
	    margin:0; 
	    padding:0; 
	    width: 100%;
	    height: 100%;
	    border: 0px;
	    overflow: hidden;
	}
	html {
	    width: 100%;
	    height: 100%;
	}
	-->
	</style>

    </head>
    <body>

	<div id="tabs">
	    <ul>
		<li><a href="#servertab">Server</a></li>
	    </ul>
	    <div id="servertab">
		</div>
	    </div>
	</div>
	


    </body>
</html>