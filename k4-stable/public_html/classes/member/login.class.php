<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     login.class.php
 *     Copyright (c) 2004, Peter Goodman

 *     Permission is hereby granted, free of charge, to any person obtaining 
 *     a copy of this software and associated documentation files (the 
 *     "Software"), to deal in the Software without restriction, including 
 *     without limitation the rights to use, copy, modify, merge, publish, 
 *     distribute, sublicense, and/or sell copies of the Software, and to 
 *     permit persons to whom the Software is furnished to do so, subject to 
 *     the following conditions:

 *     The above copyright notice and this permission notice shall be 
 *     included in all copies or substantial portions of the Software.

 *     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, 
 *     EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 
 *     MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 *     NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS 
 *     BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN 
 *     ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
 *     CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE 
 *     SOFTWARE.
 *********************************************************************************/

error_reporting(E_STRICT | E_ALL);

class LogoutEvent extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors bar (if we run into any trouble */
		$template = CreateAncestors($template, $template['L_LOGOUT']);

		if ($session['user'] instanceof Member) {
			setcookie('k4_lastactive', time(), time()+(3600*24*60)); // expire in 30 days
			DBA::Open()->Query("UPDATE ". USERS ." SET last_seen = seen, seen = 0 WHERE id = ". $session['user']['id'] );
			$session['user']		= new Guest;
			$session['rememberme']	= 'off';

			header("Location: {$_SERVER['HTTP_REFERER']}");
			exit();
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}

		return FALSE;
	}
}

class LoginEvent extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors bar (if we run into any trouble */
		$template = CreateAncestors($template, $template['L_LOGIN']);

		if ($session['user'] instanceof Guest) {
			
			$id	= $session['user']->Validate($request);
			
			if ($id === FALSE) {
				return new Error($template['L_INVALIDUSERPASS'], $template);
			}

			$session['user']	= new Member($id);
			
			if($session['user']['banned'] == 0) {

				$session['user']->Login();

				if ($request['rememberme'] == 'on')
					$session['rememberme']	= 'on';

				if (isset($session['login_request_uri'])) {
					header("Location: {$session['login_request_uri']}");
					exit();
				}

				header("Location: index.php");
			} else {
				$session['user'] = new Guest;
				$session['rememberme']	= 'off';
				return new Error($template['L_THISBANNEDUSER'], $template);
			}
		}

		return FALSE;
	}
}

class RemindMeEvent extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors bar (if we run into any trouble */
		$template = CreateAncestors($template, $template['L_INFORMATION']);

		if ($session['user'] instanceof Guest) {
			$template->content	= array('file' => 'remindme_form.html');

			return TRUE;
		} else {
			return new Error($template['L_CANTBELOGGEDIN'], $template);
		}

		return FALSE;
	}
}
?>