<?

define("METHOD_ADD",  1);
define("METHOD_EDIT", 2);

class SQL {
	
	private $__QUERIES;
	private $__link;
	private $__SQL_CONNECTION = false;
	private $__addFieldReg = Array();
	private $__sqlRegTitle = Array();
	private $__requiredField = Array();
	private $__retURLVal = Array();
	private $__getIgnoredField = Array();
	private $__sqlCustomField = Array();

	public function dbconnect() {
	        global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
	        $link = mysql_connect($DB_HOST, $DB_USER, $DB_PASS) or die("No se pudo conectar a la base de datos");
	        mysql_select_db($DB_NAME) or die("No se pudo seleccionar la base de datos");
	
			if ($link) {
				$this->__link = $link;
				$this->__SQL_CONNECTION = true;
				return true;
			} else {
				return false;
			}
	}

	public function dbquery($query) {
	        $result = @mysql_query($query, $this->__link);
	        $this->__QUERIES++;
	        return $result;
	}

	public function dbdisconnect($link) {
	        mysql_close($link);
	}

	public function fetchrow($text) {
	        return mysql_fetch_row($text);
	}

	public function fetcharray($text) {
	        return @mysql_fetch_array($text);
	}

	public function reset($res) {
		mysql_data_seek($res, 0);
	}

	public function fastquery($query) {
	        if ($this->__SQL_CONNECTION == false) $this->dbconnect();
	        $result = $this->dbquery($query);
	        return $result;
	}

	public function sql_num_rows($query) {
	        return mysql_num_rows($query);
	}
	
	public function getErrno() {
		return mysql_errno($this->__link);
	}
	
	public function addFieldReg($table, $field, $newname, $required = false) {
		
		if ($required == true)
			$this->__requiredField[$table][$field] = true;
		
		$this->__addFieldReg[$table][$field] = $newname;
	}
	
	private function getFieldReg($table, $field) {
		if ($this->__addFieldReg[$table][$field]) {
			return $this->__addFieldReg[$table][$field];
		} else {
			return $field;
		}
	}

	public function addSQLRegTitle($table, $title) {
		$this->__sqlRegTitle[$table] = $title;
	}
	
	public function getReferences($table, $column) {
		global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
        
		$query = "SELECT u.column_name, u.referenced_table_schema, u.referenced_table_name, u.referenced_column_name
			FROM information_schema.table_constraints AS c
			INNER JOIN information_schema.key_column_usage AS u
			USING ( constraint_schema, constraint_name ) 
			WHERE c.constraint_type =  'FOREIGN KEY'
			AND c.table_schema =  '$DB_NAME'
			AND c.table_name =  '$table'
			AND u.column_name = '$column'
			LIMIT 0 , 30
		";
		
		$sql = $this->fastquery($query);
		$res = $this->fetcharray($sql);
		
		return($res);
		
	}

	public function addSQLReg($table) {
		$this->_putForm($table, $id, METHOD_ADD, $GET['oldurl']);
	}
	
	public function doAddSQL($data) {
		as_array($data);
		reset($data);
		
		//print_r2($data);die();

		while (list($key, $val) = each($data)) {
			if ($key == '__SQLReturnVal') continue;
			if ($key == '__sqlRegTable') {
				$__sqltmp1 = "$val";
			} else if ($key == 'pass' || $key == 'password') {
				$__sqltmp2 .= "$key, ";
				$__sqltmp3 .= "SHA1('$val'), ";
			} else if ($key{0} == '_' && $key{1} == '_') {
				continue;
			} else {
				$__sqltmp2 .= "$key, ";
				$__sqltmp3 .= "'$val', ";
			}	
		}
		$__sqltmp1 = trim($__sqltmp1, " ,");
		$__sqltmp2 = trim($__sqltmp2, " ,");
		$__sqltmp3 = trim($__sqltmp3, " ,");
		$__sqltmp = "INSERT INTO $__sqltmp1 ($__sqltmp2) VALUES ($__sqltmp3)";

		//echo $__sqltmp;die();
		
		$ret = $this->fastquery($__sqltmp);
		if (!$ret) {
			__sql_error($this->getErrno());
		}
	}
	
	public function retURL($table, $url) {
		$this->__retURLVal[$table] = $url;
	}
	
	public function getSQLIgnoreField($table, $field) {
		$this->__getIgnoredField[$table][$field] = true;
	}
	
	public function getFieldName($table, $field) {
		$query = "SHOW FULL COLUMNS IN $table WHERE Field = '$field'";
		$sql = $this->fastquery($query);
		$res = $this->fetcharray($sql);
		
		if($res['Comment'])
			return($res['Comment']);
		else
			return($field);
	}
	
	private function isNULL($table, $field) {
		$query = "SHOW FULL COLUMNS IN $table WHERE Field = '$field'";
		$sql = $this->fastquery($query);
		$res = $this->fetcharray($sql);
		
		if ($res['Null'] == 'YES')
			return true;
		else
			return false;
	}
	
	public function getSQLRegs($table, $match) {
		global $SELECT, $DESC, $_REF;
		if ($SELECT[$table] != '') {
			$query = "SELECT $SELECT[$table] FROM $table";
		} else {
			$query = "SELECT * FROM $table";
		}
		
		$fields = Array();
		$i = 0;
		$ret = $this->fastquery("EXPLAIN $table");
		while($tmp = $this->fetcharray($ret)) {
			if ($this->__getIgnoredField[$table][$tmp['Field']]) {
				continue;
			}
			$fields[$i++] = $tmp['Field'];
		}
		
		if ($match != '') {
			$subq = 'WHERE (';
			reset($fields);
			while (list($key, $val) = each($fields)) {
				$subq .= " upper($val) LIKE upper('%$match%') OR";
			}
			$subq = preg_replace('/ OR$/', '', $subq);
			$query = "$query $subq)";
		}
		reset($fields);
		
		$ret = $this->fastquery($query);
		if (!$ret) {
			__error("Error ejecutando sentencia SQL");
		}
		
		?>
		<div align="center">
			<?
			$ii = 0;
			while ($res = $this->fetcharray($ret)) {
				if ($ii == 0) {
					?>
					<table border="0" class="listTable" style="width: 90%;">
						<thead>
							<tr>
								<?
								while (list($key1, $val1) = each($res)) {
									if (is_numeric($key1)) continue;
									?>
									<td>
										<nobr><?=$this->getFieldName($table, $key1)?></nobr>
									</td>
									<?
								}
								?>
								<td>
									&nbsp;
								</td>
							</tr>
						</thead>
						<tbody>
					<?
					reset($res);
				}
				$ii++;
				$trid = uniqid();
				?>
				<tr id="_sql_reg_<?=$trid?>">
					<?
					while (list($key, $val) = each($res)) {
						if ($this->__getIgnoredField[$table][$key]) continue;
						if (is_numeric($key)) continue;
						?>
						<td><?
						$references = $this->getReferences($table, $key);
						//print_r2($references);
						$reference_table  = $references[2];
						$reference_column = $references[3];
						
						if ($reference_column != '') {
							$fk_query = "SELECT * FROM $reference_table WHERE $reference_column = $val";
							//echo $fk_query;
							$fk_sql = $this->fastquery($fk_query);
							
							$refcount = intval($_REF[$reference_table]);
							if ($refcount == 0) $refcount = 1;
							
							while ($fk_res = $this->fetcharray($fk_sql)) {
									for ($i=1; $i <= $refcount ; $i++)
										echo "$fk_res[$i] ";								
							}
						} else {
							echo "$val";
						}
						
						
						?></td>
						<?
					}
					?>
					<td><nobr>
						<a href="generic.php?op=edit&amp;table=<?=$table?>&amp;id=<?=$res['id']?>&amp;oldurl=<?=urlencode($_SERVER['REQUEST_URI'])?>"><img src="template/img/icons/document-properties.png" border="0" alt="Editar" title="Editar"/></a>
						<a href="#" onclick="return(js_delete_reg(<?=$res['id']?>, '<?=$table?>', '_sql_reg_<?=$trid?>'))"><img src="template/img/icons/edit-delete.png" border="0" alt="Eliminar" title="Eliminar" /></a>
						</nobr>
					</td>
				</tr>
				<?
			}
			?>
			</tbody>
		</table>
		</div>
		<?
		
	}

	public function _putForm($table, $id, $method, $oldurl) {
		global $_REF;
		$sql = $this->fastquery("EXPLAIN $table");
		$sqldata = $this->fastquery("SELECT * FROM $table WHERE id = '$id'");
		$resdata = $this->fetcharray($sqldata);
		
		if ($method == METHOD_EDIT) {
			$_form_action = 'generic.php?op=doEditSQL';
			$_nochange = '__[[NOCHANGE]]__';
		} else {
			$_form_action = 'generic.php?op=doAddSQL';			
		}

		?>
		<form name="frm_<?=$table?>" id="frm_<?=$table?>" method="post" action="<?=$_form_action?>" enctype="multipart/form-data" onsubmit="return __submitForm_<?=$table?>(this);">
		<input type="hidden" name="__sqlRegTable" value="<?=$table?>">
		<input type="hidden" name="__SQLReturnVal" value="<?=htmlentities($this->__retURLVal[$table])?>">
		<input type="hidden" name="__oldurl" value="<?=$oldurl?>">
		<div align="left">
		<table border="0" class="tableReg" style="width: 350pt;">
				<?
				if ($this->__sqlRegTitle[$table]) {
					?>
					  <tr>
						<td colspan="3" align="center">
							<h1 class="tableRegTitle"><?=$this->__sqlRegTitle[$table]?></h1>
						</td>
					  </tr>
					<?
				}

				$ii = 0;
				while ($res = $this->fetcharray($sql)) {
					
					if ($res['Field'] == 'id' && $res['Extra'] == 'auto_increment') {
						?>
						<input type="hidden" name="id" value="<?=$id?>">
						<?
						continue;
					}
					
					$res['Type2'] = preg_replace('/\(.*\)/','',$res['Type']);
					?><tr id="_tr_<?=$table?>_<?=$ii++?>">
						<td id="_td_<?=$table?>_<?=$ii++?>" align="right">
							<?=$this->getFieldName($table, $res['Field'])?>
						</td>
					    <td align="left" id="_td_<?=$table?>_<?=$ii++?>">
					    	<?
							$references = $this->getReferences($table, $res['Field']);
							//print_r2($references);
							$reference_table  = $references[2];
							$reference_column = $references[3];
							
							if ($reference_column != '') {
								$fk_query = "SELECT * FROM $reference_table";
								//echo $fk_query;
								$fk_sql = $this->fastquery($fk_query);
								$refcount = intval($_REF[$reference_table]);
								if ($refcount == 0) $refcount = 1;
								echo "<select name=\"$res[Field]\">";
								while ($fk_res = $this->fetcharray($fk_sql)) {
									if ($resdata[$res['Field']] == $fk_res[0]) $fk_selected = ' selected';
									echo "<option value=\"$fk_res[0]\"$fk_selected>";
									for ($i=1; $i <= $refcount ; $i++)
										echo "$fk_res[$i] ";
									echo "</option>";
									$fk_selected = '';
								}
								echo "</select>";
							} else {
								if (!$this->isNULL($table, $res['Field']))
									$required = 'required';
								else
									$required = '';
							
					    		if ($res['Type2'] == 'varchar') {
									if ($res['Field'] == 'pass' || $res['Field'] == 'password') {
										?><input <?=$required?> style="width: 100%;" type="password" name="<?=$res['Field']?>" value="<?=$_nochange?>" /><?	
									} else {
										?><input <?=$required?> style="width: 100%;" type="text" name="<?=$res['Field']?>" value="<?=$resdata[$res['Field']]?>" /><?
									}
								} else if ($res['Type2'] == 'text') {
									?><textarea <?=$required?> style="width: 100%;" name="<?=$res['Field']?>" rows="4"><?=$resdata[$res['Field']]?></textarea><?
								} else if ($res['Type2'] == 'float') {
									?>
									<div align="right">
									<input <?=$required?> style="width: 60pt; text-align: right;" type="text" name="<?=$res['Field']?>" value="<?=$resdata[$res['Field']]?>" />
									</div>
									<?
								} else if ($res['Type2'] == 'enum') {
									// hacky: convertir enum en aray...
									$code = preg_replace('/^enum/', 'Array', $res['Type']);
									eval("\$data = $code;");
									echo "<select name=\"$res[Field]\">";
									while (list($key, $val) = each($data)) {
										if ($resdata[$res['Field']] == $val) $selected = ' selected';
										echo "<option value=\"$val\"$selected>";
										echo $key;
										echo "</option>";
										$selected = '';
									}
									echo "</select>";
								} else {
									echo "unhandled ". $res['Type2'];
								}
							
							}

					    	?>
					    </td>
						<td id="_td_<?=$table?>_<?=$ii++?>">
							<?
							if (!$this->isNULL($table, $res['Field'])) {
								echo " *";
							} else {
								echo "&nbsp;";
							}
							?>
						</td>
					  </tr>
					<?
				}
				
				$this->reset($sql);
				?>
				<tr id="_tr_<?=$table?>_<?=$ii++?>">
					<td id="_td_<?=$table?>_<?=$ii++?>" colspan="2" align="right">
						<div style="display: none;" id="_<?=$table?>_additional_1">ad</div>
						<input type="submit" value="Guardar">
					</td>
					<td>&nbsp;</td>
				</tr>
		</table>
		</div>
		</form>

		<script type="text/javascript">
		<!--
		function __submitForm_<?=$table?>(frm) {
			<?
			while ($res = $this->fetcharray($sql)) {
				if ($this->isNULL($table, $res['Field'])) continue;
				?>
				if (frm.<?=$res['Field']?>.value == '' && frm.<?=$res['Field']?>.type != 'hidden') {
					alert('Ingrese el valor del campo <?=$res['Field']?>');
					frm.<?=$res['Field']?>.focus();
					return(false);
				}
				<?
			}
			?>

			return(true);
		}
		-->
		</script>

		<?
		
	}

	public function editSQLReg($table, $id) {
		$this->_putForm($table, $id, METHOD_EDIT, $GET['oldurl']);
	}
	
	public function findPriKey($table) {
		// encontrar llave primaria
		$_query = "SHOW FULL COLUMNS IN $table WHERE `Key` = 'PRI'";
		$q = $this->fastquery($_query);
		$reskey = $this->fetcharray($q);

		if ($reskey['Field'] == '') {
			return(false);
		}

		return($reskey['Field']);
		
	}
	
	public function doEditSQL($data) {
		as_array($data);
		$table = $data['__sqlRegTable'];
		$back = $data['__SQLReturnVal'];
		$prikey = $this->findPriKey($table);
		if (!$prikey) {
			__error("Tabla $table no tiene llave primaria");
		}
		$prikeydata = $data[$prikey];

		$query = "UPDATE $table SET ";

		reset($data);
		while (list($key, $val) = each($data)) {
			if ($key{0} . $key{1} == '__') continue;
			if ($key == 'pass' && $val == '__[[NOCHANGE]]__') continue;
			if ($key == 'password' && $val == '__[[NOCHANGE]]__') continue;

			$query .= "$key = NULLIF('$val', ''), ";
		}
		$query = trim($query, ' ,');
		$query .= " WHERE $prikey = '$prikeydata' LIMIT 1";
		
		$sql = new SQL();
		$ret = $sql->fastquery($query);
		if (!$ret) {
			__error("Error ejecutando sentencia SQL");
		}
	
	}
	
}

?>