#!/usr/bin/php -q
<?
require(dirname(__FILE__) . '/lib/config.php');
error_reporting(0);

if ($argc < 4) {
    echo "Faltan argumentos!";
    exit(1);
}

$id = $argv[1];
$nick = sanitizenick($argv[2]);
$remip = $argv[3];

// IPC socket creation
$socknam = '/tmp/IRC_' . $id .'.sock';
@unlink($socknam);

$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
if (!socket_bind($socket, $socknam)) {
    echo 'error socket_bind';
    @unlink($socknam);
    exit(1);
}

if (!socket_listen($socket)) {
    echo 'error socket_listen';
    @unlink($socknam);
    exit(1);
}

if (!socket_set_nonblock($socket)) {
    echo 'error stream_set_blocking';
    @unlink($socknam);
    exit(1);
}


// create IRC connection
$fp = fsockopen($IRCDIR, $IRCPORT, $errno, $errstr, 15);

if (!$fp) {
    echo "Error conencting to irc $errno $errstr\n";
    @unlink($socknam);
    exit(1);
}

stream_set_blocking($fp, 0);
echo "webircpass $WEBIRCPASS\n";
if ($WEBIRCPASS != '')
    fwrite($fp, "WEBIRC $WEBIRCPASS cgiirc $remip $remip\r\n");
fwrite($fp, "USER webirc webirc webirc webirc\r\n");
fwrite($fp, "NICK $nick\r\n");


// main program loop
$outbuf = '';
$irc_buf = '';
$irc_all_buf = '';
$ncount = 0;
while (!feof($fp)) {
    // IPC
    if(($newc = @socket_accept($socket)) !== false) {
        $clients[] = $newc;     // got a new IPC client
    }

    while (list($a, $val) = @each($clients)) {
        //echo "socket_read: $a last error: " . socket_last_error($clients[$a]) . " \n";
        print_r($clients);
        $ipc_in_buf = @socket_read($clients[$a], 1024, PHP_NORMAL_READ);
        $ipc_in_buf = trim($ipc_in_buf);
        if ($ipc_in_buf === false || socket_last_error($clients[$a]) == 104) {
            // dead socket
            socket_close($clients[$a]);
            unset($clients[$a]);
        }

        if (strlen($ipc_in_buf) > 0) {
            // process IPC request
            echo "ipc_in_buf: $ipc_in_buf\n";
            $ipc_in_cmds = explode(' ', $ipc_in_buf);
            $ipc_in_cmd = $ipc_in_cmds[0];
            
            if ($ipc_in_cmd == 'RAWIRC') {
                $_tmp = explode(' ', $ipc_in_buf, 2);
                $outbuf .= $_tmp[1];
            }
            
            if ($ipc_in_cmd == 'GETNICK') {
                socket_write($clients[$a], "$nick\n");
                socket_write($clients[$a], "EOR\n");
                socket_close($clients[$a]);
            }
            
            if ($ipc_in_cmd == 'GETBUF') {
                socket_write($clients[$a], "$irc_buf\n");
                socket_write($clients[$a], "EOR\n");
                $irc_buf = '';
            }

            if ($ipc_in_cmd == 'GETALLBUF') {
                socket_write($clients[$a], "$irc_all_buf\n", strlen($irc_all_buf));
                socket_write($clients[$a], "EOR\n");
            }
            
            if ($ipc_in_cmd == 'PUTBUF') {
                $newbuftmp = explode(' ', $ipc_in_buf, 2);
                $newbuf = $newbuftmp[1];
                $irc_buf .= "\n" . $newbuf . "\n";
                $irc_all_buf .= "\n" . $newbuf . "\n";
                socket_write($clients[$a], "EOR\n");
            }
        }
    }
    $clients = array_merge($clients);
    @reset($clients);

    // end IPC


    $buf = fgets($fp);
    if (trim($buf) == '') {
        //echo "no buf\n";
        time_nanosleep(0, 499999999);
    }
    $cmds = explode(' ', $buf);
    
    /*if ($cmds[0] == 'PING') {
        fwrite($fp, "PONG " . $cmds[1]);
    }*/
    
    if (@$cmds[1] == '001') {
        // get my nick from server
        $nick = $cmds[2];
    }

    if (@$cmds[1] == '433') {
        $tmpid = intval(rand(1,1000));
        fwrite($fp, "NICK ${nick}${tmpid}\r\n");
    }    
    
    if (strlen($buf) > 0) {   
        echo "in_buf: $buf";
        $irc_buf .= trim($buf) . "\n";
        $irc_all_buf .= trim($buf) . "\n";
    }
    
    $outbuf = trim($outbuf);
    if (strlen($outbuf) > 0) {
        $outbuf = $outbuf . "\r\n";
        echo "out_buf: $outbuf";
        fwrite($fp, $outbuf, strlen($outbuf));
        $outbuf = '';
    }
    
}

@unlink($socknam);

?>