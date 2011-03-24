<?
$_SYSROOT = dirname(__FILE__) . '/../';

function nohup($command) {
	$PID = shell_exec("nohup $command > /dev/null 2>&1 & echo $!");
	$PID = trim($PID);
	return($PID);
}

function print_r2($arg) {
	echo "<pre>";
	print_r($arg);
	echo "</pre>";
}

function as_array(&$arr_r) { 
	foreach ($arr_r as $val) is_array($val) ? as_array($val):$val=addslashes($val); 
 	unset($val); 
}

function __error($str) {
	
	while (count(ob_list_handlers()) > 0) {
		$ret = @ob_end_clean();
		if ($ret == false) break;
	}
	?>
	<h1>Error</h1>
	<?=$str?>
	<?
	die();
}


function send_ipc_cmd($id, $cmd) {
	$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
	
	if (!@socket_connect($socket, "/tmp/IRC_" . $id . ".sock")) {
		echo "S_ERROR socket_connect";
		return;
	}

	/*if (!socket_set_nonblock($socket)) {
		echo 'error stream_set_blocking';
		return(1);
	}*/
	
	$buf = $cmd . "\n";
	$ret = socket_write($socket, $buf, strlen($buf));

	$buf = '';
	$sockarr = array($socket);
	while (1) {
		$in_buf = @socket_read($socket, 8192, PHP_NORMAL_READ);
		
		if (strlen(trim($in_buf)) == 0)
			time_nanosleep(0, 499999999);
		
		if ($in_buf === false || socket_last_error($socket) == 104) {
			// dead socket
			socket_close($socket);
			break;
		}
		
		if (trim($in_buf) == "EOR")
			break;
		
		$buf .= $in_buf;
	}

	socket_close($socket);
	return($buf);
}

function send_ipc_cmd_nr($id, $cmd) {
	$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
	
	if (!socket_connect($socket, "/tmp/IRC_" . $id . ".sock")) {
		echo "error socket_connect";
		return(1);
	}

	/*if (!socket_set_nonblock($socket)) {
		echo 'error stream_set_blocking';
		return(1);
	}*/
	
	$buf = $cmd . "\n";
	$ret = socket_write($socket, $buf, strlen($buf));

	socket_close($socket);
	return($buf);
}

function sanitizenick($nick) {
        $newnick = ereg_replace("[^A-Za-z0-9\-\ ]", "_", $nick );
        $newnick = ereg_replace("^[0-9]", '_\0', $newnick);
        $newnick = str_replace(" ", '_', $newnick);
        return $newnick;
}

?>