<?php
/**
* k4 Bulletin Board, forgotpw.class.php
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
* @version $Id: forgotpw.class.php,v 1.1 2005/04/05 03:20:26 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

class RestorePwEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		if(is_a($session['user'], 'Guest')) {
			$template->setFile('content', 'forgotpw.html');
		}
	}
}

define('RAND_MAX', mt_getrandmax());

class ResetPassword extends Event {
	var $dba;
	function GetRandom($min = 0, $max = RAND_MAX) {
		$hash = md5(microtime());
		$length = ((substr($hash,0,1) < '8') ? 8 : 7 );
		mt_srand((int)base_convert(substr($hash,0,$length),16,10));

		return(mt_rand(max($min, 0), min($max, RAND_MAX)));
	}
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		$this->dba = DBA::Open();
		
		/* Create the ancestors bar (if we run into any trouble */
		//$template = CreateAncestors($template, $template['L_INFORMATION']);

		if(is_a($session['user'], 'Guest')) {
			$email = htmlspecialchars($request['email']);
			
			if($email == check_mail($email)) {
				if($this->dba->Query("SELECT * FROM ". USERS ." WHERE email = '{$email}'")->NumRows() > 0) {
					$new_pw = $this->GetRandom();
					
					$forum = $this->dba->GetValue("SELECT name FROM ". FORUMS ." WHERE row_left = 1");
					$username = $this->dba->GetValue("SELECT name FROM ". USERS ." WHERE email = '{$email}'");
					if(mail($email, sprintf($template['L_PWSENTSUBJECT'], $forum), sprintf($template['L_PWSENTMESSAGE'], $forum, $username, $new_pw, $forum), "From: \"Password Reset - k4 Bulletin Board Mailer\" <noreply@". $_SERVER['HTTP_HOST'] .">")) {
						$this->dba->Query("UPDATE ". USERS ." SET pass = '". md5($new_pw) ."' WHERE email = '{$email}'");
						return new Error($template['L_PASSWORDSENT'] . '<meta http-equiv="refresh" content="2; url=index.php">', $template);
					} else {
						return new Error($template['L_ERRORRESETPW'], $template);
					}
				} else {
					return new Error($template['L_INVALIDEMAIL'], $template);
				}
			} else {
				return new Error($template['L_INVALIDEMAIL'], $template);
			}
		} else {
			return new Error($template['L_CANTBELOGGEDIN'], $template);
		}
	}
}

?>