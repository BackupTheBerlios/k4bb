<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     usergroups.class.php
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

class Usergroup {	
	protected $dba;
	protected $session;
	
	public function __construct($session) {
		$this->dba		= DBA::Open();
		$this->session	= $session;
	}
	
	/* Get all the usergrous which arn't hidden */
	public function getVisibleUsergroups() {
		
		/* Is the person an admin? */
		if(($this->session['user'] instanceof Member) && ($_SESSION['user']['perms'] & ADMIN)) {
			return $this->dba->Query("SELECT * FROM ". GROUPS);
		
		/* This is a normal user */
		} else {
			return $this->dba->Query("SELECT * FROM ". GROUPS ." WHERE row_type != 1");
		}
	}
	
	/* Get a Single Visible Usergroup */
	public function getVisibleUsergroup($group_id, $template) {
		
		/* alias to getVisibleUsergroups(), but for a single usergroup */
		if(($this->session['user'] instanceof Member) && ($_SESSION['user']['perms'] & ADMIN)) {
			$group = $this->dba->GetRow("SELECT * FROM ". GROUPS ." WHERE id = ". intval($group_id) );
		} else {
			$group = $this->dba->GetRow("SELECT * FROM ". GROUPS ." WHERE id = ". intval($group_id) ." AND row_type = 0");
		}

		/* If we got or not a usergroup */
		if(!is_array($group) || !array_key_exists('id', $group)) {
			return new Error($template['L_GROUPDOESNTEXIST'], $template);
		} else {
			return $group;
		}
	}
	
	/* Add a Usergroups */
	public function AddUsergroup($name, $description, $mod_name, $mod_id, $permissions, $type) {
		
		/* Get our moderator's user info */
		$user = $this->dba->GetRow("SELECT * FROM ". USERS ." WHERE id = $mod_id");
		
		/* If the set permissions for the usergroup are greater than the moderators permissions, update the moderators permissions */
		if($user['perms'] < $permissions)
			$this->dba->Query("UPDATE ". USERS ." SET perms = $permissions WHERE id = $mod_id");
		
		/* Insert the group into the groups table */
		$this->dba->Query("INSERT INTO ". GROUPS ." (name, description, mod_name, mod_id, permissions, row_type, created) VALUES ('{$name}', '{$description}', '{$mod_name}', $mod_id, $permissions, $type, ". time() .")");
		
		/* Get the latest group's id (ie: the group we just added */
		$group_id = $this->dba->GetValue("SELECT MAX(id) FROM ". GROUPS );
		
		/* Add our moderator to the usergroup */
		return $this->AddUserToUsergroup($group_id, $mod_id, $mod_name);
	}
	
	/* Add user to usergroup */
	public function AddUserToUsergroup($group_id, $user_id, $username) {
		
		/* Find out if this member is already a part of the usergroup */
		$result = $this->dba->Query("SELECT * FROM ". USER_IN_GROUP ." WHERE group_id = ". intval($group_id) ." AND id = $user_id");
		
		/* If this user does not already belong to this usergroup */
		if($result->NumRows() == 0) {
			
			/* Try and fetch the user */
			$user = $this->dba->GetRow("SELECT * FROM ". USERS ." WHERE id = $user_id");
			
			/* If the previous query returned a full array, ie: if the user actually exists or not */
			if(@intval($user['id']) == intval($user_id) && !empty($user)) {
				
				/* Get the groups permissions */
				$permissions = $this->dba->GetValue("SELECT permissions FROM ". GROUPS ." WHERE id = $group_id");
				
				/* If this user's permssions are less than that of the group's, update the users permissions */
				if($user['perms'] < $permissions)
					$this->dba->Query("UPDATE ". USERS ." SET perms = $permissions WHERE id = $user_id");
				
				/* Add this user to the group */
				return $this->dba->Query("INSERT INTO ". USER_IN_GROUP ." (group_id, id, name) VALUES ($group_id, $user_id, '{$username}')");
			}
		}
	}
}

class GroupsIterator implements Iterator {
	protected $groups;
	protected $dba;

	public function __construct() {
		$this->dba = DBA::Open();
		$this->groups = $this->dba->Query("SELECT * FROM ". GROUPS )->GetIterator();
	}
	
	public function Current() {
		return $this->groups->Current();
	}
	
	public function Key() {
		return $this->groups->Key();
	}
	
	public function Next() {
		return $this->groups->Next();
	}
	
	public function Rewind() {
		return $this->groups->Rewind();
	}
	
	public function Valid() {
		return $this->groups->Valid();
	}
}

?>