<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     mysql.driver.php
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

class MySQL_Connection extends DBA_Connection {
	private $link;
	public $num_queries = 0;

	public function __construct($info) {
		$this->Connect($info['host'], $info['user'], $info['pass']);
		if (isset($info['database']))
			$this->SelectDb($info['database']);
	}

	public function __destruct() {
		if (is_resource($this->link))
			mysql_close($this->link);
	}

	//Return the number of rows affected by the last query
	public function AffectedRows() {
		return mysql_affected_rows($this->link);
	}

	//Connect to the database
	public function Connect($host, $user, $pass) {
		$this->link	= @mysql_pconnect($host, $user, $pass);

		if (!is_resource($this->link)) {
			throw new DBA_Exception(DBA::E_INVALID_SERVER, "mysql://$user:$pass@$host");

			return FALSE;
		}
		return TRUE;
	}

	//Run a query and return TRUE for success or FALSE for failure
	public function Execute($query, $multiline = FALSE) {
		$this->num_queries = $this->num_queries+1;
		if(!$multiline) {
			return mysql_query($query, $this->link);
		} else {
			$queries = explode(";", $query);
			foreach($queries as $sql) {
				if(!is_null($sql))
					return mysql_query($sql, $this->link);
			}
		}
		
		throw new DBA_Exception(DBA::E_INVALID_QUERY, mysql_error($this->link));
		
		return FALSE;
	}

	//Run a query and return the first row of the result set
	public function GetRow($query, $type = DBA::ASSOC) {
		$result_type	= array(DBA::ASSOC => MYSQL_ASSOC, DBA::NUM => MYSQL_NUM);
		$result			= mysql_query($query, $this->link);
		
		$this->num_queries = $this->num_queries+1;

		if (!is_resource($result)) {
			throw new DBA_Exception(DBA::E_INVALID_QUERY, mysql_error($this->link));

			return FALSE;
		}

		while($row = mysql_fetch_array($result, $result_type[$type])) {
			return $row;
		}


		return FALSE;
	}

	//Run a query and return the value of the first column of the first row or FALSE
	public function GetValue($query) {
		$result = mysql_unbuffered_query($query, $this->link);

		if (!is_resource($result)) {
			throw new DBA_Exception(DBA::E_INVALID_QUERY, mysql_error($this->link));

			return FALSE;
		}
		
		if (mysql_num_rows($result) > 0) {
			$row	= mysql_fetch_array($result, MYSQL_NUM);

			mysql_free_result($result);

			return $row[0];
		}
		return FALSE;
	}

	//Return the value of the primary key from the last insert
	public function InsertId() {
		return mysql_insert_id($this->link);
	}

	//Run a query and return a result object or FALSE
	public function Query($query, $type = DBA::ASSOC) {
		$result_type	= array(DBA::ASSOC => MYSQL_ASSOC, DBA::NUM => MYSQL_NUM);
		$result			= mysql_query($query, $this->link);

		if (!is_resource($result)) {
			throw new DBA_Exception(DBA::E_INVALID_QUERY, mysql_error($this->link));

			return FALSE;
		}

		$this->num_queries = $this->num_queries+1;
		
		switch ($type) {
			case DBA::ASSOC:
			case DBA::NUM:
				return new MySQL_Result($result, $result_type[$type]);
			case DBA::FLAT:
				return new MySQL_Result($result);
			case DBA::PAIR:
				return new MySQL_Result($result);
			default:
				throw new DBA_Exception(DBA::E_INVALID_TYPE, "Invalid result type [$type]");
		}

		return FALSE;
	}

	//Quote all charactars specific to the database connection
	public function Quote($data) {
		return mysql_real_escape_string($data);
	}

	//Select a database
	public function SelectDb($database) {
		if(is_resource($this->link)) {
			if (mysql_select_db($database, $this->link) == FALSE) {
				throw new DBA_Exception(DBA::E_INVALID_DB, $database);

				return FALSE;
			}
		} else {
			throw new DBA_Exception(DBA::E_INVALID_SERVER, "$this->link");
		}

		return TRUE;
	}
	public function NumQueries() {
		return $this->num_queries;
	}
}

class MySQL_Result extends DBA_Result {
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

		while($row = mysql_fetch_array($this->result, $this->type)) {
			$rows[] = $row;
		}
		return $rows;
	}

	public function GetIterator() {
		return new DBA_Iterator($this);
	}

	public function FetchRow() {
		$row	= mysql_fetch_array($this->result, $this->type);

		if ($row != FALSE) {

			return $row;
		}

		return FALSE;
	}

	public function NumRows() {
		return mysql_num_rows($this->result);
	}

	public function Seek($index) {
		return mysql_data_seek($this->result, $index);
	}
}

class MySQL_Flat extends MySQL_Result {
	public function __construct($result) {
		parent::__construct($result, MYSQL_NUM);
	}

	public function FetchRow() {
		$value	= mysql_fetch_array($this->result, $this->type);

		if ($value != FALSE) {
			if ($this->filter != NULL) {
				$value	= $this->filter->Apply($value);
			}

			return $value;
		}

		return FALSE;
	}
}

class MySQL_Pair extends MySQL_Result {
	public function __construct($result) {
		parent::__construct($result, MYSQL_NUM);
	}

	public function GetIterator() {
		return DBA_PairIterator($this);
	}
}

?>