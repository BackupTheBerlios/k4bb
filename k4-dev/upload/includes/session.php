<?php
/**
* k4 Bulletin Board, session.php
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
* @version $Id: session.php,v 1.13 2005/04/20 20:34:07 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

function session_user_status(&$session, $sessid, $logout = FALSE) {
	
	global $_DATASTORE;

	/* Check over the user info and pre set this user to be a Gues */
	if (!isset($session['user']) || !is_a($session['user'], 'User'))
		$session['user']					= &new Guest();
	
	/* If we are _not_ logging out */
	if(!$logout) {
		
		/* Check to see if we should auto-log this person in or not */
		if(!is_a($session['user'], 'Member')) {
			
			/* Get this session ID */
			$id											= &$session['user']->ValidateLoginKey($_COOKIE);
			
			/* If this session exists, log the user in */
			if ($id	!== FALSE) {

				/* Log the user in */
				$session['user']						= &new Member($id);
				$session['user']->info['rememberme']	= 'on';
				$session['user']->Login();
			}
		}
	}

	if(is_a($session['user'], 'Guest')) {
		preg_match("~(". $_DATASTORE['search_spiders']['spiderstrings'] .")~is", USER_AGENT, $matches);
		
		if(count($matches) >= 2) {
			$session['user']->info['id']	= -1;
			$session['user']->info['name']	= $_DATASTORE['search_spiders']['spidernames'][$matches[1]];
		}
	}

	/* Set the session id to the globals class to get it later */
	Globals::setGlobal('_SESSID', $sessid);

	return $session;
}

class FADBSession {
	var $read_stmt;
	var $write_stmt;
	var $update_stmt;
	var $destroy_stmt;
	var $user_stmt;
	var $gc_stmt;
	var $dba;
	var $url;
	var $location_act;
	var $location_id;

	function FADBSession() {
		global $_DBA, $_URL;
		
		$this->dba			= &$_DBA;
		$this->url			= &$_URL;

		$this->location_act	= isset($this->url->args['act']) && $this->url->args['act'] != '' ? $this->url->args['act'] : NULL;
		$this->location_id	= isset($this->url->args['id']) && intval($this->url->args['id']) != 0 ? intval($this->url->args['id']) : 0;

		//$this->read_stmt	= $this->dba->prepareStatement("SELECT * FROM ". SESSIONS ." WHERE id=?");
		$this->user_stmt	= $this->dba->prepareStatement("UPDATE ". USERS ." SET last_seen=? WHERE id=?");
		$this->write_stmt	= $this->dba->prepareStatement("INSERT INTO ". SESSIONS ." (id, seen, name, user_id, user_agent, data, location_file, location_act, location_id) VALUES(?,?,?,?,?,?,?,?,?)");
		$this->update_stmt	= $this->dba->prepareStatement("UPDATE ". SESSIONS ." SET name=?,user_id=?,data=?,seen=?,user_agent=?,location_file=?,location_act=?,location_id=? WHERE id=?");
		$this->destroy_stmt = $this->dba->prepareStatement("DELETE FROM ". SESSIONS ." WHERE sess_id=?");
		$this->gc_stmt		= $this->dba->prepareStatement("DELETE FROM ". SESSIONS ." WHERE seen<?");
	}

	function open($dirname, $sessid) {
		return TRUE;
	}

	function close() {
		return TRUE;
	}

	function read($sessid) {
		
		$rs							= $this->dba->getRow("SELECT * FROM ". SESSIONS ." WHERE id = '". md5($sessid) ."'");
		
		$data						= is_array($rs) && !empty($rs) && $rs['data'] != '' ? @unserialize($rs['data']) : array();
		
		$data						= session_user_status($data, md5($sessid));
		
		/* Set this user's seen time to now or what the session has */
		$data['seen']				= !isset($rs['seen']) ? time() : $rs['seen'];
		$data['user']->info['seen']	= !isset($rs['seen']) ? time() : $rs['seen'];
		
		/* Add the session to the database.. this could have a user as being logged if the
		 * logged in cookie is set from a previous login
		 */
		
		/* If this is a guest, make sure that their name is unique */
		if(is_a($data['user'], 'Guest')) {
			$data['user']->info['name'] = md5($sessid);
		}

		if (!is_array($rs) || empty($rs)) {
			
			/* Do the 'write' query here */
			$this->write_stmt->setString(1, md5($sessid));
			$this->write_stmt->setInt(2,	time()); // bbtime()
			$this->write_stmt->setString(3, $data['user']->info['name']);
			$this->write_stmt->setInt(4,	$data['user']->info['id']);
			$this->write_stmt->setString(5, serialize($data));
			$this->write_stmt->setString(6, USER_AGENT);
			$this->write_stmt->setString(7, $this->url->file);
			$this->write_stmt->setString(8, $this->location_act );
			$this->write_stmt->setInt(9,	$this->location_id );
			
			$this->write_stmt->executeUpdate();
		} else {
			
			/* Do the 'update' query here, this is also used for writing */
			$this->update_stmt->setString(1,	$data['user']->info['name']);
			$this->update_stmt->setInt(2,		$data['user']->info['id']);
			$this->update_stmt->setString(3,	serialize($data));
			$this->update_stmt->setInt(4,		time()); // bbtime()
			$this->update_stmt->setString(5,	USER_AGENT);
			$this->update_stmt->setString(6,	$this->url->file);
			$this->update_stmt->setString(7,	$this->location_act);
			$this->update_stmt->setInt(8,		$this->location_id);
			$this->update_stmt->setString(9,	md5($sessid));
			
			$this->update_stmt->executeUpdate();
		}

		if(is_a($data['user'], 'Member')) {
			$this->user_stmt->setInt(1, bbtime());
			$this->user_stmt->setInt(2, $data['user']->info['id']);
			$this->user_stmt->executeUpdate();
		}

		/* Secondary Garbage collecting measures */
		$this->gc_stmt->setInt(1, time() - ini_get('session.gc_maxlifetime'));
		$this->gc_stmt->executeUpdate();
		
		if(isset($data['bbcache']))
			$_SESSION['bbcache'] = $data['bbcache'];
		
		/* Get this users permissions, they will NOT be inserted into the db */
		$data['user']->info['maps'] = get_maps();

		Globals::setGlobal('session', &$data);
		Globals::setGlobal('user', &$data['user']->info);
		
		/* Memory saving */
		unset($data);

		return TRUE;

	}

	function write($sessid) {
		
		$bbcache			= isset($_SESSION['bbcache']) ? $_SESSION['bbcache'] : array();
		
		$session			= &Globals::getGlobal('session');
		$session['bbcache'] = $bbcache;
		
		/* Don't put this user's perms into the database / memory saving */
		unset($session['user']->info['maps']);
		
		/* If this is a guest, make sure that their name is unique */
		if(is_a($session['user'], 'Guest')) {
			$session['user']->info['name'] = md5($sessid);
		}

		$this->update_stmt->setString(1,	$session['user']->info['name']);
		$this->update_stmt->setInt(2,		$session['user']->info['id']);
		$this->update_stmt->setString(3,	serialize($session));
		$this->update_stmt->setInt(4,		time());
		$this->update_stmt->setString(5,	USER_AGENT);
		$this->update_stmt->setString(6,	$this->url->file);
		$this->update_stmt->setString(7,	$this->location_act);
		$this->update_stmt->setInt(8,		$this->location_id);
		$this->update_stmt->setString(9,	md5($sessid));
		
		$this->update_stmt->executeUpdate();
				
		/* Memory saving */
		unset($session);
			
		return TRUE;
	}

	function destroy($sessid) {
		$this->destroy_stmt->setString(1, md5($sessid));
		$this->destroy_stmt->executeUpdate();

		return TRUE;
	}

	function gc($maxlifetime) {
		$this->gc_stmt->setInt(1, time() - $maxlifetime);
		$this->gc_stmt->executeUpdate();

		return TRUE;
	}
}

$instance		= &new FADBSession;
session_set_save_handler(array(&$instance,'open'), array(&$instance,'close'), array(&$instance,'read'), array(&$instance,'write'), array(&$instance,'destroy'), array(&$instance,'gc'));

/* Start the session */
session_start();

/* Execute any cached functions, Generally these are cookies */
bb_execute_cache();

/* Globalize the _DBA */
global $_DBA;

//$_DBA->executeUpdate("delete from k4_sessions;");

$sessid						= &Globals::getGlobal('_SESSID');


/**
 * Store both the session ID and _all_ of the settings in the globals array
 */

$GLOBALS['_SESSID']			= &$sessid;

?>