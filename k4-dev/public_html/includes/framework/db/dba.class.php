<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     dba.class.php
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

class DBA {
	//Return a numeric array
	const NUM	= 1;

	//Return an associative array
	const ASSOC	= 2;

	//Return an array containing the the value of the first column of each row
	const FLAT	= 4;

	//Return an array mapping the value of the first column to the value of
	//the second column of each row
	const PAIR	= 8;

	const E_INVALID_SERVER	= 'Unable to connect to database server';
	const E_INVALID_DB		= 'Unable to select database';
	const E_INVALID_QUERY	= 'Invalid query';
	const E_INVALID_TYPE	= 'Invalid result type';

	private $num_queries = 0;

	static final public function Open($name = '') {
		static $databases	= NULL;
		static $connections	= NULL;
		
		if ($name == '')
			$name	= get_setting('application', 'dba_name') == '' ? get_setting('', 'database', FALSE) : get_setting('application', 'dba_name');

		//First build a list of databases if it doesn't exist
		if ($databases == NULL) {
			$databases		= array();
			$connections	= array();

			$ini_file	= PC_CONFIG_DIR . '/databases.ini.php';

			if (!is_readable($ini_file))
				throw new Exception('todo');

			$databases	= parse_ini_file($ini_file, true);
		}

		//Second, check for an existing connection
		if (isset($connections[$name]))
			return $connections[$name];
		
		//echo "Database name: $name<br />" . OS_ENDL;

		if (!isset($databases[$name]))
			throw new Exception('That Database doesn\'t exist. Check in pagecraft.ini AND/OR databases.ini to see if you have properly configured PageCraft.');

		//Finally, attempt to create a new connection 
		if (!isset($databases[$name]['type']))
			throw new Exception('todo');

		$class				= $databases[$name]['type'] . '_Connection';
		$connections[$name]	= new $class($databases[$name]);

		return $connections[$name];
	}
}

?>