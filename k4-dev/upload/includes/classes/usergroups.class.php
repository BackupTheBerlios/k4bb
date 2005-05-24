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
* @version $Id: usergroups.class.php,v 1.2 2005/05/24 20:01:31 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class AddUserToGroup extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_USERGROUPS, $_QUERYPARAMS;
		
		if(!isset($request['id']) || intval($request['id']) == 0) {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_GROUPDOESNTEXIST'));
		}

		if(!isset($_USERGROUPS[intval($request['id'])])) {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_GROUPDOESNTEXIST'));
		}

		if(!isset($request['name']) || !$request['name'] || $request['name'] == '') {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_USERDOESNTEXIST'), TRUE);
			return TRUE;
		}
		
		$group			= $_USERGROUPS[intval($request['id'])];
		
		$member			= $dba->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". USERS ." u LEFT JOIN ". USERINFO ." ui ON u.id=ui.user_id WHERE u.name = '". $dba->quote($request['name']) ."'");
		
		if(!$member || !is_array($member) || empty($member)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_USERDOESNTEXIST'), TRUE);
			return TRUE;
		}
		
		/* Should we set the group moderator? */
		if($group['mod_name'] == '' || $group['mod_id'] == 0) {
			$admin		= $dba->getRow("SELECT * FROM ". USERS ." WHERE perms >= ". intval(ADMIN) ." ORDER BY perms,id ASC LIMIT 1");
			$dba->executeUpdate("UPDATE ". USERGROUPS  ." SET mod_name = '". $dba->quote($admin['name']) ."', mod_id = ". intval($admin['id']) ." WHERE id = ". intval($group['id']));
		
			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}
			
			$group['mod_name']	= $admin['name'];
			$group['mod_id']	= $admin['id'];
		}

		if($group['mod_id'] == $member['id']) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_YOUAREMODERATOR'), TRUE);
			return TRUE;
		}
		
		$groups					= $member['usergroups'] != '' ? iif(!unserialize($member['usergroups']), force_usergroups($member), unserialize($member['usergroups'])) : array();		
		
		$in_group				= FALSE;
		foreach($groups as $id)
			if(isset($_USERGROUPS[$id]) && $id == $group['id'])
				$in_group		= TRUE;
		
		if($in_group) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_BELONGSTOGROUP'), TRUE);
			return TRUE;
		}

		$groups[]				= intval($group['id']);
		
		$extra					= NULL;
		if($user['perms'] < $group['min_perm'])
			$extra				.= ', perms='. intval($group['min_perm']);
		
		/* Add this user to the group and change his perms if we need to */
		$dba->executeUpdate("UPDATE ". USERS ." SET usergroups='". $dba->quote(serialize($groups)) ."' $extra WHERE id = ". intval($member['id']));
		
		$template				= BreadCrumbs($template, $template->getVar('L_ADDUSER'));
		$template->setInfo('content', sprintf($template->getVar('L_ADDEDUSERTOGROUP'), $member['name'], $group['name']), FALSE);
		$template->setRedirect('usergroups.php?id='. intval($group['id']), 3);

		return TRUE;
	}
}

?>