<?php
/**
* k4 Bulletin Board, user.inc.php
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
* @version $Id: user.inc.php,v 1.5 2005/05/03 21:38:14 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

/**
 * The User class, encompasses members and guests
 * @author			Geoffrey Goodman
 * @param dba		Dtabase object
 * @param info		array holding user information
 * @param table		Table to query to get user information
 * @see ArrayAccess
 */
class User { 
	var	$dba;
	var	$info	= array();
	var	$table;

	function User() {

		global $_DBA;

		$this->dba		= &$_DBA;
	}
	/*
	function __wakeup() {
		
		global $_DBA;

		$this->dba		= &$_DBA;
		". USERS ."	= USERS;
	} */
	
	/**
	 * Function to get the user id from the user name
	 * @param name			The name of the user that we 
	 *						want the ID of
	 * @return				either an exception or the user id
	 */
	function GetId($name) {
		$name	= $this->dba->Quote($name);
		
		if(!($result = $this->dba->GetValue("SELECT id FROM ". USERS ." WHERE name='{$name}'")))
			return Error::pitch(new FAError("Could not get the User ID.", __FILE__, __LINE__));
		
		return $result;
	}
	
	/**
	 * Get the user's ID by their email
	 * @param email			The email used to get the user id
	 * @return				either an exception or the user id
	 */
	function GetIdByEmail($email) {
		$email	= $this->dba->Quote($email);
		
		if(!($result = $this->dba->GetValue("SELECT id FROM ". USERS ." WHERE email = '{$email}'")))
			return Error::pitch(new FAError("Could not get the User ID.", __FILE__, __LINE__));
		
		return $result;
	}
	
	/**
	 * Checks if an offset of user[] exists
	 * @param offset	The offset to check
	 * @return			boolean true or false
	 */
	function OffsetExists($offset) {
		return isset($this->info[$offset]);
	}
	
	/**
	 * Gets the offset of $offset
	 * @param offset	The value of the offset
	 * @return			value of $offset
	 */
	function &OffsetGet($offset) {
		return $this->info[$offset];
	}
	
	/**
	 * Set an offset
	 * @param offset	Name of the offset
	 * @param value		Value of the offset
	 */
	function &OffsetSet($offset, $value) {
		return $this->info[$offset]	= $value;
	}
	
	/**
	 * Unset an offset
	 * @param offset	Offset to unset
	 */
	function OffsetUnset($offset) {
		unset($this->info[$offset]);
	}
}

/**
 * Member class, deals with logged in users
 * @author			Geoffrey Goodman
 * @param id		The user's ID
 */
class Member extends User {
	var $id;
	var $info;

	function Member($id) {

		parent::User();
		
		$this->id	= $id;

		$this->ReadInfo();
	}
	/*
	function __sleep() {
		return array("\0*\0id");
	}

	function __wakeup() {
		parent::__wakeup();

		$this->ReadInfo();
	} */

	function Login() {
		global $_DBA;

		$time		= time();
		$this->id	= is_array($this->id) ? $this->id[0] : $this->id;
		
		Error::reset();

		@setcookie('k4_lastactive', time(), time()+(3600*24*60));
		bb_setcookie_cache('k4_lastactive', time(), time()+(3600*24*60));
		
		$_DBA->executeUpdate("UPDATE ". USERS ." SET login = $time WHERE id = $this->id");
		
		if(Error::grab())
			return trigger_error("Unable to log user in. Possible problems: Database update or Cookie setting.", E_USER_ERROR);
	
		return TRUE;
	}

	function GenerateLoginKey() {
		global $_DBA;

		$key		= md5(microtime() + rand());
		
		$this->id	= is_array($this->id) ? $this->id[0] : $this->id;
		
		Error::reset();
		
		$update		= &$_DBA->prepareStatement("UPDATE ". USERS ." SET priv_key=? WHERE id=?");
		$update->setString(1, $key);
		$update->setInt(2, $this->id);
		$update->executeUpdate();
		
		if(Error::grab())
			return trigger_error("Could not update the sessions table.", E_USER_ERROR);

		return $key;
	}

	function ReadInfo() {
		global $_DBA, $_URL, $_QUERYPARAMS;

		$this->id		= is_array($this->id) ? intval($this->id[0]) : intval($this->id);
		
		$query_params	= $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'];

		$result			= $_DBA->GetRow("SELECT $query_params FROM ". USERS ." u LEFT JOIN ". USERINFO ." ui ON u.id = ui.user_id WHERE u.id = $this->id");
		
		if ($result == FALSE)
			return trigger_error("Trying to instanciate a member with an invalid ID.", E_USER_ERROR);

		/* Set the user info */
		$this->info	= $result;
		
		return TRUE;
	}
}

class Guest extends User {
	function Guest() {
		parent::User();
		
		$this->info	= array('name' => '', 'email' => '', 'id' => 0, 'perms' => 1, 'styleset' => '');
	}
	function Register($info) {
		
		$name		= $this->dba->Quote($info['name']);
		$email		= $this->dba->Quote($info['email']);
		
		$pass		= md5($info['pass']);
		$time		= time();
		
		$sql		= "INSERT INTO ". USERS ." (name,email,pass,created) VALUES ('$name','$email','$pass',$time)";

		$this->dba->executeUpdate($sql);
		$ug			= new Usergroup($info);

		$user_id	= $this->dba->GetValue("SELECT id FROM ". USERS ." WHERE name = '$name'");

		$ug->AddUserToUsergroup(3, $user_id, $name, FALSE);

		return new Member($user_id);
	}

	function Validate($info) {
		
		global $_DBA;

		if (!isset($info['name']) || !isset($info['pass']))
			return FALSE;

		$name	= $_DBA->Quote($info['name']);
		$pass	= md5($info['pass']);
		
		Error::reset();
		$result = $_DBA->GetValue("SELECT id FROM ". USERS ." WHERE name='$name' AND pass='$pass'");
		
		if(Error::grab())
			return trigger_error("Unable to select the user ID.", E_USER_ERROR);

		return $result;
	}

	function ValidateLoginKey($info) {
		global $_DBA, $_SETTINGS;
		
		if(!isset($info['k4_autolog']))
			return FALSE;

		if($info['k4_autolog'] == '' || empty($info['k4_autolog']))
			return FALSE;
		
		$info		= $info['k4_autolog'];

		if(strlen($info) <= 32 || strlen($info) > ($_SETTINGS['maxuserlength'] + 32))
			return FALSE;

		$name		= substr($info, 0, strlen($info)-32);
		$key		= substr($info, strlen($info)-32, strlen($info));

		Error::reset();
		
		$name	= $_DBA->Quote($name);
		$key	= $_DBA->Quote($key);
		
		$result = $_DBA->getValue("SELECT id FROM ". USERS ." WHERE name = '$name' AND priv_key = '$key'");
		
		if(Error::grab())
			return trigger_error("Unable to select the user ID.", E_USER_ERROR);
		
		return $result;
	}
}

?>