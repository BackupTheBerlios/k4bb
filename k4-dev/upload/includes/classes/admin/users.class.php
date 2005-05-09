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
* @version $Id: users.class.php,v 1.1 2005/05/09 21:17:27 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class AdminBadUserNames extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			$badnames = $dba->executeQuery("SELECT * FROM ". BADUSERNAMES ." ORDER BY name ASC");
			$template->setList('badnames', $badnames);
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/badnames_manage.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminInsertBadUserName extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_SETTINGS;

			if(!isset($request['name']) || !$request['name'] || $request['name'] == '') {
				$template->setInfo('content', $template->getVar('L_SUPPLYBADUSERNAME'), TRUE);
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
			
			if($dba->getValue("SELECT * FROM ". BADUSERNAMES ." WHERE name = '". $dba->quote($request['name']) ."'") > 0) {
				$template->setInfo('content', $template->getVar('L_BADNAMEEXISTS'), TRUE);
				return TRUE;
			}

			$dba->executeUpdate("INSERT INTO ". BADUSERNAMES ." (name) VALUES ('". $dba->quote($request['name']) ."')");
			
			$template->setInfo('content', sprintf($template->getVar('L_ADDEDBADUSERNAME'), $request['name']), FALSE);
			$template->setRedirect('admin.php?act=usernames', 3);
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminUpdateBadUserName extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_SETTINGS;
			
			if(!isset($request['id']) || intval($request['id']) == 0) {
				$template->setInfo('content', $template->getVar('L_INVALIDBADNAME'), FALSE);
				return TRUE;
			}

			$bad		= $dba->getRow("SELECT * FROM ". BADUSERNAMES ." WHERE id = ". intval($request['id']));
			
			if(!is_array($bad) || empty($bad)) {
				$template->setInfo('content', $template->getVar('L_INVALIDBADNAME'), FALSE);
				return TRUE;
			}

			if(!isset($request['name']) || !$request['name'] || $request['name'] == '') {
				$template->setInfo('content', $template->getVar('L_SUPPLYBADUSERNAME'), TRUE);
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

			if($dba->getValue("SELECT * FROM ". BADUSERNAMES ." WHERE name = '". $dba->quote($request['name']) ."' AND id <> ". intval($bad['id'])) > 0) {
				$template->setInfo('content', $template->getVar('L_BADNAMEEXISTS'), TRUE);
				return TRUE;
			}

			$dba->executeUpdate("UPDATE ". BADUSERNAMES ." SET name = '". $dba->quote($request['name']) ."' WHERE id = ". intval($bad['id']));
			
			$template->setInfo('content', sprintf($template->getVar('L_UPDATEDBADUSERNAME'), $bad['name'], $request['name']), FALSE);
			$template->setRedirect('admin.php?act=usernames', 3);
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminRemoveBadUserName extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_SETTINGS;
			
			if(!isset($request['id']) || intval($request['id']) == 0) {
				$template->setInfo('content', $template->getVar('L_INVALIDBADNAME'), FALSE);
				return TRUE;
			}

			$bad		= $dba->getRow("SELECT * FROM ". BADUSERNAMES ." WHERE id = ". intval($request['id']));
			
			$dba->executeUpdate("DELETE FROM ". BADUSERNAMES ." WHERE id = ". intval($bad['id']));
			
			$template->setInfo('content', sprintf($template->getVar('L_REMOVEDBADUSERNAME'), $bad['name']), FALSE);
			$template->setRedirect('admin.php?act=usernames', 3);
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

?>