<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     interfaces.inc.php
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

error_reporting(E_STRICT | E_ALL);

//Abstract base class describing a database connection
abstract class DBA_Connection {
	abstract public function __construct($info);

	//Return the number of rows affected by the last query
	abstract public function AffectedRows();

	//Connect to the database
	//abstract public function Connect($host, $user, $pass);

	//Run a query and return TRUE for success or FALSE for failure
	abstract public function Execute($query, $multiline = FALSE);

	//Run a query and return the first row of the result set
	abstract public function GetRow($query, $type = DBA::ASSOC);

	//Run a query and return the value of the first column of the first row or FALSE
	abstract public function GetValue($query);

	//Return the value of the primary key from the last insert
	abstract public function InsertId();

	//Run a query and return a result object or FALSE
	abstract public function Query($query, $type = DBA::ASSOC);

	//Quote all charactars specific to the database connection
	abstract public function Quote($data);
	
	//Return the number of queries to the database
	abstract public function NumQueries();

	//Select a database
	//abstract public function SelectDb($database);
}

//Exception class specific to database operations
class DBA_Exception extends Exception {
	public function __construct($message, $error, $query = FALSE) {
		parent::__construct("$message [$error]");
		
		/* // If you want an error log, just uncomment this:
		if(isset($query)) {
			$fp = fopen('errors.txt', 'a+');
			fwrite($fp, $_SERVER['SCRIPT_FILENAME'] ."(". __LINE__ ."):". $query. "\n");
		}
		*/
	}
	public function __toString() {
		return $this->line;
	}
}

//Abstract base class representing a result set
abstract class DBA_Result implements IteratorAggregate {
	//Return the next row of the result set
	abstract public function FetchRow();

	//Return the number of rows in the result set
	abstract public function NumRows();

	//Seek to the given position in the result set and return TRUE on success
	//or FALSE on failure
	abstract public function Seek($index);
}


//Database specific iterator to iterate through a result set
class DBA_Iterator implements SeekableIterator {
	protected $current	= NULL;
	protected $index	= 0;
	protected $result;


	public function __construct(DBA_Result $result) {
		$this->result	= $result;

		$this->Rewind();
	}

	public function Current() {
		return $this->current;
	}

	public function Key() {
		return $this->index;
	}

	public function Next() {
		try {
			$this->current	= $this->result->FetchRow();
		} catch (DBA_Exception $e) {
			return $e->getMessage();
		}
		$this->index++;
	}

	public function Rewind() {
		if (($this->index == 0 && $this->current == NULL) || $this->result->Seek(0)) {
			try {
				$this->current	= $this->result->FetchRow();
			} catch (DBA_Exception $e) {
				return $e->getMessage();
			}
			$this->index	= 0;
		}
	}

	public function Seek($index) {
		if ($this->result->Seek($index)) {
			try {
				$this->current	= $this->result->FetchRow();
			} catch (DBA_Exception $e) {
				return $e->getMessage();
			}
			$this->index	= $index;

			return TRUE;
		}
		
		return FALSE;
	}

	public function Valid() {
		if ($this->current == NULL)
			return FALSE;

		return TRUE;
	}
}


class DBA_PairIterator extends DBA_Iterator {
	public function Current() {
		if (isset($this->current[1]))
			return $this->current[1];
	}

	public function Key() {
		if (isset($this->current[0]))
			return $this->current[0];
	}
}

?>