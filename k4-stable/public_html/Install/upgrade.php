<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     index.php
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

include '../install.lock.php';
require '../'. PC_REL_URL;
require '../tables.inc.php';
require '../classes.php';
require '../permissions.inc.php';

require '../lang/'. get_setting('config', 'lang') .'/lang.php';
require '../settings.inc.php';

/*
---------------------------------------------------
			do the PAGE HEADER
---------------------------------------------------
*/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>k4 Bulletin Board Installation</title>
<style type="text/css">
body {
	background-color: #E1E1E2;
	padding: 0px;
	margin: 0px;
	font-family: Arial, Helvetica, Sans-Serif;
}
h2 {
	color: #FF0000;
	text-align: center;
	margin-top: 50px;
	text-transform: uppercase;
}
h3 {
	color: #003366;
	width: 760px;
	background-color: #FFFFFF;
	padding: 10px;
	border-bottom: 2px solid #336699;
	margin: 0px;
	background-image:url('k4.gif');
	background-position: left bottom;
	background-repeat: no-repeat;
	height: 64px;
	text-align: right;
}
.waittext {
	color: #FF0000;
	font-size: 16px;
	font-weight: bold;
}
.tcat {
	background-color: #6C6C6C;
	color: #FFFFFF;
	text-align: center;
	font-weight: bold;
}
.tcat td { padding: 10px; }
.thead {
	padding: 10px;
}
.panel {
	padding: 10px;
}
.minitext {
	font-size: 11px;
}
.passed {
	font-weight: bold;
	color: green;
}
.failed {
	font-weight: bold;
	color: #FF0000;
}
#page_body {
	width: 770px;
	text-align: left;
	background-color: #FFFFFF;
	font-size: 12px;
	padding: 5px;
}
table {
	width: 770px;
}
</style>
</head>
<body>
<div align="center">
<h3>Welcome to the k4 Bulletin Board Upgrade Program</h1>
<div id="page_body">
<?php

// SELECT name FROM sqlite_master WHERE type='table'
// SELECT ALL * FROM ".$table["name"]." LIMIT 1

function set_dir() {
	?>
	Path (from here) to your old k4 SQLite 
	<?php
}

?>
</div>
</body>
</html>