<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     online_users.class.php
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

class Birthdays implements Iterator {
	protected $users;
	protected $count;
	protected $i;
	public function __construct() {
		$expired = time() - Lib::GetSetting('sess.gc_maxlifetime');
		$users = DBA::Open()->Query("SELECT * FROM ". USERS ." WHERE birthday != 0");
		$this->count = $users->NumRows();
		$this->users = $users->GetIterator();
		$this->i = 1;
	}
	
	public function Current() {
		$temp = $this->users->Current();
		if(date("d m", $temp['birthday']) == date("d m")) {
			$temp['text'] = $this->i == 1 ? '' : ',&nbsp;';
			$temp['text'] .= '<a href="member.php?id='. $temp['id'] .'" style="text-decoration:underline;">'. $temp['name'] .'</a>&nbsp;(<strong>'. intval(date("Y")-date("Y", $temp['birthday'])) .'</strong>)';
		}
		$this->i++;
		return $temp;
	}
	
	public function Key() {
		return $this->users->Key();
	}
	
	public function Next() {
		return $this->users->Next();
	}
	
	public function Rewind() {
		return $this->users->Rewind();
	}
	
	public function Valid() {
		return $this->users->Valid();
	}
}

?>