<?php
/**
* k4 Bulletin Board, mysql.php
*
* Copyright (c) 2004, Geoffrey Goodman
*
* Permission is hereby granted, free of charge, to any person obtaining
* a copy of this software and associated documentation files (the
* "Software"), to deal in the Software without restriction, including
* without limitation the rights to use, copy, modify, merge, publish,
* distribute, sublicense, and/or sell copies of the Software, and to
* permit persons to whom the Software is furnished to do so, subject to
* the following conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
* BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
* ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
* CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*
* @author Geoffrey Goodman
* @version $Id: mysql.php,v 1.1 2005/04/05 02:32:39 necrotic Exp $
* @package k42
*/

define('DBA_ASSOC', MYSQL_ASSOC);
define('DBA_NUM', MYSQL_NUM);

class Mysql_Result extends FADBResult {
	protected $id;
	protected $mode;
	protected $row = -1;
	protected $current;
	protected $size;

	function Mysql_Result($id, $mode) {
		$this->id = $id;
		$this->mode = $mode;
		$this->size = mysql_num_rows($this->id);
	}

	function &current() {
		return $this->current;
	}

	function hasNext() {
		return ($this->row + 1 < $this->size) ? TRUE : FALSE;
	}

	function key() {
		return $this->row;
	}

	function &next() {
		if ($this->hasNext()) {
			$this->current = mysql_fetch_array($this->id, $this->mode);
			$this->row++;

			return $this->current();
		}
	}
	
	function numRows() {
		return $this->size;
	}

	function reset() {
		if ($this->row > 0)
			mysql_data_seek($this->id, 0);

		$this->row = -1;

		return TRUE;
	}
}

class MysqlStatement extends FADBStatement {
	//Use the generic one
}

class MysqlConnection extends FADBConnection {
	protected $link;
	protected $valid = TRUE;

	function affectedRows() {
		return mysql_affected_rows($this->link);
	}

	function connect($info) {
		if (!isset($info['server']) || !isset($info['user']) || !isset($info['pass']) || !isset($info['database'])) {
			$this->valid = FALSE;
			return Error::pitch(new FAError("Missing required connection information.", __FILE__, __LINE__));
		}
		//echo '<pre>'.print_r($info,true).'</pre>';
		$link = mysql_connect($info['server'], $info['user'], $info['pass']);

		if (!is_resource($link)) {
			$this->valid = FALSE;
			return Error::pitch(new FAError("Unable to connect to the database: ".mysql_errno(), __FILE__, __LINE__));
		}

		if (!mysql_select_db($info['database'])) {
			$error = mysql_error($link);
			mysql_close($link);
			$this->valid = FALSE;
			return Error::pitch(new FAError("Unable to select database: $error", __FILE__, __LINE__));
		}

		$this->link = $link;
		//echo 'okay';

		return TRUE;
	}

	function &prepareStatement($sql) {
		return new MysqlStatement($sql, $this);
	}

	function executeUpdate($stmt) {
		$result = mysql_query($stmt, $this->link);

		if ($result == FALSE)
			return trigger_error("Invalid query: ".mysql_error($this->link), E_USER_ERROR);

		return TRUE;
	}

	function executeQuery($stmt, $mode = DBA_ASSOC) {
		$result = mysql_query($stmt, $this->link)
			or die ( mysql_error ( ) );

		if (!is_resource($result)) {
			if (mysql_errno() == 0)
				return trigger_error("Invalid query: Called executeQuery on an update<code>{$stmt}</code>", E_USER_WARNING);
			return trigger_error("Invalid query: ".mysql_error($this->link), E_USER_ERROR);
		}

		return new Mysql_Result($result, $mode);
	}
	
	function getInsertId() {
		return mysql_insert_id($this->link);
	}

	function isValid() {
		return $this->valid;
	}

	function quote($value) {
		return mysql_escape_string($value);
	}
}

?>
