<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     db.defs.php
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

define_class('DBA', dirname(__FILE__) . '/dba.class.php');

define_class('DBA_Connection', dirname(__FILE__) . '/interfaces.inc.php');
define_class('DBA_Exception', dirname(__FILE__) . '/interfaces.inc.php');
define_class('DBA_Result', dirname(__FILE__) . '/interfaces.inc.php');
define_class('DBA_Iterator', dirname(__FILE__) . '/interfaces.inc.php');

/* MySQL abstraction */
define_class('MySql_Connection', dirname(__FILE__) . '/mysql.driver.php');
/* SQLite abstraction */
define_class('SQLite_Connection', dirname(__FILE__) . '/sqlite.driver.php');
/* PostgreSQL abstraction */
define_class('pgSQL_Connection', dirname(__FILE__) . '/pgsql.driver.php');

?>