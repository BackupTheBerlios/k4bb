<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     viewforum.php
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
		if(isset($request['id'])) {
			
			/* Instanciate the forum class */
			$forum	= new Forum;

			$id = intval($request['id']);
			$suspend = ($session['user']['perms'] & ADMIN) ? 1 : 0;

			$row = $forum->getForum($id);
			
			/* Simple redirect to index.php */
			if($row['row_left'] == 1)
				exit(header("Location: index.php"));
			
			/* Check if the Category or forum exists */
			if(!$row)
				return new Error($template['L_FORUMDOESNTEXIST'], $template);

			/* Create the ancestors list with the results from the get category query */
			$template = CreateAncestors($template, $row, $suspend);
			
			/* Are we looking at forums within a category? */
			if($row['row_level'] == 1) {
				$template->welcome_msg = array('hide' => TRUE);
				$template->content	= array('file' => 'forums.html');
				$template->categories = new ForumList($id, $suspend);
			}
			/* are we looking at threads within a forum? */
			else if($row['row_level'] >= 2) {

				/* Make sure that the forum isn't a link */
				if($row['is_link'] == 0) {
					/* Is this forum password-protected? */
					if($row['private'] == 1 && @$_SESSION['forum_logged'] != $row['id']) {
						$template['forum_id'] = $row['id'];
						$template->content = array('file' => 'forum_login.html');
					} else {

						/* Get the user permissions */
						$user_perms = isset($session['user']['perms']) ? $session['user']['perms'] : ALL;
						
						/* If the current user is allowed to read the forums */
						if($user_perms >= $row['can_view']) {

							if($row['subforums'] > 0) {
								$template->subforums = new SubForumList($row, FALSE, $suspend);
							} else {
								$template->sub_forums = array('hide' => TRUE);
							}

							/* Set the template */
							$template->content	= array('file' => 'forum.html');
							
							if(isset($request['sort']) && isset($request['order'])) {
								$template[$request['sort'] .'_sort'] = $request['order'] == 'ASC' ? '&nbsp;<img src="Images/'. $template['imageset'] .'/Icons/arrow_up.gif" alt="" border="0" />' : '&nbsp;<img src="Images/'. $template['imageset'] .'/Icons/arrow_down.gif" alt="" border="0" />';
							}
							if(!isset($request['order'])) {
								$template['order'] = 'DESC';
							} else {
								$template['order'] = $request['order'] == 'DESC' ? 'ASC' : 'DESC';
							}

							/* This could return an instance of the SetError class, so we'll check that */
							$threads = new ThreadIterator($session);
							
							/* Check if there was an error */
							if($threads instanceof SetError)
								return new Error($threads->message, $template);
							else
								$template->threads = $threads;
							
							$template['total_posts'] = $row['threads'];
							$template['forum_id'] = $row['id'];
							$template['postlimit'] = $row['threadsperpage'];
							$template['pag_start'] = isset($request['start']) ? intval(@$request['start']) : 0;
						} else {
							return new Error($template['L_PERMCANTVIEW'], $template);
						}
					}
				} else {
					DBA::Open()->Execute("UPDATE ". FORUMS ." SET referals = referals+1 WHERE id = ". $row['id']);
					header("Location: ". $row['link_href']);
				}
			} else {
				return new Error($template['L_ERRORVIEWFORUM'], $template);
			}
		} else {
			return new Error($template['L_INVALIDFORUM'], $template);
		} 
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

$app	= new Forum_Controller('forum_base.html');
$app->AddAction('a_postthread', new Action('posting.php', 'post_thread&amp;id='.intval(@$_GET['id'])));

$app->ExecutePage();

?>