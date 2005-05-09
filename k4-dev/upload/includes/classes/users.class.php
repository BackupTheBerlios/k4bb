<?php
/**
* k4 Bulletin Board, users.class.php
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
* @version $Id: users.class.php,v 1.4 2005/05/09 21:17:02 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

/**
 * Get the highest permissioned group that a user belongs to
 */
function get_user_max_group($temp, $all_groups) {
	$groups				= @unserialize($temp['usergroups']);
			
	if(is_array($groups)) {
		
		
		/**
		 * Loop through all of the groups and all of this users groups
		 * Find the one with the highest permission and use it as the color
		 * for this person's username. The avatar is separate because not all
		 * groups will automatically have avatars, so get the highest possible
		 * set avatar for this user.
		 */
		foreach($groups as $g) {
			
			/* If the group variable isn't set, set it */
			if(!isset($group) && isset($all_groups[$g]))
				$group	= $all_groups[$g];
			
			if(!isset($avatar) && isset($all_groups[$g]) && $all_groups[$g]['avatar'] != '')
				$avatar	= $all_groups[$g]['avatar'];

			/**
			 * If the perms of this group are greater than that of the $group 'prev group', 
			 * set is as this users group 
			 */
			if(@$all_groups[$g]['max_perm'] > @$group['max_perm']) {
				$group	= $all_groups[$g];
				
				/* Give this user an appropriate group avatar */
				if($all_groups[$g]['avatar'] != '')
					$avatar	= $all_groups[$g]['avatar'];
			}
		}
	}
	
	$group['avatar']		= isset($avatar) ? $avatar : '';

	return $group;
}

class LogoutEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_SESS;

		/* Create the ancestors bar (if we run into any trouble */
		$template = BreadCrumbs($template, $template->getVar('L_LOGOUT'));

		if (is_a($session['user'], 'Member')) {
			
			/* Update the users' last seen time */
			$dba->executeUpdate("UPDATE ". USERS ." SET last_seen = seen, seen = 0 WHERE id = ". $user['id'] );

		} else {
			$template->setInfo('content', $template->getVar('L_NEEDLOGGEDIN'));
		}
		
		/* Persistent method to unset the cookie */
		@setcookie("k4_autolog", "", time()-3600);
		bb_setcookie_cache('k4_autolog', '', time()-3600);

		/* Make the user into a Guest user rather than a Member */
		$session['user']		= &new Guest();
		
		$_SESS->setUserStatus(TRUE);

		/* Redirect the page */
		$template->setInfo('content', $template->getVar('L_LOGGEDOUTSUCCESS'));
		$template->setRedirect($_SERVER['HTTP_REFERER'], 3);

		return TRUE;
	}
}

class LoginEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_SESS;

		/* Create the ancestors bar (if we run into any trouble */
		$template = BreadCrumbs($template, $template->getVar('L_LOGIN'));

		if (is_a($session['user'], 'Guest')) {

			$id	= $session['user']->Validate($request);

			if ($id === FALSE) {
				return $template->setInfo('content', $template->getVar('L_INVALIDUSERPASS'));
			}
			
			/* Is this user already logged in ? */
			$user_is_logged		= &$dba->prepareStatement("SELECT * FROM ". SESSIONS ." WHERE name=? AND seen>=?");
			$user_is_logged->setString(1, $request['name']);
			$user_is_logged->setInt(2, time() - ini_get('session.gc_maxlifetime'));

			$result				= &$user_is_logged->executeQuery();

			if($result->numrows() > 0)
				return $template->setInfo('content', $template->getVar('L_USERALREADYLOGGED'));

			$session['user']		= &new Member($id);
			
			if($session['user']->info['banned'] == 0) {
				
				/**
				 * Log our user in 
				 */
				$session['user']->Login();
				$user			= &$session['user']->info;
				
				/* Set the rememberme setting to this user */
				if (isset($request['rememberme']))
					$session['user']->info['rememberme']	= 'on';
				
				/* Set the auto-log cookie */
				if(isset($user['rememberme']) && $user['rememberme'] == 'on') {
					
					/* Create a safe cookie */
					$userinfo	= $user['name'] . $session['user']->GenerateLoginKey();

					/* Set the auto-logging in cookie, be persistent about it */
					@setcookie('k4_autolog', $userinfo, time()+(3600*24*60));
					bb_setcookie_cache('k4_autolog', $userinfo, time()+(3600*24*60));
				}

				if (isset($user['login_request_uri'])) {
					$template->setRedirect($user['login_request_uri'], 3);
				} else {
					$template->setRedirect('index.php', 3);
				}
				

				$template->setInfo('content', $template->getVar('L_LOGGEDINSUCCESS'));
				
			} else {
				$session['user']							= &new Guest;
				$session['user']->info['rememberme']		= 'off';
				$template->setInfo('content', $template->getVar('L_THISBANNEDUSER'));
			}
		} else {
			$template->setInfo('content', $template->getVar('L_CANTBELOGGEDIN'));
		}
		

		return TRUE;
	}
}

class RemindMeEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		/* Create the ancestors bar (if we run into any trouble */
		$template = BreadCrumbs($template, $template->getVar('L_INFORMATION'));

		if (is_a($session['user'], 'Guest')) {
			$template->setFile('content', 'remindme_form.html');

			return TRUE;
		} else {
			$template->setInfo('content', $template->getVar('L_CANTBELOGGEDIN'), FALSE);
		}

		return FALSE;
	}
}

class ForumRegisterUser extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		/* Create the ancestors bar (if we run into any trouble */
		$template = BreadCrumbs($template, $template->getVar('L_REGISTER'));

		if (is_a($session['user'], 'Guest')) {
			
			global $_USERFIELDS, $_SETTINGS;
			
			/* If we are not allowed to register */
			if(isset($_SETTINGS['allowregistration']) && $_SETTINGS['allowregistration'] == 0) {
				$template->setInfo('content', $template->getVar('L_CANTREGISTERADMIN'));
				return TRUE;
			}
			
			/* If this person has yet to agree to the forum terms */
			if(!isset($request['agreed']) || $request['agreed'] != 'yes') {
				$template->setFile('content', 'register_agree.html');
				return TRUE;
			}
			
			/* Collect the custom profile fields to display */
			$fields = array();
		
			foreach($_USERFIELDS as $field) {
				if($field['display_register'] == 1) {
					$fields[] = $field;
				}
			}
			
			$template->setVar('regmessage', sprintf($template->getVar('L_INORDERTOPOSTINFORUM'), $_SETTINGS['bbtitle']));

			$template->setList('profilefields', new FAArrayIterator($fields));
			$template->setFile('content', 'register.html');

			return TRUE;
		} else {
			$template->setInfo('content', $template->getVar('L_CANTREGISTERLOGGEDIN'), FALSE);
		}

		return FALSE;
	}
}

/**
 * Insert a user into the database
 */
class ForumInsertUser extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		/* Create the ancestors bar (if we run into any trouble */
		$template = BreadCrumbs($template, $template->getVar('L_REGISTER'));

		if (is_a($session['user'], 'Guest')) {
			
			global $_USERFIELDS, $_SETTINGS;
			
			/* If we are not allowed to register */
			if(isset($_SETTINGS['allowregistration']) && $_SETTINGS['allowregistration'] == 0) {
				$template->setInfo('content', $template->getVar('L_CANTREGISTERADMIN'));
				return TRUE;
			}
			
			/* Collect the custom profile fields to display */
			$fields = array();
		
			foreach($_USERFIELDS as $field) {
				if($field['display_register'] == 1) {
					$fields[$field['name']] = $field;
				}
			}
			
			/**
			 * Error checking
			 */

			/* Username checks */
			if(!isset($request['name']) || !$request['name'] || $request['name'] == '') {
				$template->setInfo('content', $template->getVar('L_SUPPLYUSERNAME'), TRUE);
				return TRUE;
			}
			
			if(strlen($request['name']) < intval($_SETTINGS['minuserlength'])) {
				$template->setInfo('content', sprintf($template->getVar('L_USERNAMETOOSHORT'), intval($_SETTINGS['minuserlength']), intval($_SETTINGS['maxuserlength'])), TRUE);
				return TRUE;
			}

			if(strlen($request['name']) > intval($_SETTINGS['maxuserlength'])) {
				$template->setInfo('content', sprintf($template->getVar('L_USERNAMETOOLONG'), intval($_SETTINGS['maxuserlength'])), TRUE);
				return TRUE;
			}

			if($dba->getValue("SELECT COUNT(*) FROM ". USERS ." WHERE name = '". $dba->quote($request['name']) ."'") > 0) {
				$template->setInfo('content', $template->getVar('L_USERNAMETAKEN'), TRUE);
				return TRUE;
			}
			
			if($dba->getValue("SELECT COUNT(*) FROM ". BADUSERNAMES ." WHERE name = '". $dba->quote($request['name']) ."'") > 0) {
				$template->setInfo('content', $template->getVar('L_USERNAMENOTGOOD'), TRUE);
				return TRUE;
			}
			
			/* Password checks */
			if(!isset($request['pass']) || !$request['pass'] || $request['pass'] == '') {
				$template->setInfo('content', $template->getVar('L_SUPPLYPASSWORD'), TRUE);
				return TRUE;
			}

			if(!isset($request['pass2']) || !$request['pass2'] || $request['pass2'] == '') {
				$template->setInfo('content', $template->getVar('L_SUPPLYPASSCHECK'), TRUE);
				return TRUE;
			}

			if($request['pass'] != $request['pass2']) {
				$template->setInfo('content', $template->getVar('L_PASSESDONTMATCH'), TRUE);
				return TRUE;
			}
			
			/* Email checks */
			if(!isset($request['email']) || !$request['email'] || $request['email'] == '') {
				$template->setInfo('content', $template->getVar('L_SUPPLYEMAIL'), TRUE);
				return TRUE;
			}

			if(!isset($request['email2']) || !$request['email2'] || $request['email2'] == '') {
				$template->setInfo('content', $template->getVar('L_SUPPLYEMAILCHECK'), TRUE);
				return TRUE;
			}

			if($request['email'] != $request['email2']) {
				$template->setInfo('content', $template->getVar('L_EMAILSDONTMATCH'), TRUE);
				return TRUE;
			}
			
			

			return TRUE;
		} else {
			$template->setInfo('content', $template->getVar('L_CANTREGISTERLOGGEDIN'), FALSE);
		}

		return FALSE;
	}
}

?>