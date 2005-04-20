<?php
/**
* k4 Bulletin Board, mysql.php
*
* Copyright (c) 2005, Geoffrey Goodman
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
* @author Peter Goodman
* @version $Id: mysql.php,v 1.4 2005/04/20 02:55:12 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

define('DBA_ASSOC', MYSQL_ASSOC);
define('DBA_NUM', MYSQL_NUM);

class MysqlResultIterator extends FADBResult {
	var $id;
	var $mode;
	var $row = -1;
	var $current;
	var $size;

	function MysqlResultIterator($id, $mode) {
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
	
	function freeResult() {
		return mysql_free_result($this->id);
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
	var $link;
	var $valid = TRUE;
	var $num_queries = 0;

	function affectedRows() {
		return mysql_affected_rows($this->link);
	}

	function connect($info) {
		if (!isset($info['server']) || !isset($info['user']) || !isset($info['pass']) || !isset($info['database'])) {
			$this->valid = FALSE;
			return Error::pitch(new FAError("Missing required connection information.", __FILE__, __LINE__));
		}

		$link = @mysql_pconnect($info['server'], $info['user'], $info['pass']);

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

		return TRUE;
	}

	function &prepareStatement($sql) {
		return new MysqlStatement($sql, $this);
	}

	function executeUpdate($stmt) {
		$result = mysql_query($stmt, $this->link);

		if ($result == FALSE)
			return trigger_error("Invalid query: ".mysql_error($this->link), E_USER_ERROR);
		
		if(DEBUG_SQL)
			set_debug_item($stmt, $result);

		/* Increment the number of queries */
		$this->num_queries++;

		return TRUE;
	}

	function executeQuery($stmt, $mode = DBA_ASSOC) {
		$result = mysql_query($stmt, $this->link);

		if (!is_resource($result)) {
			if (mysql_errno() == 0)
				return trigger_error("Invalid query: Called executeQuery on an update", E_USER_WARNING);
				
			return trigger_error("Invalid query: ".mysql_error($this->link), E_USER_ERROR);
		}
		
		/* Increment the number of queries */
		$this->num_queries++;

		$result = &new MysqlResultIterator($result, $mode);

		if(DEBUG_SQL)
			set_debug_item($stmt, $result);

		return $result;
	}

	function getRow($query, $type = DBA_ASSOC) {

		$result			= mysql_query($query, $this->link);

		if (!is_resource($result)) {
			return trigger_error("Invalid query: ".sqlite_error_string(sqlite_last_error($this->link)), E_USER_ERROR);
		}
		
		/* Increment the number of queries */
		$this->num_queries++;

		while($row = mysql_fetch_array($result, $type)) {
			
			if(DEBUG_SQL)
				set_debug_item($query, $row);
			
			return $row;
		}

		return FALSE;
	}

	function GetValue($query) {
		
		$result	= mysql_query($query, $this->link);
		
		/* Increment the number of queries */
		$this->num_queries++;
		
		if(is_resource($result)) {
			if (mysql_num_rows($result) > 0) {
				$row	= mysql_fetch_array($result, MYSQL_NUM);

				mysql_free_result($result);
				
				if(DEBUG_SQL)
					set_debug_item($stmt, $row[0]);

				return $row[0];
			}
		} else {
			return trigger_error("Invalid query: ".sqlite_error_string(sqlite_last_error($this->link)), E_USER_ERROR);
		}
		return FALSE;
	}
	
	function getInsertId() {
		/* Increment the number of queries */
		$this->num_queries++;

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