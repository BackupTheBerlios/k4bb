<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     ranks.class.php
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

class AdminAddRank extends Event {
	protected $dba;
	protected $update;

	public function __construct($update) {
		$this->update = $update;
		$this->dba = DBA::Open();
	}
	public function Execute(Template $template, Session $session, $request) {
		
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$parser = new BBParser($this->dba->Quote($request['rank']));
			$rank = $parser->Execute();
			$rank_to = intval($request['rank_to']);
			switch($rank_to) {
				case '1': {
					if($this->dba->Query("SELECT * FROM ". GROUPS ." WHERE id = ". intval($request['group_id']) )->NumRows() == 1) {
						$col = "group_id";
						$val = intval($request['group_id']);
					} else {
						return new Error($template['L_GROUPDOESNTEXIST'], $template);
					}
					break;
				}
				case '2': {
					if($user = $this->dba->GetRow("SELECT * FROM ". USERS ." WHERE name = '". $this->dba->Quote($request['name']) ."'")) {
						$col = "user_id";
						$val = intval($user['id']);
					} else {
						return new Error($template['L_USERDOESNTEXIST'], $template);
					}
					break;
				}
				case '3': {
					$col = "banned";
					$val = 1;
					break;
				}
			}
			if(!$this->update) {
				if($this->dba->Query("INSERT INTO ". RANKS ." ({$col}, rank) VALUES ($val, '{$rank}');")) {
					return new Error($template['L_RANKADDED'] . '<meta http-equiv="refresh" content="2; url=admin.php?act=ranks">', $template);
				}
			} else {
				$id = intval($request['id']);
				if($this->dba->Query("UPDATE ". RANKS ." SET user_id = 0, group_id = 0, banned = 0 WHERE id = $id") && $this->dba->Query("UPDATE ". RANKS ." SET {$col} = $val, rank = '{$rank}' WHERE id = $id")) {
					return new Error($template['L_RANKUPDATED'] . '<meta http-equiv="refresh" content="2; url=admin.php?act=ranks">', $template);
				}
			}
		}
		return TRUE;
	}
}

class RanksIterator implements Iterator {
	protected $ranks;
	protected $dba;

	public function __construct() {
		$this->dba = DBA::Open();
		$this->ranks = $this->dba->Query("SELECT * FROM ". RANKS )->GetIterator();
	}
	
	public function Current() {
		$temp = $this->ranks->Current();
		
		$rank = new BBParser(NULL);
		$temp['rank'] = $rank->Revert($temp['rank']);

		$temp['group_id'] = intval($temp['group_id']);
		$temp['user'] = intval($temp['user_id']) == 0 ? '' : $this->dba->GetValue("SELECT name FROM ". USERS ." WHERE id = ". $temp['user_id']);
		
		if(intval($temp['group_id']) != 0)
			$temp['rank_to'] = 1;
		else if(intval($temp['user_id']) != 0)
			$temp['rank_to'] = 2;
		else
			$temp['rank_to'] = 3;

		return $temp;		
	}
	
	public function Key() {
		return $this->ranks->Key();
	}
	
	public function Next() {
		return $this->ranks->Next();
	}
	
	public function Rewind() {
		return $this->ranks->Rewind();
	}
	
	public function Valid() {
		return $this->ranks->Valid();
	}
}

?>