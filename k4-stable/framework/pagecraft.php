<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     pagecraft.php
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

define('PC_ROOT',		dirname(__FILE__));
define('PC_APP_DIR',	dirname($_SERVER['SCRIPT_FILENAME']));
define('PC_CONFIG_DIR',	PC_ROOT . '/config');


if (@$_ENV['OS'] == 'Windows_NT') {
	define('WIN32', true);
	define('OS_ENDL', "\r\n");
}


function __autoload($class) {
	$class	= strtolower($class);

	if (isset($GLOBALS['lazy_load'][$class])) {
		include $GLOBALS['lazy_load'][$class];
	}
}


//This file muse be the first file included
include PC_ROOT . '/core/functions.inc.php';
include PC_ROOT . '/core/exceptions.inc.php';

include PC_ROOT . '/core/core.defs.php';
include PC_ROOT . '/db/db.defs.php';
include PC_ROOT . '/template/template.defs.php';

?>