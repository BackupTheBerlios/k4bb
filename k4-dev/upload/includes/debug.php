<?php
/**
* k4 Bulletin Board, debug.php
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
* @version $Id: debug.php,v 1.1 2005/04/20 02:54:45 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

function debug_header($filename) {
	?>
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
	<html>
	<head>
	<title>k4 Query Debugger</title>
	<meta name="Generator" content="k4 Bulletin Board">
	<meta name="Author" content="">
	<meta name="Keywords" content="">
	<meta name="Description" content="">
	<style type="text/css">
		body {
			background-color: #F7F7F7;
			margin: 0px;
			padding: 20px;
			font-family: Arial, Helvetica, Sans-Serif;
			font-size: 12px;
		}
		.debug_content {
			border: 1px solid #363636;
			background-color: #FFFAC1;
			padding: 15px;
		}
		.debug_item {
			border: 1px dotted #D88900;
			background-color: #C8FFC1;
			padding: 5px;
		}
		.debug_query {
			border: 1px solid #999999;
			background-color: #F6F6F6;
			color: #8C8C8C;
			font-size: 11px;
			overflow: auto;
			width: 99%;
		}
		.debug_results {
			overflow: auto;
			width: 99%;
		}
		.debug_file {
			
			border-top: 1px dotted #D88900;
			border-left: 1px dotted #D88900;
			border-right: 1px dotted #D88900;
			border-bottom: 1px solid #C8FFC1;
			background-color: #C8FFC1;
			padding: 3px;
			font-weight: bold;
		}
	</style>
	</head>
	<body>
	<div class="debug_content">
		<h2>SQL Queries for: <?php echo $filename; ?></h2>
	<?php
}
function debug_item($backtrace, $query, &$results) {
	//print_r($backtrace); exit;
	?>
	<br />
	<span class="debug_file">
		<?php echo substr(str_replace(FORUM_BASE_DIR, '', $backtrace['file']), 1); ?>
		(Line <?php echo $backtrace['line']; ?>)
	</span>
	<div class="debug_item">
		<div class="debug_query"><?php echo $query; ?></div>
		<br />
		<fieldset>
			<legend><strong>Results</strong></legend>
			<div class="debug_results">
				<?php echo format_results($results); ?>
			</div>
		</fieldset>
	</div>
	<?php
}
function debug_footer() {
	?>
	</div>

	</body>
	</html>
	<?php
}
function format_results(&$results) {
	
	$result_str		= '';

	/* Was this a query? */
	if(is_a($results, 'FADBResult')) {
		$result_str	.= '<strong>Number of Rows Returned:</strong> '. $results->numrows();
	} else {
		
		if(is_array($results)) {
			ob_start();
			print_r($results);
			$array = ob_get_contents();
			ob_end_clean();

			$result_str .= '<strong>Array Returned:</strong><br />'. nl2br($array);
		} else if(is_numeric($results)) {
			$result_str	.= '<strong>Integer Returned:</strong> '. $results;
		} else if(is_string($results)) {
			$result_str	.= '<strong>String Returned:</strong> '. $results;
		} else if(is_resource($results)) {
			
			ob_start();
			var_dump($results);
			$array = ob_get_contents();
			ob_end_clean();

			$result_str .= '<strong>Resource Returned:</strong><br />'. nl2br($array);
		}
	}

	return $result_str;
}

function set_debug_item($query, &$result) {
	global $_DEBUGITEMS;
	
	$backtrace		= debug_backtrace();
	$backtrace		= $backtrace[count($backtrace)-1];

	$_DEBUGITEMS[] = array('backtrace' => $backtrace, 'query' => $query, 'result' => $result);

	$GLOBALS['_DEBUGITEMS'] = $_DEBUGITEMS;

	return TRUE;
}

function debug_sql() {
	global $_URL, $_DEBUGITEMS;

	ob_start();
	
	echo debug_header($_URL->file);
	
	foreach($_DEBUGITEMS as $debug) {
		echo debug_item($debug['backtrace'], $debug['query'], &$debug['result']);
	}

	echo debug_footer();

	$debug_contents = ob_get_contents();
	ob_end_clean();

	$handle = @fopen(FORUM_BASE_DIR .'/tmp/debug/'. $_URL->file, "w");
	@chmod(FORUM_BASE_DIR .'/tmp/debug/'. $_URL->file, 0777);
	@fwrite($handle, $debug_contents);
	@fclose($handle);
}

?>