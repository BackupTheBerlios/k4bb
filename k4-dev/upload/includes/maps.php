<?php
/**
* k4 Bulletin Board, maps.inc.php
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
* @version $Id: maps.php,v 1.1 2005/04/05 03:18:59 k4st Exp $
* @package k42
*/

function get_maps() {
	
	global $_DBA, $_QUERYPARAMS;

	$maps	= array();
	
	/* Get everything from the maps table, this is only executed once per session */
	$query	= "SELECT ". $_QUERYPARAMS['maps'] ." FROM ". MAPS ." m ";
	//LEFT JOIN ". MAPS ." mm ON M.id = mm.id WHERE (M.row_left <= mm.row_left AND M.row_right >= mm.row_right) OR (M.user_id = $user_id) OR (M.is_global = 1 AND mm.varname = '$varname')"

	$result = &$_DBA->executeQuery($query);

	while($result->next()) {
		$val = $result->current();

		if($val['varname'] != '') {
			if($val['forum_id'] != 0) {
				if(!isset($maps['forums'][$val['forum_id']]) && ($val['varname'] == 'forum'. $val['forum_id']) ) {
					$maps['forums'][$val['forum_id']] = $val;
				} else {
					$maps['forums'][$val['forum_id']][$val['varname']] = $val;
				}
			} else if($val['group_id'] != 0) {
				if(!isset($maps['groups'][$val['group_id']]) && ($val['varname'] == 'group'. $val['group_id']) ) {
					$maps['groups'][$val['group_id']] = $val;
				} else {
					$maps['groups'][$val['group_id']][$val['varname']] = $val;
				}
			} else if($val['user_id'] != 0) {
				if(!isset($maps['users'][$val['user_id']]) && ($val['varname'] == 'user'. $val['user_id']) ) {
					$maps['users'][$val['user_id']] = $val;
				} else {
					$maps['users'][$val['user_id']][$val['varname']] = $val;
				}
			} else if($val['category_id'] != 0) {
				if(!isset($maps['categories'][$val['category_id']]) && ($val['varname'] == 'category'. $val['category_id']) ) {
					$maps['categories'][$val['category_id']] = $val;
				} else {
					$maps['categories'][$val['category_id']][$val['varname']] = $val;
				}
			} else {
				$maps[$val['varname']] = $val;
			}
		} else {
			$maps['global'] = $val;
		}
	}

	return $maps;
}

?>