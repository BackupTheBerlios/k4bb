<?php
/**
* k4 Bulletin Board, replies.class.php
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
* @author Geoffrey Goodman
* @author James Logsdon
* @version $Id: replies.class.php,v 1.1 2005/05/01 17:42:10 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

/**
 * Post / Preview a reply
 */
class PostReply extends Event {
	function getNumOnLevel($row_left, $row_right, $level) {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". INFO ." WHERE row_left > $row_left AND row_right < $row_right AND row_level = $level");
	}
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS, $_DATASTORE;

		$this->dba			= &$dba;
		
		/* Check the request ID */
		if(!isset($request['topic_id']) || !$request['topic_id'] || intval($request['topic_id']) == 0) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDTOPIC'));
			$template->setInfo('content', $template->getVar('L_TOPICDOESNTEXIST'), FALSE);
			return TRUE;
		}

		/* Get our topic */
		$topic				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($request['topic_id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDTOPIC'));
			$template->setInfo('content', $template->getVar('L_TOPICDOESNTEXIST'), FALSE);

			return TRUE;
		}
			
		$forum				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($topic['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
			return TRUE;
		}
			
		/* Make sure the we are trying to delete from a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_CANTDELFROMNONFORUM'), FALSE);
			return TRUE;
		}

		/* Do we have permission to post to this topic in this forum? */
		if($user['perms'] < get_map($user, 'replies', 'can_add', array('forum_id'=>$forum['id']))) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			return $template->setInfo('content', $template->getVar('L_PERMCANTPOST'), FALSE);		
		}

		if(isset($request['parent_id']) && intval($request['parent_id']) != 0) {
			$reply				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". REPLIES ." r LEFT JOIN ". INFO ." i ON r.reply_id = i.id WHERE i.id = ". intval($request['parent_id']));
			
			if(!$reply || !is_array($reply) || empty($reply)) {
				/* set the breadcrumbs bit */
				$template		= BreadCrumbs($template, $template->getVar('L_INVALIDREPLY'));
				$template->setInfo('content', $template->getVar('L_REPLYDOESNTEXIST'), FALSE);
				
				return TRUE;
			}
		}

		$parent					= isset($reply) && is_array($reply) ? $reply : $topic;

		/* Do we have permission to post to this forum? */
		if($user['perms'] < get_map($user, 'topics', 'can_add', array('forum_id'=>$forum['id']))) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_PERMCANTPOST'), FALSE);
			return TRUE;
		}

		/* General error checking */
		if(!isset($request['name']) || $request['name'] == '') {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'), $forum['row_left'], $forum['row_right']);
			$template->setInfo('content', $template->getVar('L_INSERTTOPICNAME'), TRUE);
			return TRUE;
		}
		if(!isset($request['message']) || $request['message'] == '') {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'), $forum['row_left'], $forum['row_right']);
			$template->setInfo('content', $template->getVar('L_INSERTTOPICMESSAGE'), TRUE);
			return TRUE;
		}
				
		/**
		 * Start building info for the queries
		 */
				
		/* Find out how many nodes are on the current level */
		$num_on_level		= $this->getNumOnLevel($parent['row_left'], $parent['row_right'], $parent['row_level']+1);
		
		/* If there are more than 1 nodes on the current level */
		if($num_on_level > 0) {
			$left			= $parent['row_right'];
		} else {
			$left			= $parent['row_left'] + 1;
		}
		
		/* Set this nodes level */
		$level				= $parent['row_level']+1;

		$right				= $left+1;
		
		/* Set the topic created time */
		$created			= time();
		
		/* Initialize the bbcode parser with the topic message */
		$bbcode	= &new BBCodex(&$user, $request['message'], $forum['id'], 
			iif((isset($request['disable_html']) && $request['disable_html'] == 'on'), FALSE, TRUE), 
			iif((isset($request['disable_bbcode']) && $request['disable_bbcode'] == 'on'), FALSE, TRUE), 
			iif((isset($request['disable_emoticons']) && $request['disable_emoticons'] == 'on'), FALSE, TRUE), 
			iif((isset($request['disable_aurls']) && $request['disable_aurls'] == 'on'), FALSE, TRUE));
		
		/* Parse the bbcode */
		$body_text = $bbcode->parse();
		
		if($request['submit'] == $template->getVar('L_SUBMIT')) {
						
			/**
			 * Build the queries
			 */

			/* Prepare the queries */
			$update_a			= &$dba->prepareStatement("UPDATE ". INFO ." SET row_right = row_right+2 WHERE row_left < ? AND row_right >= ?");
			$update_b			= &$dba->prepareStatement("UPDATE ". INFO ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= ?");
			$insert_a			= &$dba->prepareStatement("INSERT INTO ". INFO ." (name,parent_id,row_left,row_right,row_type,row_level,created) VALUES (?,?,?,?,?,?,?)");
			$insert_b			= &$dba->prepareStatement("INSERT INTO ". REPLIES ." (reply_id,topic_id,forum_id,category_id,poster_name,poster_id,poster_ip,body_text,posticon,disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			
			/* Set the insert variables needed */
			$update_a->setInt(1, $left);
			$update_a->setInt(2, $left);
			$update_b->setInt(1, $left);

			$update_a->executeUpdate();
			$update_b->executeUpdate();
			
			/* Set the inserts for adding the actual node */
			$insert_a->setString(1, htmlentities($request['name'], ENT_QUOTES));
			$insert_a->setInt(2, $forum['id']);
			$insert_a->setInt(3, $left);
			$insert_a->setInt(4, $right);
			$insert_a->setInt(5, REPLY);
			$insert_a->setInt(6, $level);
			$insert_a->setInt(7, $created);
			
			/* Add the main topic information to the database */
			$insert_a->executeUpdate();

			$reply_id			= $dba->getInsertId();
			
			//topic_id,forum_id,category_id,poster_name,poster_id,body_text,posticon
			//disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls,is_draft
			$insert_b->setInt(1, $reply_id);
			$insert_b->setInt(2, $topic['id']);
			$insert_b->setInt(3, $forum['id']);
			$insert_b->setInt(4, $forum['category_id']);
			$insert_b->setString(5, iif($user['id'] <= 0, htmlentities(@$request['poster_name'], ENT_QUOTES), $user['name']));
			$insert_b->setInt(6, $user['id']);
			$insert_b->setInt(7, USER_IP);
			$insert_b->setString(8, $body_text);
			$insert_b->setString(9, iif(($user['perms'] >= get_map($user, 'posticons', 'can_add', array('forum_id'=>$forum['id']))), @$request['posticon'], 'clear.gif'));
			$insert_b->setInt(10, iif((isset($request['disable_html']) && $request['disable_html'] == 'on'), 1, 0));
			$insert_b->setInt(11, iif((isset($request['disable_bbcode']) && $request['disable_bbcode'] == 'on'), 1, 0));
			$insert_b->setInt(12, iif((isset($request['disable_emoticons']) && $request['disable_emoticons'] == 'on'), 1, 0));
			$insert_b->setInt(13, iif((isset($request['enable_sig']) && $request['enable_sig'] == 'on'), 0, 1));
			$insert_b->setInt(14, iif((isset($request['disable_areply']) && $request['disable_areply'] == 'on'), 1, 0));
			$insert_b->setInt(15, iif((isset($request['disable_aurls']) && $request['disable_aurls'] == 'on'), 1, 0));

			$insert_b->executeUpdate();
			
			/** 
			 * Update the forum, and update the datastore 
			 */

			//topic_created,topic_name,topic_uname,topic_id,topic_uid,post_created,post_name,post_uname,post_id,post_uid
			$forum_update		= &$dba->prepareStatement("UPDATE ". FORUMS ." SET replies=replies+1,posts=posts+1,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
			$topic_update		= &$dba->prepareStatement("UPDATE ". TOPICS ." SET reply_time=?,reply_uname=?,reply_id=?,reply_uid=? WHERE topic_id=?");
			$datastore_update	= &$dba->prepareStatement("UPDATE ". DATASTORE ." SET data=? WHERE varname=?");
			$user_update		= $dba->executeUpdate("UPDATE ". USERINFO ." SET num_posts=num_posts+1 WHERE user_id=". intval($user['id']));
			
			/* Update the forums and datastore tables */

			/* Set the forum values */
			$forum_update->setInt(1, $created);
			$forum_update->setString(2, htmlentities($request['name'], ENT_QUOTES));
			$forum_update->setString(3, iif($user['id'] <= 0, htmlentities(@$request['poster_name'], ENT_QUOTES), $user['name']));
			$forum_update->setInt(4, $reply_id);
			$forum_update->setInt(5, $user['id']);
			$forum_update->setString(6, iif(($user['perms'] >= get_map($user, 'posticons', 'can_add', array('forum_id'=>$forum['id']))), @$request['posticon'], 'clear.gif'));
			$forum_update->setInt(7, $forum['id']);

			/* Set the topic values */
			$topic_update->setInt(1, $created);
			$topic_update->setString(2, iif($user['id'] <= 0, htmlentities(@$request['poster_name'], ENT_QUOTES), $user['name']));
			$topic_update->setInt(3, $reply_id);
			$topic_update->setInt(4, $user['id']);
			$topic_update->setInt(5, $topic['id']);
			
			/* Set the datastore values */
			$datastore					= $_DATASTORE['forumstats'];
			$datastore['num_replies']	+= 1;
			
			$datastore_update->setString(1, serialize($datastore));
			$datastore_update->setString(2, 'forumstats');
			
			/**
			 * Update the forums table and datastore table
			 */
			$forum_update->executeUpdate();
			$topic_update->executeUpdate();
			$datastore_update->executeUpdate();

			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_POSTREPLY'), $parent['row_left'], $parent['row_right']);
			
			/* Added the reply */
				
			/* Redirect the user */
			$template->setInfo('content', sprintf($template->getVar('L_ADDEDREPLY'), htmlentities($request['name'], ENT_QUOTES), $topic['name']));
			$template->setRedirect('findpost.php?id='. $reply_id, 3);

		} else {
			
			/**
			 * Post Previewing
			 */
			
			/* Get and set the emoticons and post icons to the template */
			$emoticons	= &$dba->executeQuery("SELECT * FROM ". EMOTICONS ." WHERE clickable = 1");
			$posticons	= &$dba->executeQuery("SELECT * FROM ". POSTICONS);

			$template->setList('emoticons', $emoticons);
			$template->setList('posticons', $posticons);

			$template->setVar('emoticons_per_row', $template->getVar('smcolumns'));
			$template->setVar('emoticons_per_row_remainder', $template->getVar('smcolumns')-1);
			
			$template	= topic_post_options($template, $user, $forum);

			/* Set the forum info to the template */
			foreach($forum as $key => $val)
				$template->setVar('forum_'. $key, $val);

			$template = topic_post_options($template, $user, $forum);
						
			$reply_preview	= array(
								'name' => htmlentities($request['name'], ENT_QUOTES),
								'body_text' => $body_text,
								'poster_name' => $user['name'],
								'poster_id' => $user['id'],
								'forum_id' => $forum['id'],
								'topic_id' => $topic['id'],
								'posticon' => iif(($user['perms'] >= get_map($user, 'posticons', 'can_add', array('forum_id'=>$forum['id']))), @$request['posticon'], 'clear.gif'),
								'disable_html' => iif((isset($request['disable_html']) && $request['disable_html'] == 'on'), 1, 0),
								'disable_sig' => iif((isset($request['enable_sig']) && $request['enable_sig'] == 'on'), 0, 1),
								'disable_bbcode' => iif((isset($request['disable_bbcode']) && $request['disable_bbcode'] == 'on'), 1, 0),
								'disable_emoticons' => iif((isset($request['disable_emoticons']) && $request['disable_emoticons'] == 'on'), 1, 0),
								'disable_areply' => iif((isset($request['disable_areply']) && $request['disable_areply'] == 'on'), 1, 0),
								'disable_aurls' => iif((isset($request['disable_aurls']) && $request['disable_aurls'] == 'on'), 1, 0)
								);

			/* Add the reply information to the template (same as for topics) */
			$reply_iterator = &new TopicIterator($reply_preview, FALSE);
			$template->setList('topic', $reply_iterator);
			
			/* Assign the topic preview values to the template */
			$reply_preview['body_text'] = $request['message'];
			
			foreach($reply_preview as $key => $val)
				$template->setVar('reply_'. $key, $val);
			
			/* Set the the button display options */
			$template->show('edit_reply');
						
			/* Set the appropriate parent id */
			if(isset($reply)) {
				$template->show('parent_id');
				$template->setVar('parent_id', $parent['id']);
			}

			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_POSTREPLY'), $parent['row_left'], $parent['row_right']);
			
			/* Get replies that are above this point */
			$replies	= &$dba->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". INFO ." i LEFT JOIN ". REPLIES ." r ON i.id = r.reply_id WHERE i.row_left <= ". $parent['row_left'] ." AND i.row_right >= ". $parent['row_right'] ." ORDER BY i.created DESC LIMIT 10");

			$template->setList('topic_review', new TopicIterator($topic, TRUE, $replies));
			
			/* Get replies that are above this point */
			$replies	= &$dba->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". INFO ." i LEFT JOIN ". REPLIES ." r ON i.id = r.reply_id WHERE i.row_left <= ". $parent['row_left'] ." AND i.row_right >= ". $parent['row_right'] ." ORDER BY i.created DESC LIMIT 10");

			$template->setList('topic_review', new TopicReviewIterator($topic, $replies, $user));
			
			foreach($parent as $key => $val)
				$template->setVar('parent_'. $key, $val);

			/* Set the post topic form */
			$template->setFile('preview', 'post_preview.html');
			$template->setFile('content', 'newreply.html');
		}

		return TRUE;
	}
}

?>