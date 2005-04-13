<?php
/**
* k4 Bulletin Board, login.class.php
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
* @version $Id: login.class.php,v 1.2 2005/04/13 02:53:03 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class LogoutEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_SESSID;

		/* Create the ancestors bar (if we run into any trouble */
		$template = BreadCrumbs($template, $template->getVar('L_LOGOUT'));

		if (is_a($session['user'], 'Member')) {
			
			/* Set the last active cookie to expire in 30 days */
			setcookie("k4_lastactive", time(), time()+(3600*24*60));
			
			/* Update the users' last seen time */
			$dba->executeUpdate("UPDATE ". USERS ." SET last_seen = seen, seen = 0 WHERE id = ". $user['id'] );

		} else {
			$template->setInfo('content', $template->getVar('L_NEEDLOGGEDIN'));
		}
		
		/* Persistent method to unset the cookie */
		setcookie("k4_autolog", "", time()-3600);
		bb_setcookie_cache('k4_autolog', '', time()-3600);

		/* Make the user into a Guest user rather than a Member */
		$session['user']		= &new Guest();
		$session				= session_user_status($session, $_SESSID, TRUE);

		/* Update the database with the new session inforamtion */
		$update		= &$dba->prepareStatement("UPDATE ". SESSIONS ." SET data=?,name=?,user_id=? WHERE id=?");
		$update->setString(1, serialize($session));
		$update->setString(2, '');
		$update->setInt(3, 0);
		$update->setString(4, $_SESSID);
		$update->executeUpdate();

		/* Redirect the page */
		$template->setInfo('content', $template->getVar('L_LOGGEDOUTSUCCESS'));
		$template->setRedirect($_SERVER['HTTP_REFERER'], 3);

		/* Reset the session data */
		Globals::setGlobal('session', &$session);
		Globals::setGlobal('user', &$session['user']->info);

		return TRUE;
	}
}

class LoginEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_SESSID;

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

				$session['user']->Login();
				$user			= &$session['user']->info;
				
				/* Set the rememberme setting to this user */
				if (isset($request['rememberme']))
					$session['user']->info['rememberme']	= 'on';
				
				/* Set the auto-log cookie */
				if(isset($user['rememberme']) && $user['rememberme'] == 'on') {
					
					/* Create a safe cookie */
					$userinfo	= $user['name'] . $session['user']->GenerateLoginKey();

					/* Set the auto-logging in cookie */
					setcookie('k4_autolog', $userinfo, time()+(3600*24*60));
				}
				if (isset($user['login_request_uri'])) 
					$template->setRedirect($user['login_request_uri'], 3);
				else
					$template->setRedirect('index.php', 3);
				
				$template->setInfo('content', $template->getVar('L_LOGGEDINSUCCESS'));
				

				//header("Location: index.php");
			} else {
				$session['user']							= &new Guest;
				$session['user']->info['rememberme']		= 'off';
				$template->setInfo('content', $template->getVar('L_THISBANNEDUSER'));
			}
		} else {
			$template->setInfo('content', $template->getVar('L_CANTBELOGGEDIN'));
		}

		$session		= session_user_status($session, $_SESSID);
		
		if(is_a($session['user'], 'Member')) {
			
			$update		= &$dba->prepareStatement("UPDATE ". SESSIONS ." SET data=?,name=?,user_id=? WHERE id=?");
			$update->setString(1, serialize($session));
			$update->setString(2, $session['user']->info['name']);
			$update->setInt(3, $session['user']->info['id']);
			$update->setString(4, $_SESSID);

			$update->executeUpdate();
		}
		
		
		Globals::setGlobal('session', &$session);
		Globals::setGlobal('user', &$session['user']->info);

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

/*
class LoginFilter extends Filter {
	function Execute(&$template, &$session, &$cookie, &$post, &$get) {
		
		if (!isset($session->i) || !is_a($session->i, 'User')) {
			$session->i							= &new Guest();

			$id									= $session->ValidateLoginKey($cookie);

			if ($id	!== FALSE) {
				//$session->info['rememberme']	= isset($request['rememberme']) && $request['rememberme'] ? 'on' : 'off';
				$session->info['rememberme']	= 'on';
				$session->i						= new Member($id); 
				$session->Login();
			}
		}

		if (isset($session->info['rememberme']) && $session->info['rememberme'] == 'on') {
			unset($session->info['rememberme']);

			setcookie('name', $session->info['name'], time() + (3600 * 24 * 60));
			setcookie('key', $session->GenerateLoginKey(), time() + (3600 * 24 * 60));
		}

		if (!isset($session->info['rememberme']) || $session->info['rememberme'] == 'off') {
			unset($session->info['rememberme']);

			setcookie('name', '', time() - 3600);
			setcookie('key', '', time() - 3600);
		}
		
		return TRUE;
	}
}
*/

?>