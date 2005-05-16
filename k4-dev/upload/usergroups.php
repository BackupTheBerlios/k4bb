<?php
/**
* k4 Bulletin Board, usergroups.php
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
* @version $Id: usergroups.php,v 1.1 2005/05/16 02:10:03 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

require 'forum.inc.php';

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_USERGROUPS, $_QUERYPARAMS, $_URL;
		
		/**
		 * Are we looking at the list of user groups?
		 */
		if(!isset($request['id']) || intval($request['id']) == 0) {
			$groups			= isset($user['usergroups']) && $user['usergroups'] != '' ? iif(!unserialize($user['usergroups']), force_usergroups($user), unserialize($user['usergroups'])) : array();
			
			$query			= "SELECT * FROM ". USERGROUPS ." WHERE display_legend = 1";
			
			if($user['perms'] < ADMIN) {
				foreach($groups as $id) {
					if(isset($_USERGROUPS[$id])) {
						$query .= ' OR id = '. intval($id);
					}
				}
			} else {
				$query		= "SELECT * FROM ". USERGROUPS;
			}

			$groups		= $dba->executeQuery( $query );
			
			$template->setList('usergroups', $groups);

			$template	= BreadCrumbs($template, $template->getVar('L_USERGROUPS'));
			$template->setFile('content', 'usergroups.html');
		
		/**
		 * Are we looking at a specific user group?
		 */
		} else {

			/* Is this user group set? */
			if(!isset($_USERGROUPS[intval($request['id'])])) {
				$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
				$template->setInfo('content', $template->getVar('L_GROUPDOESNTEXIST'));
			}
			
			$group			= $_USERGROUPS[intval($request['id'])];
			
			/**
			 * If the group admin has yet to be set, set it to our administrator
			 */
			if($group['mod_name'] == '' || $group['mod_id'] == 0) {
				
				/* Get our administrator */
				$admin		= $dba->getRow("SELECT * FROM ". USERS ." WHERE perms >= ". intval(ADMIN) ." ORDER BY perms,id DESC LIMIT 1");
				$dba->executeUpdate("UPDATE ". USERGROUPS  ." SET mod_name = '". $dba->quote($admin['name']) ."', mod_id = ". intval($admin['id']) ." WHERE id = ". intval($group['id']));
				
				/* Change the file modification time of our cache file */
				if(!@touch(CACHE_FILE, time()-86460)) {
					@unlink(CACHE_FILE);
				}
				
				/* Add this info to the group array so that we can access it later */
				$group['mod_name']	= $admin['name'];
				$group['mod_id']	= $admin['id'];
			}
			
			/* Get our admins max user group.. it _should_ be the administrators group */
			$g						= get_user_max_group($dba->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". USERS ." u LEFT JOIN ". USERINFO ." ui ON u.id=ui.user_id WHERE u.id = ". intval($group['mod_id'])), $_USERGROUPS);
			
			/* Set his group's color */
			$group['mod_color']		= !isset($g['color']) || $g['color'] == '' ? '000000' : $g['color'];
			
			/* Add this group's info to the database */
			foreach($group as $key => $val)
				$template->setVar('group_'. $key, $val);
			
			/* Create the Pagination */
			$resultsperpage		= 10;
			$num_results		= $dba->getValue("SELECT COUNT(*) FROM ". USERS ." WHERE usergroups LIKE '%;i:". intval($group['id']) .";%' AND id <> ". intval($group['mod_id']));

			$perpage			= isset($request['limit']) && ctype_digit($request['limit']) && intval($request['limit']) > 0 ? intval($request['limit']) : $resultsperpage;
			$num_pages			= ceil($num_results / $perpage);
			$page				= isset($request['page']) && ctype_digit($request['page']) && intval($request['page']) > 0 ? intval($request['page']) : 1;
			$pager				= &new TPL_Paginator($_URL, $num_results, $page, $perpage);
			
			if($num_results > $perpage) {
				$template->setPager('users_pager', $pager);
			}
			
			/* Outside valid page range, redirect */
			if(!$pager->hasPage($page) && $num_results > $resultsperpage) {
				$template->setInfo('content', $template->getVar('L_PASTPAGELIMIT'));
				$template->setRedirect('usergroups.php?id='. $group['id'] .'&limit='. $perpage .'&page='. $num_pages, 3);
			}

			/* Get the members for this usergroup */
			$start				= ($page - 1) * $perpage;
			
			/* Get the members of this usergroup */
			$result				= &$dba->executeQuery("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". USERS ." u LEFT JOIN ". USERINFO ." ui ON u.id=ui.user_id WHERE u.usergroups LIKE '%;i:". intval($group['id']) .";%' AND u.id <> ". intval($group['mod_id']) ." LIMIT ". intval($start) .", ". intval($perpage));
			$users				= &new UsersIterator($result);

			$template->setVar('num_group_members', $num_results);
			
			if($user['id'] == $group['mod_id'])
				$template->show('add_user');

			$template		= BreadCrumbs($template, $group['name']);
			$template->setList('users_in_usergroup', $users);
			$template->setFile('content', 'lookup_usergroup.html');
		}
		
		return TRUE;
	}
}

$app	= new Forum_Controller('forum_base.html');

$app->AddEvent('add_user_to_group', new AddUserToGroup);

$app->ExecutePage();

?>