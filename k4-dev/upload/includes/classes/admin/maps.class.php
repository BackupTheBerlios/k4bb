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
* @version $Id: maps.class.php,v 1.2 2005/04/11 02:18:24 k4st Exp $
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

class AdminInsertMap {
	var $dba;
	function AdminInsertMap() {
		global $_DBA;
		$this->dba		= $_DBA;
	}
	function getNumOnLevel($row_left, $row_right, $level) {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". MAPS ." WHERE row_left > $row_left AND row_right < $row_right AND row_level = $level");
	}
	function insertNode($request, $category_id = FALSE, $forum_id = FALSE, $group_id = FALSE, $user_id = FALSE) {
		
		/**
		 * Error checking on request fields 
		 */
		if(!isset($request['parent_id']))
			return Error::pitch(new FAError('L_INVALIDMAPID', __FILE__, __LINE__));
		
		if(!isset($request['varname']))
			return Error::pitch(new FAError('L_MAPSNEEDVARNAME', __FILE__, __LINE__));

		if(!isset($request['name']))
			return Error::pitch(new FAError('L_MAPSNEEDNAME', __FILE__, __LINE__));
		
		/**
		 * Start building info for the queries
		 */

		/* Get the last node to the furthest right in the tree */
		$last_node = $this->dba->GetRow("SELECT * FROM ". MAPS ." WHERE row_level = 1 ORDER BY row_right DESC LIMIT 1");

		$level = 1;
		
		/* Is this a top level node? */
		if(intval($request['parent_id']) == 0) {
			
			$left			= $last_node['row_right']+1;
			$level			= 1;
			$parent			= array('category_id' => intval($category_id), 'forum_id' => intval($forum_id), 'group_id' => intval($group_id), 'user_id' => intval($user_id));
		
		/* If we are actually dealing with a parent node */
		} else if(intval($request['parent_id']) > 0) {
			
			/* Get the parent node */
			$parent			= $this->dba->GetRow("SELECT * FROM ". MAPS ." WHERE id = ". intval($request['parent_id']));
			
			/* Check if the parent node exists */
			if(!is_array($parent) || empty($parent))
				return Error::pitch(new FAError('L_INVALIDMAPID', __FILE__, __LINE__));
			
			/* Find out how many nodes are on the current level */
			$num_on_level	= $this->getNumOnLevel($parent['row_left'], $parent['row_right'], $parent['row_level']+1);
			
			/* If there are more than 1 nodes on the current level */
			if($num_on_level > 0) {
				$left			= $parent['row_right'];
			} else {
				$left			= $parent['row_left'] + 1;
			}
			
			/* Should we need to reset some of the $parent values? */
			$parent['category_id']	= !$category_id ? $parent['category_id'] : intval($category_id);
			$parent['forum_id']		= !$forum_id ? $parent['forum_id'] : intval($forum_id);
			$parent['group_id']		= !$group_id ? $parent['group_id'] : intval($group_id);
			$parent['user_id']		= !$user_id ? $parent['user_id'] : intval($user_id);

			/* Set this nodes level */
			$level			= $parent['row_level']+1;
		} else {
			return Error::pitch(new FAError('L_INVALIDMAPID', __FILE__, __LINE__));
		}

		$right = $left+1;
		
		/**
		 * Build the queries
		 */

		/* Prepare the queries */
		$update_a			= &$this->dba->prepareStatement("UPDATE ". MAPS ." SET row_right = row_right+2 WHERE row_left < ? AND row_right >= ?");
		$update_b			= &$this->dba->prepareStatement("UPDATE ". MAPS ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= ?");
		$insert				= &$this->dba->prepareStatement("INSERT INTO ". MAPS ." (row_left,row_right,row_level,name,varname,category_id,forum_id,user_id,group_id,can_view,can_add,can_edit,can_del,inherit) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
		
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
			
			$map			= &new AdminInsertMap();
			
			Error::reset();
			$map->insertNode($request);

			if(Error::grab()) {
				$error		= &Error::grab();
				return $template->setError('content', $template->getVar($error->message));
			}

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
			
			$heirarchy		= &new Heirarchy();
			$heirarchy->removeNode($map, MAPS);

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
	var $start_level;
	function MAPSIterator($data = NULL, $start_level = 1) {
		global $_DBA;

		$this->dba			= &$_DBA;
		$this->start_level	= $start_level;
		
		parent::FAProxyIterator($data);
	}

	function &current() {
		$temp			= parent::current();
		
		$num_children	= @(($temp['row_right'] - $temp['row_left'] - 1) / 2);
		$temp['level']	= str_repeat('&nbsp;&nbsp;&nbsp;', $temp['row_level']-$this->start_level);

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