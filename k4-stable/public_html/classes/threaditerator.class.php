<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     threaditerator.class.php
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

/* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ */
/* @@ Class: Thread Iterator					@@ */
/* @@ Description: Iterates through the threads	@@ */
/* @@ displayed on the single forum view page	@@ */
/* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ */
class ThreadIterator implements Iterator {
	protected $announcements;
	protected $threads;
	protected $current;
	protected $lang;
	protected $session;
	protected $settings;

	public function __construct($session) {
		global $lang;
		global $settings;
		$this->lang		= $lang;
		$request		= $_GET;
		$this->session	= $session;
		$this->settings = $settings;
		
		if(!$request['id'])
			return SetError::Set($this->lang['L_INVALIDFORUM']);
		else
			$id = intval($request['id']);
		
		/* Pagination */
		$limit			= isset($_GET['limit']) ? intval($_GET['limit']) : NULL;
		$start			= isset($_GET['start']) ? intval($_GET['start']) : NULL;
		$extra			= (!is_null($limit) && !is_null($start)) ? "LIMIT ". $start .", ".($limit) : "LIMIT ". $this->settings['maxthreads'];
		$second_sort	= isset($_GET['sort']) ? $_GET['sort'] : "created";
		$order			= isset($_GET['order']) && ($_GET['order'] == 'ASC' || $_GET['order'] == 'DESC') ? $_GET['order'] : "DESC";
		$timeprune		= isset($_GET['daysprune']) && is_numeric($_GET['daysprune']) ? mktime(0,0,0,date("m"),-$_GET['daysprune'],date("Y")) : 0;
		
		/* Query */
		$dba = DBA::Open();
		$this->announcements = $dba->Query("SELECT * FROM ". POSTS ." WHERE parent_id = $id AND row_type = 2 AND row_status > 2 ORDER BY row_status DESC, created DESC")->GetIterator();
		
		//$this->threads = $dba->Query("SELECT p.name as name, p.poster_id as poster_id, p.row_right as row_right, p.row_left as row_left, p.poster_name as poster_name, p.views as views, p.last_reply as last_reply, p.created as created, p.reply_uid as reply_uid, p.reply_uname as reply_uname, p.poster_name as poster_name, p.id as id, p.poll as poll, p.row_status as row_status, p.attach as attach, (p.row_right-p.row_left-1)/2 as num_replies 
		//, SUM(r.rating) as rating_sum, COUNT(r.thread_id) as num_rates FROM ". POSTS ." p, ". RATINGS ." r WHERE r.thread_id = p.id AND p.parent_id = $id AND p.row_type = 2 AND p.row_status < 3 AND p.created > $timeprune ORDER BY p.row_status DESC, $second_sort $order $extra")->GetIterator();
		
		$this->threads = $dba->Query("SELECT *, (row_right-row_left-1)/2 as num_replies FROM ". POSTS ." WHERE parent_id = $id AND row_type = 2 AND row_status < 3 AND created > $timeprune ORDER BY row_status DESC, $second_sort $order $extra")->GetIterator();
		
		$this->ratings = array();
		foreach($dba->Query("SELECT * FROM ". RATINGS) as $rating) {
			@$this->ratings[$rating['thread_id']]['rating'] += $rating['rating'];
			@$this->ratings[$rating['thread_id']]['count'] += 1;
		}

		// (SELECT SUM(rating) FROM ". RATINGS ." WHERE thread_id = threadid) as rating_sum, (SELECT COUNT(*) FROM ". RATINGS ." WHERE thread_id = threadid) as num_rates

		if ($this->announcements->Valid())
			$this->current = $this->announcements;
		else
			$this->current = $this->threads;
	}
	
	public function Current() {
		
		//$temp = !($this->round >= $this->num_special) ? $this->special->Current() : $this->threads->Current();
		$temp = $this->current->Current();

		$temp['num_children']	= ($temp['row_right'] - $temp['row_left'] - 1) / 2;
		$temp['thread_icon'] = thread_image($temp, $this->session);
		
		if($temp['num_children'] > $this->settings['maxposts']) {
			$temp['pages'] = ' <span class="smalltext">(# ';
			$num_pages = ceil($temp['num_children'] / $this->settings['maxposts']);
			$max_pages = $num_pages <= 6 ? $num_pages : 6;
			for($i = 1; $i <= $max_pages; $i++) {
				$temp['pages'] .= $i == $max_pages ? '<a href="viewthread.php?id='. $temp['id'] .'&amp;start='. ($i * $this->settings['maxposts']) .'&amp;limit='. $this->settings['maxposts'] .'" style="text-decoration:underline;">'. $i .'</a>' : '<a href="viewthread.php?id='. $temp['id'] .'&amp;start='. ($i * $this->settings['maxposts']) .'&amp;limit='. $this->settings['maxposts'] .'" style="text-decoration:underline;">'. $i .'</a>,&nbsp;';
			}
			$temp['pages'] .= '&nbsp;...&nbsp;<a href="viewthread.php?id='. $temp['id'] .'&amp;start='. ($num_pages * $this->settings['maxposts']) .'&amp;limit='. $this->settings['maxposts'] .'" style="text-decoration:underline;">'. $this->lang['L_LASTPAGE'] .'</a>&nbsp;)</span>';
		}

		$temp['last_reply']		= $temp['last_reply'] ? relative_time($temp['last_reply']) : relative_time($temp['created']);
		$temp['reply_uid']		= $temp['reply_uid'] != '' ? $temp['reply_uid'] : $temp['poster_id'];
		$temp['reply_uname']	= $temp['reply_uname'] ? $temp['reply_uname'] : $temp['poster_name'];
		
		$temp['name'] = strlen(stripslashes($temp['name'])) > 48 ? substr(stripslashes($temp['name']), 0, 45).'...' : stripslashes($temp['name']);
		
		$temp['name'] = '<a href="viewthread.php?id='. $temp['id'] .'" title="'. htmlentities(stripslashes(@$temp['description'])) .'"><strong>'. htmlspecialchars($temp['name']) .'</strong></a>';

		/* Write Poll: if the current thread is a poll */
		if($temp['poll'] == 1)
			$temp['name'] = $this->settings['pollthreadprefix'] . $temp['name'];
		
		/* Write out Sticky or Announcement depending on what this thread is */
		if($temp['row_status'] == 2)
			$temp['name'] = $this->settings['stickythreadprefix'] . $temp['name'];
		else if($temp['row_status'] == 3)
			$temp['name'] = $this->settings['announcementthreadprefix'] . $temp['name'];
		
		/* If there are attachments, put out that little paperclip image */
		if($temp['attach'] == 1)
			$temp['name'] = '<span style="float:right;font-size:11px;"><img src="Images/'. $this->settings['imageset'] .'/Icons/paperclip.gif" border="0" alt="'. $this->lang['L_ATTACHMENTS'] .'" title="'. $this->lang['L_ATTACHMENTS'] .'" /></span>'.$temp['name'];
		
		/* Make the rating */
		if(count(@$this->ratings[$temp['id']]) > 0) {
			$rating = round(@$this->ratings[$temp['id']]['rating'] / @$this->ratings[$temp['id']]['count'] );
			$temp['rating'] = '<span style="float:right;font-size:11px;"><img src="Images/'. $this->settings['imageset'] .'/Icons/Rating/'. $rating .'stars.gif" border="0" alt="'. $this->lang['L_RATING'] .': '. $rating .'; '. $this->lang['L_VOTES'] .': '. @$this->ratings[$temp['id']]['count'] .'" /></span>';
		} else {
			$temp['rating'] = '';
		}

		return $temp;		
	}
	
	public function Key() {
		return $this->current->Key();
	}
	
	public function Next() {
		$this->current->Next();

		if ($this->current == $this->announcements && !$this->announcements->Valid())
			$this->current = $this->threads;

		return TRUE;
	}
	
	public function Rewind() {
		$this->announcements->Rewind();
		$this->threads->Rewind();

		if ($this->announcements->Valid())
			$this->current = $this->announcements;
		else
			$this->current = $this->threads;

		return TRUE;
	}
	
	public function Valid() {
		return $this->current->Valid();
	}
}

/* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ */
/* @@ Class: Replies Iterator					@@ */
/* @@ Description: Gets a single thread and		@@ */
/* @@ iterates through it's replies.			@@ */
/* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ */
class RepliesIterator implements Iterator {
	protected $lang;
	protected $temp;
	protected $parent_count;
	protected $dba;
	protected $threads;
	protected $session;
	protected $settings;
	
	public function __construct($id) {
		global $lang;
		global $settings;
		$this->lang = $lang;
		$this->dba = DBA::Open();
		$this->threads = $this->dba->Query("SELECT p.name as name, p.edited as edited, f.id as parent_id, f.allowavatars as allow_avatars, f.allowsignatures as allow_sigs, f.postsperpage as postlimit, p.id as id, p.icon as icon, p.attach as attach, p.created as created, p.row_left as row_left, p.row_type as row_type, p.row_right as row_right, p.body_text as body_text, p.poster_name as poster_name, p.poster_id as poster_id 
		FROM ". POSTS ." p, ". FORUMS ." f WHERE f.id = p.parent_id AND p.id = $id AND p.row_type = 2 LIMIT 1")->GetIterator();
		$this->session = $_SESSION;
		$this->settings = $settings;
	}
	
	public function Current() {
		$temp = $this->threads->Current();

		$temp['user_ranks']			= '';

		if($temp['poster_id'] != 0) {
			$user = $this->dba->GetRow("SELECT * FROM ". USERS ." WHERE id = ". $temp['poster_id'] );
			if($user['seen'] >= (time() - Lib::GetSetting('sess.gc_maxlifetime')))
				$temp['online_status']	= $this->lang['L_ONLINE'];
			else
				$temp['online_status']	= $this->lang['L_OFFLINE'];

			$temp['user_num_posts']		= $user['posts'];
			$temp['user_rank']			= $user['rank'] != '' ? $user['rank'] : '--';

			$temp['avatar']				= $user['avatar'] != '' && $user['avatar'] != 0 && $temp['allow_avatars'] == 1 ? '<img src="Uploads/Avatars/'. $user['id'] .'.gif" border="0" alt="" />' : NULL;
			$temp['signature']			= $user['signature'] != '' && $temp['allow_sigs'] == 1 ? '<br /><br />'. stripslashes($user['signature']) : NULL;
			
			/* Get this users's ranks */
			foreach($this->dba->Query("SELECT * FROM ". RANKS ." WHERE group_id = (SELECT group_id FROM ". USER_IN_GROUP ." WHERE id = ". $user['id'] .")") as $rank) {	
				$temp['user_ranks'] .= $rank['rank'] .' <br />';
			}
			foreach($this->dba->Query("SELECT * FROM ". RANKS ." WHERE user_id = ". $user['id'] ) as $rank) {
				$temp['user_ranks'] .= $rank['rank'] .' <br />';
			}
		} else {
			$temp['online_status']		= '--';
			$temp['user_num_posts']		= '--';
			$temp['user_rank']			= '--';
		}

		if($this->session['user']['perms'] & ADMIN) {
			$temp['delete'] = '<a href="admin.php?act=delete_single&amp;type=1&amp;id='. $temp['id'] .'"><img src="Images/'. $this->settings['imageset'] .'/Buttons/delete.gif" alt="" border="0" /></a>';
		} 
		if($this->session['user']['perms'] >= MOD) {
			$temp['lock'] = '<a href="admin.php?act=lock_thread&amp;id='. $temp['id'] .'"><img src="Images/'. $this->settings['imageset'] .'/Buttons/lock.gif" alt="" border="0" /></a>';
		}
		
		$temp['created']				= relative_time($temp['created']);
		$temp['num_children']			= ($temp['row_right'] - $temp['row_left'] - 1) / 2;
		
		$temp['name']					= stripslashes($temp['name']);
		$temp['icon']					= !$temp['icon'] ? 'clear.gif' : $temp['icon'];
		
		$bbcode							= new BBParser(stripslashes($temp['body_text']), TRUE);
		$temp['body_text']				= $bbcode->QuickExecute();

		$temp['edited']					= intval($temp['edited']) != 0 ? '<br /><br /><span class="smalltext"><em>'. $this->lang['L_EDITEDON']. '&nbsp;'. date("F j, Y, g:i a", $temp['edited']) .'</em></span>' : ' ';
		
		$temp['count']					= 1;
		$this->parent_count				= 1;

		$this->temp						= $temp;
		return $temp;		
	}
	
	public function Key() {
		return $this->threads->Key();
	}
	
	public function Next() {
		return $this->threads->Next();
	}
	
	public function Rewind() {
		return $this->threads->Rewind();
	}
	
	public function Valid() {
		return $this->threads->Valid();
	}
	public function GetChildren() {
		return new ReplyIterator($this->temp);
	}
}

/* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ */
/* @@ Class: Reply Iterator						@@ */
/* @@ Description: Iterate through a threads	@@ */
/* @@ replies.									@@ */
/* @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ */
class ReplyIterator implements Iterator {
	protected $post_info;
	protected $lang;
	protected $count = 1;
	protected $dba;
	protected $session;
	protected $settings;

	public function __construct($thread) {
		global $lang;
		global $settings;
		$this->lang = $lang;
		$this->dba = DBA::Open();
		$this->session = $_SESSION;
		$this->settings = $settings;
		$limit = isset($_GET['limit']) ? intval($_GET['limit']) : $thread['postlimit'];
		$start = isset($_GET['start']) ? intval($_GET['start']) : NULL;
		$extra = (!is_null($limit) && !is_null($start)) ? "LIMIT ". $start .", ".($limit) : "LIMIT ". $thread['postlimit'];
		
		$this->post_info = $this->dba->Query("SELECT p.name as name, p.edited as edited, f.allowavatars as allow_avatars, f.allowsignatures as allow_sigs, p.id as id, p.row_left as row_left, p.created as created, p.row_type as row_type, p.row_right as row_right, p.body_text as body_text, p.poster_name as poster_name, p.poster_id as poster_id 
		FROM ". POSTS ." p, ". FORUMS ." f WHERE f.id = ". $thread['parent_id'] ." AND p.row_left > ". $thread['row_left'] ." AND p.row_right < ". $thread['row_right'] ." AND p.row_type = 4 ORDER BY p.created ASC $extra")->GetIterator();
	}
	public function Current() {
		$row = $this->post_info->Current();
		$this->count++;
		$row['count'] = $this->count;
		$row['created'] = relative_time($row['created']);
		
		
		if($row['poster_id'] != 0) {
			$user = $this->dba->GetRow("SELECT * FROM ". USERS ." WHERE id = ". $row['poster_id'] );
			if($user['seen'] >= (time() - Lib::GetSetting('sess.gc_maxlifetime')))
				$row['online_status']	= $this->lang['L_ONLINE'];
			else
				$row['online_status']	= $this->lang['L_OFFLINE'];

			$row['user_num_posts']		= $user['posts'];
			$row['user_rank']			= $user['rank'] != '' ? $user['rank'] : '--';

			$row['avatar']				= $user['avatar'] != '' && $user['avatar'] != 0 && $row['allow_avatars'] == 1 ? '<img src="Uploads/Avatars/'. $user['id'] .'.gif" border="0" alt="" />' : ' ';
			$row['signature']			= $user['signature'] != '' && !is_null($user['signature']) && $row['allow_sigs'] == 1 ? '<br /><br />'. stripslashes($user['signature']) : ' ';
			/* Set the user ranks */
			$row['user_ranks']				= '';
			foreach($this->dba->Query("SELECT * FROM ". RANKS ." WHERE group_id = (SELECT group_id FROM ". USER_IN_GROUP ." WHERE id = ". $user['id'] .")") as $rank) {
				$row['user_ranks'] .= $rank['rank'] .' <br />';
			}
			foreach($this->dba->Query("SELECT * FROM ". RANKS ." WHERE user_id = ". $user['id'] ) as $rank) {
				$row['user_ranks'] .= $rank['rank'] .' <br />';
			}
			if($this->session['user']['perms'] & ADMIN) {
				$row['delete'] = '<a href="admin.php?act=delete_single&amp;type=1&amp;id='. $row['id'] .'"><img src="Images/'. $this->settings['imageset'] .'/Buttons/delete.gif" alt="" border="0" /></a>';
			}
		} else {
			$row['online_status']		= '--';
			$row['user_num_posts']		= '--';
			$row['user_rank']			= '--';
			$row['user_ranks']			= '';
		}
		$row['name'] = stripslashes($row['name']);
		
		$bbcode = new BBParser(stripslashes($row['body_text']), TRUE);		
		$row['body_text'] = $bbcode->QuickExecute();

		$row['edited']					= intval($row['edited']) != 0 ? '<br /><br /><span class="smalltext"><em>'. $this->lang['L_EDITEDON']. '&nbsp;'. date("F j, Y, g:i a", $row['edited']) .'</em></span>' : ' ';

		return $row;		
	}
	
	public function Key() {
		return $this->post_info->Key();
	}
	
	public function Next() {
		return $this->post_info->Next();
	}
	
	public function Rewind() {
		return $this->post_info->Rewind();
	}
	
	public function Valid() {
		return $this->post_info->Valid();
	}
}

?>