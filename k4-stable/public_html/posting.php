<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     posting.php
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
		/* Default event is to post. */
		

		/* If the action is to post a thread */
		if($request['act'] == 'post_thread' && isset($request['id'])) {
			
			$f = new Forum;
			
			/* Try to get the forum from the REQUEST id variable */
			$forum = $f->getForum(intval($request['id']));
			
			/* Is this forum password-protected? */
			if($forum['private'] == 1 && @$_SESSION['forum_logged'] != $forum['id']) {
				$template['forum_id'] = $forum['id'];
				$template->content = array('file' => 'forum_login.html');
			} else {

				/* Set the user's permissions */
				$user_perms = isset($session['user']['perms']) ? $session['user']['perms'] : ALL;
				
				/* Check if the forum is suspended or locked */
				if(((($forum['suspend'] == 1) && ($user_perms & ADMIN)) || ($forum['suspend'] != 1)) && ((($forum['row_lock'] != 1) || (($forum['row_lock'] == 1) && ($user_perms >= MOD))))) {
					
					/* Are we allowed to post polls? */
					if(isset($request['poll']) && $request['poll'] == TRUE) {
						if($forum['can_pollcreate'] == 0 && !($user_perms & ADMIN))
							return new Error($template['L_CANTPOSTPOLLSFORUM'], $template);
					}

					/* If the user has permission to post */
					if($user_perms >= $forum['can_post']) {
						
						/* Set the template */
						$template->content = array('file' => 'newthread.html');
						
						/* Check if the post_vars session has been set */
						if(isset($session['post_vars'])) {
							$template['posttitle']		= @$session['post_vars']['posttitle'];
							$template['poll_question']	= @$session['post_vars']['poll_question'];
							$template['polloptions']	= @$session['post_vars']['polloptions'];
							$template['message']		= @$session['post_vars']['message'];
						}

						/* Set some template variables */
						$template['forum_id'] = $request['id'];
						$template['user_name'] = $session['user']['name'];
						
						/* Hide specific fun features if they are not allowed */
						if($forum['allowbbcode'] == 0)
							$template->bbcode = array('hide' => TRUE);
						if($forum['allowsmilies'] == 0)
							$template->smilies = array('hide' => TRUE);
						if($forum['allowposticons'] == 0)
							$template->post_icons = array('hide' => TRUE);
						
						/* Set the thread action */
						$template['a_add_thread'] = new Action('posting.php', 'add_thread');

						/* Set the post icons and the emoticons */
						$template->posticons = DBA::Open()->Query("SELECT * FROM ". POSTICONS );
						$template->emoticons = DBA::Open()->Query("SELECT * FROM ". EMOTICONS );
						
						/* basic checking over this user's permissions */
						if($user_perms < $forum['can_pollcreate'])
							$template->poll_options = array('hide' => TRUE);
						
						/* If we're just posting a normal thread */
						if(!isset($request['poll']))
							$template->poll_options = array('hide' => TRUE);

						if($user_perms < $forum['can_sticky'])
							$template->can_sticky = array('hide' => TRUE);

						if($user_perms < $forum['can_announce'])
							$template->can_announce = array('hide' => TRUE);

						if($user_perms < $forum['can_attach'])
							$template->can_attach = array('hide' => TRUE);

						/* Hide the edit post part */
						$template->edit_post = array('hide' => TRUE);

					} else {
						return new Error($template['L_PERMCANTPOST'], $template);
					}
				} else {
					return new Error($template['L_PERMCANTPOST'], $template);
				}
			} // end check forum login required
			$template = CreateAncestors($template, $template['L_POSTTHREAD']);
		} else {
			return new Error($template['L_CHOOSETOPOSTTO'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class PostReply extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		$this->dba = DBA::Open();
		
		
		/* Set the user's permissions */
		$user_perms = isset($session['user']['perms']) ? $session['user']['perms'] : ALL;
	
		/* Check the action and id REQUEST variables */
		if($request['act'] == 'post_reply' && isset($request['id'])) {
			
			/* Create the ancestors bar */
			$template = CreateAncestors($template, $template['L_POSTREPLY']);

			/* $request['id'] isn't necissarily the thread's id */
			$post = $this->dba->GetRow("SELECT * FROM ". POSTS ." WHERE id = ". intval($request['id']) );
			
			/* Try to get the threads information */
			$thread = $this->dba->GetRow("SELECT * FROM ". POSTS ." WHERE row_left <= ". $post['row_left'] ." AND row_right >= ". $post['row_right'] ." AND row_type = 2");
			
			$f = new Forum;
			
			/* Try to get the forum from the threads information */
			$forum = $f->getForum(intval($thread['parent_id']));
			
			/* Is this forum password-protected? */
			if($forum['private'] == 1 && @$_SESSION['forum_logged'] != $forum['id']) {
				$template['forum_id'] = $forum['id'];
				$template->content = array('file' => 'forum_login.html');
			} else {

				/* Check if the forum is suspended or closed */
				if(((($forum['suspend'] == 1) && ($user_perms & ADMIN)) || ($forum['suspend'] != 1)) && (($thread['row_locked'] != 1) || (($thread['row_locked'] == 1) && ($user_perms >= MOD))) && ((($forum['row_lock'] != 1) || (($forum['row_lock'] == 1) && ($user_perms >= MOD))))) {

					/* If the user has permission to post */
					if($user_perms >= $forum['can_reply']) {
						
						/* Hide specific fun features if they are not allowed */
						if($forum['allowbbcode'] == 0)
							$template->bbcode = array('hide' => TRUE);
						if($forum['allowsmilies'] == 0)
							$template->smilies = array('hide' => TRUE);

						/* Set the template Emoticons */
						$template->emoticons = DBA::Open()->Query("SELECT * FROM ". EMOTICONS );
						
						/* Set the template */
						$template->content = array('file' => 'newreply.html');
						
						$id = intval($request['id']);
						
						/* Try to get the reply */
						$reply = $this->dba->GetRow("SELECT * FROM ". POSTS ." WHERE id = $id");
						
						/* If the reply is in fact a reply */
						if(isset($request['quote']) && $request['quote'] == 'true') {
							
							/* Parse the body text for BB code */
							$parser = new BBParser($reply['body_text']);
							
							/* Add a quote tag into the body text */
							$template['body_text'] = '[quote='. $reply['poster_name']. ']'.$parser->Revert($reply['body_text']).'[/quote]';
							if(!$reply['name'])
								$template['post_name'] = stripslashes(substr($reply['body_text'], 0, 50).'...');
							else
								$template['post_name'] = stripslashes($reply['name']);
						} else {
							$template['post_name'] = stripslashes($reply['name']); 
						}

						/* Set some basic template variables */
						$template['replyto_id'] = $request['id'];
						$template['user_name'] = $session['user']['name'];
					
					} else {
						return new Error($template['L_PERMCANTREPLY'], $template);
					}
				} else {
					return new Error($template['L_PERMCANTREPLY'], $template);
				}
			} // end check forum login required
		} else {
			return new Error($template['L_CHOOSETOREPLYTO'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class VoteOnPoll extends Event {
	public function Execute(Template $template, Session $session, $request) {
		

		/* Create the ancestors bar (if we run into any trouble */
		$template = CreateAncestors($template, $template['L_VOTEONPOLL']);

		/* Set the user's permissions */
		$user_perms = isset($session['user']['perms']) ? $session['user']['perms'] : ALL;
		
		$f = new Forum;

		/* Try and get the forum */
		$forum = $f->getForum(intval($request['id']));

		/* If we have permission to vote on this poll */
		if($user_perms >= $forum['can_vote']) {
			if(isset($request['option'])) {
				if(DBA::Open()->GetValue("SELECT COUNT(*) FROM ". POLLVOTES ." WHERE user_id = ". $session['user']['id'] ." AND poll_id = ". intval($request['id']) ) == 0) {
					if(DBA::Open()->Query("INSERT INTO ". POLLVOTES ." (poll_id, option_id, user_id) VALUES (". intval($request['id']) .", ". intval($request['option']) .", ". $session['user']['id'] .")"))
						header("Location: ". $_SERVER['HTTP_REFERER']);
				} else {
					return new Error($template['L_USERHASVOTED'], $template);
				}
			} else {
				return new Error($template['L_CHOOSEPOLLOPTION'], $template);
			}
		} else {
			return new Error($template['L_YOUNEEDPERMS'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class EditPost extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		$dba = DBA::Open();

		/* Create the ancestors bar (if we run into any trouble */
		$template = CreateAncestors($template, $template['L_EDITPOST']);

		/* Set the user's permissions */
		$user_perms = isset($session['user']['perms']) ? $session['user']['perms'] : ALL;
		
		if($session['user'] instanceof Member) {
			
			$id = intval(@$request['id']);

			if(isset($request['id']) && $id != 0) {

				try {
					@$post = $dba->GetRow("SELECT * FROM ". POSTS ." WHERE id = $id");
				} catch(DBA_Exception $e) {
					return new TplException($e, $template);
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
						
						/* Set the post icons and the emoticons */
						$template->posticons = DBA::Open()->Query("SELECT * FROM ". POSTICONS );
						$template->emoticons = DBA::Open()->Query("SELECT * FROM ". EMOTICONS );
						
						/* Hide the part of this template that has to do with replying */
						$template->post_thread = array('hide' => TRUE);

						/* Set the template */
						$template->content = array('file' => 'newthread.html');

						$parser = new BBParser($post['body_text']);
						$template['message'] = $parser->Revert($post['body_text']);
						$template['posttitle'] = $post['name'];

						/* Remove all of the extra features */
						$template->poll_options = array('hide' => TRUE);
						$template->post_options = array('hide' => TRUE);
						$template->can_attach = array('hide' => TRUE);
						
						/* Set the thread action */
						$template['a_add_thread'] = new Action('posting.php', 'update_post');

						/* Set the post id */
						$template['post_id'] = $post['id'];

					} else {
						return new Error($template['L_PERMSEDITPOST'], $template);
					}
				} else {
					return new Error($template['L_INVALIDPOSTID'], $template);
				}
			} else {
				return new Error($template['L_INVALIDPOSTID'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}


$app	= new Forum_Controller('forum_base.html');

//$app->AddAction('a_add_thread', new Action('posting.php', 'add_thread'));
$app->AddAction('a_add_reply', new Action('posting.php', 'add_reply'));
$app->AddEvent('post_reply', new PostReply);
$app->AddEvent('add_reply', new AddReply);
$app->AddEvent('add_thread', new AddThread);
$app->AddEvent('edit', new EditPost);
$app->AddEvent('update_post', new UpdatePost);
$app->AddEvent('vote', new VoteOnPoll);

$app->ExecutePage();

?>