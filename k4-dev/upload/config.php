<?php
/**
* k4 Bulletin Board, config.php
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
* @author Peter Goodman
* @version $Id: config.php,v 1.1 2005/04/05 03:10:22 k4st Exp $
* @package k42
*/

$CONFIG['application']['action_var']	= 'act';
$CONFIG['application']['lang']			= 'english';
$CONFIG['application']['dba_name']		= 'k4_forum';

$CONFIG['template']['path']				= dirname(__FILE__) . '/templates';
//$CONFIG['template']['tplfolder']		= 'Descent';
//$CONFIG['template']['imgfolder']		= 'Descent';
$CONFIG['template']['force_compile']	= FALSE;
$CONFIG['template']['ignore_white']		= FALSE;

$CONFIG['ftp']['use_ftp']				= FALSE;
$CONFIG['ftp']['username']				= '';
$CONFIG['ftp']['password']				= '';
$CONFIG['ftp']['server']				= '';

$CONFIG['dba']['driver']				= 'sqlite';
$CONFIG['dba']['database']				= 'k4_forum.sqlite';
$CONFIG['dba']['directory']				= dirname(__FILE__) . '/includes/sqlite';
$CONFIG['dba']['server']				= 'localhost:3306';
$CONFIG['dba']['user']					= '';
$CONFIG['dba']['pass']					= '';

$GLOBALS['_CONFIG']						= &$CONFIG;

?>