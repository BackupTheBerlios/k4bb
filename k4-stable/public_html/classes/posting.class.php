<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     posting.class.php
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

/* Class to add a thread */
class AddThread extends Event {
	protected $dba;
	
	protected function getNumOnLevel($parent_id) {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". POSTS ." WHERE parent_id = $parent_id");
	}
	protected function lastPostByUser($id) {
		return $this->dba->GetRow("SELECT * FROM ". POSTS ." WHERE poster_id = $id ORDER BY created DESC");
	}
	protected function Upload($dir, $files) {
		global $lang;
		$sum = 0;
		foreach($files as $key=>$val) {
			$sum += $_FILES[$val]['size'];
		}
		
		if($sum <= 2097152) {
			$rel_dir = 'Uploads/';
			$make_dir = FALSE;
			
			if(get_setting('ftp', 'use_ftp') || intval(get_setting('ftp', 'use_ftp')) == 1) {
				if($conn_id = ftp_connect(get_setting('ftp', 'server'))) {
					if(@ftp_login($conn_id, get_setting('ftp', 'username'), get_setting('ftp', 'password'))) { 
						@ftp_mkdir($conn_id, $rel_dir.$dir);
						@ftp_chmod($conn_id, 0777, $rel_dir.$dir);
						$make_dir = TRUE;
					}
				}
			} else {
				if(@mkdir($rel_dir.$dir))
					$make_dir = TRUE;
			}
			if($make_dir) {
				foreach($files as $key=>$file) {
					@move_uploaded_file($_FILES[$file]['tmp_name'], $rel_dir.$dir .'/'. $_FILES[$file]['name']);
				}
			}
		} else {
			return SetError::Set($lang['L_ERRORFILESTOOBIG']);
		}

		return TRUE;
	}
	public function Execute(Template $template, Session $session, $request) {
		
		/* Set the post vars session */
		$session['post_vars'] = $request;
		
		/* Create the ancestors bar (if we run into any trouble */
		$template = CreateAncestors($template, $template['L_POSTTHREAD']);

		/* Open a connection to the database */
		$this->dba = DBA::Open();

		/* Set the a variable to this user's permissions and id */
		$user_perms = isset($session['user']['perms']) ? $session['user']['perms'] : ALL;
		$user_id	= $session['user']['id'];

		/* Get our parent forum */
		try {
			$parent_id = intval($request['forum_id']);
			@$parent = $this->dba->GetRow("SELECT * FROM ". FORUMS ." WHERE id = $parent_id");
		} catch(DBA_Exception $e) {
			return new TplException($e, $template);
		}
		
		/* Quote all of the REQUEST variables */
		foreach($request as $key => $val) {
			$request[$key] = $this->dba->Quote($val);
		}

		/* Parse the Message */
		$parser = new BBParser(substr($request['message'], 0, $template['postmaxchars']));
		//$parser->addOmit('omit', 'omit');
		$request['message'] = $parser->Execute();
		
		/* Set the post icon */
		if(isset($request['posticon']) && intval($request['posticon']) != 0 && $request['posticon'] != '-1') {
			try {
				$posticon = $this->dba->GetValue("SELECT image FROM ". POSTICONS ." WHERE id = ". intval($request['posticon']) );
			} catch(DBA_Exception $e) {
				return new TplException($e, $template);
			}
		} else {
			$posticon = 'clear.gif';
		}
		
		/* Is it a poll, if so, deal with it. */
		
		$polloptions = array();
		$poll = 0;
		$poll_question = '';

		if(isset($request['polloptions'])) {
			if($user_perms >= $parent['can_pollcreate']) {
				$poll = 1;
				
				if($request['poll_question'] == '')
					return new Error($template['L_MUSTHAVEPOLLQUESTION'], $template);

				$poll_question = BB::Open($request['poll_question'])->Execute();
				
				$opts = explode("\n", $request['polloptions']);
				if(count($opts) <= $parent['maxpolloptions']) {
					foreach($opts as $key=>$option) {
						preg_match('~\[color=(.*?)\](.*?)\[\/color\]~is', $option, $matches);
						$polloptions[] = (array_key_exists(1, $matches)) ? array('color' => $matches[1], 'option' => $matches[2]) : array('color' => 'blue', 'option' => $option);
					}
				} else {
					return new Error(sprintf($template['L_TOOMANYPOLLOPTIONS'], count($opts), $parent['maxpolloptions']) . '<meta http-equiv="refresh" content="1; url='. $_SERVER['HTTP_REFERER'] .'">', $template);
				}
			}
		}

		/* Bring in the forums clas */
		$forum = new Forum;
		$stack = $forum->getForums();
		
		/* Check if the forum that we are adding this thread to is NOT the root forum */
		if($parent['row_left'] != 1) {

			/* Set a shorter version of the $parent variable */
			$f = $parent;

			
			/* Is this forum password-protected? */
			if($f['private'] == 1 && @$_SESSION['forum_logged'] != $f['id'] ) {
				$template['forum_id'] = $f['id'];
				$template->content = array('file' => 'forum_login.html');
			} else {

				/* Check if the forum is suspended or locked */
				if(((($f['suspend'] == 1) && ($session['user']['perms'] & ADMIN)) || ($f['suspend'] != 1)) && ($f['is_link'] != 1) && ((($f['row_lock'] != 1) || (($f['row_lock'] == 1) && ($session['user']['perms'] >= MOD))))) {
					
					/* Fix some cariables if they are not set */
					$request['attach_files'] = !isset($request['attach_files']) ? 0 : $request['attach_files'];

					/* The status of the Thread is sticky/announcement/normal */
					$status = isset($request['status']) ? intval($request['status']) : 1;
					
					/* Check if the user has permission to make sticky or announcement threads */
					if($status == 2) {
						$status = $user_perms >= $f['can_sticky'] ? 2 : 1;
					} else if($status == 3) {
						$status = $user_perms >= $f['can_announce'] ? 3 : 1;
					}
					
					/* Get the number of threads on the same level as this one */
					if($this->getNumOnLevel($parent_id) > 0) {
						$left = $parent['row_right'];
					} else {
						$left = $parent['row_left']+1;
					}

					/* Set a depth variable, and the the right value */
					$depth = $parent['row_level']+1;
					$right = $left+1;
					
					/* Timestamp */
					$time = time();
					
					/* If this user can post */
					if($user_perms >= $f['can_post']) {
						
						try {
							
							/* Make room for the thread in the Forums table by updating the right values */
							@$this->dba->Query("UPDATE ". FORUMS ." SET row_right = row_right+2 WHERE row_left < $left AND row_right >= $left"); // Good
							
							/* Keep updating the Forums table by changing all of the necessary left AND right values */
							@$this->dba->Query("UPDATE ". FORUMS ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= $left"); // Good
							
							/* Make room in the Posts table for this thread */
							@$this->dba->Query("UPDATE ". POSTS ." SET row_right = row_right+2 WHERE row_left < $left AND row_right >= $left"); 
							
							/* Keep updating the Posts table */
							@$this->dba->Query("UPDATE ". POSTS ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left > $left");

							/* Finally insert our thread into the Posts table */
							@$this->dba->Query("INSERT INTO ". POSTS ." (row_left, row_right, name, forum_id, parent_id, row_level, description, body_text, created, poster_name, poster_id, row_type, attach, icon, poll, poll_question, row_status) VALUES ($left, $right, '". stripslashes($request['title']) ."', ". $f['id'] .", $parent_id, $depth, '". $parser->Revert(stripslashes(substr($request['message'], 0, 50)))."..." ."', '". stripslashes($request['message']) ."', ". $time .", '". $session['user']['name'] ."', ". $session['user']['id'] .", 2, ". intval($request['attach_files']) .", '$posticon', $poll, '{$poll_question}', $status)");

						} catch(DBA_Exception $e) {
							return new TplException($e, $template);
						}

						/* Change the REQUEST title variable to make it shorter for the forums last post info */
						$request['title']	= strlen($request['title']) > 29 ? substr($request['title'], 0, 29).'...' : $request['title'];
						
						/* Get the id of the thread that we just inserted into the database */
						$last_post			= $this->lastPostByUser($user_id);
						$last_post_id		= $last_post['id'];
						
						try {

							/* Update the Forums post & thread count, and last post info for this thread*/
							@$this->dba->Query("UPDATE ". FORUMS ." SET posts = posts+1, threads = threads+1, thread_created = $time, thread_name = '". $request['title'] ."', thread_id = ". $last_post_id .", thread_uname = '". $session['user']['name'] ."', thread_uid = ". $session['user']['id'] ." WHERE id = $parent_id");
							
							/* Update the users's post count */
							if($user_id != 0) {
								@$this->dba->Query("UPDATE ". USERS ." SET posts = posts+1 WHERE id = ". $session['user']['id'] );
							}
						} catch(DBA_Exception $e) {
							return new TplException($e, $template);
						}
						/* If there are files to attach, try to attach them */
						if(intval($request['attach_files']) == 1) {
							if($user_perms >= $f['can_attach']) {
								if(@$this->Upload($last_post_id, array('attach1', 'attach2', 'attach3', 'attach4')) instanceof SetError) {
									$p = new Prune;

									/* Remove everything that we just added to the db */
									$p->KillSingle($last_post, 1);
									return new Error($upload->message, $template);
								}
							}
						}

						/* If there are poll options, add them to the database */
						if(isset($request['polloptions'])) {
							
							/* Does the user have permission to create the poll? */
							if($user_perms >= $f['can_pollcreate']) {
								foreach($polloptions as $option) {
									try {
										@$this->dba->Query("INSERT INTO ". POLLOPTIONS ." (poll_id, name, color) VALUES ($last_post_id, '". $option['option'] ."', '". $option['color'] ."')");
									} catch(DBA_Exception $e) {
										return new TplException($e, $template);
									}
								}
							}
						}
					} else {
						return new Error($template['L_PERMCANTPOST'], $template);
					}
					
					/* Assuming that we've made it this far, unset the post vars session */
					unset($session['post_vars']);

					/* If we've gotten to this point, reload the page to our recently added thread :) */
					return new Error($template['L_ADDEDTHREAD'] . '<meta http-equiv="refresh" content="1; url=viewthread.php?id='. $last_post_id .'">', $template);
				} else {
					return new Error($template['L_PERMCANTPOST'], $template);
				}
			} // end check forum login required
		} else {
			return new Error($template['L_ERRORPOSTING'], $template);
		}
	}
}



class AddReply extends Event {
	protected $dba;
	
	public function getNumOnLevel($parent_id) {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". POSTS ." WHERE parent_id = $parent_id");
	}
	public function lastPostByUser($id) {
		return $this->dba->GetValue("SELECT id FROM ". POSTS ." WHERE poster_id = $id ORDER BY created DESC");
	}
	public function GetForumByReply($parent_left, $parent_right) {
		$top_thread = $this->dba->GetRow("SELECT * FROM ". POSTS ." WHERE row_left <= $parent_left AND row_right >= $parent_right AND row_type = 2");
		return $top_thread['parent_id'];
	}
	public function Execute(Template $template, Session $session, $request) {
		/* Create the ancestors bar (if we run into any trouble */
		$template = CreateAncestors($template, $template['L_POSTREPLY']);

		/* Open a connection to the database */
		$this->dba = DBA::Open();

		/* Set the a variable to this user's permissions and id */
		$user_perms = isset($session['user']['perms']) ? $session['user']['perms'] : ALL;
		$user_id	= $session['user']['id'];
		
		/* Quote all of the REQUEST variables */
		foreach($request as $key => $val) {
			$request[$key] = $this->dba->Quote($val);
		}

		/* Parse the body text to replace bbcodes, emoticons, etc */
		$parser = new BBParser(substr($request['message'], 0, $template['postmaxchars']));
		//$parser->addOmit('omit', 'omit');
		$request['message'] = $parser->Execute();
		
		/* Get forums, etc */
		try {
			$forum = new Forum;
			$stack = $forum->getForums();
		} catch(DBA_Exception $e) {
			return new TplException($e, $template);
		}
		
		/* Get the id of whatever you are replying to */
		$parent_id = intval($request['replyto_id']);
		
		try {

			/* This gets a result from whatever the parent_id is */
			@$parent = $this->dba->GetRow("SELECT * FROM ". POSTS ." WHERE id = $parent_id"); // todo error checking
			
			/* Even though the $parent could be the thread, we still need to get the thread, because we don't want to check if it is or not the thread */
			@$thread = $this->dba->GetRow("SELECT * FROM ". POSTS ." WHERE row_left <= ". $parent['row_left'] ." AND row_right >= ". $parent['row_right'] ." AND row_type = 2");

			/* Get the forum from the thread's parent_id */
			@$f = $forum->getForum($thread['parent_id']);
		
		} catch(DBA_Exception $e) {
			return new TplException($e, $template);
		}

		/* I came into the weirdest problem.. It seems to be that sqlite_escape_string make that Ø when nothing is passed to it. */
		if(($request['title'] == 'Ø') || !$request['title'])
			$title = 'Re: '.stripslashes($this->dba->Quote($parent['name']));
		else
			$title = stripslashes($request['title']);
		
		/* Is this forum password-protected? */
		if($f['private'] == 1 && @$_SESSION['forum_logged'] != $f['id'] ) {
			$template['forum_id'] = $f['id'];
			$template->content = array('file' => 'forum_login.html');
		} else {

			/* Check if the forum is locked or suspended, and if it is one of the above, check if the user is an admin or a moderator */
			if(((($f['suspend'] == 1) && ($session['user']['perms'] & ADMIN)) || ($f['suspend'] != 1)) && (($thread['row_locked'] != 1) || (($thread['row_locked'] == 1) && ($f['is_link'] != 1) && ($session['user']['perms'] >= MOD))) && ((($f['row_lock'] != 1) || (($f['row_lock'] == 1) && ($session['user']['perms'] >= MOD))))) {
				
				/* If the parent_id is invalid */
				if($parent_id != 0 || !$parent_id) {
					
					/* Get the number of replies on the same level as this */
					if($this->getNumOnLevel($parent_id) > 0) {
						$left = $parent['row_right'];
					} else {
						$left = $parent['row_left']+1;
					}

					/* Get the depth and set the right value */
					$depth = $parent['row_level']+1;
					$right = $left+1;
					
					/* If this user has permission to post */
					if($user_perms >= $f['can_reply']) {
						
						/* Should we ammend to the thread? */
						if( ((($thread['row_right'] - $thread['row_left'] - 1) / 2 == 0) && $thread['poster_id'] == $session['user']['id'])) {
							try {
								
								/* Create new body text */
								$body_text = stripslashes($this->dba->Quote($thread['body_text'])) . "\n<br />\n<br /><!-- OMIT --><strong>". $title ."</strong>\n<br />". stripslashes($request['message']) ."<!-- /OMIT -->";

								/* Ammend to the thread */
								@$this->dba->Query("UPDATE ". POSTS ." SET body_text = '{$body_text}' WHERE id = ". $thread['id']);

							} catch(DBA_Exception $e) {
								return new TplException($e, $template);
							}
						} else {
							
							$time = time();

							try {
								/* Make space in the Forums table for the reply */
								@$this->dba->Query("UPDATE ". FORUMS ." SET row_right = row_right+2 WHERE row_left < $left AND row_right >= $left"); 
								
								/* Keep making space in the Forums table for the reply */
								@$this->dba->Query("UPDATE ". FORUMS ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= $left");
								
								/* Make space in the Posts table for the reply */
								@$this->dba->Query("UPDATE ". POSTS ." SET row_right = row_right+2 WHERE row_left < $left AND row_right >= $left"); 
								
								/* Keep making space in the Posts table for the reply */
								@$this->dba->Query("UPDATE ". POSTS ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= $left");
							
								/* Finally Insert the reply into the database */
								@$this->dba->Query("INSERT INTO ". POSTS ." (row_left, row_right, name, parent_id, row_level, body_text, created, poster_name, poster_id, row_type, forum_id) VALUES ($left, $right, '{$title}', $parent_id, $depth, '". stripslashes($request['message']) ."', ". time() .", '". $session['user']['name'] ."', ". $session['user']['id'] .", 4, ". $f['id'] .")");

								/* Set the last reply info for the thread info */
								@$this->dba->Query("UPDATE ". POSTS ." SET last_reply = ". $time .", reply_uid = ". $session['user']['id'] .", reply_uname = '". $session['user']['name'] ."' WHERE id = ". $thread['id']);
													
								/* get the last post by this user */
								$last_post_id = @$this->lastPostByUser($session['user']['id']);
								
								/* Update the post count for the forum */
								$this->dba->Query("UPDATE ". FORUMS ." SET posts = posts+1, thread_created = $time, thread_name = '". $title ."', thread_id = ". $thread['id'] .", thread_uname = '". $session['user']['name'] ."', thread_uid = ". $session['user']['id'] ." WHERE id = ". $f['id'] );
								
								/* Update the user count if the user exists :) */
								if($user_id != 0) {
									$this->dba->Query("UPDATE ". USERS ." SET posts = posts+1 WHERE id = ". $session['user']['id'] );
								}
							} catch(DBA_Exception $e) {
								return new TplException($e, $template);
							}
						}
					} else {
						return new Error($template['L_PERMCANTREPLY'], $template);
					}

					/* If we've gotten this far, reload the page :) */
					return new Error($template['L_SUCCESSADDINGREPLY'] . '<meta http-equiv="refresh" content="1; url=viewthread.php?id='. $thread['id'] .'">', $template);

				} else {
					return new Error($template['L_ERRORREPLYING'], $template);
				}
			} else {
				return new Error($template['L_PERMCANTREPLY'], $template);
			}
		} // end check forum login required
	}
}

class UpdatePost extends Event {

	protected $dba;

	public function Execute(Template $template, Session $session, $request) {
		/* Create the ancestors bar (if we run into any trouble */
		$template = CreateAncestors($template, $template['L_POSTREPLY']);

		/* Open a connection to the database */
		$this->dba = DBA::Open();

		/* Set the a variable to this user's permissions and id */
		$user_perms = isset($session['user']['perms']) ? $session['user']['perms'] : ALL;
		$user_id	= $session['user']['id'];

		/* Get our parent forum */
		try {
			@$post		= $this->dba->GetRow("SELECT * FROM ". POSTS ." WHERE id = ". intval($request['post_id']));
			@$thread	= $this->dba->GetRow("SELECT * FROM ". POSTS ." WHERE row_left <= ". $post['row_left'] ." AND row_right >= ". $post['row_right']);
		} catch(DBA_Exception $e) {
			return new TplException($e, $template);
		}

		/* Parse the Message */
		$request['message'] = BB::Open($request['message'])->Execute();
		
		/* Quote all of the REQUEST variables */
		foreach($request as $key => $val) {
			$request[$key] = $this->dba->Quote($val);
		}
		
		/* Set the post icon */
		if(isset($request['posticon']) && intval($request['posticon']) != 0 && $request['posticon'] != '-1') {
			try {
				$posticon = $this->dba->GetValue("SELECT image FROM ". POSTICONS ." WHERE id = ". intval($request['posticon']) );
			} catch(DBA_Exception $e) {
				return new TplException($e, $template);
			}
		} else {
			$posticon = 'clear.gif';
		}

		if(is_array($post) && !empty($post)) {
					
			/* Try and get the forum */
			try {
				@$f = new Forum;
				@$forum = $f->getForum($post['forum_id']);
			} catch(DBA_Exception $e) {
				return new TplException($e, $template);
			}
			if(($user_perms >= $forum['can_edit']) && ($session['user']['id'] == $post['poster_id'] || $user_perms & ADMIN)) {
				try {
					@$this->dba->Query("UPDATE ". POSTS ." SET name = '". $request['title'] ."', body_text = '". $request['message'] ."', icon = '". $posticon ."', edited = ". time() ." WHERE id = ". $post['id']);
				} catch(DBA_Exception $e) {
					return new TplException($e, $template);
				}
				
				/* If we've gotten to this point, reload the page to our recently added thread :) */
				return new Error($template['L_UPDATEDPOST'] . '<meta http-equiv="refresh" content="1; url=viewthread.php?id='. $thread['id'] .'">', $template);

			} else {
				return new Error($template['L_PERMSEDITPOST'], $template);
			}
		} else {
			return new Error($template['L_INVALIDPOSTID'], $template);
		}
	}
}

?>