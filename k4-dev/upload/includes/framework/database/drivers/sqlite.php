<?php
/**
* k4 Bulletin Board, sqlite.php
*
* Copyright (c) 2005, Peter Goodman
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
* @version $Id: sqlite.php,v 1.2 2005/04/11 02:20:31 k4st Exp $
* @package k42
*/

define('DBA_ASSOC', SQLITE_ASSOC);
define('DBA_NUM', SQLITE_NUM);


class SQLiteResultIterator extends FADBResult {
	var $id;
	var $mode;
	var $row = -1;
	var $current;

	function SQLiteResultIterator($id, $mode) {
		$this->id = $id;
		$this->mode = $mode;
		$this->size = sqlite_num_rows($this->id);
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
			$this->current = sqlite_fetch_array($this->id, $this->mode);
			$this->row++;

			return $this->current();
		}
	}
	
	function numRows() {
		return $this->size;
	}

	function freeResult() {
		return TRUE;
	}

	function reset() {
		if ($this->row > 0)
			sqlite_seek($this->id, 0);

		$this->row = -1;

		return TRUE;
	}
}

class SQLiteStatement extends FADBStatement {
	//Use the generic one
}

class SQLiteConnection extends FADBConnection {
	var $link;
	var $valid = TRUE;
	var $num_queries = 0;

	function affectedRows() {
		//return mysql_affected_rows($this->link);
	}

	function connect($info) {
		if (!isset($info['database']) || !isset($info['directory'])) {
			$this->valid = FALSE;
			return Error::pitch(new FAError("Missing required connection information.", __FILE__, __LINE__));
		}

		$link = @sqlite_open($info['directory'] .'/'. $info['database'], 0666);

		if (!is_resource($link)) {
			$this->valid = FALSE;
			return Error::pitch(new FAError("Unable to connect to the database.", __FILE__, __LINE__));
		}

		/*if (!mysql_select_db($info['database'])) {
			$error = sqlite_error_string(sqlite_last_error($link));
			sqlite_close($link);
			$this->valid = FALSE;
			return Error::pitch(new FAError("Unable to select database: $error", __FILE__, __LINE__));
		}*/

		$this->link = $link;

		return TRUE;
	}

	function &prepareStatement($sql) {
		return new SQLiteStatement($sql, $this);
	}

	function executeUpdate($stmt) {

		$result = sqlite_query($stmt, $this->link);

		if ($result == FALSE)
			return trigger_error("Invalid query: ".sqlite_error_string(sqlite_last_error($this->link)), E_USER_ERROR);
		
		/* Increment the number of queries */
		$this->num_queries++;

		return TRUE;
	}

	function executeQuery($stmt, $mode = DBA_ASSOC) {

		$result = sqlite_query($stmt, $this->link);

		if (!is_resource($result)) {
			if (sqlite_last_error($this->link) == 0)
				return trigger_error("Invalid query: Called executeQuery on an update", E_USER_WARNING);
				
			return trigger_error("Invalid query: ".sqlite_error_string(sqlite_last_error($this->link)), E_USER_ERROR);
		}
		
		/* Increment the number of queries */
		$this->num_queries++;

		return new SQLiteResultIterator($result, $mode);
	}

	function getRow($query, $type = DBA_ASSOC) {

		$result			= sqlite_query($query, $this->link);

		if (!is_resource($result)) {
			return trigger_error("Invalid query: ".sqlite_error_string(sqlite_last_error($this->link)), E_USER_ERROR);
		}
		
		/* Increment the number of queries */
		$this->num_queries++;

		if (sqlite_has_more($result)) {
			if (isset($type)) {
				$row	= sqlite_fetch_array($result, $type);
								
				return $row;
			}
		}

		return FALSE;
	}

	function GetValue($query) {

		$result	= sqlite_query($query, $this->link);
		
		/* Increment the number of queries */
		$this->num_queries++;
		
		if(is_resource($result)) {
			if (sqlite_has_more($result)) {

				$value	= sqlite_fetch_single($result);

				return $value;
			}
		} else {
			return trigger_error("Invalid query: ".sqlite_error_string(sqlite_last_error($this->link)), E_USER_ERROR);
		}
		return FALSE;
	}
	
	function getInsertId() {
		/* Increment the number of queries */
		$this->num_queries++;

		return sqlite_last_insert_rowid($this->link);
	}

	function isValid() {
		return $this->valid;
	}

	function quote($value) {
		return sqlite_escape_string($value);
	}
}

?>