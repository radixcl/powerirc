<?
require(dirname(__FILE__) . '/lib/config.php');

$op = (empty($_GET['op'])) ? 'main' : $_GET['op'];

switch($op) {
    case 'main':
        main();
        break;
    case 'login':
        irc_login();
        break;
    case 'irccmd':
        irc_cmd();
        break;
    case 'getbuf':
        getbuf();
        break;
    case 'getallbuf':
        getallbuf();
        break;
    case 'getnick':
        getnick();
        break;
    case 'putbuf':
        putbuf();
        break;
}


function main() {
    if (@$_COOKIE['irc_connected'] == '1'){
        header('Location: interface.php');
        return;
    }
    ?>
    <form name="form1" method="get">
        <input type="hidden" name="op" value="login">
        <label>Nick <input type="text" name="nick" value=""></label><br>
        <input type="submit">
    </form>
    <?
}


function irc_login() {
    $nick = $_GET['nick'];      // FIXME: must sanitize it
    $nick = addslashes($nick);
    
    $id = sha1(rand() . uniqid('',true));
    
    $pid = nohup("./irc.cgi '$id' '$nick' '$_SERVER[REMOTE_ADDR]'");

    /*echo "proceso $id iniciado con pid $pid<br>";
    echo "<a href=\"?op=irccmd&id=$id&cmd=JOIN%20%23Powers\">Join powers</a><br>";
    echo "<a href=\"?op=getbuf&id=$id\">Getbuf</a><br>";
    echo "<a href=\"interface.php?id=$id\">Interface</a><br>";*/
    
    setcookie('irc_id', $id);
    setcookie('irc_hideservertab', '1');
    setcookie('firstCmds', "JOIN #powers");
    
    header('Refresh:1;url=interface.php?id=' . $id);
    
    echo "Loading...";

}

function irc_cmd() {
    $id = $_GET['id'];
    $cmd = $_GET['cmd'];
    send_ipc_cmd_nr($id, "RAWIRC " . $cmd);
}

function getbuf() {
    $id = $_GET['id'];
    $buf = send_ipc_cmd($id, "GETBUF");
    echo $buf;
}

function getallbuf() {
    $id = $_GET['id'];
    $buf = send_ipc_cmd($id, "GETALLBUF");
    echo $buf;
}

function getnick() {
    $id = $_GET['id'];
    $ret = send_ipc_cmd($id, "GETNICK");
    echo $ret;
}

function putbuf() {
    $id = $_GET['id'];
    $buf = $_GET['buf'];
    send_ipc_cmd($id, "PUTBUF $buf");
}

?>