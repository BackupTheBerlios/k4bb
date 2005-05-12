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
* @version $Id: sqlite.php,v 1.8 2005/05/12 01:37:42 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

define('DBA_ASSOC', SQLITE_ASSOC);
define('DBA_NUM', SQLITE_NUM);


class SQLiteResultIterator extends FADBResult {
	var $id;
	var $mode;
	var $row = -1;
	var $current;

	function SQLiteResultIterator($id, $mode) {
		$this->id	= $id;
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
		//echo '<br /><br />'. $stmt .'<br /><br />';
		
		$result = sqlite_query($stmt, $this->link);

		if ($result == FALSE) {
			return compile_error("Invalid query: ". sqlite_error_string(sqlite_last_error($this->link)), __FILE__, __LINE__);
		}
		if(DEBUG_SQL)
			set_debug_item($stmt, $result);

		/* Increment the number of queries */
		$this->num_queries++;

		return TRUE;
	}

	function executeQuery($stmt, $mode = DBA_ASSOC) {

		$result = sqlite_query($stmt, $this->link);

		if (!is_resource($result)) {
			if (sqlite_last_error($this->link) == 0) {
				return compile_error("Invalid query: Called executeQuery on an update", __FILE__, __LINE__);
			}
				
			return compile_error("Invalid query: ". sqlite_error_string(sqlite_last_error($this->link)), __FILE__, __LINE__);
		}
		
		/* Increment the number of queries */
		$this->num_queries++;

		$result = &new SQLiteResultIterator($result, $mode);

		if(DEBUG_SQL)
			set_debug_item($stmt, $result);

		return $result;
	}

	function getRow($query, $type = DBA_ASSOC) {

		$result			= sqlite_query($query, $this->link);

		if (!is_resource($result)) {
			return compile_error("Invalid query: ". sqlite_error_string(sqlite_last_error($this->link)), __FILE__, __LINE__);
		}
		
		/* Increment the number of queries */
		$this->num_queries++;

		if (sqlite_has_more($result)) {
			if (isset($type)) {
				$row	= sqlite_fetch_array($result, $type);
				
				if(DEBUG_SQL)
					set_debug_item($query, $row);		

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
				
				if(DEBUG_SQL)
					set_debug_item($query, $value);

				return $value;
			}
		} else {
			return compile_error("Invalid query: ".sqlite_error_string(sqlite_last_error($this->link)), __FILE__, __LINE__);
		}
		return FALSE;
	}

	function Query($stmt) {
		$result = @sqlite_query($stmt, $this->link);
		return $result;
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

	/* Modified and cleaned version of http://code.jenseng.com/db/ */
	function alterTable($table, $alterdefs) {
	
		if($alterdefs != '') {
			$result								= sqlite_query($this->link, "SELECT sql, name, type FROM sqlite_master WHERE tbl_name = '". $table ."' ORDER BY type DESC");
		
			if(sqlite_num_rows($result) > 0) {
			
				$row							= sqlite_fetch_array($result); //table sql
				$tmpname						= 't'. time();
				$origsql						= trim(preg_replace("/[\s]+/", " ", str_replace(",", ", ", preg_replace("/[\(]/", "( ", $row['sql'], 1))));
				$createtemptableSQL				= 'CREATE TEMPORARY '.substr(trim(preg_replace("'". $table ."'", $tmpname, $origsql, 1)), 6);
				$createindexsql					= array();
				$i								= 0;
				$defs							= preg_split("/[,]+/", $alterdefs, -1, PREG_SPLIT_NO_EMPTY);
				$prevword						= $table;
				
				/* Doesn't work with decimal() columns.. e.g. decimal(5,2) */
				$oldcols						= preg_split("/[,]+/", substr(trim($createtemptableSQL), strpos(trim($createtemptableSQL),'(')+1), -1, PREG_SPLIT_NO_EMPTY);

				$newcols						= array();

				for($i = 0; $i < count($oldcols); $i++ ) {
					$colparts						= preg_split("/[\s]+/", $oldcols[$i], -1, PREG_SPLIT_NO_EMPTY);
					$oldcols[$i]					= $colparts[0];
					$newcols[$colparts[0]]			= $colparts[0];
				}

				$newcolumns = '';
				$oldcolumns = '';

				reset($newcols);

				while(list($key, $val) = each($newcols)) {
					$newcolumns .= iif($newcolumns, ', ', '') . $val;
					$oldcolumns .= iif($oldcolumns, ', ', '') . $key;
				}

				$copytotempsql						= 'INSERT INTO '. $tmpname .'('. $newcolumns .') SELECT '. $oldcolumns .' FROM '. $table;
				$dropoldsql							= 'DROP TABLE '. $table;
				$createtesttableSQL					= $createtemptableSQL;

				foreach($defs as $def) {
					$defparts						= preg_split("/[\s]+/", $def, -1, PREG_SPLIT_NO_EMPTY);
					$action							= strtolower($defparts[0]);

					switch($action) {
						case 'add': {
							
							if(sizeof($defparts) <= 2) {
								error::pitch(new FAError('An error occured near "'. $defparts[0] . iif($defparts[1], ' '. $defparts[1], '').'": syntax error.', __FILE__, __LINE__));
								return FALSE;
							}
							
							$createtesttableSQL				= substr($createtesttableSQL,0,strlen($createtesttableSQL)-1).',';
							
							for($i = 1; $i < sizeof($defparts); $i++) {
								$createtesttableSQL.=' '.$defparts[$i];
							}
							
							$createtesttableSQL				.= ')';

							break;
						}
						case 'change': {

							if(count($defparts) <= 3) {
								error::pitch(new FAError('An error occured near "'. $defparts[0] . iif($defparts[1], ' '. $defparts[1], '') . iif($defparts[2], ' '. $defparts[2], '') .'": syntax error.', __FILE__, __LINE__));
								return FALSE;
							}
							if($severpos = strpos($createtesttableSQL, ' '. $defparts[1] .' ')) {
								
								if($newcols[$defparts[1]] != $defparts[1]) {
									error::pitch(new FAError('unknown column "'. $defparts[1] .'" in "'. $table .'"', __FILE__, __LINE__));
									return FALSE;
								}
								$newcols[$defparts[1]] = $defparts[2];
								$nextcommapos = strpos($createtesttableSQL, ',', $severpos);
								$insertval = '';
								for($i = 2; $i < count($defparts); $i++) {
									$insertval .= ' '. $defparts[$i];
								}

								if($nextcommapos) {
									$createtesttableSQL = substr($createtesttableSQL, 0, $severpos) . $insertval . substr($createtesttableSQL, $nextcommapos);
								} else {
									$createtesttableSQL = substr($createtesttableSQL, 0, $severpos - iif(strpos($createtesttableSQL,','), 0, 1)) . $insertval .')';
								}
							
							} else {
								error::pitch(new FAError('Unknown column "'. $defparts[1] .'" in "'. $table .'"', __FILE__, __LINE__));
								return FALSE;
							}
							break;
						}

						case 'drop': {
							if(count($defparts) < 2){
								error::pitch(new FAError('An error occured near "'. $defparts[0] . iif($defparts[1], ' '. $defparts[1], '') .'": syntax error.', __FILE__, __LINE__));
								return FALSE;
							}
							if($severpos = strpos($createtesttableSQL,' '. $defparts[1].' ')) {
								
								$nextcommapos			= strpos($createtesttableSQL, ',', $severpos);
								if($nextcommapos) {
									$createtesttableSQL = substr($createtesttableSQL,0,$severpos).substr($createtesttableSQL,$nextcommapos + 1);
								} else {
									$createtesttableSQL = substr($createtesttableSQL,0,$severpos-(strpos($createtesttableSQL,',')?0:1) - 1).')';
								}
								unset($newcols[$defparts[1]]);
							} else{
								error::pitch(new FAError('Unknown column "'. $defparts[1] .'" in "'. $table .'".', __FILE__, __LINE__));
								return FALSE;
							}
							break;
						}
						default: {
							error::pitch(new FAError('An error occured near "'. $prevword .'": syntax error.', __FILE__, __LINE__));
							return FALSE;
						}
					}

					$prevword = $defparts[count($defparts)-1];
				}


				/**
				 * this block of code generates a test table simply to verify that the columns 
				 * specifed are valid in an sql statement this ensures that no reserved words 
				 * are used as columns, for example.
				 */
				if(!$this->query($createtesttableSQL)){
					error::pitch(new FAError('The test table could not be created.<br /><br />'. $createtesttable, __FILE__, __LINE__));
					return FALSE;
				}

				$droptempsql = 'DROP TABLE '. $tmpname;
				sqlite_query($this->link, $droptempsql);
				/* end block */


				$createnewtableSQL	= 'CREATE '.substr(trim(preg_replace("'". $tmpname ."'", $table, $createtesttableSQL, 1)), 17);
				$newcolumns			= '';
				$oldcolumns			= '';

				reset($newcols);

				while(list($key, $val) = each($newcols)) {
					$newcolumns		.= iif($newcolumns, ', ', '') . $val;
					$oldcolumns		.= iif($oldcolumns, ', ', '') . $key;
				}

				$copytonewsql		= 'INSERT INTO '. $table .'('. $newcolumns .') SELECT '. $oldcolumns .' FROM '. $tmpname;
				
				/**
				 * Use a transaction here so that if one query fails, they all fail
				 */
				
				/* Begin the transaction */
				$this->executeUpdate("BEGIN TRANSACTION");
				
				/* Create our temporary table */
				$this->executeUpdate($createtemptableSQL);

				/* Copy the data to the temporary table */
				$this->executeUpdate($copytotempsql);

				/* Drop the table that we are modifying */
				$this->executeUpdate($dropoldsql);
				
				/* Recreate that original table with the column added/changed/droped */
				$this->executeUpdate($createnewtableSQL);

				/* Copy the data from our temporary table to our new table */
				$this->executeUpdate($copytonewsql);

				/* Drop our temporary table */
				$this->executeUpdate($droptempsql);
				
				/* Finish the transaction */
				$this->executeUpdate("COMMIT");

			} else {
				error::pitch(new FAError('Non-existant table: '. $table, __FILE__, __LINE__));
				return FALSE;
			}

			return true;
		}
	}
}

?>