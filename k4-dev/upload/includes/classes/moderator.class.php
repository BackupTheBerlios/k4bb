<?php
/**
* k4 Bulletin Board, moderator.class.php
*
* Copyright (c) 2005, Peter Goodman
*
* Permission is hereby granted, free of charge, to any person obtaining
* a copy of this software and associated documentation files (the
* "Software"), to deal in the Software without restriction, including
* without limitation the rights to use, copy, modify, merge, publish,
* distribute, sublicense, and/or sell copies of the Software, and to
* permit persons to whom the Software is furnished to do so, subject to
* the following conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
* BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
* ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
* CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*
* @author Peter Goodman
* @version $Id: moderator.class.php,v 1.1 2005/05/24 20:01:31 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class ModerateForum extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS;

		/* Check the request ID */
		if(!isset($request['id']) || !$request['id'] || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
			return TRUE;
		}
			
		$forum				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($request['id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
			return TRUE;
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_CANTMODNONFORUM'), FALSE);
			return TRUE;
		}

		/**
		 * Check for moderating permission
		 */
		
		if(!is_moderator($user, $forum)) {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setFile('content', 'login_form.html');
			$template->show('no_perms');
			return TRUE;
		}
		
		if(!isset($request['action']) || $request['action'] == '') {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_NEEDSELECTACTION'), FALSE);
			return TRUE;
		}

		if(!isset($request['topics']) || $request['topics'] == '') {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_NEESSELECTTOPICS'), FALSE);
			return TRUE;
		}

		$topics		= explode("|", $request['topics']);

		if(!is_array($topics) || count($topics) == 0) {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_NEESSELECTTOPICS'), FALSE);
			return TRUE;
		}

		$query_extra	= '';
		$move_extra		= '';
		$i				= 0;
		foreach($topics as $id) {
			$query_extra .= $i == 0 ? ' ' : ' OR ';
			$query_extra .= 'topic_id = '. intval($id);
			$move_extra .= $i == 0 ? ' ' : ' OR ';
			$move_extra .= 't.topic_id = '. intval($id);
			
			$i++;
		}

		switch($request['action']) {

			/**
			 * Lock topics
			 */
			case 'lock': {
				
				if($user['perms'] < get_map($user, 'closed', 'can_add', array('forum_id' => $forum['id']))) {
					$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
					$template->setFile('content', 'login_form.html');
					$template->show('no_perms');
					return TRUE;
				}

				$dba->executeUpdate("UPDATE ". TOPICS ." SET topic_locked = 1 WHERE ". $query_extra);
				
				$template	= BreadCrumbs($template, $template->getVar('L_LOCKTOPICS'), $forum['row_left'], $forum['row_right']);
				$template->setInfo('content', $template->getVar('L_LOCKEDTOPICS'), TRUE);
				$template->setRedirect(referer(), 3);

				break;
			}

			/**
			 * Stick topics
			 */
			case 'stick': {
				
				if($user['perms'] < get_map($user, 'sticky', 'can_add', array('forum_id' => $forum['id']))) {
					$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
					$template->setFile('content', 'login_form.html');
					$template->show('no_perms');
					return TRUE;
				}

				$dba->executeUpdate("UPDATE ". TOPICS ." SET topic_type = ". TOPIC_STICKY .", topic_expire = 0 WHERE ". $query_extra);
				
				$template	= BreadCrumbs($template, $template->getVar('L_STICKTOPICS'), $forum['row_left'], $forum['row_right']);
				$template->setInfo('content', $template->getVar('L_STUCKTOPICS'), TRUE);
				$template->setRedirect(referer(), 3);
				
				break;
			}

			/**
			 * Announce topics
			 */
			case 'announce': {
				
				if($user['perms'] < get_map($user, 'announce', 'can_add', array('forum_id' => $forum['id']))) {
					$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
					$template->setFile('content', 'login_form.html');
					$template->show('no_perms');
					return TRUE;
				}

				$dba->executeUpdate("UPDATE ". TOPICS ." SET topic_type = ". TOPIC_ANNOUNCE .", topic_expire = 0 WHERE ". $query_extra);
				
				$template	= BreadCrumbs($template, $template->getVar('L_ANNOUNCETOPICS'), $forum['row_left'], $forum['row_right']);
				$template->setInfo('content', $template->getVar('L_ANNOUNCEDTOPICS'), TRUE);
				$template->setRedirect(referer(), 3);
				
				break;
			}

			/**
			 * Feature topics
			 */
			case 'feature': {
				
				if($user['perms'] < get_map($user, 'feature', 'can_add', array('forum_id' => $forum['id']))) {
					$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
					$template->setFile('content', 'login_form.html');
					$template->show('no_perms');
					return TRUE;
				}

				$dba->executeUpdate("UPDATE ". TOPICS ." SET is_feature = 1, topic_expire = 0 WHERE ". $query_extra);
				
				$template	= BreadCrumbs($template, $template->getVar('L_FEATURETOPICS'), $forum['row_left'], $forum['row_right']);
				$template->setInfo('content', $template->getVar('L_FEATUREDTOPICS'), TRUE);
				$template->setRedirect(referer(), 3);

				break;
			}

			/**
			 * Remove any special formatting on topics
			 */
			case 'normal': {
				
				if($user['perms'] < get_map($user, 'normalize', 'can_add', array('forum_id' => $forum['id']))) {
					$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
					$template->setFile('content', 'login_form.html');
					$template->show('no_perms');
					return TRUE;
				}

				$dba->executeUpdate("UPDATE ". TOPICS ." SET is_feature = 0, display = 1, queue = 0, topic_type = ". TOPIC_NORMAL .", topic_expire = 0, topic_locked = 0 WHERE ". $query_extra);
				
				$template	= BreadCrumbs($template, $template->getVar('L_SETASNORMALTOPICS'), $forum['row_left'], $forum['row_right']);
				$template->setInfo('content', $template->getVar('L_NORMALIZEDTOPICS'), TRUE);
				$template->setRedirect(referer(), 3);

				break;
			}

			/**
			 * Insert the topics into the moderator's queue for checking
			 */
			case 'queue': {

				if($user['perms'] < get_map($user, 'queue', 'can_add', array('forum_id' => $forum['id']))) {
					$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
					$template->setFile('content', 'login_form.html');
					$template->show('no_perms');
					return TRUE;
				}

				$dba->executeUpdate("UPDATE ". TOPICS ." SET queue = 1 WHERE ". $query_extra);
				
				$template	= BreadCrumbs($template, $template->getVar('L_QUEUETOPICS'), $forum['row_left'], $forum['row_right']);
				$template->setInfo('content', $template->getVar('L_QUEUEDTOPICS'), TRUE);
				$template->setRedirect(referer(), 3);

				break;
			}

			/**
			 * Subscribe to all of the selected topics
			 */
			case 'subscribe': {
				foreach($topics as $topic_id) {
					$is_subscribed		= $dba->getRow("SELECT * FROM ". SUBSCRIPTIONS ." WHERE user_id = ". intval($user['id']) ." AND topic_id = ". intval($topic_id));
					if(!is_array($is_subscribed) || empty($is_subscribed)) {
						$subscribe			= &$dba->prepareStatement("INSERT INTO ". SUBSCRIPTIONS ." (user_id,user_name,topic_id,forum_id,email,category_id) VALUES (?,?,?,?,?,?)");
						$subscribe->setInt(1, $user['id']);
						$subscribe->setString(2, $user['name']);
						$subscribe->setInt(3, $topic_id);
						$subscribe->setInt(4, $forum['id']);
						$subscribe->setString(5, $user['email']);
						$subscribe->setInt(6, $forum['category_id']);
						$subscribe->executeUpdate();
					}
				}

				$template	= BreadCrumbs($template, $template->getVar('L_SUBSCRIPTION'), $forum['row_left'], $forum['row_right']);
				$template->setInfo('content', $template->getVar('L_SUBSCRIBEDTOPICS'), TRUE);
				$template->setRedirect(referer(), 3);

				break;
			}

			/**
			 * Add selected topics to the queue to be deleted
			 */
			case 'delete': {
				
				if($user['perms'] < get_map($user, 'delete', 'can_add', array('forum_id' => $forum['id']))) {
					$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
					$template->setFile('content', 'login_form.html');
					$template->show('no_perms');
					return TRUE;
				}

				$dba->executeUpdate("UPDATE ". TOPICS ." SET display = 0 WHERE ". $query_extra);
				
				$queue			= array();
				foreach($topics as $topic_id) {
					$queue[]	= intval($topic_id);
				}
				$topicqueue		= serialize($queue);
				
				$queue			= $dba->prepareStatement("INSERT INTO ". TOPICQUEUE ." (topicinfo,finished) VALUES (?,0)");
				$queue->setString(1, $topicqueue);
				$queue->executeUpdate();
				
				if(!@touch(CACHE_TOPIC_FILE, time()-86460)) {
					@unlink(CACHE_TOPIC_FILE);
				}

				$template		= BreadCrumbs($template, $template->getVar('L_DELETETOPICS'), $forum['row_left'], $forum['row_right']);
				$template->setInfo('content', $template->getVar('L_DELETEDTOPICS'), TRUE);
				$template->setRedirect(referer(), 5);

				break;
			}

			/**
			 * Move/copy topics to a destination forum
			 */
			case 'move': {
				
				if($user['perms'] < get_map($user, 'move', 'can_add', array('forum_id' => $forum['id']))) {
					$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
					$template->setFile('content', 'login_form.html');
					$template->show('no_perms');
					return TRUE;
				}

				if(count($topics) <= 0) {
					$template		= BreadCrumbs($template, $template->getVar('L_MOVETOPICS'), $forum['row_left'], $forum['row_right']);
					$template->setInfo('content', $template->getVar('L_NEEDSELECTTOPIC'));
				}

				/* Get the topics */
				$result				= &$dba->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE t.is_draft=0 AND t.queue = 0 AND t.display = 1 AND i.row_type=". TOPIC ." AND t.forum_id = ". intval($forum['id']) ." AND (". $move_extra .") ORDER BY created DESC");
				
				/* Apply the topics iterator */
				$it					= &new TopicsIterator($result, &$session, $template->getVar('IMG_DIR'), $forum);
				$template->setList('topics', $it);
				
				$template->setVar('topics', $request['topics']);
				$template->setVar('forum_id', $forum['id']);

				$template->setVar('modpanel', 1);

				$template		= BreadCrumbs($template, $template->getVar('L_MOVETOPICS'), $forum['row_left'], $forum['row_right']);
				$template->setFile('content', 'move_topics.html');

				break;
			}

			/* Invalid action has been taken */
			default: {
				
				$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
				$template->setInfo('content', $template->getVar('L_NEEDSELECTACTION'), FALSE);
				return TRUE;
				break;
			}
		}

		return TRUE;

	}
}

class MoveTopics extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS;

		/* Check the request ID */
		if(!isset($request['id']) || !$request['id'] || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
			return TRUE;
		}
		
		/* Check the other request ID */
		if(!isset($request['forum']) || !$request['forum'] || intval($request['forum']) == 0) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_DESTFORUMDOESNTEXIST'), FALSE);
			return TRUE;
		}
			
		$forum				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($request['id']));
		$destination		= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($request['forum']));

		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
			return TRUE;
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_CANTMODNONFORUM'), FALSE);
			return TRUE;
		}

		/* Check the forum data given */
		if(!$destination || !is_array($destination) || empty($destination)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_DESTFORUMDOESNTEXIST'), FALSE);
			return TRUE;
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($destination['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_CANTMODNONFORUM'), FALSE);
			return TRUE;
		}

		/**
		 * Check for moderating permission
		 */
		
		if(!is_moderator($user, $forum)) {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setFile('content', 'login_form.html');
			$template->show('no_perms');
			return TRUE;
		}

		if(!is_moderator($user, $destination)) {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setFile('content', 'login_form.html');
			$template->show('no_perms');
			return TRUE;
		}

		if($user['perms'] < get_map($user, 'move', 'can_add', array('forum_id' => $forum['id']))) {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setFile('content', 'login_form.html');
			$template->show('no_perms');
			return TRUE;
		}

		if($user['perms'] < get_map($user, 'move', 'can_add', array('forum_id' => $destination['id']))) {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setFile('content', 'login_form.html');
			$template->show('no_perms');
			return TRUE;
		}
		
		if(!isset($request['action']) || $request['action'] == '') {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_NEEDSELECTACTION'), FALSE);
			return TRUE;
		}

		if(!isset($request['topics']) || $request['topics'] == '') {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_NEESSELECTTOPICS'), FALSE);
			return TRUE;
		}

		$topics		= explode("|", $request['topics']);

		if(!is_array($topics) || count($topics) == 0) {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_NEESSELECTTOPICS'), FALSE);
			return TRUE;
		}
		
		switch($request['action']) {
			case 'move': {
				
				$heirarchy = new Heirarchy();

				foreach($topics as $topic_id) {
					$topic			= $dba->getRow("SELECT * FROM ". INFO ." WHERE id = ". intval($topic_id));
					
					$heirarchy->allocateSpace($topic['row_left'], $topic['row_right'], $destination, INFO);
					
					$plusminus		= $destination['row_right'] > $topic['row_left'] ? "+" : "-";
					$minusplus		= $destination['row_right'] > $topic['row_left'] ? "-" : "+";
					$gl				= $destination['row_right'] > $topic['row_left'] ? "<" : ">";

					$difference		= abs($destination['row_right'] - $topic['row_left']);
					$move			= $topic['row_right'] - $topic['row_left'];
					
					$dba->beginTransaction();

					/* Move the topic and its replies */
					$dba->executeUpdate("UPDATE ". INFO ." SET row_left=row_left". $plusminus . $difference .", row_right=row_right". $plusminus . $difference ." WHERE row_left >= ". $topic['row_left'] ." AND row_right <= ". $topic['row_right']);
					
					/* Update topic */
					$dba->executeUpdate("UPDATE ". TOPICS ." SET forum_id=". intval($destination['id']) .", category_id=". intval($destination['category_id']) ." WHERE topic_id=". intval($topic['id']));
					
					/* Update replies */
					if(($topic['row_right'] - $topic['row_left']) > 1) {
						$dba->executeUpdate("UPDATE ". REPLIES ." SET forum_id=". intval($destination['id']) .", category_id=". intval($destination['category_id']) ." WHERE topic_id=". intval($topic['id']));
					}

					/* Fix the tree */
					
					$dba->executeUpdate("UPDATE ". INFO ." SET 
					row_left=row_left". $minusplus . $move ." WHERE row_left ". $gl ." ". $topic['row_left']);
					
					$dba->executeUpdate("UPDATE ". INFO ." SET 
					row_right=row_right". $minusplus . $move ." WHERE row_right ". $gl ." ". $topic['row_right']);
					
					/* Commit this transaction */
					$dba->commitTransaction();
				}

				$template		= BreadCrumbs($template, $template->getVar('L_MOVECOPYTOPICS'), $forum['row_left'], $forum['row_right']);
				$template->setInfo('content', sprintf($template->getVar('L_MOVEDTOPICS'), $forum['name'], $destination['name']), FALSE);
				$template->setRedirect('viewforum.php?id='. $destination['id'], 3);

				break;
			}
			case 'movetrack': {
				
				break;
			}
			case 'copy': {
				
				foreach($topics as $topic_id) {
					
				}

				break;
			}
			default: {
				header("Location: ". referer());
				break;
			}
		}

		return TRUE;
	}
}

class SimpleUpdateTopic extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {

		global $_QUERYPARAMS, $_DATASTORE;

		/* Check the request ID */
		if(!isset($request['forum_id']) || !$request['forum_id'] || intval($request['forum_id']) == 0) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
			return TRUE;
		}
			
		$forum				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($request['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
			return TRUE;
		}
			
		/* Make sure the we are trying to edit in a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_CANTEDITTONONFORUM'), FALSE);
			return TRUE;
		}
		
		if(!isset($request['id']) || !$request['id'] || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDTOPIC'));
			$template->setInfo('content', $template->getVar('L_TOPICDOESNTEXIST'), FALSE);
			return TRUE;
		}			

		/* Get our topic */
		$topic				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($request['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDTOPIC'));
			$template->setInfo('content', $template->getVar('L_TOPICDOESNTEXIST'), FALSE);

			return TRUE;
		}

		if(!isset($request['name']) || $request['name'] == '') {
			$name	= $topic['name'];
		} else {
			$name	= $request['name'];
		}
		
		if(!is_moderator($user, $forum)) {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setFile('content', 'login_form.html');
			$template->show('no_perms');
			return TRUE;
		}

		if($topic['poster_id'] == $user['id']) {
			if($user['perms'] < get_map($user, 'topics', 'can_edit', array('forum_id' => $forum['id']))) {
				$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
				$template->setFile('content', 'login_form.html');
				$template->show('no_perms');
				return TRUE;
			}
		} else {
			if($user['perms'] < get_map($user, 'other_topics', 'can_edit', array('forum_id' => $forum['id']))) {
				$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
				$template->setFile('content', 'login_form.html');
				$template->show('no_perms');
				return TRUE;
			}
		}
		
		$update_a		= &$dba->prepareStatement("UPDATE ". INFO ." SET name=? WHERE id=?");
		$update_b		= &$dba->prepareStatement("UPDATE ". TOPICS ." SET edited_time=?,edited_username=?,edited_userid=? WHERE topic_id=?");
		
		$update_a->setString(1, $name);
		$update_a->setInt(2, $topic['id']);

		$update_b->setInt(1, time());
		$update_b->setString(2, $user['name']);
		$update_b->setInt(3, $user['id']);
		$update_b->setInt(4, $topic['id']);

		$update_a->executeUpdate();
		$update_b->executeUpdate();
		
		if($forum['topic_id'] == $topic['id']) {
			$update_c	= &$dba->prepareStatement("UPDATE ". FORUMS ." SET topic_name=? WHERE forum_id=?");
			$update_c->setString(1, $name);
			$update_c->setInt(2, $forum['id']);
			$update_c->executeUpdate();
		}

		if($forum['post_id'] == $topic['id']) {
			$update_d	= &$dba->prepareStatement("UPDATE ". FORUMS ." SET post_name=? WHERE forum_id=?");
			$update_d->setString(1, $name);
			$update_d->setInt(2, $forum['id']);
			$update_d->executeUpdate();
		}

		$template	= BreadCrumbs($template, $template->getVar('L_EDITTOPIC'), $forum['row_left'], $forum['row_right']);
		$template->setInfo('content', sprintf($template->getVar('L_UPDATEDTOPIC'), $topic['name']));
		$template->setRedirect(referer(), 3);

		return TRUE;
	}
}

?>