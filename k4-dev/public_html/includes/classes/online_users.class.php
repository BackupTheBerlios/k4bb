<?php
/**
* k4 Bulletin Board, online_users.class.php
*
* Copyright (c) 2004, Peter Goodman
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
* @version $Id: online_users.class.php,v 1.1 2005/04/05 02:32:36 necrotic Exp $
* @package k42
*/

class OnlineUsersIterator extends FAProxyIterator {
	var $dba;
	
	function OnlineUsersIterator($extra = NULL) {
		global $_CONFIG, $_DBA, $_QUERYPARAMS;
		
		$this->dba		= $_DBA;
		$expired		= time() - ini_get('session.gc_maxlifetime');
		
		$query			= "SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['session'] ." FROM ". USERS ." u LEFT JOIN ". SESSIONS ." s ON u.id = s.user_id WHERE s.seen >= $expired $extra ORDER BY u.name ASC";
		
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
			$temp['name'] = '<span style="color:#FFA34F; font-weight: bold;">'. $temp['name'] .'</span>';
		if($temp['perms'] >= MODERATOR)
			$temp['name'] = '<span style="color:#006600; font-weight: bold;">'. $temp['name'] .'</span>';

		return $temp;
	}
}

?>