<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     pgsql.driver.php
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

class PgSQL_Connection extends DBA_Connection {
	private $link;
	public $num_queries = 0;

	public function __construct($info) {
		$this->Connect($info['host'], $info['user'], $info['pass'], $info['database']);
	}

	public function __destruct() {
		if (is_resource($this->link))
			pg_close($this->link);
	}

	//Return the number of rows affected by the last query
	public function AffectedRows() {
		return FALSE;
	}

	//Connect to the database
	public function Connect($host, $user, $pass, $db) {
		$this->link	= @pg_pconnect("host=$host dbname=$db user=$user password=$pass");

		if (!is_resource($this->link)) {
			throw new DBA_Exception(DBA::E_INVALID_SERVER, "pgsql://$user:$pass@$host.$db");

			return FALSE;
		}
		return TRUE;
	}

	//Run a query and return TRUE for success or FALSE for failure
	public function Execute($query, $multiline = FALSE) {
		
		$this->num_queries = $this->num_queries+1;
		
		if(!$multiline) {
			return @pg_query($this->link, $query);
		} else {
			$queries = explode(";", $query);
			foreach($queries as $sql) {
				if(!is_null($sql))
					return @pg_query($this->link, $sql);
			}
		}
		
		throw new DBA_Exception(DBA::E_INVALID_QUERY, pg_last_error($this->link));
		
		return FALSE;
	}

	//Run a query and return the first row of the result set
	public function GetRow($query, $type = DBA::ASSOC) {
		$result_type	= array(DBA::ASSOC => PGSQL_ASSOC, DBA::NUM => PGSQL_NUM);
		$result			= pg_query($this->link, $query);
		
		$this->num_queries = $this->num_queries+1;

		if (!is_resource($result)) {
			throw new DBA_Exception(DBA::E_INVALID_QUERY, pg_last_error($this->link), $query);

			return FALSE;
		}

		while($row = @pg_fetch_array($result, NULL, $result_type[$type])) {
			return $row;
		}


		return FALSE;
	}

	//Run a query and return the value of the first column of the first row or FALSE
	public function GetValue($query) {
		$result = pg_query($this->link, $query);

		if (!is_resource($result)) {
			throw new DBA_Exception(DBA::E_INVALID_QUERY, pg_last_error($this->link), $query);

			return FALSE;
		}
		
		if (pg_num_rows($result) > 0) {
			$row	= @pg_fetch_array($result, NULL, PGSQL_NUM);

			pg_free_result($result);

			return $row[0];
		}
		return FALSE;
	}

	//Return the value of the primary key from the last insert
	public function InsertId() {
		return FALSE;
	}

	//Run a query and return a result object or FALSE
	public function Query($query, $type = DBA::ASSOC) {
		$result_type	= array(DBA::ASSOC => PGSQL_ASSOC, DBA::NUM => PGSQL_NUM);
		$result			= pg_query($this->link, $query);

		if (!is_resource($result)) {
			throw new DBA_Exception(DBA::E_INVALID_QUERY, pg_last_error($this->link), $query);

			return FALSE;
		}

		$this->num_queries = $this->num_queries+1;
		
		switch ($type) {
			case DBA::ASSOC:
			case DBA::NUM:
				return new PgSQL_Result($result, $result_type[$type]);
			case DBA::FLAT:
				return new PgSQL_Result($result);
			case DBA::PAIR:
				return new PgSQL_Result($result);
			default:
				throw new DBA_Exception(DBA::E_INVALID_TYPE, "Invalid result type [$type]");
		}

		return FALSE;
	}

	//Quote all charactars specific to the database connection
	public function Quote($data) {
		return pg_escape_string($data);
	}

	//Select a database
	public function SelectDb($database) {
		return TRUE;
	}
	public function NumQueries() {
		return $this->num_queries;
	}
}

class PgSQL_Result extends DBA_Result {
	private $result;
	private $type;


	public function __construct($result, $type) {
		$this->result	= $result;
		$this->type		= $type;
	}

	public function GetIterator() {
		return new DBA_Iterator($this);
	}

	public function FetchRow() {
		$row	= @pg_fetch_array($this->result, NULL, $this->type);

		if (is_resource($row)) {

			return $row;
		} else {
			throw new DBA_Exception(DBA::E_INVALID_QUERY, "Invalid Query");
		}

		return FALSE;
	}

	public function NumRows() {
		return pg_num_rows($this->result);
	}

	public function Seek($index) {
		return pg_result_seek($this->result, $index);
	}
}

class PgSQL_Flat extends PgSQL_Result {
	public function __construct($result) {
		parent::__construct($result, PGSQL_NUM);
	}

	public function FetchRow() {
		$value	= @pg_fetch_array($this->result, NULL, $this->type);

		if ($value != FALSE) {
			if ($this->filter != NULL) {
				$value	= $this->filter->Apply($value);
			}

			return $value;
		}

		return FALSE;
	}
}

class PgSQL_Pair extends PgSQL_Result {
	public function __construct($result) {
		parent::__construct($result, PGSQL_NUM);
	}

	public function GetIterator() {
		return DBA_PairIterator($this);
	}
}

?>