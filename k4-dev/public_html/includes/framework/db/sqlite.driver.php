<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     sqlite.driver.php
 *     Copyright (c) 2004, Peter Goodman

 *     Permission is hereby granted, free of charge, to any person obtaining 
 *     a copy of this software and associated documentation files (the 
 *     "Software"), to deal in the Software without restriction, including 
 *     without limitation the rights to use, copy, modify, merge, publish, 
 *     distribute, sublicense, and/or sell copies of the Software, and to 
 *     permit persons to whom the Software is furnished to do so, subject to 
 *     the following conditions:

 *     The above copyright notice and this permission notice shall be 
 *     included in all copies or substantial portions of the Software.

 *     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, 
 *     EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 
 *     MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 *     NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS 
 *     BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN 
 *     ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
 *     CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE 
 *     SOFTWARE.
 *********************************************************************************/

error_reporting(E_ALL);

class SqLite_Connection extends DBA_Connection {
	private $link;
	public $num_queries = 0;

	public function __construct($info) {
		if (isset($info['database']))
			$this->SelectDb($info['database']);
	}

	public function __destruct() {
		if (is_resource($this->link))
			sqlite_close($this->link);
	}

	//Return the number of rows affected by the last query
	public function AffectedRows() {
		return FALSE;
	}

	//Connect to the database
	public function Connect($host, $user, $pass) {
		return TRUE;
	}

	//Run a query and return TRUE for success or FALSE for failure
	public function Execute($query, $multiline = FALSE) {
		if ($multiline) {
			$lines	= explode(';', $query);

			foreach ($lines as $line) {
				$line	= trim($line);
				if ($line && $this->Execute($line) == FALSE)
					return FALSE;
			}
			
			return TRUE;
		}

		$return	= sqlite_query($this->link, $query);

		if ($return == TRUE || is_resource($return)) {
			$this->num_queries = $this->num_queries+1;
			return TRUE;
		}
		
		throw new DBA_Exception(DBA::E_INVALID_QUERY, sqlite_error_string(sqlite_last_error($this->link)));
		
		return FALSE;
	}

	//Run a query and return the first row of the result set
	public function GetRow($query, $type = DBA::ASSOC) {
		$sqlite_type	= array(DBA::ASSOC => SQLITE_ASSOC, DBA::NUM => SQLITE_NUM);
		$result			= sqlite_unbuffered_query($this->link, $query);

		if (!is_resource($result)) {
			throw new DBA_Exception(DBA::E_INVALID_QUERY, sqlite_error_string(sqlite_last_error($this->link)));

			return FALSE;
		}
		
		if (sqlite_has_more($result)) {
			if (isset($sqlite_type[$type])) {
				$row	= sqlite_fetch_array($result, $sqlite_type[$type]);
				$this->num_queries = $this->num_queries+1;
				return $row;
			}
		}
		return FALSE;
	}

	//Run a query and return the value of the first column of the first row or FALSE
	public function GetValue($query) {

		$result	= sqlite_unbuffered_query($this->link, $query);
		if(is_resource($result)) {
			if (sqlite_has_more($result)) {
				$value	= sqlite_fetch_single($result);
				$this->num_queries = $this->num_queries+1;
				return $value;
			}
		} else {
			throw new DBA_Exception(DBA::E_INVALID_QUERY, sqlite_error_string(sqlite_last_error($this->link)));
		}
		return FALSE;
	}

	//Return the value of the primary key from the last insert
	public function InsertId() {
		return sqlite_last_insert_rowid($this->link);
	}

	//Run a query and return a result object or FALSE
	public function Query($query, $type = DBA::ASSOC) {

		$sqlite_type	= array(DBA::ASSOC => SQLITE_ASSOC, DBA::NUM => SQLITE_NUM);
		$result			= sqlite_query($this->link, $query);

		if (!is_resource($result)) {
			throw new DBA_Exception(DBA::E_INVALID_QUERY, sqlite_error_string(sqlite_last_error($this->link)));

			return FALSE;
		}
		$this->num_queries = $this->num_queries+1;
		switch ($type) {
			case DBA::ASSOC:
			case DBA::NUM:
				return new SQLite_Result($result, $sqlite_type[$type]);
			case DBA::FLAT:
				return new SQLite_Flat($result);
			case DBA::PAIR:
				return new SQLite_Pair($result);
			default:
				throw new DBA_Exception(DBA::E_INVALID_TYPE, "Invalid result type [$type]");
		}

		return FALSE;
	}

	//Quote all charactars specific to the database connection
	public function Quote($data) {
		return sqlite_escape_string($data);
	}

	//Select a database
	public function SelectDb($database) {
		$database	= append_slash(FORUM_BASE_DIR . DIRECTORY_SEPARATOR . 'includes/sqlite') . $database;

		if (!file_exists($database) && !is_writable(dirname($database))) {
			throw new DBA_Exception(DBA::E_INVALID_DATABASE, "Unable to create database [$database]");

			return FALSE;
		}

		$mode		= get_setting('sqlite', 'mode') or
			$mode	= 0666;

		$this->link	= sqlite_open($database, $mode, $conn_error);

		if ($this->link === FALSE) {
			throw new DBA_Exception(DBA::E_INVALID_DATABASE, "Unable to open database [$conn_error]");

			return FALSE;
		}
	}
	public function NumQueries() {
		return $this->num_queries;
	}
}

class SQLite_Result extends DBA_Result {
	private $result;
	private $type;


	public function __construct($result, $type) {
		$this->result	= $result;
		$this->type		= $type;
	}
	
	public function GetRows() {
		
		if (!is_resource($this->result))
			return FALSE;
		
		$rows = array();

		while($row = sqlite_fetch_array($this->result, $this->type)) {
			$rows[] = $row;
		}
		return $rows;
	}

	public function GetIterator() {
		return new DBA_Iterator($this);
	}

	public function FetchRow() {
		$row	= sqlite_fetch_array($this->result, $this->type);

		if ($row != FALSE) {

			return $row;
		}

		return FALSE;
	}

	public function NumRows() {
		return sqlite_num_rows($this->result);
	}

	public function Seek($index) {
		return sqlite_seek($this->result, $index);
	}
}

class SQLite_Flat extends SQLite_Result {
	public function __construct($result) {
		parent::__construct($result, MYSQL_NUM);
	}

	public function FetchRow() {
		$value	= sqlite_fetch_single($this->result, $this->type);

		if ($value != FALSE) {
			if ($this->filter != NULL) {
				$value	= $this->filter->Apply($value);
			}

			return $value;
		}

		return FALSE;
	}
}

class SQLite_Pair extends SQLite_Result {
	public function __construct($result) {
		parent::__construct($result, MYSQL_NUM);
	}

	public function GetIterator() {
		return DBA_PairIterator($this);
	}
}

?>