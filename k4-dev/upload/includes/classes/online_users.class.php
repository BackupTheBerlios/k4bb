<?php
/**
* k4 Bulletin Board, online_users.class.php
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
* @version $Id: online_users.class.php,v 1.3 2005/04/13 02:52:19 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class OnlineUsersIterator extends FAProxyIterator {
	var $dba;
	
	function OnlineUsersIterator($extra = NULL) {
		global $_CONFIG, $_DBA, $_QUERYPARAMS;
		
		$this->dba		= $_DBA;
		$expired		= time() - ini_get('session.gc_maxlifetime');
		
		$query			= "SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['session'] ." FROM ". USERS ." u LEFT JOIN ". SESSIONS ." s ON u.id = s.user_id WHERE s.seen >= $expired $extra GROUP BY u.name ORDER BY u.seen DESC";
		
		$result			= $this->dba->executeQuery($query);

		Globals::setGlobal('num_online_members', $result->numrows());
		Globals::setGlobal('num_online_invisible', 0);

		parent::FAProxyIterator($result);
	}

	function &current() {
		$temp = parent::current();
		
		if($temp['invisible'] == 1)
			Globals::setGlobal('num_online_invisible', Globals::getGlobal('num_online_invisible')+1);

		if($temp['perms'] >= ADMIN)
			$temp['name'] = '<span class="admin_user">'. $temp['name'] .'</span>';
		if($temp['perms'] >= MODERATOR)
			$temp['name'] = '<span class="mod_user">'. $temp['name'] .'</span>';

		return $temp;
	}
}

?>