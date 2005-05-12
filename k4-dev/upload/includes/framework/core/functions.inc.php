<?php
/**
* k4 Bulletin Board, functions.inc.php
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
* @author Geoffrey Goodman
* @version $Id: functions.inc.php,v 1.6 2005/05/12 01:35:33 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

/**
 * Format a custom profile field
 */
function format_profilefield($data) {
	global $lang;

	switch($data['inputtype']) {
		case 'text': {
			
			$input		= '<input type="text" class="inputbox" name="'. $data['name'] .'" id="'. $data['name'] .'" value="'. $data['default_value'] .'" size="'. $data['display_size'] .'" maxlength="'. $data['user_maxlength'] .'" />';
			
			if($data['is_required'] == 1)
				$input .= '<script type="text/javascript">addVerification(\''. $data['name'] .'\', \'.+\', \''. $data['name'] .'_error\', \'inputfailed\');</script><div id="'. $data['name'] .'_error" style="display: none;">'. sprintf($lang['L_FILLINTHISFIELD'], $data['title']) .'</div>';

			break;
		}
		case 'textarea': {
			
			$input		= '<textarea name="'. $data['name'] .'" id="'. $data['name'] .'" cols="'. $data['display_size'] .'" rows="'. $data['display_rows'] .'" class="inputbox">'. $data['default_value'] .'</textarea>';

			if($data['is_required'] == 1)
				$input .= '<script type="text/javascript">addVerification(\''. $data['name'] .'\', \'(\n|\r\n|\r|.)+\', \''. $data['name'] .'_error\', \'inputfailed\');</script><div id="'. $data['name'] .'_error" style="display: none;">'. sprintf($lang['L_FILLINTHISFIELD'], $data['title']) .'</div>';

			break;
		}
		case 'select': {
			
			$input		= '<select name="'. $data['name'] .'" id="'. $data['name'] .'">';
			
			$options	= @unserialize($data['inputoptions']);

			if(is_array($options) && !empty($empty)) {
				foreach($options as $option)
					$input .= '<option value="'. $option .'">'. $option .'</option>';
			}

			$input		.= '</select>';

			break;
		}
		case 'multiselect': {
			
			$input		= '<select name="'. $data['name'] .'[]" id="'. $data['name'] .'" multiple="multiple" '. iif(intval($data['display_rows']) > 0, 'size="'. intval($data['display_rows']) .'"', '') .'>';
			
			$options	= @unserialize($data['inputoptions']);

			if(is_array($options) && !empty($empty)) {
				foreach($options as $option)
					$input .= '<option value="'. $option .'">'. $option .'</option>';
			}

			$input		.= '</select>';

			break;
		}
		case 'radio': {
			
			$options	= @unserialize($data['inputoptions']);
			
			$input		= '';
			
			if(is_array($options) && !empty($empty)) {
				
				$i = 0;
				foreach($options as $option) {
					$input .= '<label for="'. $data['name'] . $i .'"><input type="radio" name="'. $data['name'] .'" id="'. $data['name'] . $i .'" value="'. $option .'" />&nbsp;&nbsp;'. $option .'</label>';
					$i++;
				}
			}

			break;
		}
		case 'check': {
			
			$options	= @unserialize($data['inputoptions']);
			
			$input		= '';
			
			if(is_array($options) && !empty($empty)) {
				
				$i = 0;
				foreach($options as $option) {
					$input .= '<label for="'. $data['name'] . $i .'"><input type="checkbox" name="'. $data['name'] .'[]" id="'. $data['name'] . $i .'" value="'. $option .'" />&nbsp;&nbsp;'. $option .'</label>';
					$i++;
				}
			}

			break;
		}
	}

	if(isset($input))
		return $input;
}

/* A quick way to do a conditional statement */
function iif($argument, $true_val, $false_val) {
	if($argument) {
		return $true_val;
	} else {
		return $false_val;
	}
}

/* Format a timestamp according to the user's timezone settings */
function bbtime($timestamp = FALSE) {
	
	if(!$timestamp)
		$timestamp = time();

	if(is_a($_SESSION['user'], 'Member'))
		return $timestamp + ($_SESSION['user']->info['timezone'] * 3600);
	else
		return $timestamp;
}

/* Function to find out what the end of today would be */
function bbendofday() {
	$timenow		= bbtime();

	$hour_now		= (23 - date("G", $timenow)) * 3600;
	$min_now		= (59 - date("i", $timenow)) * 60;
	$sec_now		= (59 - date("s", $timenow));

	$endofday		= $hour_now + $min_now + $sec_now;

	return time() + $endofday;
}

/* I got the following two functions, to check an email address from php.net comments,
* But more importanly, from 'expert@dotgeek.org' Thanks a lot :) */

function ereg_words($car, $data){
   $err = false;
   $cnt = strlen($data);
   $len = strlen($car);
   for($i=0;$i<$cnt;$i++){
       $errm = false;
       $chrm = strtolower($data{$i});
       for($k=0;$k<$len;$k++) if($car{$k}==$chrm) $errm = true;
       if(!$errm) $err = true;
   }
   return $err;
}

/* A function to _really_ validate an email */
function check_mail($mail){
	
	$mail	= strtolower($mail);

	// $car -> list acceptable words
	$car = "0123456789.abcdefghijklmnopqrstuvwxyz_@-";
	// $ext -> list extension domain words
	$ext = "abcdefghijklmnopqrstuvwxyz";

	/**
	* if you not use return(), is necesary to put elseif()
	*/

	if(ereg_words($car, $mail)) 
		return FALSE; // contain invalid caracter(s)
	
	$expMail = explode("@", $mail);
	
	if(count($expMail)==1) 
		return FALSE; // invalid format
	
	if(count($expMail)>2) {
		return FALSE; // contain multi @ caracters
	} else {
		if(empty($expMail[0])) 
			return FALSE; // begin at @ is empty
		if(strlen($expMail[1])< 3) 
			return FALSE; // after @ invalid format
		
		$expSep = explode(".", $expMail[1]);
		
		if(count($expSep)==1) {
			return FALSE; // invalid format domain host
		} else {
			if(empty($expSep[count($expSep)-2])) 
				return FALSE; // domain name is empty
			if(strlen($expSep[count($expSep)-1])<2 || strlen($expSep[count($expSep)-1])>4) 
				return FALSE; // invalid extension domain
			if(ereg_words($ext, $expSep[count($expSep)-1])) 
				return FALSE; // extension domain contain invalid caracter(s)
		}
	}

	return TRUE;

}

/* Append a '/' onto the end of a string */
function append_slash($in) {
	if (strpos("\\/", substr($in, -1)) === false) {
		$in	.= '/';
	}

	return $in;
}

/* Check if an iterator has array access (PHP5) */
function array_access($in) {
	if (is_array($in) || is_a($in, 'ArrayAccess') || is_a($in, 'ArrayObject')) return true;
}

/* Check if a class is defined, either already, or in the lazy_load files */
function class_defined($class) {
	if (class_exists($class)) return TRUE;
	if (isset($GLOBALS['lazy_load'][strtolower($class)])) return TRUE;

	return FALSE;
}

/* Define a class to be loaded by the lazy_load */
function define_class($class, $path) {
	assert('is_readable($path)');

	$GLOBALS['lazy_load'][strtolower($class)]	= $path;
}

/* Get/Check if an object is/has an iterator (PHP5) */
function get_iterator($in) {
	if (is_a($in, 'Iterator')) return $in;
	if (is_a($in, 'IteratorAggregate')) return $in->GetIterator();
	if (is_array($in)) return new ArrayIterator($in);
}

/* Function to get the setting from the $_CONFIG array */
function get_setting($section, $key) {
	global $_CONFIG;
	
	return isset($_CONFIG[$section][$key]) ? $_CONFIG[$section][$key] : FALSE;
}

/* Check if an iterator is traversable (PHP5) */
function is_traversable($in) {
	if (is_array($in) || is_a($in, 'Traversable')) return TRUE;
}

function relative_time($timestamp, $format = 'g:iA') {
	$time	= mktime(0, 0, 0);
	$delta	= time() - $timestamp;

	if ($timestamp < $time - 86400) {
		return date("F j, Y, g:i a", $timestamp);
	}

	if ($delta > 86400 && $timestamp < $time) {
		return "Yesterday at " . date("g:i a", $timestamp);
	}

	$string	= '';

	if ($delta > 7200)
		$string	.= floor($delta / 3600) . " hours, ";

	else if ($delta > 3660)
		$string	.= "1 hour, ";

	else if ($delta >= 3600)
		$string	.= "1 hour ";

	$delta	%= 3600;

	if ($delta > 60)
		$string	.= floor($delta / 60) . " minutes ";
	else
		$string .= $delta . " seconds ";

	return "$string ago";
}

function require_class($class) {
	if (!class_exists($class) && class_defined($class))
		require $GLOBALS['lazy_load'][strtolower($class)];
}

function compile_error($message, $file, $line) {
	error::reset();
	error::pitch(new FAError($message, $file, $line));
	critical_error();
}

function critical_error() {
	$error	= &error::grab();

	$logo	= file_exists(FORUM_BASE_DIR .'/Images/k4.gif') ? 'Images/k4.gif' : '';

	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
	<title>k4 v2.0 - Critical Error - Powered by k4 BB</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	<meta name="generator" content="k4 Bulletin Board 2.0" />
	<meta name="robots" content="all" />
	<meta name="revisit-after" content="1 Days" />

	<meta http-equiv="Expires" content="1" />
	<meta name="description" content="k4 v2.0 - Powered by k4 Bulletin Board" />
	<meta name="keywords" content="k4, bb, bulletin, board, bulletin board, forum, k4st, forums, message, board, message board" />
	<meta name="author" content="k4 Bulletin Board" />
	<meta name="distribution" content="GLOBAL" />
	<meta name="rating" content="general" />
	<link rel="icon" href="favicon.ico" type="image/x-icon" />
	<style type="text/css">
	body {
		background-color: #FFFFFF;
		padding: 0px;
		margin: 0px;
	}
	a {
		font-family: Geneva, Arial, Helvetica, Sans-Serif;
		font-size: 12px;
		color: #000000;
		text-decoration: none;
	}
	h2 {
		font-family: Geneva, Arial, Helvetica, Sans-Serif;
		color: #045975;
	}
	.error_box {
		text-align: left;
		border: 1px solid #666666;
		background-color: #f7f7f7;
		color: #000000;
		font-family: Geneva, Arial, Helvetica, Sans-Serif;
		font-size: 12px;
		width: 500px;
		padding: 10px;
	}
	.redtext {
		color: #FF0000;
	}
	.greentext {
		color: #009900;
		font-weight: bold;
	}
	</style>
</head>
<body>
<div align="center">
	<?php
	if($logo != '')
		echo '<img src="'. $logo .'" alt="k4 Bulletin Board" border="0" />';
	else
		echo '<h2>k4 Bulletin Board</h2>';
	?>
	<div class="error_box">
		<span class="redtext">The following critical error occured:</span>
		<br /><br />
		<span class="greentext"><?php echo $error->message; ?></span>
		<br /><br />
		Line: <strong><?php echo $error->line; ?></strong><br />
		File: <strong><?php echo $error->filename; ?></strong>
	</div>
</div>
<br /><br />
<div align="center">
	<span style="width:150px;color:#666666;border-top:1px dashed #666666;padding-top:2px;margin:4px;" class="smalltext">
		[ <a href="http://www.k4bb.org" title="k4 Bulletin Board" target="_blank">Powered By: k4 Bulletin Board</a> ]
	</span>
	<br />
</div>
</body>
</html>
</div>
	<?php
	
	exit;
}

?>