<?php
/**
* k4 Bulletin Board, maps.class.php
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
* @author James Logsdon
* @version $Id: maps.class.php,v 1.1 2005/04/05 03:19:35 k4st Exp $
* @package k42
*/

class AdminMapsGui extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			$maps	= &new MAPSIterator($dba->executeQuery("SELECT * FROM ". MAPS ." WHERE row_level = 1 ORDER BY row_left ASC"));
			$template->setList('maps_list', $maps);
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/maps_tree.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminMapsInherit extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		

		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {

			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setError('content', $template->getVar('L_INVALIDMAPID'));

			$map	= $dba->getRow("SELECT * FROM ". MAPS ." WHERE id = ". intval($request['id']));			
			
			if(!is_array($map) || empty($map))
				return $template->setError('content', $template->getVar('L_INVALIDMAPID'));
			
			if(isset($request['inherit'])) {
				if($request['inherit'] == 'true') {
					
					$dba->executeUpdate("UPDATE ". MAPS ." SET inherit = 1 WHERE id = ". $map['id']);

					$template->setInfo('content', $template->getVar('L_INHERITEDMAPS'), FALSE);
					$template->setRedirect('admin.php?act=permissions_gui', 3);
				} else if($request['inherit'] == 'false') {
					
					$dba->executeUpdate("UPDATE ". MAPS ." SET inherit = 0 WHERE id = ". $map['id']);

					$template->setInfo('content', $template->getVar('L_UNINHERITEDMAPS'), FALSE);
					$template->setRedirect('admin.php?act=permissions_gui', 3);
				} else {
					$template->setError('content', $template->getVar('L_MUSTSETINHERITOPTION'), TRUE);
				}
			} else {
				$template->setError('content', $template->getVar('L_MUSTSETINHERITOPTION'), TRUE);
			}
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminMapsUpdate extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		

		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {

			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setError('content', $template->getVar('L_INVALIDMAPID'));

			$map	= $dba->getRow("SELECT * FROM ". MAPS ." WHERE id = ". intval($request['id']));			
			
			if(!is_array($map) || empty($map))
				return $template->setError('content', $template->getVar('L_INVALIDMAPID'));
			
			$stmt	= &$dba->prepareStatement("UPDATE ". MAPS ." SET can_view=?,can_add=?,can_edit=?,can_del=? WHERE id=? OR (row_left > ? AND row_right < ? AND inherit = 1)");
			
			$stmt->setInt(1, @$request['can_view']);
			$stmt->setInt(2, @$request['can_add']);
			$stmt->setInt(3, @$request['can_edit']);
			$stmt->setInt(4, @$request['can_del']);
			$stmt->setInt(5, $map['id']);
			$stmt->setInt(6, $map['row_left']);
			$stmt->setInt(7, $map['row_right']);

			$stmt->executeUpdate();

			$template->setInfo('content', $template->getVar('L_UPDATEDMAPS'), FALSE);
			$template->setRedirect('admin.php?act=permissions_gui', 3);
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminMapsAddNode extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		

		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {

			if(!isset($request['id']))
				return $template->setError('content', $template->getVar('L_INVALIDMAPID'));

			$map	= $dba->getRow("SELECT * FROM ". MAPS ." WHERE id = ". intval($request['id']));			
			
			if(!is_array($map) || empty($map)) {
				$template->setVar('maps_id', 0);
			} else {
				foreach($map as $key => $val){
					$template->setVar('maps_'. $key, $val);
				}
			}
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/maps_addnew.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminMapsInsertNode extends Event {
	var $dba;
	function getNumOnLevel($row_left, $row_right, $level) {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". MAPS ." WHERE row_left > $row_left AND row_right < $row_right AND row_level = $level");
	}
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		$this->dba		= &$dba;

		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			/**
			 * Error checking on request fields 
			 */
			
			if(!isset($request['parent_id']))
				return $template->setError('content', $template->getVar('L_INVALIDMAPID'));
			
			if(!isset($request['varname']))
				return $template->setError('content', $template->getVar('L_MAPSNEEDVARNAME'));

			if(!isset($request['name']))
				return $template->setError('content', $template->getVar('L_MAPSNEEDNAME'));
			
			/**
			 * Start building info for the queries
			 */

			/* Get the last node to the furthest right in the tree */
			$last_node = $dba->GetRow("SELECT * FROM ". MAPS ." WHERE row_level = 1 ORDER BY row_right DESC LIMIT 1");

			$level = 1;
			
			/* Is this a top level node? */
			if(intval($request['parent_id']) == 0) {
				
				$left			= $last_node['row_right']+1;
				$level			= 1;
				$parent			= array('category_id' => 0, 'forum_id' => 0, 'group_id' => 0, 'user_id' => 0);
			
			/* If we are actually dealing with a parent node */
			} else if(intval($request['parent_id']) > 0) {
				
				/* Get the parent node */
				$parent			= $dba->GetRow("SELECT * FROM ". MAPS ." WHERE id = ". intval($request['parent_id']));
				
				/* Check if the parent node exists */
				if(!is_array($parent) || empty($parent))
					return $template->setError('content', $template->getVar('L_INVALIDMAPID'));
				
				/* Find out how many nodes are on the current level */
				$num_on_level	= $this->getNumOnLevel($parent['row_left'], $parent['row_right'], $parent['row_level']+1);
				
				/* If there are more than 1 nodes on the current level */
				if($num_on_level > 0) {
					$left			= $parent['row_right'];
				} else {
					$left			= $parent['row_left'] + 1;
				}
				
				/* Set this nodes level */
				$level			= $parent['row_level']+1;
			} else {
				return $template->setError('content', $template->getVar('L_INVALIDMAPID'));
			}

			$right = $left+1;
			
			/**
			 * Build the queries
			 */

			/* Prepare the queries */
			$update_a			= &$dba->prepareStatement("UPDATE ". MAPS ." SET row_right = row_right+2 WHERE row_left < ? AND row_right >= ?");
			$update_b			= &$dba->prepareStatement("UPDATE ". MAPS ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= ?");
			$insert				= &$dba->prepareStatement("INSERT INTO ". MAPS ." (row_left,row_right,row_level,name,varname,category_id,forum_id,user_id,group_id,can_view,can_add,can_edit,can_del,inherit) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			
			/* Set the insert variables needed */
			$update_a->setInt(1, $left);
			$update_a->setInt(2, $left);
			$update_b->setInt(1, $left);

			/* Set the inserts for adding the actual node */
			$insert->setInt(1, $left);
			$insert->setInt(2, $right);
			$insert->setInt(3, $level);
			$insert->setString(4, $request['name']);
			$insert->setString(5, $request['varname']);
			$insert->setInt(6, $parent['category_id']);
			$insert->setInt(7, $parent['forum_id']);
			$insert->setInt(8, $parent['user_id']);
			$insert->setInt(9, $parent['group_id']);
			$insert->setInt(10, @$request['can_view']);
			$insert->setInt(11, @$request['can_add']);
			$insert->setInt(12, @$request['can_edit']);
			$insert->setInt(13, @$request['can_del']);
			$insert->setInt(14, @$request['inherit']);
			

			/**
			 * Execute the queries
			 */

			/* Execute the queries */
			$update_a->executeUpdate();
			$update_b->executeUpdate();
			$insert->executeUpdate();
			
			/* Redirect the user */
			$template->setInfo('content', $template->getVar('L_ADDEDMAPSITEM'), FALSE);
			$template->setRedirect('admin.php?act=permissions_gui', 3);
			
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminMapsRemoveNode extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		

		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			/* Error check */
			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setError('content', $template->getVar('L_INVALIDMAPID'));

			$map	= $dba->getRow("SELECT * FROM ". MAPS ." WHERE id = ". intval($request['id']));			
			
			/* Error check */
			if(!is_array($map) || empty($map))
				return $template->setError('content', $template->getVar('L_INVALIDMAPID'));
			
			$val = (($map['row_right'] - $map['row_left'] - 1) / 2) + 2;
			$val = $val % 2 == 0 ? $val : $val+1; // Make it an even number
			
			/**
			 * Create the Queries
			 */
			$delete		= &$dba->prepareStatement("DELETE FROM ". MAPS ." WHERE row_left >= ? AND row_right <= ?");
			$update_a	= &$dba->prepareStatement("UPDATE ". MAPS ." SET row_right = row_right-? WHERE row_left < ? AND row_right > ?");
			$update_b	= &$dba->prepareStatement("UPDATE ". MAPS ." SET row_left = row_left-?, row_right=row_right-? WHERE row_left > ?");
			
			/**
			 * Populate the queries
			 */
			$delete->setInt(1, $map['row_left']);
			$delete->setInt(2, $map['row_right']);

			$update_a->setInt(1, $val);
			$update_a->setInt(2, $map['row_left']);
			$update_a->setInt(3, $map['row_left']);

			$update_b->setInt(1, $val);
			$update_b->setInt(2, $val);
			$update_b->setInt(3, $map['row_left']);
			
			/**
			 * Execute the queries
			 */
			$delete->executeUpdate();
			$update_a->executeUpdate();
			$update_b->executeUpdate();

			/* Redirect the user */
			$template->setInfo('content', $template->getVar('L_REMOVEDMAPSITEM'), FALSE);
			$template->setRedirect('admin.php?act=permissions_gui', 3);
			
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class MAPSIterator extends FAProxyIterator {
	var $dba;
	function MAPSIterator($data = NULL) {
		global $_DBA;

		$this->dba		= &$_DBA;
		
		parent::FAProxyIterator($data);
	}

	function &current() {
		$temp			= parent::current();
		
		$num_children	= @(($temp['row_right'] - $temp['row_left'] - 1) / 2);
		$temp['level']	= str_repeat('&nbsp;&nbsp;&nbsp;', $temp['row_level']-1);

		$temp['name']	= $temp['inherit'] == 1 ? '<span style="color: green;">'. $temp['name'] .'</span>' : '<span style="color: firebrick;">'. $temp['name'] .'</span>';
		
		//print_r($_COOKIE['mapsgui_menu']); exit;

		if(isset($_COOKIE['mapsgui_'. $temp['id']]) && $_COOKIE['mapsgui_'. $temp['id']] == 'yes' && $temp['row_level'] == 1) {
			$temp['expanded']		= 1;
			$temp['maps_children']	= &new MAPSIterator($this->dba->executeQuery("SELECT * FROM ". MAPS ." WHERE row_level > 1 AND row_left > ". $temp['row_left'] ." AND row_right < ". $temp['row_right'] ." ORDER BY row_left ASC"));				
		} else {
			$temp['expanded']		= 0;
		}
		return $temp;
	}
}

?>