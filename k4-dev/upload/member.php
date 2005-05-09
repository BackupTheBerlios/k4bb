<?php
/**
* k4 Bulletin Board, member.php
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
* @version $Id: member.php,v 1.3 2005/05/09 21:16:21 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

require 'forum.inc.php';

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {

		return TRUE;
	}
}

class ForumLogin extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		/* Create the ancestors bar */
		$template = BreadCrumbs($template, $template->getVar('L_LOGIN'));
		
		/* Check if the user is logged in or not */
		if(!is_a($session['user'], 'Member')) {
			
			
			$template->setFile('content', 'login_form.html');
		
		} else {
			return new Info($template->getVar('L_YOUARELOGGEDIN'), $template);
		}
		
		return TRUE;
	}
}

/* Set our wrapper template */
$app	= new Forum_Controller('forum_base.html');

/* Apply all of the events */
$app->AddEvent('login', new ForumLogin);
$app->AddEvent('register', new ForumRegisterUser);
$app->AddEvent('register_user', new ForumInsertUser);
$app->AddEvent('login_user', new LoginEvent);
$app->AddEvent('logout', new LogoutEvent);
$app->AddEvent('remindme', new RemindMeEvent);

$app->ExecutePage();

?>