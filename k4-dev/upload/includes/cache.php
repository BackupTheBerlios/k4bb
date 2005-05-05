<?php
/**
* k4 Bulletin Board, cache.php
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
* @version $Id: cache.php,v 1.6 2005/05/05 21:35:48 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

/**
 * MAPS Caching function
 */

function get_cached_maps() {
	
	if(!isset($_SESSION['bbcache']))
		$_SESSION['bbcache'] = array();

	if(!isset($_SESSION['bbcache']['maps'])) {
		$_SESSION['bbcache']['maps_time']	= time();
		$_SESSION['bbcache']['maps']		= get_maps();
	} else {

		/* If 10 minutes has passed since the last update of the map's */
		if((time() - $_SESSION['bbcache']['maps_time']) > 600) {
			$_SESSION['bbcache']['maps_time']	= time();
			$_SESSION['bbcache']['maps']		= get_maps();
		}
	}
	
	return $_SESSION['bbcache']['maps'];
}

/**
 * Forums Caching functions
 */

function get_cached_forum($id) {
	global $_DBA, $_ALLFORUMS;

	$id														= intval($id);
	
	/* This should _always_ return the forum */
	if(isset($_ALLFORUMS[$id])) {
		
		/* Return the info */
		return $_ALLFORUMS[$id];

	} else {
		
		/* Get the info */
		$result												= $_DBA->getRow("SELECT i.* FROM ". INFO ." i WHERE id = ". $id);

		/* Return the info */
		return $result;
	}
}

function cache_forum($info) {

	if(!isset($_SESSION['bbcache']))
		$_SESSION['bbcache'] = array();

	$required	= array('id', 'parent_id', 'row_left', 'row_right', 'row_type', 'row_level', 'row_order', 'name', 'created', 'subforums');
	$data		= array();
	foreach($required as $val) {
		if(isset($info[$val]))
			$data[$val]	= $info[$val];
	}

	$data['subforums']											= intval(@$info['subforums']);
	
	$_SESSION['bbcache']['forums'][$data['id']]					= $data;
	$_SESSION['bbcache']['forums'][$data['id']]['forum_time']	= time();
}

function set_forum_cache_item($name, $val, $id) {
	$_SESSION['bbcache']['forums'][$id][$name] = $val;
}

function isset_forum_cache_item($name, $id) {
	return isset($_SESSION['bbcache']['forums'][$id][$name]);
}

/**
 * Styleset Caching
 */
function get_cached_styleset($styleset, $default_styleset) {

	global $_DBA;
	
	if(!file_exists(FORUM_BASE_DIR .'/tmp/cache/'. $styleset .'.css')) {

		$query			= &$_DBA->prepareStatement("SELECT css.name as name, css.properties as properties FROM ". CSS ." css LEFT JOIN ". STYLES ." styles ON styles.id = css.style_id WHERE styles.name = ? ORDER BY css.name ASC");
		$css			= "/* k4 Bulletin Board v". VERSION ." CSS Generated Style Set :: ". $styleset ." */\r\n\r\n";

		/* Set the user's styleset to the query */
		$query->setString(1, $styleset);
		
		/* Get the result */
		$result			= &$query->executeQuery();
		
		/* If this styleset doesn't exist, use the default one instead */
		if($result->numrows() == 0) {
			
			$styleset	= $default_styleset;

			/* Set the user's styleset to the query */
			$query->setString(1, $default_styleset);
			
			/* Get the result */
			$result		= &$query->executeQuery();
		}
		
		/* Loop through the result iterator */
		while($result->next()) {
			$temp = $result->current();
			$css .= $temp['name'] ." { ". $temp['properties'] ." }\r\n";
		}
		
		$result->freeResult();

		/* Create a cached file for the CSS info */
		$handle = @fopen(FORUM_BASE_DIR .'/tmp/cache/'. $styleset .'.css', "w");
		@chmod(FORUM_BASE_DIR .'/tmp/cache/'. $styleset .'.css', 0777);
		@fwrite($handle, $css);
		@fclose($handle);
	}

	return $styleset;
}

/* Set a temporary session cache */
function bb_setcookie_cache($name, $value, $expire) {
	if(!isset($_SESSION['bbcache']))
		$_SESSION['bbcache'] = array();

	$_SESSION['bbcache']['cookies'][] = array('name' => $name, 'value' => $value, 'expire' => $expire);
}

/* Set a page-context temporary cached cookie item value for topics-only */
function bb_settopic_cache_item($name, $value, $expire) {
	if(!isset($_SESSION['bbcache']))
		$_SESSION['bbcache'] = array();

	$_SESSION['bbcache']['temp_cookies'][] = array('name' => $name, 'value' => $value, 'expire' => $expire);
}


/* Funtion to execute and unset all bbcache cookie items */
function bb_execute_cache() {
	if(isset($_SESSION['bbcache'])) {
		
		/* Cached cookie setting */
		if(isset($_SESSION['bbcache']['cookies'])) {
			for($i = 0; $i < count($_SESSION['bbcache']['cookies']); $i++) {
				
				$temp = $_SESSION['bbcache']['cookies'][$i];

				setcookie($temp['name'], $temp['value'], $temp['expire']);
			}
		}

		/* Clear the bbcache session under 'cookies' */
		$_SESSION['bbcache']['cookies'] = array();
	}
}

/* Funtion to execute and unset all bbcache temp cookie (topic) items */
function bb_execute_topiccache() {
	if(isset($_SESSION['bbcache'])) {
		
		/* Cached cookie setting */
		if(isset($_SESSION['bbcache']['temp_cookies'])) {
			for($i = 0; $i < count($_SESSION['bbcache']['temp_cookies']); $i++) {
				
				$temp = $_SESSION['bbcache']['temp_cookies'][$i];

				@setcookie($temp['name'], $temp['value'], $temp['expire']);
				//bb_setcookie_cache($temp['name'], '', time()-3600);
			}
		}

		/* Clear the bbcache session under 'cookies' */
		$_SESSION['bbcache']['temp_cookies'] = array();
	}
}

/**
 * Cache info from the database in XML-like format,
 * then compile it to a monstrous PHP array
 */
class DBCache {
	function newArray($array) {
		if(is_array($array)) {
			$contents				= "\n\t\t\t\t\t\t\t\t\t\t\t\t\t\tarray(";
			foreach($array as $column => $val) {
				if(is_array($val)) {
					$contents	.= "'". $column ."' => ". DBCache::newArray($val) .", ";
				} else {
					$contents	.= "'". $column ."' => '". htmlentities($val, ENT_QUOTES) ."', ";
				}
			}
			$contents				.= ")";
		} else {
			$contents				= "'". htmlentities($array, ENT_QUOTES) ."', ";
		}

		return $contents;
	}
	function createCache($allinfo, $serialize = FALSE) {
		$contents				= "<?php \nerror_reporting(E_ALL); \n\nif(!defined('IN_K4')) { \n\texit; \n}";
		
		if(!$serialize) {
			
			$contents			.= "\n\n\$cache = " . var_export($allinfo, TRUE) .";";
			

		} else {
			$contents			.= "\n\n\$cache = '" . htmlentities(serialize($allinfo), ENT_QUOTES) ."';";
		}
		$contents				.= "\n?>";
		
		/* Create our file */
		$handle = @fopen(CACHE_FILE, "w");
		@chmod(CACHE_FILE, 0777);
		@fwrite($handle, $contents);
		@fclose($handle);
	}
}

?>