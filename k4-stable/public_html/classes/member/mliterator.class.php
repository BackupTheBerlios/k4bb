<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     mliterator.class.php
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

class MemeberListIterator implements Iterator {
	protected $users;
	protected $lang;

	public function __construct($request) {
		global $lang;
		$this->lang = $lang;
		if(isset($_GET['sort']) && $_GET['sort'] != "*") {
			//if($request['sort'] == "*") {
				//$order ="[a-zA-Z].*$";
				//$like = 'REGEXP';
			//} else {
				$order = strtolower($_GET['sort']) .'%';
				$like = 'LIKE';
			//}
		} else {
			$order = "%";
			$like = 'LIKE';
		}
		
		$limit			= isset($_GET['limit']) ? intval($_GET['limit']) : NULL;
		$start			= isset($_GET['start']) ? intval($_GET['start']) : NULL;

		global $settings;
		
		$db_type = get_setting(get_setting('application', 'dba_name'), 'type');

		$proper_limit = $db_type == 'pgsql' ? "LIMIT $limit OFFSET $start" : "LIMIT $start, $limit";

		$extra = (!is_null($limit) && !is_null($start)) ? $proper_limit : "LIMIT ". $settings['memberlistperpage'];

		$query = "SELECT * FROM ". USERS ." WHERE name $like '{$order}' $extra";

		$this->users = DBA::Open()->Query($query)->GetIterator();
	}
	
	public function Current() {
		$temp = $this->users->Current();
		// todo format datre
		$temp['created'] = date("d-m-y", $temp['created']);
		if($temp['seen'] >= (time() - Lib::GetSetting('sess.gc_maxlifetime')))
			$temp['online_status'] = $this->lang['L_ONLINE'];
		else
			$temp['online_status'] = $this->lang['L_OFFLINE'];
		
		$temp['avatar'] = $temp['avatar'] != '' && $temp['avatar'] != 0 ? '<img src="Uploads/Avatars/'. $temp['id'] .'.gif" border="0" alt="" />' : NULL;

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