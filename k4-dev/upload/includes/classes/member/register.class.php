<?php
/**
* k4 Bulletin Board, register.class.php
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
* @version $Id: register.class.php,v 1.1 2005/04/05 03:20:26 k4st Exp $
* @package k42
*/

error_reporting(E_STRICT | E_ALL);

class RegisterEvent extends Event {
	var function Pop($element) {
		if(isset($session->info['post_vars'])) {
			foreach($session->info['post_vars'] as $key=>$val) {
				if($key == $element)
					unset($session->info['post_vars'][$element]);
			}
		}
	}
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		$guest	= $session->i;
		
		$session->info['post_vars'] = $request;
		
		/* I pop out the password and pass_check right away because I don't want those fields restored if the person encounters errors */
		$this->Pop('pass');
		$this->Pop('pass_check');

		/* Create the ancestors bar (if we run into any trouble */
		//$template = CreateAncestors($template, $template['L_REGISTER']);
		
		/* dba quote all of the request variables, just to make sure :) */
		foreach($request as $key=>$val) {
			$request[$key] = $dba->Quote($val);
		}
		
		/* Check if the person is already logged in */
		if (is_a($guest, 'Member'))
			return new Error($template['L_CANTBELOGGEDIN'], $template);
		
		/* Check if that username is already taken */
		if (isset($request['name']) && $guest->GetId($request['name']) != FALSE) {
			$this->Pop('name');
			return new Error($template['L_USERNAMETAKEN'], $template);
		}

		/* Check if this is an invalid username */
		if($dba->executeQuery("SELECT lower(name) FROM ". BADNAMES ." WHERE lower(name) = lower('". $request['name'] ."')")->NumRows() >= 1) {
			$this->Pop('name');
			return new Error($template['L_USERNAMENOTGOOD'], $template);
		}

		if(isset($request['name']) && strlen($request['name']) < 3) {
			$this->Pop('name');
			return new Error($template['L_USERNAMETOOSHORT'], $template);
		} else if(isset($request['name']) && strlen($request['name']) > 16) {
			$this->Pop('name');
			return new Error($template['L_USERNAMETOOLONG'], $template);
		}
		
		/* Check if that email is already taken */
		if (isset($request['email']) && $guest->GetIdByEmail($request['email']) != FALSE) {
			$this->Pop('email');
			return new Error($template['L_EMAILTAKEN'], $template);
		}
		
		/* Is the email valid ? */
		if(isset($request['email']) && $request['email'] != check_mail($request['email'])) {
			$this->Pop('email');
			return new Error($template['L_SUPPLYVALIDEMAIL'], $template);
		}

		/* Simple array of all of the required fields */
		$required	= array('name' => $template['L_SUPPLYUSERNAME'], 'email' => $template['L_SUPPLYVALIDEMAIL'], 'pass' => $template['L_SUPPLYPASS'], 'pass_check' => $template['L_SUPPLYPASSCHECK']);
		
		/* Return error messages if the fields are not filled in */
		foreach ($required as $field => $message) {
			if (!isset($request[$field]) || $request[$field] == '') {
				$this->Pop($field);
				return new Error($message, $template);
			}
		}
		
		if (isset($request['pass']) && isset($request['pass_check']) && $request['pass'] != $request['pass_check']) {
			return new Error($template['L_PASSESDONTMATCH'], $template);
		}
		
		$member	= $guest->Register($request);
		
		if (is_a($member, 'Member')) {
			$member->Login();

			if ($request['rememberme'] == 'on')
				$session->info['rememberme']	= 'on';
			
			$session->i	= $member;
			
			@mail($request['email'], sprintf($template['L_USERWELCOMEK4'], $template['bbtitle']), sprintf($template['L_USERWELCOMEMSGK4'], $template['bbtitle'], $session->info['name'], $request['pass']), "From: \"k4 Bulletin Board Mailer\" <noreply@". $_SERVER['HTTP_HOST'] .">");
			
			header("Location: index.php");
			exit();
		}

		return FALSE;
	}
	var function Validate($info, & $errors) {
		
	}
}
?>