<?php

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