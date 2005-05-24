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
* @version $Id: session.php,v 1.22 2005/05/24 20:03:26 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}
/*
function session_user_status($logout = FALSE) {
	
}
*/
class FADBSession {
	var $is_new;
	
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
	var $location_file;

	function FADBSession() {
		global $_DBA, $_URL;
		
		$this->dba			= &$_DBA;
		$this->url			= &$_URL;

		$this->location_act	= isset($this->url->args['act']) && $this->url->args['act'] != '' ? $this->url->args['act'] : NULL;
		$this->location_id	= isset($this->url->args['id']) && intval($this->url->args['id']) != 0 ? intval($this->url->args['id']) : 0;
		$this->location_file= $this->url->file;

		$this->read_stmt	= $this->dba->prepareStatement("SELECT * FROM ". SESSIONS ." WHERE id=? GROUP BY user_id ORDER BY seen DESC LIMIT 1");
		$this->user_stmt	= $this->dba->prepareStatement("UPDATE ". USERS ." SET seen=?,ip=? WHERE id=?");
		$this->write_stmt	= $this->dba->prepareStatement("INSERT INTO ". SESSIONS ." (id, seen, name, user_id, user_agent, data, location_file, location_act, location_id) VALUES(?,?,?,?,?,?,?,?,?)");
		$this->update_stmt	= $this->dba->prepareStatement("UPDATE ". SESSIONS ." SET name=?,user_id=?,data=?,seen=?,user_agent=?,location_file=?,location_act=?,location_id=? WHERE id=?");
		$this->destroy_stmt	= $this->dba->prepareStatement("DELETE FROM ". SESSIONS ." WHERE sess_id=?");
		$this->gc_stmt		= $this->dba->prepareStatement("DELETE FROM ". SESSIONS ." WHERE seen<?");
		
		session_set_save_handler(array(&$this,'open'), array(&$this,'close'), array(&$this,'read'), array(&$this,'write'), array(&$this,'destroy'), array(&$this,'gc'));
		session_start();
	}

	function open($dirname, $sessid) {
		return TRUE;
	}

	function close() {
		return TRUE;
	}

	function read($sessid) {

		$this->read_stmt->setString(1, $sessid);
		
		$result				= $this->read_stmt->executeQuery();
		
		$this->is_new		= TRUE;
		$this->is_first		= TRUE;
		$data = '';
		
		if ($result->next()) {
			//The session already exists
			$this->is_new	= FALSE;
			
			$data			= $result->get('data');
			
			/* Is this this users first visit to this page? */
			//$this->is_first = ( ($data['location_file'] == $this->location_file) && ($data['location_act'] == $this->location_act) && ($data['location_id'] == $this->location_id) ) ? TRUE : FALSE;
			
		}
		
		//Globals::setGlobal('sessid', $sessid);
		
		return $data;
	}

	function write($sessid, $data) {
		
		if ($this->is_new) {
			
			/**
			 * This is the initial creation of the session
			 */

			//(id, seen, name, user_id, user_agent, data, location_file, location_act, location_id)
			$this->write_stmt->setString(1, $sessid);
			$this->write_stmt->setInt(2,    time());
			$this->write_stmt->setString(3, $_SESSION['user']->info['name']);
			$this->write_stmt->setInt(4,    $_SESSION['user']->info['id']);
			$this->write_stmt->setString(5, USER_AGENT);
			$this->write_stmt->setString(6, $data);
			$this->write_stmt->setString(7, $this->location_file);
			$this->write_stmt->setString(8, $this->location_act );
			$this->write_stmt->setInt(9, $this->location_id );
			
			$this->write_stmt->executeUpdate();
		} else {
			
			/**
			 * The session already exists, only update
			 */
			
			//name=?,user_id=?,data=?,seen=?,user_agent=?,location_file=?,location_act=?,location_id=? WHERE id=?
			$this->update_stmt->setString(1,	$_SESSION['user']->info['name']);
			$this->update_stmt->setInt(2,		$_SESSION['user']->info['id']);
			$this->update_stmt->setString(3,	serialize($data));
			$this->update_stmt->setInt(4,		time());
			$this->update_stmt->setString(5,	USER_AGENT);
			$this->update_stmt->setString(6,	$this->location_file);
			$this->update_stmt->setString(7,	$this->location_act);
			$this->update_stmt->setInt(8,		$this->location_id);
			$this->update_stmt->setString(9,	$sessid);
			
			$this->update_stmt->executeUpdate();
			
		}
					
		return TRUE;
	}

	function destroy($sessid) {
		$this->destroy_stmt->setString(1, $sessid);
		$this->destroy_stmt->executeUpdate();

		return TRUE;
	}

	function gc($maxlifetime) {
		$this->gc_stmt->setInt(1, time() - $maxlifetime);
		$this->gc_stmt->executeUpdate();

		return TRUE;
	}
	
	function setUserStatus($logout = FALSE) {
		global $_DATASTORE, $_DBA;

		/* Check over the user info and pre set this user to be a Guest */
		if (!isset($_SESSION['user']) || !is_a($_SESSION['user'], 'User'))
			$_SESSION['user'] = &new Guest();
		
		/* Auto-logging in */
		if(!$logout && $this->is_new) {
			
			/* Check to see if we should auto-log this person in or not */
			if(!is_a($_SESSION['user'], 'Member') && isset($_COOKIE['k4_autolog'])) {
				
				/* Get this session ID */
				$id											= &$_SESSION['user']->ValidateLoginKey($_COOKIE);
				
				/* If this session exists, log the user in */
				if ($id !== FALSE) {

					/* Log the user in */
					$_SESSION['user']						= &new Member($id);
					$_SESSION['user']->info['rememberme']	= 'on';
					$_SESSION['user']->Login();
					
					$_DBA->executeUpdate("UPDATE ". USERS ." SET last_seen = ". time() ." WHERE id = ". intval($id));
				}
			}
		}
		
		/* If this person is a guest */
		if(is_a($_SESSION['user'], 'Guest')) {
			preg_match("~(". $_DATASTORE['search_spiders']['spiderstrings'] .")~is", USER_AGENT, $matches);
			
			if(count($matches) >= 2) {
				$_SESSION['user']->info['id']		= -1;
				$_SESSION['user']->info['name']		= $_DATASTORE['search_spiders']['spidernames'][$matches[1]];
			}
		}

		/* Update our user if this person is logged in */
		if(is_a($_SESSION['user'], 'Member')) {
			
			$this->user_stmt->setInt(1, time());
			$this->user_stmt->setString(2, USER_IP);
			$this->user_stmt->setInt(3, $_SESSION['user']->info['id']);

			$this->user_stmt->executeUpdate();
		}

	}
}

$instance       = &new FADBSession;
$instance->setUserStatus();

/*
$sessid			= Globals::getGlobal('sessid');

if($sessid) {
	$instance->update_stmt->setString(1,	$_SESSION['user']->info['name']);
	$instance->update_stmt->setInt(2,		$_SESSION['user']->info['id']);
	$instance->update_stmt->setString(3,	'');
	$instance->update_stmt->setInt(4,		time());
	$instance->update_stmt->setString(5,	USER_AGENT);
	$instance->update_stmt->setString(6,	$instance->location_file);
	$instance->update_stmt->setString(7,	$instance->location_act);
	$instance->update_stmt->setInt(8,		$instance->location_id);
	$instance->update_stmt->setString(9,	$sessid);
	
	$instance->update_stmt->executeUpdate();
}
*/
/*
	Ok, I have changed it so that creating an instance of FADBSession
	starts the session.  What you need to do is to modify
	FADBSession::setUserStatus() to do the things that session_user_status
	used to do.  I don't uderstand why you store the sessid anywhere.
	Also, regarding loging people in, you can use the member var $is_new
	to check if you need to try and auto-login someone.  If $is_new ==
	TRUE, then you can assume that it is a guest unless the cookie is
	validates, otherwise, you can assume that $_SESSION already contains
	the information from the last page.
*/

/* Execute any cached functions, Generally these are cookies */
bb_execute_cache();


$GLOBALS['_SESS'] = &$instance;

?> 