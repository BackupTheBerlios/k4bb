<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     viewthread.php
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
	protected $dba;
	/*public function ThreadedView($id, $img_dir) {
		$right = array();

		$view = '<script language="javascript" type="text/javascript">';
		$i = 1;
		/$rows = $this->dba->Query("SELECT * FROM ". POSTS ." WHERE row_left >= (SELECT row_left FROM ". POSTS ." WHERE id = {$id}) AND row_right <= (SELECT row_right FROM ". POSTS ." WHERE id = {$id}) ORDER BY row_left ASC");
		$num_rows = $rows->NumRows();
		
		// Base level in this case is anything above 2
		$thread = $rows->FetchRow();
		$base = $thread['row_level'];
		foreach($rows as $row) {
			$row['level'] = $row['row_level'] - $base + 1;
			$view .= 'drawRow(\''. $row['name'] .'\', \''. $row['id'] .'\', \''. $row['poster_name'] .'\', \''. $row['poster_id'] .'\', \''. relative_time($row['created']) .'\', new Array('. str_repeat('\'0\',', $row['row_level']-1) .'\'1\'), \''. $img_dir .'\');';
		}

		$view .= '</script>';

		return $view;
	}*/
	public function Execute(Template $template, Session $session, $request) {

		$this->dba = DBA::Open();
		$suspend = ($session['user']['perms'] & ADMIN) ? 1 : 0;

		$id = isset($request['id']) ? intval($request['id']) : 0;
		
		if($id == 0)
			return new Error($template['L_INVALIDTHREADID'], $template);
		
		$forum = new Forum;

		/* Update the posts table, set this thread's num views = +1 */
		$this->dba->Query("UPDATE ". POSTS ." SET views = views+1 WHERE id = $id");

		/* Get some info about the thread */
		$row = $this->dba->GetRow("SELECT (SELECT COUNT(*) FROM ". POLLVOTES ." WHERE poll_id = $id) as poll_votes, (SELECT poll_question FROM ". POSTS ." WHERE id = $id) as poll_question, (SELECT attach FROM ". POSTS ." WHERE id = $id) as attach, (SELECT poll FROM ". POSTS ." WHERE id = $id) as poll, COUNT(*) as total_posts, (SELECT parent_id FROM ". POSTS ." WHERE id = $id) as parent_id, (SELECT id FROM ". POSTS ." WHERE id = $id AND row_type = 2) as id, (SELECT name FROM ". POSTS ." WHERE id = $id AND row_type = 2) as thread_name FROM ". POSTS ." WHERE row_left >= (SELECT row_left FROM ". POSTS ." WHERE id = $id) AND row_right <= (SELECT row_right FROM ". POSTS ." WHERE id = $id)");
		if(!$row['id']) {
			
			/* Create the ancestors bar (if we run into any trouble */
			$template = CreateAncestors($template, $template['L_INFORMATION'], $suspend);

			return new Error($template['L_INVALIDTHREADID'], $template);
		}

		/* Get the forum that we're in */	
		$f = $this->dba->GetRow("SELECT * FROM ". FORUMS ." WHERE id = ". $row['parent_id'] );
		
		/* Is this forum password-protected? */
		if($f['private'] == 1 && @$_SESSION['forum_logged'] != $f['id']) {
			$template['forum_id'] = $f['id'];
			$template->content = array('file' => 'forum_login.html');
		} else {

			/* Set the user's permissions */
			$user_perms = isset($session['user']['perms']) ? $session['user']['perms'] : ALL;
			
			/* Set the templates ancestors bar */
			$template = ThreadAncestors($template, $suspend);
			
			/* Check if we're aloud to see this thread */
			if($user_perms >= $f['can_read']) {

				$template['total_posts'] = $row['total_posts'];
				/* The following things I have not decided to completely pursue */
				/*
				if(isset($request['display']) && ($request['display'] == 'hybrid' || $request['display'] == 'threaded')) {
					$template->threaded_list = array('file' => 'threaded_view.html');
					$template['threaded_view'] = $this->ThreadedView($id, $template['IMG_DIR']);
				}
				
				if(isset($request['display']) && ($request['display'] != 'linear'))
					$template->thread_pagination = array('hide' => TRUE);
				*/

				$template->thread = new RepliesIterator($id);

				if($row['attach'] == 0) {
					$template->attach = array('hide' => TRUE);
				} else {
					if(is_dir('Uploads/'.$id)) {
						if($dir = dir('Uploads/'.$id)) {
							$array = array();
							while (false !== ($file = $dir->read())) {
								if($file != '.' && $file != '..') {
									$vars = explode('.', $file);
									$ext = $vars[count($vars)-1];
									$array[] = array('name' => $file, 'post_id' => $id, 'img' => $ext);
								}
							}
							$template->attachments = $array;

						} else {
							$template->attach = array('hide', TRUE);
						}
					}
				}
				
				$dba = DBA::Open();

				if($row['poll'] == 0) {
					$template->poll = array('hide' => TRUE);
				} else {
					if($user_perms >= $f['can_vote']) {
						if(!isset($request['results'])) {
							if($dba->GetValue("SELECT COUNT(*) FROM ". POLLVOTES ." WHERE user_id = ". $session['user']['id'] ." AND poll_id = ". intval($request['id']) ) == 0)
								$template->poll_results = array('hide' => TRUE);
							else
								$template->poll_vote = array('hide' => TRUE);
						} else {
							$template->poll_vote = array('hide' => TRUE);
						}
					} else {
						$template->poll_vote = array('hide' => TRUE);
					}
				}
				
				$template->poll_options		= new PollIterator($id);
				$template['thread_id']		= $id;
				$template['poll_question']	= stripslashes($row['poll_question']);
				$template['total_votes']	= $row['poll_votes'];
				$template['postlimit']		= $f['postsperpage'];
				
				

				/* I surpressed the error because I implemented this before updating the db */
				$display = intval(@$session['user']['thread_display']) == 0 ? 'linear' : 'vertical';
				
				if(!isset($request['display'])) {
					$template->content = array('file' => 'thread_'. $display .'.html');
				} else if(isset($request['display']) && ($request['display'] == 'vertical')) {
					$template->content = array('file' => 'thread_vertical.html');
				} else {
					$template->content = array('file' => 'thread_linear.html');
				}
				
				/* Get the similar Threads */
				$similar_threads = $dba->Query("SELECT p.name as name, p.poster_name as poster_name, p.poster_id as poster_id, ((p.row_right-p.row_left-1)/2) AS num_replies, p.id as id, p.last_reply as last_reply, p.reply_uname as reply_uname, p.reply_id as reply_id, p.reply_uid as reply_uid, f.name as forum_name, f.id as forum_id FROM ". POSTS ." p, ". FORUMS ." f WHERE p.forum_id = f.id AND p.parent_id = p.forum_id AND lower(p.name) LIKE lower('%". $dba->Quote(stripslashes($row['thread_name'])) ."%') AND p.id != $id LIMIT 10"); // p.name LIKE '". $row['thread_name'] ."'
				
				if($similar_threads->NumRows() > 0) {
					$template->similar_threads = array('file' => 'similar_threads.html');
					$template->similarthreads = $similar_threads;
				}

			} else {
				return new Error($template['L_PERMCANTREAD'], $template);
			}
		} // end check forum login required
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

$app	= new Forum_Controller('forum_base.html');

$app->ExecutePage();

?>