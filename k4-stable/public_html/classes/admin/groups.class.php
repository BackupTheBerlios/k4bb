<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     groups.class.php
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

class AdminDeleteGroup extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$dba = DBA::Open();
			if($group = $dba->GetRow("SELECT * FROM ". GROUPS ." WHERE id = ". intval($request['id']))) {
			if($dba->Query("dELETE FROM ". GROUPS ." WHERE id = ". $group['id']) && $dba->Query("DELETE FROM ". USERS_IN_GROUP ." WHERE group_id = ". $group['id']))
				header("Location: admin.php?act=groups");
			} else {
				return new Error($template['L_GROUPDOESNTEXIST'], $template);
			}
		}
		return TRUE;
	}
}

class AdminAddGroup extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$ug				= new Usergroup;
			$dba			= DBA::Open();
			
			if(!$request['name'])
				return new Error(sprintf($template['L_REQUIREDFIELDS'], $template['L_NAME']), $template);
			if(!$request['description'])
				return new Error(sprintf($template['L_REQUIREDFIELDS'], $template['L_DESCRIPTION']), $template);
			if(!$request['mod_name'])
				return new Error(sprintf($template['L_REQUIREDFIELDS'], $template['L_MODERATOR']), $template);

			$name			= $dba->Quote($request['name']);
			$description	= $dba->Quote($request['description']);
			$mod_name		= $dba->Quote($request['mod_name']);
			$mod_id			= $dba->GetValue("SELECT id FROM ". USERS ." WHERE name = '{$mod_name}'");
			$permissions	= intval($request['perms']);
			$type			= intval($request['type']);
			if($ug->AddUsergroup($name, $description, $mod_name, $mod_id, $permissions, $type))
				header("Location: admin.php?act=groups");
		}
		return TRUE;
	}
}

class AdminUpdateGroup extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$dba = DBA::Open();

			if(!$request['name'])
				return new Error(sprintf($template['L_REQUIREDFIELDS'], $template['L_NAME']), $template);
			if(!$request['description'])
				return new Error(sprintf($template['L_REQUIREDFIELDS'], $template['L_DESCRIPTION']), $template);
			if(!$request['mod_name'])
				return new Error(sprintf($template['L_REQUIREDFIELDS'], $template['L_MODERATOR']), $template);
			if(!$request['perms'])
				return new Error(sprintf($template['L_REQUIREDFIELDS'], $template['L_PERMISSIONS']), $template);

			$name			= $dba->Quote($request['name']);
			$id				= intval($request['id']);
			$description	= $dba->Quote($request['description']);
			$mod_name		= $dba->Quote($request['mod_name']);
			try {	
				$mod			= $dba->GetRow("SELECT * FROM ". USERS ." WHERE name = '{$mod_name}'");
				$permissions	= intval($request['perms']);
				$users			= $dba->Query("SELECT * FROM ". USER_IN_GROUP ." WHERE group_id = $id");
			
				foreach($users as $user) {
					$temp_user = $dba->GetRow("SELECT * FROM ". USERS ." WHERE id = ". $user['id'] );
					if($permissions > $temp_user['perms'])
						@$dba->Query("UPDATE ". USERS ." SET perms = $permissions WHERE id = ". $user['id'] );
				}
				if(@$dba->Query("UPDATE ". GROUPS ." SET name = '{$name}', description = '{$description}', mod_name = '". $mod['name'] ."', mod_id = ". $mod['id'] .", permissions = $permissions WHERE id = $id"))
					header("Location: admin.php?act=groups");
			} catch(DBA_Exception $e) {
				return new TplException($e, $template);
			}
		}
		return TRUE;
	}
}

?>