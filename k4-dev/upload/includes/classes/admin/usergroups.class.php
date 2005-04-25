<?php
/**
* k4 Bulletin Board, usergroups.class.php
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
* @version $Id: usergroups.class.php,v 1.2 2005/04/25 19:52:34 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class AdminUserGroups extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/usergroups_manage.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminAddUserGroup extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/usergroups_add.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminInsertUserGroup extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			/* Error checking on the fields */
			if(!isset($request['name']) || $request['name'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTGROUPNAME'), TRUE);
				return TRUE;
			}

			if(!isset($request['nicename']) || $request['nicename'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTGROUPNICENAME'), TRUE);
				return TRUE;
			}
			
			$g = $dba->getRow("SELECT * FROM ". USERGROUPS ." WHERE name = '". $dba->quote($request['name']) ."'");			
			
			if(is_array($g) && !empty($g)) {
				$template->setInfo('content', $template->getVar('L_GROUPNAMEEXISTS'), TRUE);
				return TRUE;
			}

			if(!isset($request['description']) || $request['description'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTGROUPDESC'), TRUE);
				return TRUE;
			}
			
			if(!isset($request['mod_name']) || $request['mod_name'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTMODNAME'), TRUE);
				return TRUE;
			}

			$moderator			= $dba->getRow("SELECT * FROM ". USERS ." WHERE name = '". $dba->quote($request['mod_name']) ."'");
			
			if(!is_array($moderator) || empty($moderator)) {
				$template->setInfo('content', $template->getVar('L_INVALIDMODNAME'), TRUE);
				return TRUE;
			}

			if(!isset($request['color']) || $request['color'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTGROUPCOLOR'), TRUE);
				return TRUE;
			}
			
			$filename		= '';

			if(isset($_FILES['avatar_upload']) && is_array($_FILES['avatar_upload']))
				$filename	= $_FILES['avatar_upload']['tmp_name'];
			
			if(isset($request['avatar_browse']) && $request['avatar_browse'] != '') {
				$filename	= $request['avatar_browse'];
			}
			
			if($filename != '') {

				$file_ext		= explode(".", $filename);
				$exts			= array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'tiff');
				
				if(count($file_ext) >= 2) {
					$file_ext		= $file_ext[count($file_ext) - 1];

					if(!in_array(strtolower($file_ext), $exts)) {
						$template->setInfo('content', $template->getVar('L_INVALIDAVATAREXT'), TRUE);
						return TRUE;
					}
				} else {
					$template->setInfo('content', $template->getVar('L_INVALIDAVATAREXT'), TRUE);
					return TRUE;
				}
			}
			
			/* Build the queries */
			$insert_a			= &$dba->prepareStatement("INSERT INTO ". USERGROUPS ." (name,nicename,description,mod_name,mod_id,created,min_perm,max_perm,display_legend,color,avatar) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
			$update_a			= &$dba->prepareStatement("UPDATE ". USERS ." SET usergroups=?,perms=? WHERE id=?");

			/* Set the query values */
			$insert_a->setString(1, $request['name']);
			$insert_a->setString(2, $request['nicename']);
			$insert_a->setString(3, $request['description']);
			$insert_a->setString(4, $moderator['name']);
			$insert_a->setInt(5, $moderator['id']);
			$insert_a->setInt(6, time());
			$insert_a->setInt(7, $request['min_perm']);
			$insert_a->setInt(8, $request['max_perm']);
			$insert_a->setInt(9, $request['display_legend']);
			$insert_a->setString(10, $request['color']);
			$insert_a->setString(11, $filename);
			
			/* Add the category to the info table */
			$insert_a->executeUpdate();
			
			$group_id			= $dba->getInsertId();
			
			$usergroups			= $moderator['usergroups'] != '' ? @unserialize($moderator['usergroups']) : array();
			
			if(is_array($usergroups)) {
				$usergroups[]	= $group_id;
			} else {
				$usergroups		= array($group_id);
			}

			$update_a->setString(1, serialize($usergroups));
			$update_a->setInt(2, iif(intval($request['min_perm']) > $moderator['perms'], $request['min_perm'], $moderator['perms']));
			$update_a->setInt(3, $moderator['id']);
			
			/* Update the user's information */
			$update_a->executeUpdate();
			
			if(isset($_FILES['avatar_upload']) && is_array($_FILES['avatar_upload'])) {
				$dir		= FORUM_BASE_DIR . '/tmp/upload/group_avatars';
				
				@chmod($dir, 0777);
				@move_uploaded_file($_FILES['avatar_upload']['tmp_name'], $dir .'/'. $filename);
			}

			$template->setInfo('content', sprintf($template->getVar('L_ADDEDUSERGROUP'), $request['name']), FALSE);
			$template->setRedirect('admin.php?act=usergroups', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminRemoveUserGroup extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			if(!isset($request['id']) || intval($request['id']) == 0) {
				$template->setInfo('content', $template->getVar('L_INVALIDUSERGROUP'), TRUE);
				return TRUE;
			}

			$group		= $dba->getRow("SELECT * FROM ". USERGROUPS ." WHERE id = ". intval($request['id']));

			if(!is_array($group) || empty($group)) {
				$template->setInfo('content', $template->getVar('L_INVALIDUSERGROUP'), TRUE);
				return TRUE;
			}

			/* Get all users of this usergroup */
			$users		= &$dba->executeQuery("SELECT * FROM ". USERS ." WHERE usergroups LIKE '%;i:". intval($group['id']) .";%'");
			
			while($users->next()) {
				$user	= $users->current();
				$groups	= @unserialize($user['usergroups']);
				
				/* Are we dealing with an array? */
				if(is_array($groups)) {
					
					/* make a new array because if we unset values in the $groups array, it will kill the for() */
					$new_groups = array();
					
					/* Loop through the array */
					for($i = 0; $i < count($groups); $i++) {
						
						/* This will remove this usergroup, and any ninexistant ones from this user's array */
						if($groups[$i] != $group['id'] && $groups[$i] != 0) {
							$new_groups[] = $groups[$i];
						}
					}
					
					/* Reset the groups variable */
					$groups = $new_groups;
				
				/**
				 * Attempt to break down a malformed basic numeric serialized array 
				 * into its parts and remake it
				 */
				} else {

					/* Auto-set our groups array so we can default back on it */
					$groups = array();
					
					/* If the usergroups variable is not equal to nothing */
					if($user['usergroups'] != '') {
						
						/* Look for something that identifies the scope of this serialized array */
						preg_match("~\{(.*?)\}~ise", $user['usergroups'], $matches);

						/* Check the results of our search */
						if(is_array($matches) && isset($matches[1])) {
							
							/* Explode the matched value into its parts */
							$parts	= explode(";", $matches[1]);
							
							if(count($parts) > 0) {
								preg_match("~i\:([0-9])\;i\:([0-9])~is", $parts, $_matches);
								
								/** 
								 * If the number of matches is greater than 3, means that there is 1 key and 1 val 
								 * at least 
								 */
								if(count($_matches) > 3) {

									/* loop through the matches, skip [0] because it represents the pattern */
									for($i = 1; $i < count($_matches); $i++) {
										
										/**
										 * This will remove this usergroup, and any ninexistant ones from this 
										 * user's array 
										 */
										if($_matches[$i+1] != $group['id'] && $_matches[$i+1] != 0) {
											$groups[$_matches[$i]] = $_matches[$i+1];
										}

										/* Increment, (+1) so that we always increment by odd numbers */
										$i++;
									}
								}
							}
						}
					}
				}
				
				$groups		= serialize($groups);
				
				$dba->executeUpdate("UPDATE ". USERS ." SET usergroups = '". $dba->quote($groups) ."' WHERE id = ". $user['id']);
			}
			
			/* Remove the usergroup */
			$dba->executeUpdate("DELETE FROM ". USERGROUPS ." WHERE id = ". intval($group['id']));

			$template->setInfo('content', sprintf($template->getVar('L_REMOVEDUSERGROUP'), $group['name']), FALSE);
			$template->setRedirect('admin.php?act=usergroups', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminEditUserGroup extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			if(!isset($request['id']) || intval($request['id']) == 0) {
				$template->setInfo('content', $template->getVar('L_INVALIDUSERGROUP'), TRUE);
				return TRUE;
			}

			$group		= $dba->getRow("SELECT * FROM ". USERGROUPS ." WHERE id = ". intval($request['id']));

			if(!is_array($group) || empty($group)) {
				$template->setInfo('content', $template->getVar('L_INVALIDUSERGROUP'), TRUE);
				return TRUE;
			}
			
			/** 
			 * Get the mega admin user if we need him/her, normally their id
			 * should be 1, but you can never be too sure
			 */
			$mega_admin		= $dba->getRow("SELECT * FROM ". USERS ." WHERE perms = 10 ORDER BY id ASC LIMIT 1");
			
			/* If the mega admin fails, set the mega admin to whoever is logged in using this feature */
			if(!is_array($mega_admin) || empty($mega_admin))
				$mega_admin	= $user;

			$group['mod_name']	= $group['mod_name'] == '' ? $mega_admin['name'] : $group['mod_name'];
			$group['mod_id']	= $group['mod_id'] == 0 ? $mega_admin['id'] : $group['mod_id'];

			foreach($group as $key => $val) {
				$template->setVar('group_'. $key, $val);
			}

			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/usergroups_edit.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminUpdateUserGroup extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			/* Error checking on the fields */
			if(!isset($request['id']) || intval($request['id']) == 0) {
				$template->setInfo('content', $template->getVar('L_INVALIDUSERGROUP'), TRUE);
				return TRUE;
			}

			$group		= $dba->getRow("SELECT * FROM ". USERGROUPS ." WHERE id = ". intval($request['id']));

			if(!is_array($group) || empty($group)) {
				$template->setInfo('content', $template->getVar('L_INVALIDUSERGROUP'), TRUE);
				return TRUE;
			}

			if(!isset($request['name']) || $request['name'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTGROUPNAME'), TRUE);
				return TRUE;
			}

			if(!isset($request['nicename']) || $request['nicename'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTGROUPNICENAME'), TRUE);
				return TRUE;
			}
			
			$g = $dba->getRow("SELECT * FROM ". USERGROUPS ." WHERE name = '". $dba->quote($request['name']) ."' AND id != ". intval($group['id']));			
			
			if(is_array($g) && !empty($g)) {
				$template->setInfo('content', $template->getVar('L_GROUPNAMEEXISTS'), TRUE);
				return TRUE;
			}

			if(!isset($request['description']) || $request['description'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTGROUPDESC'), TRUE);
				return TRUE;
			}
			
			if(!isset($request['mod_name']) || $request['mod_name'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTMODNAME'), TRUE);
				return TRUE;
			}

			$moderator			= $dba->getRow("SELECT * FROM ". USERS ." WHERE name = '". $dba->quote($request['mod_name']) ."'");
			
			if(!is_array($moderator) || empty($moderator)) {
				$template->setInfo('content', $template->getVar('L_INVALIDMODNAME'), TRUE);
				return TRUE;
			}

			if(!isset($request['color']) || $request['color'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTGROUPCOLOR'), TRUE);
				return TRUE;
			}
			
			$filename		= '';

			if(isset($_FILES['avatar_upload']) && is_array($_FILES['avatar_upload']))
				$filename	= $_FILES['avatar_upload']['tmp_name'];
			
			if(isset($request['avatar_browse']) && $request['avatar_browse'] != '') {
				$filename	= $request['avatar_browse'];
			}
			
			if($filename != '') {

				$file_ext		= explode(".", $filename);
				$exts			= array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'tiff');
				
				if(count($file_ext) >= 2) {
					$file_ext		= $file_ext[count($file_ext) - 1];

					if(!in_array(strtolower($file_ext), $exts)) {
						$template->setInfo('content', $template->getVar('L_INVALIDAVATAREXT'), TRUE);
						return TRUE;
					}
				} else {
					$template->setInfo('content', $template->getVar('L_INVALIDAVATAREXT'), TRUE);
					return TRUE;
				}
			}
			
			/* Build the queries */
			$update_a			= &$dba->prepareStatement("UPDATE ". USERGROUPS ." SET name=?,nicename=?,description=?,mod_name=?,mod_id=?,min_perm=?,max_perm=?,display_legend=?,color=?,avatar=? WHERE id=?");
			$update_b			= &$dba->prepareStatement("UPDATE ". USERS ." SET usergroups=?,perms=? WHERE id=?");

			/* Set the query values */
			$update_a->setString(1, $request['name']);
			$update_a->setString(2, $request['nicename']);
			$update_a->setString(3, $request['description']);
			$update_a->setString(4, $moderator['name']);
			$update_a->setInt(5, $moderator['id']);
			$update_a->setInt(6, $request['min_perm']);
			$update_a->setInt(7, $request['max_perm']);
			$update_a->setInt(8, $request['display_legend']);
			$update_a->setString(9, $request['color']);
			$update_a->setString(10, $filename);
			$update_a->setInt(11, $group['id']);
			
			/* Add the category to the info table */
			$update_a->executeUpdate();

			$group_id			= $dba->getInsertId();
			
			$usergroups			= $moderator['usergroups'] != '' ? @unserialize($moderator['usergroups']) : array();

			if(is_array($usergroups)) {
				$usergroups[]	= $group_id;
			} else {
				$usergroups		= array($group_id);
			}

			$update_b->setString(1, serialize($usergroups));
			$update_b->setInt(2, iif(intval($request['min_perm']) > $moderator['perms'], $request['min_perm'], $moderator['perms']));
			$update_b->setInt(3, $moderator['id']);
			
			/**
			 * Update the user's information, if the mod name changes, the previous moderator will
			 * still be a member of the group, just not the moderator.
			 */
			$update_b->executeUpdate();
			
			if(isset($_FILES['avatar_upload']) && is_array($_FILES['avatar_upload']) && $_FILES['avatar_upload']['tmp_name'] != $group['avatar']) {
				$dir		= FORUM_BASE_DIR . '/tmp/upload/group_avatars';
				
				@chmod($dir, 0777);
				@move_uploaded_file($_FILES['avatar_upload']['tmp_name'], $dir .'/'. $filename);
			}

			$template->setInfo('content', sprintf($template->getVar('L_UPDATEDUSERGROUP'), $request['name']), FALSE);
			$template->setRedirect('admin.php?act=usergroups', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

?>