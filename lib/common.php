<?
$_SYSROOT = dirname(__FILE__) . '/../';
require($_SYSROOT . 'lib/sql.php');

function nohup($command) {
	$PID = shell_exec("nohup $command > /tmp/lala 2>&1 & echo $!");
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

function __sql_error($errno) {
	while (count(ob_list_handlers()) > 0) {
		$ret = @ob_end_clean();
		if ($ret == false) break;
	}
	
	$template = new _Template();
	$template->setFile('template_main.html');
	$template->startBuffer();
	$template->setVar('SUBTITLE', '- Error');
	$template->putHeader();
	?>
	<?
	if ($errno == 1062) {
		?>
		<h1>Error</h1>
		Entrada duplicada, probablemente la entrada que Ud. intenta ingresar ya existe previamente.
		<p>
		<a href="javascript:history.back(1);">Volver</a>
		<?
	} else {
		?>
		<h1>Error</h1>
		SQL Error <?=$errno?>
		<?
	}
	ob_start();
	//print_r($GLOBALS);
	$buff = htmlentities(ob_get_contents());
	ob_end_clean();
	echo "<pre>";
	echo $buff;
	echo "</pre>";
	$template->putFooter();
	$template->putBuffer();

	die();
}

function __error($str) {
	
	while (count(ob_list_handlers()) > 0) {
		$ret = @ob_end_clean();
		if ($ret == false) break;
	}
	
	$template = new _Template();
	$template->setFile('template_main.html');
	$template->startBuffer();
	$template->setVar('SUBTITLE', '- Inicio');
	$template->putHeader();
	?>
	<h1>Error</h1>
	<?=$str?>
	<?
	ob_start();
	//print_r($GLOBALS);
	$buff = htmlentities(ob_get_contents());
	ob_end_clean();
	echo "<pre>";
	echo $buff;
	echo "</pre>";
	$template->putFooter();
	$template->putBuffer();

	die();
}

class _Template {
	public  $file;
	private $vars;
	private $filedata;
	private $header;
	private $footer;
	private $contentdata;
	
	private function assignVars($obj) {
		while (list($var, $value) = each($this->vars)) {
			$this->header = str_replace('{' . $var . '}', $value, $obj);
		}
	}
	
	public function setFile($filename) {
		global $_SYSROOT;
		$this->file = $filename;
		$this->filedata = @file_get_contents($_SYSROOT . '/template/' . $this->file);
		
		if ($this->filedata == false) {
			$this->clearBuffer();
			echo "Error leyendo template " . $this->file;
			die();
		}
		
		$tdata = explode('<!--[TEMPLATE_SPLIT]-->', $this->filedata);
		$this->header = $tdata[0];
		$this->footer = $tdata[1];
		
	}
	
	public function startBuffer() {
		ob_start();
	}
	
	public function getBuffer() {
		$buff = ob_get_clean();
		return($buff);
	} 
	
	public function putBuffer() {
		$buff = $this->getBuffer();
		$this->endBuffer();
		echo $buff;
	}

	public function endBuffer() {
		ob_end_clean();
	}
		
	public function setVar($var, $value) {
		$this->vars[$var] = $value;
	}
	
	public function getHeader() {
		$this->assignVars($this->header);
		return($this->header);
	}

	public function getFooter() {
		$this->assignVars($this->footer);
		return($this->footer);
	}
	
	public function putHeader() {
		echo $this->getHeader();
	}

	public function putFooter() {
		echo $this->getFooter();
	}
	
	public function clearBuffer() {
		ob_end_clean();
	}
	
	public function getFile($filename) {
		global $_SYSROOT;
		$this->contentdata .= @file_get_contents($_SYSROOT . '/template/' . $filename);
	
		if ($this->contentdata == false) {
			$this->clearBuffer();
			echo "Error leyendo template " . $filename;
			die();
		}
	
		$this->assignVars($this->contentdata);
		return($this->contentdata);
	}

	public function putFile($filename) {
		echo $this->getFile($filename);
	}

	public function __construct() {
		$this->file = '';
		$this->vars = Array();
	}
	
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

?>