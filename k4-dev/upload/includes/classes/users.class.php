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
* @version $Id: users.class.php,v 1.11 2005/05/26 18:35:44 k4st Exp $
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
	$groups				= $temp['usergroups'] != '' ? iif(!unserialize($temp['usergroups']), array(), unserialize($temp['usergroups'])) : array();
			
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
			if(isset($all_groups[$g]['max_perm']) && isset($group['max_perm']) && ($all_groups[$g]['max_perm'] > $group['max_perm'])) {
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

function email_user($id) {
	if(ctype_digit($id) && intval($id) > 0) {

		global $_DBA, $lang, $_SETTINGS;

		$user		= $_DBA->getRow("SELECT * FROM ". USERS ." WHERE id = ". intval($id));

		if($user && is_array($user) && !empty($user)) {
			@mail($user['email'], sprintf($lang('L_REGISTEREMAILTITLE'), $_SETTINGS['bbtitle']), $email, "From: \"". $_SETTINGS['bbtitle'] ." Forums\" <noreply@". $verify_url->__toString() .">");
		}
	}
}

class LoginEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_SESS;

		/* Create the ancestors bar (if we run into any trouble */
		$template = BreadCrumbs($template, $template->getVar('L_LOGIN'));

		if (is_a($session['user'], 'Guest')) {
			
			global $_SETTINGS;

			$u					= $session['user']->Validate($request);
			
			if ($u === FALSE) {
				$template->setInfo('content', $template->getVar('L_INVALIDUSERPASS'));
				return TRUE;
			}
			
			/* Did this user just register? */
			if($u['perms'] == PENDING_MEMBER && $u['priv_key'] != '' && intval($_SETTINGS['verifyemail']) == 1) {
				
				$template->setInfo('content', $template->getVar('L_NEEDVERIFYEMAIL'), TRUE);
				return TRUE;
			}
			
			/* Is this user already logged in ? */
			$user_is_logged		= &$dba->prepareStatement("SELECT * FROM ". SESSIONS ." WHERE name=? AND seen>=?");
			$user_is_logged->setString(1, $request['name']);
			$user_is_logged->setInt(2, time() - ini_get('session.gc_maxlifetime'));

			$result				= &$user_is_logged->executeQuery();

			if($result->numrows() > 0) {
				$template->setInfo('content', $template->getVar('L_USERALREADYLOGGED'));
				return TRUE;
			}

			$session['user']		= &new Member($u['id']);
			
			if($session['user']->info['banned'] == 0) {
				
				/* Set the rememberme setting to this user */
				if (isset($request['rememberme']))
					$session['user']->info['rememberme']	= 'on';

				/**
				 * Log our user in 
				 */
				$session['user']->Login();
				$user			= &$session['user']->info;
				
				/* Set the auto-log cookie */
				if(isset($user['rememberme']) && $user['rememberme'] == 'on') {
					
					/* Create a safe cookie */
					$session['user']->GenerateLoginKey();
					//$userinfo	= $user['name'] . $session['user']->GenerateLoginKey();

					/* Set the auto-logging in cookie, be persistent about it */
					//@setcookie('k4_autolog', $userinfo, time()+(3600*24*60));
					//bb_setcookie_cache('k4_autolog', $userinfo, time()+(3600*24*60));
				}
				
				$dba->executeUpdate("UPDATE ". USERS ." SET last_seen = ". time() ." WHERE id = ". intval($u['id']));
				
				$template->setRedirect('index.php', 3);
				$template->setInfo('content', $template->getVar('L_LOGGEDINSUCCESS'));
				
			} else {
				$session['user']							= &new Guest();
				$session['user']->info['rememberme']		= 'off';
				$template->setInfo('content', $template->getVar('L_THISBANNEDUSER'));
				return TRUE;
			}
		} else {
			$template->setInfo('content', $template->getVar('L_CANTBELOGGEDIN'));
			return TRUE;
		}

		return TRUE;
	}
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

		$referer = !$_SERVER['HTTP_REFERER'] || $_SERVER['HTTP_REFERER'] == '' ? 'index.php' : $_SERVER['HTTP_REFERER'];

		/* Redirect the page */
		$template->setInfo('content', $template->getVar('L_LOGGEDOUTSUCCESS'));
		$template->setRedirect($referer, 3);

		return TRUE;
	}
}

class ValidateUserByEmail extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		/* Create the ancestors bar (if we run into any trouble */
		$template = BreadCrumbs($template, $template->getVar('L_VALIDATEMEMBERSHIP'));

		if (is_a($session['user'], 'Guest')) {
			
			global $_SETTINGS;
			
			if(!isset($request['key']) || strlen($request['key']) != 32) {
				$template->setInfo('content', $template->getVar('L_INVALIDREGID'));
				return TRUE;
			}

			$u			= $dba->getRow("SELECT * FROM ". USERS ." WHERE priv_key = '". $dba->quote($request['key']) ."' AND perms = ". intval(PENDING_MEMBER));

			if(!is_array($u) || empty($u)) {
				$template->setInfo('content', $template->getVar('L_INVALIDREGID'));
				return TRUE;
			}
			
			$dba->executeUpdate("UPDATE ". USERS ." SET priv_key = '', perms = ". MEMBER ." WHERE id = ". intval($u['id']));
			
			$template->setInfo('content', $template->getVar('L_REGVALIDATEDEMAIL'));
			$template->setRedirect('index.php', 3);

			return TRUE;
		} else {
			$template->setInfo('content', $template->getVar('L_CANTBELOGGEDIN'), FALSE);
			return TRUE;
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

		return TRUE;
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
			
			global $_USERFIELDS, $_SETTINGS, $_URL, $_DATASTORE;
			
			/* If we are not allowed to register */
			if(isset($_SETTINGS['allowregistration']) && $_SETTINGS['allowregistration'] == 0) {
				$template->setInfo('content', $template->getVar('L_CANTREGISTERADMIN'));
				return TRUE;
			}
			
			/* Collect the custom profile fields to display */
			$query_fields	= '';
			$query_params	= '';
		
			foreach($_USERFIELDS as $field) {
				if($field['display_register'] == 1) {
					
					/* This insures that we only put in what we need to */
					if(isset($request[$field['name']])) {
						
						switch($field['inputtype']) {
							default:
							case 'text':
							case 'textarea':
							case 'select': {
								if($request[$field['name']] != '') {
									$query_fields	.= ', '. $field['name'];
									$query_params	.= ", '". $dba->quote(htmlentities($request[$field['name']], ENT_QUOTES)) ."'";
								}
								break;
							}
							case 'multiselect':
							case 'radio':
							case 'check': {
								if(is_array($request[$field['name']]) && !empty($request[$field['name']])) {
									$query_fields	.= ', '. $field['name'];
									$query_params	.= ", '". $dba->quote(serialize($request[$field['name']])) ."'";
								}
								break;
							}
						}						
					}
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
			
			if(!check_mail($request['email'])) {
				$template->setInfo('content', $template->getVar('L_NEEDVALIDEMAIL'), TRUE);
				return TRUE;
			}

			if($_SETTINGS['requireuniqueemail'] == 1) {
				if($dba->getValue("SELECT COUNT(*) FROM ". USERS ." WHERE email = '". $dba->quote($request['email']) ."'") > 0) {
					$template->setInfo('content', $template->getVar('L_EMAILTAKEN'), TRUE);
					return TRUE;
				}
			}
			
			/**
			 * Do the database inserting
			 */
			
			$name						= htmlentities($request['name'], ENT_QUOTES);
			$priv_key					= md5(microtime() + rand());

			$insert_a					= &$dba->prepareStatement("INSERT INTO ". USERS ." (name,email,pass,perms,priv_key,usergroups,created) VALUES (?,?,?,?,?,?,?)");
			
			$insert_a->setString(1, $name);
			$insert_a->setString(2, $request['email']);
			$insert_a->setString(3, md5($request['pass']));
			$insert_a->setInt(4, PENDING_MEMBER);
			$insert_a->setString(5, $priv_key);
			$insert_a->setString(6, 'a:1:{i:0;i:2;}'); // Registered Users
			$insert_a->setInt(7, time());
			
			$insert_a->executeUpdate();
			
			$user_id					= $dba->getInsertId();

			$insert_b					= &$dba->prepareStatement("INSERT INTO ". USERINFO ." (user_id,timezone". $query_fields .") VALUES (?,?". $query_params .")");
			$insert_b->setInt(1, $user_id);
			$insert_b->setInt(2, intval(@$request['timezone']));

			$insert_b->executeUpdate();
			
			$datastore_update	= &$dba->prepareStatement("UPDATE ". DATASTORE ." SET data=? WHERE varname=?");
			
			/* Set the datastore values */
			$datastore					= $_DATASTORE['forumstats'];
			$datastore['num_members']	= $dba->getValue("SELECT COUNT(*) FROM ". USERS);
			
			$datastore_update->setString(1, serialize($datastore));
			$datastore_update->setString(2, 'forumstats');

			$datastore_update->executeUpdate();

			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}
			
			/* Do we need to validate their email by having them follow a url? */
			if(intval($_SETTINGS['verifyemail']) == 1) {
				
				$verify_url				= $_URL;
				$verify_url->args		= array('act' => 'activate_accnt', 'key' => $priv_key);
				$verify_url->file		= 'member.php';
				$url					= $verify_url->__toString();

				$dba->executeUpdate("UPDATE ". USERS ." SET usergroups = 'a:1:{i:0;i:1;}' WHERE id = ". intval($user_id));

				$email					= sprintf($template->getVar('L_REGISTEREMAILRMSG'), $request['name'], $_SETTINGS['bbtitle'], $url, $_SETTINGS['bbtitle']);

				$template->setInfo('content', sprintf($template->getVar('L_SUCCESSREGISTEREMAIL'), $_SETTINGS['bbtitle'], $request['email']), FALSE);
				$template->setRedirect('index.php', 5);
			} else {
				$dba->executeUpdate("UPDATE ". USERS ." SET perms = 5, priv_key = '' WHERE id = ". intval($user_id));
				$template->setInfo('content', sprintf($template->getVar('L_SUCCESSREGISTER'), $_SETTINGS['bbtitle']), FALSE);
				$template->setRedirect('index.php', 5);

				$email					= sprintf($template->getVar('L_REGISTEREMAILMSG'), $request['name'], $_SETTINGS['bbtitle'], $_SETTINGS['bbtitle']);
			}

			/* Send our email, make the url email looking ;) */
			$verify_url->args			= array();
			$verify_url->file			= FALSE;
			$verify_url->anchor			= FALSE;
			$verify_url->scheme			= FALSE;
			$verify_url->path			= FALSE;
			
			/* Finally, mail our user */
			@mail($request['email'], sprintf($template->getVar('L_REGISTEREMAILTITLE'), $_SETTINGS['bbtitle']), $email, "From: \"". $_SETTINGS['bbtitle'] ." Forums\" <noreply@". $verify_url->__toString() .">");

			return TRUE;
		} else {
			$template->setInfo('content', $template->getVar('L_CANTREGISTERLOGGEDIN'), FALSE);
		}

		return FALSE;
	}
}

class EmailUser extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS;

		/**
		 * Error checking on this member
		 */
		if(!isset($request['id']) || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_USERDOESNTEXIST'), TRUE);
			return TRUE;
		}

		$member = $dba->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". USERS ." u LEFT JOIN ". USERINFO ." ui ON u.id = ui.user_id WHERE u.id = ". intval($request['id']));

		if(!$member || !is_array($member) || empty($member)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_USERDOESNTEXIST'), TRUE);
			return TRUE;
		}

		if(!is_a($session['user'], 'Member')) {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setFile('content', 'login_form.html');
			$template->show('no_perms');
			return TRUE;
		}
		
		foreach($member as $key => $val)
			$template->setVar('member_'. $key, $val);
				
		/**
		 * Set the info we need
		 */
		$template	= BreadCrumbs($template, $template->getVar('L_EMAILUSER'));
		$template->setFile('content', 'email_user.html');
		
		return TRUE;
	}
}

class SendEmailToUser extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS;

		/**
		 * Error checking on this member
		 */
		if(!isset($request['id']) || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_USERDOESNTEXIST'), TRUE);
			return TRUE;
		}

		$member = $dba->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". USERS ." u LEFT JOIN ". USERINFO ." ui ON u.id = ui.user_id WHERE u.id = ". intval($request['id']));

		if(!$member || !is_array($member) || empty($member)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_USERDOESNTEXIST'), TRUE);
			return TRUE;
		}

		if(!is_a($session['user'], 'Member')) {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setFile('content', 'login_form.html');
			$template->show('no_perms');
			return TRUE;
		}
		
		if(!isset($request['subject']) || $request['subject'] == '') {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_INSERTMAILSUBJECT'), TRUE);
			return TRUE;
		}

		if(!isset($request['message']) || $request['message'] == '') {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_INSERTMAILMESSAGE'), TRUE);
			return TRUE;
		}
		$template	= BreadCrumbs($template, $template->getVar('L_EMAILUSER'));

		if(!@mail($member['email'], htmlentities(stripslashes($request['subject']), ENT_QUOTES), htmlentities(stripslashes($request['message']), ENT_QUOTES), "Content-type: text/html; charset=iso-8859-1\r\nFrom: \"". iif($user['realname'] == '', $user['name'], $user['realname']) ." - k4 Bulletin Board Mailer\" <". $user['email'] .">")) {
			$template->setInfo('content', sprintf($template->getVar('L_ERROREMAILING'), $member['name']));
		} else {
			$template->setInfo('content', sprintf($template->getVar('L_EMAILSENT'), $member['name']));
			$template->setRedirect('member.php?id='. $member['id'], 3);
		}
		
		return TRUE;
	}
}

class FindPostsByUser extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS;

		/**
		 * Error checking on this member
		 */
		if(!isset($request['id']) || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_USERDOESNTEXIST'), TRUE);
			return TRUE;
		}

		$member = $dba->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". USERS ." u LEFT JOIN ". USERINFO ." ui ON u.id = ui.user_id WHERE u.id = ". intval($request['id']));

		if(!$member || !is_array($member) || empty($member)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_USERDOESNTEXIST'), TRUE);
			return TRUE;
		}
		
		foreach($member as $key => $val)
			$template->setVar('member_'. $key, $val);
				
		/**
		 * Set the info we need
		 */
		$template	= BreadCrumbs($template, $template->getVar('L_FINDPOSTS'));
		$template->setFile('content', 'user_posts.html');
		
		return TRUE;
	}
}

class UsersIterator extends FAProxyIterator {
	
	var $result;
	var $groups;

	function UsersIterator(&$result) {
		global $_USERGROUPS;

		$this->result			= &$result;
		$this->groups			= $_USERGROUPS;
		
		parent::FAProxyIterator($this->result);
	}

	function &current() {
		$temp					= parent::current();
		$group					= get_user_max_group($temp, $this->groups);
		
		$temp['group_color']	= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
		$temp['group_nicename']	= $group['nicename'];
		$temp['group_avatar']	= $group['avatar'];

		return $temp;
	}
}

?>