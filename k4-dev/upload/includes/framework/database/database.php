<?php
/**
* k4 Bulletin Board, database.php
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
* @version $Id: database.php,v 1.2 2005/04/13 02:53:48 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

define('DBA_BASE_DIR', dirname(__FILE__));

class Database {
	function &open($info) {
		static $connections = array();

		$name = serialize($info);

		if (isset($connections[$name]))
			return $connections[$name];

		if (!isset($info['driver']))
			return Error::pitch(new FAError("No database driver specified.", __FILE__, __LINE__));

		$driver = DBA_BASE_DIR."/drivers/{$info['driver']}.php";
		$class = "{$info['driver']}Connection";

		if (!is_readable($driver))
			return Error::pitch(new FAError("Driver does not exist.", __FILE__, __LINE__));

		require_once $driver;

		if (!class_exists($class))
			return Error::pitch(new FAError("Driver class does not exist or is misnamed.", __FILE__, __LINE__));

		$dba = &new $class();

		if (!is_a($dba, 'FADBConnection'))
			return Error::pitch(new FAError("Driver class does not extend FADBConnection.", __FILE__, __LINE__));

		// Error is thrown in the constructor (hopefully)
		$dba->connect($info);

		$connections[$name] = $dba;

		return $dba;
	}
}

class FADBConnection {
	function connect($info) {}
	//function
}

class FADBResult extends FAIterator {
	function get($column) {
		if (isset($this->current[$column]))
			return $this->current[$column];
	}

	function getDate($column, $format = '%x') {
		if (isset($this->current[$column]))
			return strftime($format, $this->current[$column]);
	}

	function getFloat($column) {
		if (isset($this->current[$column]))
			return (float)$this->current[$column];
	}

	function getInt($column) {
		if (isset($this->current[$column]))
			return (int)$this->current[$column];
	}

	function getString($column) {
		if (isset($this->current[$column]))
			return (string)$this->current[$column];
	}

	function getTime($column, $format = '%X') {
		if (isset($this->current[$column]))
			return strftime($format, $this->current[$column]);
	}

	function getTimestamp($column, $format = 'Y-m-d H:i:s') {
		if (isset($this->current[$column]))
			return date($format, $this->current[$column]);
	}
}

class FADBStatement {
	var $db;
	var $params = 0;
	var $vars;
	var $stmt;

	function FADBStatement($stmt, &$db) {
		$this->stmt = preg_replace('/(\?)/e', "\$this->stmtReplace();", $stmt);
		$this->db = &$db;
	}

	function &executeQuery($mode = DBA_ASSOC) {
		return $this->db->executeQuery($this->getSql(), $mode);
	}

	function executeUpdate($mode = DBA_ASSOC) {
		return $this->db->executeUpdate($this->getSql(), $mode);
	}

	function stmtReplace() {
		$this->params++;

		return "{\$vars[{$this->params}]}";
	}

	function setFloat($n, $value) {
		$this->vars[$n] = floatval($value);
	}

	function setInt($n, $value) {
		$this->vars[$n] = intval($value);
	}

	function setNull($n) {
		$this->vars[$n] = 'NULL';
	}

	function setString($n, $value) {
		$this->vars[$n] = "'".$this->db->quote($value)."'";
	}

	function getSql() {
		$vars = $this->vars;

		eval("\$stmt = \"{$this->stmt}\";");

		return $stmt;
	}

	function __toString() {
		return $this->getSql();
	}
}

?>
