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
* @version $Id: mysqli.php,v 1.1 2005/05/26 20:30:52 ggoodman Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

define('DBA_ASSOC', MYSQLI_ASSOC);
define('DBA_NUM', MYSQLI_NUM);

class MysqliResultIterator extends FADBResult {
	var $id;
	var $mode;
	var $row = -1;
	var $current;
	var $size;

	function MysqliResultIterator($id, $mode) {
		$this->id = $id;
		$this->mode = $mode;
		$this->size = mysqli_num_rows($this->id);
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
			$this->current = mysqli_fetch_array($this->id, $this->mode);
			$this->row++;

			return $this->current();
		}
	}
	
	function freeResult() {
		return mysqli_free_result($this->id);
	}

	function numRows() {
		return $this->size;
	}

	function reset() {
		if ($this->row > 0)
			mysqli_data_seek($this->id, 0);

		$this->row = -1;

		return TRUE;
	}
}

class MysqliStatement extends FADBStatement {
	//Use the generic one
}

class MysqliConnection extends FADBConnection {
	var $link;
	var $valid = TRUE;
	var $num_queries = 0;

	function affectedRows() {
		return mysqli_affected_rows($this->link);
	}

	function connect($info) {
		if (!isset($info['server']) || !isset($info['user']) || !isset($info['pass']) || !isset($info['database'])) {
			$this->valid = FALSE;
			return Error::pitch(new FAError("Missing required connection information.", __FILE__, __LINE__));
		}

		if(!function_exists('mysqli_connect')) {
			$this->valid = FALSE;
			return Error::pitch(new FAError("Please make sure that MySQL is properly installed.", '--', '--'));
		}
		
		$link = @mysqli_connect($info['server'], $info['user'], $info['pass'], $info['database']);
		
		if (!$link) {
			$this->valid = FALSE;
			return Error::pitch(new FAError("Unable to connect to the database: ".mysqli_errno($this->link), __FILE__, __LINE__));
		}
		
		$this->link = $link;

		return TRUE;
	}

	function &prepareStatement($sql) {
		return new MysqliStatement($sql, $this);
	}

	function executeUpdate($stmt) {
		
		$result = mysqli_query($this->link, $stmt);

		if ($result == FALSE)
			return compile_error("Invalid query: ". mysqli_error($this->link), __FILE__, __LINE__);
		
		if(DEBUG_SQL)
			set_debug_item($stmt, $result);

		/* Increment the number of queries */
		$this->num_queries++;

		return TRUE;
	}

	function executeQuery($stmt, $mode = DBA_ASSOC) {
		$result = mysqli_query($this->link, $stmt);
		
		if (!$result) {
			if (mysqli_errno($this->link) == 0)
				return compile_error("Invalid query: Called executeQuery on an update", __FILE__, __LINE__);
				
			return compile_error("Invalid query: ".mysqli_error($this->link), __FILE__, __LINE__);
		}
		
		/* Increment the number of queries */
		$this->num_queries++;

		$result = &new MysqliResultIterator($result, $mode);
		
		if(DEBUG_SQL)
			set_debug_item($stmt, $result);

		return $result;
	}

	function getRow($query, $type = DBA_ASSOC) {
		$result = $this->executeQuery($query, $type);
		
		if ($result->next()) {
			return $result->current();
		}
		
		return FALSE;
	}

	function GetValue($query) {
		$result = $this->executeQuery($query, DBA_NUM);
		
		if ($result->next()) {
			return $result->get(0);
		}
		
		return FALSE;
	}

	function Query($stmt) {
		return @mysqli_query($stmt, $this->link);
	}
	
	function getInsertId() {
		/* Increment the number of queries */
		$this->num_queries++;

		return mysqli_insert_id($this->link);
	}

	function isValid() {
		return $this->valid;
	}

	function quote($value) {
		return mysqli_escape_string($this->link, $value);
	}
	function alterTable($table, $stmt) {
		$this->executeUpdate("ALTER TABLE $table $stmt");
	}
	function beginTransaction() {
		return TRUE;
	}
	function commitTransaction() {
		return TRUE;
	}
}

?>