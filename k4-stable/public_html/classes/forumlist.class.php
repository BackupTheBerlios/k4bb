<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     forumlist.class.php
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

class ForumList implements Iterator {
	protected $forums;
	protected $depth;
	protected $temp;
	protected $suspend;

	public function __construct($id = FALSE, $suspend = 0) {
		$session = $_SESSION;
		$session['user']['perms'] = intval($session['user']['perms']) == 0 ? ALL : $session['user']['perms'];
		$extra = (isset($id) && $id != FALSE) ? " AND id = $id " : NULL;

		$query = DBA::Open()->Query("SELECT * FROM ". FORUMS ." WHERE row_level = 1 AND suspend <= $suspend ". $extra ." AND can_view <= ". intval($session['user']['perms']) ." ORDER BY f_order ASC");
		
		$this->forums = $query->GetIterator();
		$this->suspend = $suspend;
	}
	
	public function Current() {
		$this->temp = $this->forums->Current();
		$temp		= $this->forums->Current();

		$temp['name']			= stripslashes($temp['name']);
		$temp['description']	= stripslashes($temp['description']);
		return $temp;
	}
	
	public function Key() {
		return $this->forums->Key();
	}
	
	public function Next() {
		return $this->forums->Next();
	}
	
	public function Rewind() {
		return $this->forums->Rewind();
	}
	
	public function Valid() {
		return $this->forums->Valid();
	}
	
	public function GetChildren() {
		$row	= $this->temp;
		if($row['row_left'] != 1)
			return new SubForumList($row, " AND row_level = 2", $this->suspend);
	}
}

class SubForumList implements Iterator {
	protected $child;
	protected $suspend;
	protected $dba;
	protected $session;
	protected $lang;
	protected $settings;
	public function __construct($row, $extra = FALSE, $suspend) {
		global $lang;
		global $settings;
		$this->lang = $lang;
		$this->settings = $settings;
		$this->session = $_SESSION;
		$this->session['user']['perms'] = intval($this->session['user']['perms']) == 0 ? ALL : $this->session['user']['perms'];
		$this->suspend = $suspend;
		$this->dba = DBA::Open();
		$this->child = $this->dba->Query("SELECT * FROM ". FORUMS ." WHERE row_left > ". $row['row_left'] ." AND row_right < ". $row['row_right'] ." AND suspend <= $this->suspend AND can_view <= ". intval($this->session['user']['perms']) ." $extra" ." ORDER BY f_order ASC")->GetIterator();
	}
	
	public function Current() {
		$temp = $this->child->Current();
		
		if($this->session['user'] instanceof Member) {
			$extra = $temp['row_lock'] == 1 ? '_lock' : NULL;
			$status = $this->session['user']['seen'] >= $temp['thread_created'] ? 'forum_off' : 'forum_on';
			$status = $temp['row_lock'] == 1 && $this->settings['showlocks'] == 1 && $this->session['user']['perms'] > REG ? $status : 'forum_off';
			$icon_final = $status.$extra;
		} else {
			$icon_final = 'forum_off';
		}

		if($temp['is_link'] == 1)
			$icon_final = 'forum_link';
		
		$temp['class']			= $temp['suspend'] == 1 ? 'special_panel' : 'panel';
		$temp['name']			= stripslashes($temp['name']);
		$temp['description']	= $this->settings['showforumdescription'] == 1 ? stripslashes($temp['description']) : '';
		
		$temp['posts']			= $temp['is_link'] == 1 ? '<span class="minitext">'. $this->lang['L_REFERALS'] .':</span>' : number_format($temp['posts']);
		$temp['threads']		= $temp['is_link'] == 1 ? number_format($temp['referals']) : number_format($temp['threads']);

		$temp['forum_alt']		= isset($status) && ($status == 'forum_on') ? $this->lang['L_NEWPOSTS'] : $this->lang['L_NONEWPOSTS'];
		$temp['forum_icon']		= $icon_final;
		$temp['thread_created'] = date("m.d.y", $temp['thread_created']);
		$temp['thread_name']	= strlen($temp['thread_name']) > 25 ? substr(stripslashes($temp['thread_name']), 0, 25) .'...' : stripslashes($temp['thread_name']);
		if($this->settings['showsubforums'] == 1) {
			if($temp['subforums'] > 0) {
				$temp['sub_forums'] = '<strong>'. $this->lang['L_SUBFORUMS'].'</strong>: ';
				$rows = $this->dba->Query("SELECT * FROM ". FORUMS ." WHERE row_left > ". $temp['row_left'] ." AND row_right < ". $temp['row_right']);
				$count = $rows->NumRows();
				$i = 1;
				foreach($rows as $sub) {
					$temp['sub_forums'] .= '<a href="viewforum.php?id='. $sub['id'] .'">'. $sub['name'] .'</a>';
					$temp['sub_forums'] .= $i == $count ? '&nbsp;' : ',&nbsp;';
					$i++;
				}
			}
		}

		return $temp;
	}
	
	public function Key() {
		return $this->child->Key();
	}
	
	public function Next() {
		return $this->child->Next();
	}
	
	public function Rewind() {
		return $this->child->Rewind();
	}
	
	public function Valid() {
		return $this->child->Valid();
	}
}
?>