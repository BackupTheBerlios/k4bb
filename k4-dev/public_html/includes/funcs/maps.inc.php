<?php
/**
* k4 Bulletin Board, maps.inc.php
*
* Copyright (c) 2004, Peter Goodman
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
* @version $Id: maps.inc.php,v 1.1 2005/04/05 02:32:36 necrotic Exp $
* @package k42
*/

function getMaps($varname, $user_id, $perms, $dba) {

	$maps	= array();
	
	$query	= "SELECT * FROM ". MAPS ." WHERE row_left <= (SELECT row_left FROM ". MAPS ." WHERE varname = '$varname') AND row_right >= (SELECT row_right FROM ". MAPS ." WHERE varname = '$varname') OR user_id = $user_id OR is_global = 1";
	$query	.= " ";
	
	$result = $dba->executeQuery($query);

	while($result->next()) {
		$val = $result->current();

		if($val['varname'] != '') {
			if($val['forum_id'] != 0)
				$maps['forums'][$val['forum_id']][$val['varname']] = $val;
			else if($val['group_id'] != 0)
				$maps['groups'][$val['group_id']][$val['varname']] = $val;
			else if($val['user_id'] != 0)
				$maps['users'][$val['user_id']][$val['varname']] = $val;
			else if($val['category_id'] != 0)
				$maps['categories'][$val['category_id']][$val['varname']] = $val;
			else
				$maps[$val['varname']] = $val;
		} else {
			$maps['global'] = $val;
		}
	}

	return $maps;
}

?>