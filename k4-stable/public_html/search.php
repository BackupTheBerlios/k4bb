<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     search.php
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

require 'forum.inc.php';

class DefaultEvent extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Set the template */
		$template->content = array('file' => 'search.html');
		
		/* Make the ancestors Bar */
		$template = CreateAncestors($template, $template['L_SEARCH']);
		
		/* Set some date and time stuff */
		$template['yesterday']		= time()-(3600*24);
		$template['oneweek']		= time()-(3600*24*7);
		$template['twoweeks']		= time()-(3600*24*7*2);
		$template['threeweeks']		= time()-(3600*24*7*3);

		/* Set the list of forums in the search template */
		$template->forums = new SearchForumList;
		
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class Search extends Event {
		
	protected $exact		= TRUE;
	protected $dba;
	protected $user_count;
	
	public function __construct() {
		$this->dba = DBA::Open();
	}

	public function Execute(Template $template, Session $session, $request) {
		
		/* Fool around with the session and the request variables so pagination works throughout */
		if(isset($request['keywords']) || isset($request['username']))
			$session->OffsetSet('search_results', $request);
		else
			$session['search_results'] = isset($session['search_results']) ? $session['search_results'] : $request;
		
		$request = $session['search_results'];

		/* Set the template */
		$template->content = array('file' => 'search_results.html');

		/* Set the ancestors bar */
		$template = CreateAncestors($template, $template['L_SEARCHRESULTS']);
		
		/* Do the search */
		if((isset($request['keywords']) && $request['keywords']) || (isset($request['username']) && $request['username'])) {
			
			/* Set a variable to the column that we will search against */
			$to_use = '';

			/* Check to find out which field we want to search for */
			if($request['keywords'] != '' && $request['username'] != '')
				$to_use = 1;
			
			if($to_use == '')
				$to_use = $request['keywords'] != '' ? 1 : 2;

			/* Auto quote out invalid characters */
			foreach($request as $key=>$val) {
				if($key != 'forums')
					$request[$key] = $this->dba->Quote($val);
			}
			
			
			$this->exact = isset($request['exact']) ? TRUE : FALSE;
			
			/* Get the forums to search in */
			$forums = isset($request['forums']) ? $request['forums'] : array();
			
			/* Check if the user has actually selected any forums to search in */
			if(count($forums) == 0)
				return new Error($template['L_FORUMDOESNTEXIST'], $template);
			
			/* Set up this section of the query */
			$query_users = '';
			$query_posts = '';

			/* If we are searching using keywords */
			if($to_use == 1) {
			
				$keywords = htmlspecialchars($request['keywords']);
				
				$template['search_terms'] = $template['L_KEYWORDS'] .': '. $keywords;

				$field = intval($request['search_where']) == 1 ? 'body_text' : 'name';

				$query_posts = " lower(". $field .") LIKE lower('%". $keywords ."%') ";
				
			/* If we are searching by poster names */
			} else if($to_use == 2) {
				
				$template['search_terms'] = $template['L_USERNAME'] .': '. $request['username'];

				/* Get the user(s) */
				$users = $this->GetUsers($this->dba->Quote($request['username']));
				
				$i = 1;
				
				if($users instanceof SetError) {
					return new Error($users->message, $template);
				} else {
					
					/* Loop through the users */
					foreach($users as $user) {
						
						/* Make this section of the query */
						$query_users .= ($i != $this->user_count) ? "poster_name = '". $user['name'] ."' OR " : "poster_name = '". $user['name'] ."'";

						/* increment the $i variable */
						$i++;
					}
					
					/* If we are just looking for threads by the user */
					if(intval($request['user_where']) == '2')
						$query_users .= " AND row_type = 2 ";

				}				
			}

			$query_forums = '';

			/* Loop the forums and make that part of the query */
			for($f = 0; $f < count($forums); $f++) {
				
				/* Make the forums part of the query */
				$query_forums .= ($f != count($forums)-1) ? "id = ". $forums[$f] ." OR " : "id = ". $forums[$f];
			}
			
			$at_least = '';
			//$at_least = intval($request['at_least']) == 0 ? '<=' : '>=';
			//$at_least = " AND (right-left-1)/2 $at_least ". intval(@$request['num_posts']) ." ";

			/* set the display order */
			$order = intval($request['sort']) == 1 ? ' ORDER BY created DESC' : ' ORDER BY created ASC';
			
			/* Set from how long ago the posts will be */
			$oldnew = intval($request['posts_oldnew']) == 1 ? '>=' : '<=';
			$from = " AND created $oldnew '". intval($request['posts_from']) ."' ";
			
			$template['postlimit']		= 30;
			$template['total_posts']	= DBA::Open()->GetValue("SELECT COUNT(*) FROM ". FORUMS ." WHERE $query_forums");

			$template->search_results	= new SearchResultsIterator($query_forums, $query_posts, $query_users, $order, $at_least, $from);

		} else {

			/* Return an error if they have not put anything to search for */
			return new Error($template['L_MUSTDEFINESEARCH'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}

	private function GetUsers($name) {

		/* Get the user(s) */
		$users = $this->exact == FALSE ? $this->dba->Query("SELECT name FROM ". USERS ." WHERE lower(name) LIKE lower('". $this->dba->Quote($name) ."%') GROUP BY name ORDER BY name DESC") : $this->dba->GetRow("SELECT name FROM ". USERS ." WHERE name = '". $name ."'");

		if(method_exists($users, 'NumRows')) {
			$this->user_count = $users->NumRows();
		} else if(is_array($users) && array_key_exists('name', $users)) {
			$this->user_count = 1;
			$users = array($users);
		} else {
			global $lang;
			return SetError::Set($lang['L_USERDOESNTEXIST']);
		}
		
		return $users;

	}
}

class SearchResultsIterator implements Iterator {
	protected $forums;
	protected $keywords;
	protected $users;
	protected $order;
	protected $forum_id;
	protected $at_least;
	protected $from;

	public function __construct($forums, $keywords, $users, $order, $at_least, $from) {
		$this->keywords		= $keywords;
		$this->users		= $users;
		$this->order		= $order;
		$this->at_least		= $at_least;
		$this->from			= $from;

		$limit = isset($request['limit']) ? intval($request['limit']) : NULL;
		$start = isset($request['start']) ? intval($request['start']) : NULL;
		
		$db_type = get_setting(get_setting('application', 'dba_name'), 'type');

		$proper_limit = $db_type == 'pgsql' ? "LIMIT $limit OFFSET $start" : "LIMIT $start, $limit";

		$extra = (!is_null($limit) && !is_null($start)) ? $proper_limit : "LIMIT 30";

		$this->forums		= DBA::Open()->Query("SELECT * FROM ". FORUMS ." WHERE $forums $extra")->GetIterator();
	}
	
	public function Current() {
		$temp = $this->forums->Current();
		$this->forum_id = $temp['id'];
		
		$temp['forum_name'] = stripslashes($temp['name']);
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
		/* Pagination */
		$limit = isset($_GET['limit']) ? intval($_GET['limit']) : NULL;
		$start = isset($_GET['start']) ? intval($_GET['start']) : NULL;
		$extra = (!is_null($limit) && !is_null($start)) ? "LIMIT ". $start .", ".($limit) : "LIMIT 50";
		
		/* Return another iterator */
		return new PostResultsIterator(DBA::Open()->Query("SELECT * FROM ". POSTS ." WHERE forum_id = $this->forum_id AND $this->keywords $this->users $this->from $this->at_least $this->order $extra"));
	}
}

class PostResultsIterator implements Iterator {
	protected $posts;

	public function __construct($query) {
		$this->posts = $query->GetIterator();
	}
	
	public function Current() {
		$temp						= $this->posts->Current();
		global $lang;
		
		/* Strip slashes on the name */
		$temp['name']				= stripslashes($temp['name']);
		
		/* Set stuff that has to do with this post */
		$temp['num_children'] = ($temp['row_right'] - $temp['row_left'] - 1) / 2;
		$temp['last_reply'] = $temp['last_reply'] ? relative_time($temp['last_reply']) : relative_time($temp['created']);
		$temp['reply_uid'] = $temp['reply_uid'] != '' ? $temp['reply_uid'] : $temp['poster_id'];
		$temp['reply_uname'] = $temp['reply_uname'] ? $temp['reply_uname'] : $temp['poster_name'];

		/* Write Poll: if the current thread is a poll */
		if($temp['poll'] == 1)
			$temp['name'] = $lang['L_POLL'].':&nbsp;'. $temp['name'];
		
		/* Write out Sticky or Announcement depending on what this thread is */
		if($temp['row_status'] == 2)
			$temp['name'] = $lang['L_STICKY'].':&nbsp;'. $temp['name'];
		else if($temp['row_status'] == 3)
			$temp['name'] = $lang['L_ANNOUNCEMENT'] .':&nbsp;'. $temp['name'];
		
		/* If there are attachments, put out that little paperclip image */
		if($temp['attach'] == 1)
			$temp['name'] = '<img src="Images/'. get_setting('template', 'imgfolder') .'/Icons/paperclip.gif" border="0" style="float:left;" alt="'. $lang['L_ATTACHMENTS'] .'" title="'. $lang['L_ATTACHMENTS'] .'" />&nbsp;'. $temp['name'];

		return $temp;
	}
	
	public function Key() {
		return $this->posts->Key();
	}
	
	public function Next() {
		return $this->posts->Next();
	}
	
	public function Rewind() {
		return $this->posts->Rewind();
	}
	
	public function Valid() {
		return $this->posts->Valid();
	}
}

class SearchForumList implements Iterator {
	protected $forums;

	public function __construct() {
		$this->forums = DBA::Open()->Query("SELECT * FROM ". FORUMS ." WHERE row_left > 1 AND row_level > 1 ORDER BY row_left ASC")->GetIterator();
	}
	
	public function Current() {
		$temp = $this->forums->Current();
		
		$temp['name'] = str_repeat("&nbsp;&nbsp;&nbsp;", intval($temp['row_level']-1)) . stripslashes($temp['name']);
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
}

$app	= new Forum_Controller('forum_base.html');

$app->AddEvent('search', new Search);

$app->ExecutePage();

?>