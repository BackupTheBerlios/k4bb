<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     users.class.php
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

class AdminUpdateUser extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			foreach($request as $key=>$val) {
				$request[$key] = $this->dba->Quote($val);
			}
			/* Check if that is a valid email */
			if(!check_mail($request['email']))
				return new Error($template['L_INVALIDEMAIL'], $template);
			
			$id = intval($request['id']);
			if($this->dba->Query("SELECT * FROM ". USERS ." WHERE id = $id")->NumRows() == 1) {
				if($this->dba->Query("UPDATE ". USERS ." SET name = '". $request['name'] ."', email = '". $request['email'] ."', posts = ". intval($request['posts']) .", rank = '". $request['rank'] ."' WHERE id = ". $id )) {
					echo '<meta http-equiv="refresh" content="2; url=member.php?id='. $id .'">';
					return new Error($template['L_USERUPDATED'], $template);
				}
			} else {
				return new Error($template['L_USERDOESNTEXIST'], $template);
			}
		}
		
		return TRUE;
	}
}

class AdminAddBadName extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$badname = $this->dba->Quote($request['name']);
			if($this->dba->Query("INSERT INTO ". BADNAMES ." (name) VALUES ('{$badname}')"))
				header("Location: admin.php?act=users");
		}
		
		return TRUE;
	}
}

class AdminUpdateBadNames extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$badname = $this->dba->Quote($request['name']);
			$id = intval($request['id']);
			if($this->dba->Query("UPDATE ". BADNAMES ." SET name = '{$badname}' WHERE id = $id"))
				header("Location: admin.php?act=users");
		}
		
		return TRUE;
	}
}

class AdminRemoveBadName extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			$id = intval($request['id']);

			if($this->dba->Query("DELETE FROM ". BADNAMES ." WHERE id = $id"))
				header("Location: admin.php?act=users");
		}

		return TRUE;
	}
}

class AdminBanUser extends Event {
	protected $dba;
	protected $ban;
	public function __construct($ban) {
		$this->dba = DBA::Open();
		$this->ban = intval($ban);
	}
	public function Execute(Template $template, Session $session, $request) {
		
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			$user = $this->dba->Quote($request['name']);
			if($this->dba->Query("SELECT * FROM ". USERS ." WHERE name = '{$user}'")->NumRows() == 1) {
				
				$rank = $this->ban == 0 ? $template['L_ONPAROLE'] : $template['L_BANNED'];

				if($this->dba->Query("UPDATE ". USERS ." SET banned = $this->ban, rank = '{$rank}' WHERE name = '{$user}'"))
					header("Location: admin.php?act=users");
			} else {
				return new Error($template['L_USERDOESNTEXIST'], $template);
			}
		}
		
		return TRUE;
	}
}

/* Redirects to edit user, or deletes the user */
class AdminRedirectEditUser extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {

		$this->dba = DBA::Open();
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			$user = $this->dba->Quote($request['name']);
			if($this->dba->Query("SELECT * FROM ". USERS ." WHERE name = '{$user}'")->NumRows() == 1) {
				$id = $this->dba->GetValue("SELECT id FROM ". USERS ." WHERE name = '{$user}'");
				if(isset($request['edit'])) {
					header("Location: member.php?id=". $id);
				} else if(isset($request['delete'])) {
					if($id != 1) {
						$this->dba->Execute("DELETE FROM ". SESSIONS ." WHERE uid = ". $id);
						$this->dba->Execute("DELETE FROM ". USERS ." WHERE id = ". $id);
						$this->dba->Execute("UPDATE ". POSTS ." SET poster_id = 0 WHERE poster_id = ". $id);
						$this->dba->Execute("UPDATE ". FORUMS ." SET thread_uid = 0 WHERE thread_uid = ". $id);

						return new Error($template['L_DELETEDUSER'], $template);
					} else {
						return new Error($template['L_CANNOTDELETEADMIN'], $template);
					}
				}
			} else {
				return new Error($template['L_USERDOESNTEXIST'], $template);
			}

		}
	}
}

?>