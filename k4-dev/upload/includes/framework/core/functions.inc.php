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
* @version $Id: functions.inc.php,v 1.1 2005/04/05 03:21:02 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

/* Format a timestamp according to the user's timezone settings */
function bbtime($timestamp = FALSE) {
	
	if(!$timestamp)
		$timestamp = time();

	$session		= &Globals::getGlobal('session');
	$user			= &Globals::getGlobal('user');

	if(is_a($session['user'], 'Member'))
		return $timestamp + ($user['timezone'] * 3600);
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

   // $car -> list acceptable words
   $car = "0123456789.abcdefghijklmnopqrstuvwxyz_@-";
   // $ext -> list extension domain words
   $ext = "abcdefghijklmnopqrstuvwxyz";

   /**
   * if you not use return(), is necesary to put elseif()
   */

	if(ereg_words($car, $mail)) 
		return "01"; // contain invalid caracter(s)
	
	$expMail = explode("@", $mail);
	
	if(count($expMail)==1) 
		return "02"; // invalid format
	
	if(count($expMail)>2) {
		return "03"; // contain multi @ caracters
	} else {
		if(empty($expMail[0])) 
			return "04"; // begin at @ is empty
		if(strlen($expMail[1])< 3) 
			return "05"; // after @ invalid format
		$expSep = explode(".", $expMail[1]);
		if(count($expSep)==1) {
			return "06"; // invalid format domain host
		} else {
			if(empty($expSep[count($expSep)-2])) 
				return "07"; // domain name is empty
			if(strlen($expSep[count($expSep)-1])<2 || strlen($expSep[count($expSep)-1])>4) 
				return "08"; // invalid extension domain
			if(ereg_words($ext, $expSep[count($expSep)-1])) 
				return "09"; // extension domain contain invalid caracter(s)
		}
	}

	return $mail;

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

function thread_image($thread, $session) {
	global $settings;

	$dba = DBA::Open();
	// set with a dot or not
	if(@$session->info['name'] == $thread['poster_name'])
		$extra = 'dot_';
	else
		$extra = NULL;
	
	$EXT = 'gif';
	
	$lock = intval(@$thread['row_locked']) == 1 ? 'lock' : NULL;
	
	//$seen = $dba->GetRow("SELECT * FROM ". SESSIONS ." WHERE name = '". @$session->info['name'] ."' AND name != 'Guest'");
	$lastactive = $session->last_active;
	//$lastactive = $session->info['seen'];
	
	if(@$thread['row_status'] == 2) { // announcement
		if($thread['last_reply'] >= $lastactive)
			$image = 'Images/'. $settings['imageset'] .'/Icons/Status/newsticky.'.$EXT;
		else
			$image = 'Images/'. $settings['imageset'] .'/Icons/Status/sticky.'.$EXT;
	
	} else if(@$thread['row_status'] == 3) { // announcement
		if($thread['last_reply'] >= $lastactive)
			$image = 'Images/'. $settings['imageset'] .'/Icons/Status/newannounce.'.$EXT;
		else
			$image = 'Images/'. $settings['imageset'] .'/Icons/Status/announce.'.$EXT;
	
	} else if(@$thread['poll'] == 1) { // poll
		
		$image = 'Images/'. $settings['imageset'] .'/Icons/Status/poll.'.$EXT; // hot topic, new posts
	
	} else if($thread['views'] >= 300 || intval($thread['num_children']) >= 30) {
		
		if($thread['last_reply'] >= $lastactive)
			$image = 'Images/'. $settings['imageset'] .'/Icons/Status/'.$extra.'newhot'.$lock.'folder.'.$EXT; // hot topic, new posts
		else
			$image = 'Images/'. $settings['imageset'] .'/Icons/Status/'.$extra.'hot'.$lock.'folder.'.$EXT; // hot topic, no new posts
	
	} else if($thread['views'] < 300 || $thread['num_posts'] < 30) {
		
		if($thread['last_reply']>$lastactive)
			$image = 'Images/'. $settings['imageset'] .'/Icons/Status/'.$extra.'new'.$lock.'folder.'.$EXT; // topic, new posts
		elseif($thread['last_reply'] < $lastactive || empty($seen))
			$image = 'Images/'. $settings['imageset'] .'/Icons/Status/'.$extra.$lock.'folder.'.$EXT; // topic, no new posts
		else
			$image = 'Images/'. $settings['imageset'] .'/Icons/Status/'.$extra.$lock.'folder.'.$EXT; // topic, no new posts
	
	} else {
		$image = 'Images/'. $settings['imageset'] .'/Icons/Status/'.$extra.'new'.$lock.'folder.'.$EXT; // topic, no new posts
	}
	
	return $image;
}
?>