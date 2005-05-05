<?php
/**
* k4 Bulletin Board, topics.class.php
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
* @version $Id: topics.class.php,v 1.13 2005/05/05 21:36:06 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

/**
 * Post / Preview a topic
 */
class PostTopic extends Event {
	function getNumOnLevel($row_left, $row_right, $level) {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". INFO ." WHERE row_left > $row_left AND row_right < $row_right AND row_level = $level");
	}
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS, $_DATASTORE;

		$this->dba			= &$dba;

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
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_CANTPOSTTONONFORUM'), FALSE);
			return TRUE;
		}

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
		$num_on_level		= $this->getNumOnLevel($forum['row_left'], $forum['row_right'], $forum['row_level']+1);
		
		/* If there are more than 1 nodes on the current level */
		if($num_on_level > 0) {
			$left			= $forum['row_right'];
		} else {
			$left			= $forum['row_left'] + 1;
		}
		
		/* Set this nodes level */
		$level				= $forum['row_level']+1;

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
		
		if($request['submit'] == $template->getVar('L_SUBMIT') || $request['submit'] == $template->getVar('L_SAVEDRAFT')) {
			
			/* Does this person have permission to post a draft? */
			if($request['submit'] == $template->getVar('L_SAVEDRAFT')) {
				if($user['perms'] < get_map($user, 'post_save', 'can_add', array('forum_id'=>$forum['id']))) {
					$template	= BreadCrumbs($template, $template->getVar('L_POSTTOPIC'), $forum['row_left'], $forum['row_right']);
					$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
					return TRUE;
				}
			}

			/**
			 * Figure out what type of topic type this is
			 */
			$topic_type			= isset($request['topic_type']) && intval($request['topic_type']) != 0 ? $request['topic_type'] : TOPIC_NORMAL;

			if($topic_type == TOPIC_STICKY && $user['perms'] < get_map($user, 'sticky', 'can_add', array('forum_id'=>$forum['id']))) {
				$topic_type		= TOPIC_NORMAL;
			} else if($topic_type == TOPIC_ANNOUNCE && $user['perms'] < get_map($user, 'announce', 'can_add', array('forum_id'=>$forum['id']))) {
				$topic_type		= TOPIC_NORMAL;
			} else if($topic_type == TOPIC_GLOBAL && $user['perms'] < get_map($user, 'global', 'can_add', array('forum_id'=>$forum['id']))) {
				$topic_type		= TOPIC_NORMAL;
			}
			
			/**
			 * Build the queries
			 */

			/* Prepare the queries */
			$update_a			= &$dba->prepareStatement("UPDATE ". INFO ." SET row_right = row_right+2 WHERE row_left < ? AND row_right >= ?");
			$update_b			= &$dba->prepareStatement("UPDATE ". INFO ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= ?");
			$insert_a			= &$dba->prepareStatement("INSERT INTO ". INFO ." (name,parent_id,row_left,row_right,row_type,row_level,created) VALUES (?,?,?,?,?,?,?)");
			$insert_b			= &$dba->prepareStatement("INSERT INTO ". TOPICS ." (topic_id,forum_id,category_id,poster_name,poster_id,poster_ip,body_text,posticon,disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls,is_draft,topic_type,topic_expire) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			
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
			$insert_a->setInt(5, TOPIC);
			$insert_a->setInt(6, $level);
			$insert_a->setInt(7, $created);
			
			/* Add the main topic information to the database */
			$insert_a->executeUpdate();

			$topic_id			= $dba->getInsertId();
			
			//topic_id,forum_id,category_id,poster_name,poster_id,body_text,posticon
			//disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls,is_draft
			$insert_b->setInt(1, $topic_id);
			$insert_b->setInt(2, $forum['id']);
			$insert_b->setInt(3, $forum['category_id']);
			$insert_b->setString(4, iif($user['id'] <= 0, htmlentities(@$request['poster_name'], ENT_QUOTES), $user['name']));
			$insert_b->setInt(5, $user['id']);
			$insert_b->setInt(6, USER_IP);
			$insert_b->setString(7, $body_text);
			$insert_b->setString(8, iif(($user['perms'] >= get_map($user, 'posticons', 'can_add', array('forum_id'=>$forum['id']))), @$request['posticon'], 'clear.gif'));
			$insert_b->setInt(9, iif((isset($request['disable_html']) && $request['disable_html'] == 'on'), 1, 0));
			$insert_b->setInt(10, iif((isset($request['disable_bbcode']) && $request['disable_bbcode'] == 'on'), 1, 0));
			$insert_b->setInt(11, iif((isset($request['disable_emoticons']) && $request['disable_emoticons'] == 'on'), 1, 0));
			$insert_b->setInt(12, iif((isset($request['enable_sig']) && $request['enable_sig'] == 'on'), 0, 1));
			$insert_b->setInt(13, iif((isset($request['disable_areply']) && $request['disable_areply'] == 'on'), 1, 0));
			$insert_b->setInt(14, iif((isset($request['disable_aurls']) && $request['disable_aurls'] == 'on'), 1, 0));
			$insert_b->setInt(15, iif($request['submit'] == $template->getVar('L_SAVEDRAFT'), 1, 0));
			// DO THIS 16 -> topic_type, 17 -> topic_expire
			$insert_b->setInt(16, $topic_type);
			$insert_b->setInt(17, iif($topic_type > TOPIC_NORMAL, intval($request['topic_expire']), 0) );

			$insert_b->executeUpdate();
			
			/** 
			 * Update the forum, and update the datastore 
			 */

			//topic_created,topic_name,topic_uname,topic_id,topic_uid,post_created,post_name,post_uname,post_id,post_uid
			$where				= $topic_type != TOPIC_GLOBAL ? "WHERE forum_id=?" : "WHERE forum_id=? OR forum_id<>0";
			$forum_update		= &$dba->prepareStatement("UPDATE ". FORUMS ." SET topics=topics+1,posts=posts+1,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? $where");
			$datastore_update	= &$dba->prepareStatement("UPDATE ". DATASTORE ." SET data=? WHERE varname=?");
			$user_update		= $dba->executeUpdate("UPDATE ". USERINFO ." SET num_posts=num_posts+1 WHERE user_id=". intval($user['id']));
			
			/* If this isn't a draft, update the forums and datastore tables */
			if($request['submit'] != $template->getVar('L_SAVEDRAFT')) {

				/* Set the forum values */
				$forum_update->setInt(1, $created);
				$forum_update->setString(2, htmlentities($request['name'], ENT_QUOTES));
				$forum_update->setString(3, iif($user['id'] <= 0, htmlentities(@$request['poster_name'], ENT_QUOTES), $user['name']));
				$forum_update->setInt(4, $topic_id);
				$forum_update->setInt(5, $user['id']);
				$forum_update->setString(6, iif(($user['perms'] >= get_map($user, 'posticons', 'can_add', array('forum_id'=>$forum['id']))), @$request['posticon'], 'clear.gif'));
				$forum_update->setInt(7, $created);
				$forum_update->setString(8, htmlentities($request['name'], ENT_QUOTES));
				$forum_update->setString(9, iif($user['id'] <= 0, htmlentities(@$request['poster_name'], ENT_QUOTES), $user['name']));
				$forum_update->setInt(10, $topic_id);
				$forum_update->setInt(11, $user['id']);
				$forum_update->setString(12, iif(($user['perms'] >= get_map($user, 'posticons', 'can_add', array('forum_id'=>$forum['id']))), @$request['posticon'], 'clear.gif'));
				$forum_update->setInt(13, $forum['id']);
				
				/* Set the datastore values */
				$datastore					= $_DATASTORE['forumstats'];
				$datastore['num_topics']	+= 1;
				
				$datastore_update->setString(1, serialize($datastore));
				$datastore_update->setString(2, 'forumstats');
				
				/**
				 * Update the forums table and datastore table
				 */
				$forum_update->executeUpdate();
				$datastore_update->executeUpdate();
			}

			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_POSTTOPIC'), $forum['row_left'], $forum['row_right']);
			
			/* Added the topic */
			if($request['submit'] == $template->getVar('L_SUBMIT')) {
				
				/* Redirect the user */
				$template->setInfo('content', sprintf($template->getVar('L_ADDEDTOPIC'), htmlentities($request['name'], ENT_QUOTES), $forum['name']));
				$template->setRedirect('viewtopic.php?id='. $topic_id, 3);
			} else {
				/* Redirect the user */
				$template->setInfo('content', sprintf($template->getVar('L_SAVEDDRAFTTOPIC'), htmlentities($request['name'], ENT_QUOTES), $forum['name']));
				$template->setRedirect('viewforum.php?id='. $forum['id'], 3);
			}
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
			
			$template->setVar('newtopic_action', 'newtopic.php?act=posttopic');
						
			$topic_preview	= array(
								'name' => htmlentities($request['name'], ENT_QUOTES),
								'body_text' => $body_text,
								'poster_name' => $user['name'],
								'poster_id' => $user['id'],
								'posticon' => iif(($user['perms'] >= get_map($user, 'posticons', 'can_add', array('forum_id'=>$forum['id']))), @$request['posticon'], 'clear.gif'),
								'disable_html' => iif((isset($request['disable_html']) && $request['disable_html'] == 'on'), 1, 0),
								'disable_sig' => iif((isset($request['enable_sig']) && $request['enable_sig'] == 'on'), 0, 1),
								'disable_bbcode' => iif((isset($request['disable_bbcode']) && $request['disable_bbcode'] == 'on'), 1, 0),
								'disable_emoticons' => iif((isset($request['disable_emoticons']) && $request['disable_emoticons'] == 'on'), 1, 0),
								'disable_areply' => iif((isset($request['disable_areply']) && $request['disable_areply'] == 'on'), 1, 0),
								'disable_aurls' => iif((isset($request['disable_aurls']) && $request['disable_aurls'] == 'on'), 1, 0)
								);

			/* Add the topic information to the template */
			$topic_iterator = &new TopicIterator($topic_preview, FALSE);
			$template->setList('topic', $topic_iterator);
			
			/* Assign the topic preview values to the template */
			$topic_preview['body_text'] = $request['message'];
			foreach($topic_preview as $key => $val)
				$template->setVar('topic_'. $key, $val);
			
			/* Assign the forum information to the template */
			foreach($forum as $key => $val)
				$template->setVar('forum_'. $key, $val);

			/* Set the the button display options */
			$template->show('save_draft');
			$template->show('edit_topic');
			$template->show('topic_id');
			
			$drafts		= $dba->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE t.forum_id = ". intval($forum['id']) ." AND t.is_draft = 1 AND t.poster_id = ". intval($user['id']));
			if($drafts->numrows() > 0)
				$template->show('load_button');
			else
				$template->hide('load_button');

			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_POSTTOPIC'), $forum['row_left'], $forum['row_right']);
			
			/* Set the post topic form */
			$template->setFile('preview', 'post_preview.html');
			$template->setFile('content', 'newtopic.html');
		}

		return TRUE;
	}
}

/**
 * Post / Preview a draft topic
 */
class PostDraft extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS, $_DATASTORE;

		$this->dba			= &$dba;

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
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_CANTPOSTTONONFORUM'), FALSE);
			return TRUE;
		}

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
		
		/* Get our topic */
		$draft				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($request['topic_id']) ." AND t.is_draft = 1 AND t.poster_id = ". intval($user['id']));
		
		if(!$draft || !is_array($draft) || empty($draft)) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDDRAFT'));
			$template->setInfo('content', $template->getVar('L_DRAFTDOESNTEXIST'), FALSE);

			return TRUE;
		}

		/* set the breadcrumbs bit */
		$template	= BreadCrumbs($template, $template->getVar('L_POSTTOPIC'), $forum['row_left'], $forum['row_right']);
		
		$created			= time();
		
		/* Initialize the bbcode parser with the topic message */
		$bbcode	= &new BBCodex(&$user, $request['message'], $forum['id'], 
			iif((isset($request['disable_html']) && $request['disable_html'] == 'on'), FALSE, TRUE), 
			iif((isset($request['disable_bbcode']) && $request['disable_bbcode'] == 'on'), FALSE, TRUE), 
			iif((isset($request['disable_emoticons']) && $request['disable_emoticons'] == 'on'), FALSE, TRUE), 
			iif((isset($request['disable_aurls']) && $request['disable_aurls'] == 'on'), FALSE, TRUE));
		
		/* Parse the bbcode */
		$body_text = $bbcode->parse();
		
		if($request['submit'] == $template->getVar('L_SUBMIT') || $request['submit'] == $template->getVar('L_SAVEDRAFT')) {

			/**
			 * Build the queries to add the draft
			 */
			
			$update_a			= $dba->prepareStatement("UPDATE ". INFO ." SET name=?,created=? WHERE id=?");
			$update_b			= $dba->prepareStatement("UPDATE ". TOPICS ." SET body_text=?,posticon=?,disable_html=?,disable_bbcode=?,disable_emoticons=?,disable_sig=?,disable_areply=?,disable_aurls=?,is_draft=? WHERE topic_id=?");
			
			$update_a->setString(1, htmlentities($request['name'], ENT_QUOTES));
			$update_a->setInt(2, $created);
			$update_a->setInt(3, $draft['id']);
			
			$update_b->setString(1, $body_text);
			$update_b->setString(2, iif(($user['perms'] >= get_map($user, 'posticons', 'can_add', array('forum_id'=>$forum['id']))), @$request['posticon'], 'clear.gif'));
			$update_b->setInt(3, iif((isset($request['disable_html']) && $request['disable_html'] == 'on'), 1, 0));
			$update_b->setInt(4, iif((isset($request['disable_bbcode']) && $request['disable_bbcode'] == 'on'), 1, 0));
			$update_b->setInt(5, iif((isset($request['disable_emoticons']) && $request['disable_emoticons'] == 'on'), 1, 0));
			$update_b->setInt(6, iif((isset($request['enable_sig']) && $request['enable_sig'] == 'on'), 0, 1));
			$update_b->setInt(7, iif((isset($request['disable_areply']) && $request['disable_areply'] == 'on'), 1, 0));
			$update_b->setInt(8, iif((isset($request['disable_aurls']) && $request['disable_aurls'] == 'on'), 1, 0));
			$update_b->setInt(9, 0);
			$update_b->setInt(10, $draft['id']);
			
			/**
			 * Do the queries
			 */
			$update_a->executeUpdate();
			$update_b->executeUpdate();

			$forum_update		= &$dba->prepareStatement("UPDATE ". FORUMS ." SET topics=topics+1,posts=posts+1,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
			$datastore_update	= &$dba->prepareStatement("UPDATE ". DATASTORE ." SET data=? WHERE varname=?");
			$user_update		= $dba->executeUpdate("UPDATE ". USERINFO ." SET num_posts=num_posts+1 WHERE user_id=". intval($user['id']));	
				
			/* Set the forum values */
			$forum_update->setInt(1, $created);
			$forum_update->setString(2, htmlentities($request['name'], ENT_QUOTES));
			$forum_update->setString(3, iif($user['id'] <= 0, htmlentities(@$request['poster_name'], ENT_QUOTES), $user['name']));
			$forum_update->setInt(4, $draft['id']);
			$forum_update->setInt(5, $user['id']);
			$forum_update->setString(6, iif(($user['perms'] >= get_map($user, 'posticons', 'can_add', array('forum_id'=>$forum['id']))), @$request['posticon'], 'clear.gif'));
			$forum_update->setInt(7, $created);
			$forum_update->setString(8, htmlentities($request['name'], ENT_QUOTES));
			$forum_update->setString(9, iif($user['id'] <= 0, htmlentities(@$request['poster_name'], ENT_QUOTES), $user['name']));
			$forum_update->setInt(10, $draft['id']);
			$forum_update->setInt(11, $user['id']);
			$forum_update->setString(12, iif(($user['perms'] >= get_map($user, 'posticons', 'can_add', array('forum_id'=>$forum['id']))), @$request['posticon'], 'clear.gif'));
			$forum_update->setInt(13, $forum['id']);
			
			/* Set the datastore values */
			$datastore					= $_DATASTORE['forumstats'];
			$datastore['num_topics']	+= 1;
			
			$datastore_update->setString(1, serialize($datastore));
			$datastore_update->setString(2, 'forumstats');
			
			/**
			 * Update the forums table and datastore table
			 */
			$forum_update->executeUpdate();
			$datastore_update->executeUpdate();

			/* Redirect the user */
			$template->setInfo('content', sprintf($template->getVar('L_ADDEDTOPIC'), htmlentities($request['name'], ENT_QUOTES), $forum['name']));
			$template->setRedirect('viewtopic.php?id='. $draft['id'], 3);
		
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
			
			$template->setVar('newtopic_action', 'newtopic.php?act=postdraft');

			$template		= topic_post_options($template, $user, $forum);
						
			$topic_preview	= array(
								'id' => @$draft['id'],
								'name' => htmlentities($request['name'], ENT_QUOTES),
								'posticon' => @$request['posticon'],
								'body_text' => $body_text,
								'poster_name' => html_entity_decode($draft['poster_name'], ENT_QUOTES),
								'poster_id' => $user['id'],
								'posticon' => iif(($user['perms'] >= get_map($user, 'posticons', 'can_add', array('forum_id'=>$forum['id']))), @$request['posticon'], 'clear.gif'),
								'disable_html' => iif((isset($request['disable_html']) && $request['disable_html'] == 'on'), 1, 0),
								'disable_sig' => iif((isset($request['enable_sig']) && $request['enable_sig'] == 'on'), 0, 1),
								'disable_bbcode' => iif((isset($request['disable_bbcode']) && $request['disable_bbcode'] == 'on'), 1, 0),
								'disable_emoticons' => iif((isset($request['disable_emoticons']) && $request['disable_emoticons'] == 'on'), 1, 0),
								'disable_areply' => iif((isset($request['disable_areply']) && $request['disable_areply'] == 'on'), 1, 0),
								'disable_aurls' => iif((isset($request['disable_aurls']) && $request['disable_aurls'] == 'on'), 1, 0)
								);

			/* Add the topic information to the template */
			$topic_iterator = &new TopicIterator($topic_preview, FALSE);
			$template->setList('topic', $topic_iterator);
			
			/* Assign the topic preview values to the template */
			$topic_preview['body_text'] = $request['message'];
			foreach($topic_preview as $key => $val)
				$template->setVar('topic_'. $key, $val);
			
			/* Assign the forum information to the template */
			foreach($forum as $key => $val)
				$template->setVar('forum_'. $key, $val);

			/* Set the the button display options */
			$template->hide('save_draft');
			$template->hide('load_button');
			$template->show('edit_topic');
			$template->show('topic_id');
			
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_POSTTOPIC'), $forum['row_left'], $forum['row_right']);
			
			/* Set the post topic form */
			$template->setFile('preview', 'post_preview.html');
			$template->setFile('content', 'newtopic.html');
		}

		return TRUE;
	}
}


/**
 * Delete a topic draft
 */
class DeleteDraft extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS, $_DATASTORE;

		$this->dba			= &$dba;
		
		/* Get our draft */
		$draft				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($request['id']) ." AND t.is_draft = 1 AND t.poster_id = ". intval($user['id']));
		
		if(!$draft || !is_array($draft) || empty($draft)) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDDRAFT'));
			$template->setInfo('content', $template->getVar('L_DRAFTDOESNTEXIST'), FALSE);

			return TRUE;
		}
		
		$forum				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($draft['forum_id']));
		
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
			$template->setInfo('content', $template->getVar('L_CANTPOSTTONONFORUM'), FALSE);
			return TRUE;
		}			

		/* set the breadcrumbs bit */
		$template	= BreadCrumbs($template, $template->getVar('L_DELETEDRAFT'), $forum['row_left'], $forum['row_right']);
		
		/* Remove this draft from the information table */
		$h			= &new Heirarchy();
		$h->removeNode($draft, INFO);
		
		/* Now remove the information stored in the topics table */
		$dba->executeUpdate("DELETE FROM ". TOPICS ." WHERE topic_id = ". intval($draft['id']) ." AND is_draft = 1");
		
		/* Redirect the user */
		$template->setInfo('content', sprintf($template->getVar('L_REMOVEDDRAFT'), $draft['name'], $forum['name']));
		$template->setRedirect('viewforum.php?id='. $forum['id'], 3);

		return TRUE;
	}
}

/**
 * Edit a topic
 */
class EditTopic extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS, $_DATASTORE;
		
		/* Get our topic */
		$topic				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($request['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDDRAFT'));
			$template->setInfo('content', $template->getVar('L_DRAFTDOESNTEXIST'), FALSE);

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
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_CANTPOSTTONONFORUM'), FALSE);
			return TRUE;
		}
		
		/* set the breadcrumbs bit */
		$template	= BreadCrumbs($template, $template->getVar('L_EDITTOPIC'), $forum['row_left'], $forum['row_right']);
		
		if($topic['poster_id'] == $user['id']) {
			if(get_map($user, 'topics', 'can_edit', array('forum_id'=>$forum['id'])) > $user['perms']) {
				$template->setInfo('content', $template->getVar('L_YOUNEEDPERMS'), FALSE);
				return TRUE;
			}
		} else {
			if(get_map($user, 'other_topics', 'can_edit', array('forum_id'=>$forum['id'])) > $user['perms']) {
				$template->setInfo('content', $template->getVar('L_YOUNEEDPERMS'), FALSE);
				return TRUE;
			}
		}

		/* Does this user have permission to edit this topic if it is locked? */
		if($topic['topic_locked'] == 1 && get_map($user, 'closed', 'can_edit', array('forum_id' => $forum['id'])) > $user['perms']) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_YOUNEEDPERMS'), FALSE);
			return TRUE;
		}
		
		$bbcode				= &new BBCodex($user, $topic['body_text'], $forum['id'], TRUE, TRUE, TRUE, TRUE);
		
		/* Get and set the emoticons and post icons to the template */
		$emoticons			= &$dba->executeQuery("SELECT * FROM ". EMOTICONS ." WHERE clickable = 1");
		$posticons			= &$dba->executeQuery("SELECT * FROM ". POSTICONS);

		$template->setList('emoticons', $emoticons);
		$template->setList('posticons', $posticons);

		$template->setVar('emoticons_per_row', $template->getVar('smcolumns'));
		$template->setVar('emoticons_per_row_remainder', $template->getVar('smcolumns')-1);
		
		$template->setVar('newtopic_action', 'newtopic.php?act=updatetopic');

		$template			= topic_post_options($template, $user, $forum);
		
		$topic['body_text'] = $bbcode->revert();

		foreach($topic as $key => $val)
			$template->setVar('topic_'. $key, $val);
		
		/* Assign the forum information to the template */
		foreach($forum as $key => $val)
			$template->setVar('forum_'. $key, $val);

		/* Set the the button display options */
		$template->hide('save_draft');
		$template->hide('load_button');
		$template->show('edit_topic');
		$template->show('topic_id');
		$template->hide('post_topic');
		$template->show('edit_post');
		
		/* set the breadcrumbs bit */
		$template	= BreadCrumbs($template, $template->getVar('L_EDITTOPIC'), $forum['row_left'], $forum['row_right']);
		
		/* Set the post topic form */
		$template->setFile('preview', 'post_preview.html');
		$template->setFile('content', 'newtopic.html');

		return TRUE;
	}
}

/**
 * Update a topic
 */
class UpdateTopic extends Event {
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
		
		/* Get our topic */
		$topic				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($request['topic_id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDTOPIC'));
			$template->setInfo('content', $template->getVar('L_TOPICDOESNTEXIST'), FALSE);

			return TRUE;
		}

		$type				= $topic['poll'] == 1 ? 'polls' : 'topics';

		/* Does this person have permission to edit this topic? */
		if($topic['poster_id'] == $user['id']) {
			if(get_map($user, $type, 'can_edit', array('forum_id'=>$forum['id'])) > $user['perms']) {
				$template->setInfo('content', $template->getVar('L_YOUNEEDPERMS'), FALSE);
				return TRUE;
			}
		} else {
			if(get_map($user, 'other_'. $type, 'can_edit', array('forum_id'=>$forum['id'])) > $user['perms']) {
				$template->setInfo('content', $template->getVar('L_YOUNEEDPERMS'), FALSE);
				return TRUE;
			}
		}
		
		/* Does this user have permission to edit this topic if it is locked? */
		if($topic['topic_locked'] == 1 && get_map($user, 'closed', 'can_edit', array('forum_id' => $forum['id'])) > $user['perms']) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_YOUNEEDPERMS'), FALSE);
			return TRUE;
		}

		/* set the breadcrumbs bit */
		$template	= BreadCrumbs($template, $template->getVar('L_EDITTOPIC'), $forum['row_left'], $forum['row_right']);
				
		/* Initialize the bbcode parser with the topic message */
		$bbcode	= &new BBCodex(&$user, $request['message'], $forum['id'], 
			iif((isset($request['disable_html']) && $request['disable_html'] == 'on'), FALSE, TRUE), 
			iif((isset($request['disable_bbcode']) && $request['disable_bbcode'] == 'on'), FALSE, TRUE), 
			iif((isset($request['disable_emoticons']) && $request['disable_emoticons'] == 'on'), FALSE, TRUE), 
			iif((isset($request['disable_aurls']) && $request['disable_aurls'] == 'on'), FALSE, TRUE));
		
		/* Parse the bbcode */
		$body_text = $bbcode->parse();
		
		$template->setVar('newtopic_action', 'newtopic.php?act=updatetopic');

		if($request['submit'] == $template->getVar('L_SUBMIT')) {

			/**
			 * Build the queries to add the draft
			 */
			
			$update_a			= $dba->prepareStatement("UPDATE ". INFO ." SET name=? WHERE id=?");
			$update_b			= $dba->prepareStatement("UPDATE ". TOPICS ." SET body_text=?,posticon=?,disable_html=?,disable_bbcode=?,disable_emoticons=?,disable_sig=?,disable_areply=?,disable_aurls=?,is_draft=?,edited_time=?,edited_username=?,edited_userid=? WHERE topic_id=?");
			
			$update_a->setString(1, htmlentities($request['name'], ENT_QUOTES));
			$update_a->setInt(2, $topic['id']);
			
			$update_b->setString(1, $body_text);
			$update_b->setString(2, iif(($user['perms'] >= get_map($user, 'posticons', 'can_add', array('forum_id'=>$forum['id']))), @$request['posticon'], 'clear.gif'));
			$update_b->setInt(3, iif((isset($request['disable_html']) && $request['disable_html'] == 'on'), 1, 0));
			$update_b->setInt(4, iif((isset($request['disable_bbcode']) && $request['disable_bbcode'] == 'on'), 1, 0));
			$update_b->setInt(5, iif((isset($request['disable_emoticons']) && $request['disable_emoticons'] == 'on'), 1, 0));
			$update_b->setInt(6, iif((isset($request['enable_sig']) && $request['enable_sig'] == 'on'), 0, 1));
			$update_b->setInt(7, iif((isset($request['disable_areply']) && $request['disable_areply'] == 'on'), 1, 0));
			$update_b->setInt(8, iif((isset($request['disable_aurls']) && $request['disable_aurls'] == 'on'), 1, 0));
			$update_b->setInt(9, 0);
			$update_b->setInt(10, time());
			$update_b->setString(11, iif($user['id'] <= 0, htmlentities(@$request['poster_name'], ENT_QUOTES), $user['name']));
			$update_b->setInt(12, $user['id']);
			$update_b->setInt(13, $topic['id']);
			
			/**
			 * Do the queries
			 */
			$update_a->executeUpdate();
			$update_b->executeUpdate();
			
			/* Redirect the user */
			$template->setInfo('content', sprintf($template->getVar('L_UPDATEDTOPIC'), htmlentities($request['name'], ENT_QUOTES)));
			$template->setRedirect('viewtopic.php?id='. $topic['id'], 3);
		
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

			$template		= topic_post_options($template, $user, $forum);
						
			$topic_preview	= array(
								'id' => @$topic['id'],
								'name' => htmlentities($request['name'], ENT_QUOTES),
								'posticon' => @$request['posticon'],
								'body_text' => $body_text,
								'poster_name' => html_entity_decode($topic['poster_name'], ENT_QUOTES),
								'poster_id' => $user['id'],
								'disable_html' => iif((isset($request['disable_html']) && $request['disable_html'] == 'on'), 1, 0),
								'disable_sig' => iif((isset($request['enable_sig']) && $request['enable_sig'] == 'on'), 0, 1),
								'disable_bbcode' => iif((isset($request['disable_bbcode']) && $request['disable_bbcode'] == 'on'), 1, 0),
								'disable_emoticons' => iif((isset($request['disable_emoticons']) && $request['disable_emoticons'] == 'on'), 1, 0),
								'disable_areply' => iif((isset($request['disable_areply']) && $request['disable_areply'] == 'on'), 1, 0),
								'disable_aurls' => iif((isset($request['disable_aurls']) && $request['disable_aurls'] == 'on'), 1, 0)
								);

			/* Add the topic information to the template */
			$topic_iterator = &new TopicIterator($topic_preview, FALSE);
			$template->setList('topic', $topic_iterator);
			
			/* Assign the topic preview values to the template */
			$topic_preview['body_text'] = $request['message'];
			foreach($topic_preview as $key => $val)
				$template->setVar('topic_'. $key, $val);
			
			/* Assign the forum information to the template */
			foreach($forum as $key => $val)
				$template->setVar('forum_'. $key, $val);

			/* Set the the button display options */
			$template->hide('save_draft');
			$template->hide('load_button');
			$template->show('edit_topic');
			$template->show('topic_id');
			$template->hide('post_topic');
			$template->show('edit_post');
			
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_POSTTOPIC'), $forum['row_left'], $forum['row_right']);
			
			/* Set the post topic form */
			$template->setFile('preview', 'post_preview.html');
			$template->setFile('content', 'newtopic.html');
		}

		return TRUE;
	}
}

/**
 * Delete a topic
 */
class DeleteTopic extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_USERGROUPS;
		
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
		
		/* set the breadcrumbs bit */
		$template	= BreadCrumbs($template, $template->getVar('L_DELETETOPIC'), $forum['row_left'], $forum['row_right']);
		
		/* Are we dealing with a topic or a poll? */
		$type				= $topic['poll'] == 1 ? 'polls' : 'topics';

		/* Does this person have permission to remove this topic? */
		if($topic['poster_id'] == $user['id']) {
			if(get_map($type, 'can_del', array('forum_id'=>$forum['id'])) > $user['perms']) {
				$template->setInfo('content', $template->getVar('L_YOUNEEDPERMS'), FALSE);
				return TRUE;
			}
		} else {
			if(get_map($user, 'other_'. $type, 'can_del', array('forum_id'=>$forum['id'])) > $user['perms']) {
				$template->setInfo('content', $template->getVar('L_YOUNEEDPERMS'), FALSE);
				return TRUE;
			}
		}
		
		$user_usergroups	= @unserialize($user['usergroups']);
		$forum_usergroups	= @unserialize($forum['moderating_groups']);
		
		/* Check if this user belongs to one of this forums moderatign groups, if any exist */
		if(is_array($forum_usergroups) && !empty($forum_usergroups)) {
			if(is_array($user_usergroups) && !empty($user_usergroups)) {

				$error		= true;

				foreach($user_usergroups as $group) {
					if(!in_array($group, $forum_usergroups) && !$error) {
						$error	= false;
					} else {
						$error	= $_USERGROUPS[$group];
					}
				}
				
				if(!$error) {
					$template->setInfo('content', $template->getVar('L_YOUNEEDPERMS'), FALSE);
					return TRUE;
				}
			} else {
				$template->setInfo('content', $template->getVar('L_YOUNEEDPERMS'), FALSE);
				return TRUE;
			}
		}
				
		$num_replies		= @intval(($topic['row_right'] - $topic['row_left'] - 1) / 2);
		
		/* Get that last topic in this forum that's not this topic */
		$last_topic			= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE i.id <> ". intval($topic['id']) ." AND t.is_draft=0 ORDER BY i.created DESC LIMIT 1");
		$last_topic			= !$last_topic || !is_array($last_topic) ? array() : $last_topic;
		
		/* Get that last post in this forum that's not part of/from this topic */
		$last_post			= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". REPLIES ." r LEFT JOIN ". INFO ." i ON r.reply_id = i.id WHERE r.topic_id <> ". intval($topic['id']) ." ORDER BY i.created DESC LIMIT 1");
		$last_post			= !$last_post || !is_array($last_post) ? $last_topic : $last_post;
		
		/**
		 * Update the forum and the datastore
		 */
		
		$forum_update		= &$dba->prepareStatement("UPDATE ". FORUMS ." SET topics=topics-1,posts=posts-?,replies=replies-?,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
		$datastore_update	= &$dba->prepareStatement("UPDATE ". DATASTORE ." SET data=? WHERE varname=?");
			
		/* Set the forum values */
		$forum_update->setInt(1, intval($num_replies)+1);
		$forum_update->setInt(2, intval($num_replies));
		$forum_update->setInt(3, @$last_topic['created']);
		$forum_update->setString(4, @$last_topic['name']);
		$forum_update->setString(5, @$last_topic['poster_name']);
		$forum_update->setInt(6, @$last_topic['id']);
		$forum_update->setInt(7, @$last_topic['poster_id']);
		$forum_update->setString(8, @$last_topic['posticon']);
		$forum_update->setInt(9, @$last_post['created']);
		$forum_update->setString(10, @$last_post['name']);
		$forum_update->setString(11, @$last_post['poster_name']);
		$forum_update->setInt(12, @$last_post['id']);
		$forum_update->setInt(13, @$last_post['poster_id']);
		$forum_update->setString(14, @$last_post['posticon']);
		$forum_update->setInt(15, @$forum['id']);
		
		/* Set the datastore values */
		$datastore					= $_DATASTORE['forumstats'];
		$datastore['num_topics']	= $datastore['num_topics'] - 1;
		$datastore['num_replies']	= $datastore['num_replies'] - intval($num_replies);
		
		$datastore_update->setString(1, serialize($datastore));
		$datastore_update->setString(2, 'forumstats');
		
		/* Execute the forum and datastore update queries */
		$forum_update->executeUpdate();
		$datastore_update->executeUpdate();

		/**
		 * Change user post counts
		 */
		
		/* Update the user that posted this topic */
		if($topic['poster_id'] > 0)
			$dba->executeUpdate("UPDATE ". USERINFO ." SET num_posts=num_posts-1 WHERE user_id=". intval($topic['poster_id']));

		$users						= array();
		
		/* Only if there are more than 0 replies should we update post counts */
		if(intval($num_replies) > 0) {
			
			/* Get all of the replies */
			$replies				= $dba->executeQuery("SELET poster_id FROM ". REPLIES ." WHERE topic_id = ". intval($topic['id']));
			
			while($replies->next()) {
				$reply				= $replies->current();
				
				if(!isset($users[$reply]) && $reply > 0)
					$users[$reply] = 1;
				
				$users[$reply] += 1;
			}
			
			/* Memory saving */
			$replies->freeResult();
			unset($replies);

			/* Update all of the users that posted */
			if(count($users) > 0) {
				
				/* Loop through the users and change their post counts */
				foreach($users as $user_id => $num_posts) {
					$dba->executeUpdate("UPDATE ". USERINFO ." SET num_posts=num_posts-". intval($num_posts) ." WHERE user_id=". intval($user_id));
				}
			}

			/* Memory saving */
			unset($users);
		}

		/**
		 * Remove the topic and all of its replies
		 */
		
		/* Remove the topic and all replies from the information table */
		$h				= &new Heirarchy();
		$h->removeNode($topic, INFO);
		
		/* Now remove the information stored in the topics and replies table */
		$dba->executeUpdate("DELETE FROM ". TOPICS ." WHERE topic_id = ". intval($topic['id']));
		$dba->executeUpdate("DELETE FROM ". REPLIES ." WHERE topic_id = ". intval($topic['id']));

		/* Redirect the user */
		$template->setInfo('content', sprintf($template->getVar('L_DELETEDTOPIC'), $topic['name'], $forum['name']));
		$template->setRedirect('viewforum.php?id='. $forum['id'], 3);
		return TRUE;
	}
}

/**
 * Set the topic locking parameters
 */
class LockTopic extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_USERGROUPS;
		
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

		if(get_map($user, 'closed', 'can_add', array('forum_id' => $forum['id'])) > $user['perms']) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_YOUNEEDPERMS'), FALSE);
			return TRUE;
		}
		
		/* set the breadcrumbs bit */
		$template	= BreadCrumbs($template, $template->getVar('L_LOCKTOPIC'), $topic['row_left'], $topic['row_right']);
	
		/* Lock the topic */
		$lock		= &$dba->prepareStatement("UPDATE ". TOPICS ." SET topic_locked=1 WHERE topic_id=?");
		$lock->setInt(1, $topic['id']);
		$lock->executeUpdate();

		/* Redirect the user */
		$template->setInfo('content', sprintf($template->getVar('L_LOCKEDTOPIC'), $topic['name']));
		$template->setRedirect('viewtopic.php?id='. $topic['id'], 3);
		return TRUE;
	}
}

/**
 * Set the topic locking parameters
 */
class UnlockTopic extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_USERGROUPS;
		
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

		if(get_map($user, 'closed', 'can_add', array('forum_id' => $forum['id'])) > $user['perms']) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_YOUNEEDPERMS'), FALSE);
			return TRUE;
		}
		
		/* set the breadcrumbs bit */
		$template	= BreadCrumbs($template, $template->getVar('L_UNLOCKTOPIC'), $topic['row_left'], $topic['row_right']);
	
		/* Lock the topic */
		$lock		= &$dba->prepareStatement("UPDATE ". TOPICS ." SET topic_locked=0 WHERE topic_id=?");
		$lock->setInt(1, $topic['id']);
		$lock->executeUpdate();

		/* Redirect the user */
		$template->setInfo('content', sprintf($template->getVar('L_UNLOCKEDTOPIC'), $topic['name']));
		$template->setRedirect('viewtopic.php?id='. $topic['id'], 3);
		return TRUE;
	}
}

/**
 * Make the topic image for a specified topic
 */
function topic_image($topic, &$user, $img_dir, $lastactive) {
	global $settings;

	/* The dot means that this user posted this topic */
	if($user['name'] == $topic['poster_name'])
		$extra = 'dot_';
	else
		$extra = NULL;
	
	$EXT						= 'gif';
	
	$lock						= intval(@$topic['topic_locked']) == 1 ? 'lock' : NULL;
		
	/* Set the number of replies that this topic has */
	$topic_num_replies			= @(($topic['row_right'] - $topic['row_left'] - 1) / 2);

	/* If this topic is a Sticky */
	if(@$topic['topic_type'] == TOPIC_STICKY) {
		
		/* If the last reply time is greater than the user's last activity */
		if($topic['reply_time'] >= $lastactive)
			$image = 'Images/'. $img_dir .'/Icons/Status/newsticky.'.$EXT;
		else
			$image = 'Images/'. $img_dir .'/Icons/Status/sticky.'.$EXT;
	
	/* If this topic is an Announcement */
	} else if(@$topic['topic_type'] == TOPIC_ANNOUNCE || @$topic['topic_type'] == TOPIC_GLOBAL) {

		/* If the last reply time is greater than the user's last activity */
		if($topic['reply_time'] >= $lastactive)
			$image = 'Images/'. $img_dir .'/Icons/Status/newannounce.'.$EXT;
		else
			$image = 'Images/'. $img_dir .'/Icons/Status/announce.'.$EXT;
	
	} else if(@$topic['poll'] == 1) { // poll
		
		$image = 'Images/'. $img_dir .'/Icons/Status/poll.'.$EXT; // hot topic, new posts
	
	/* If the number of views is greater than 300, or the number of replies is greater thand 30 */
	} else if($topic['views'] >= 300 || $topic_num_replies >= 30) {
		
		/* If the last reply time is greater than the user's last activity */
		if($topic['reply_time'] >= $lastactive)
			$image = 'Images/'. $img_dir .'/Icons/Status/'.$extra.'newhot'.$lock.'folder.'.$EXT; // hot topic, new posts
		else
			$image = 'Images/'. $img_dir .'/Icons/Status/'.$extra.'hot'.$lock.'folder.'.$EXT; // hot topic, no new posts
	
	/* If the number of views is less than 300 or the number of replies is less than 30 */
	} else if($topic['views'] < 300 || $topic_num_replies < 30) {
		
		/* If the last reply time of the topic is greater than the user's last activity */
		if($topic['reply_time'] > $lastactive) {
			$image = 'Images/'. $img_dir .'/Icons/Status/'.$extra.'new'.$lock.'folder.'.$EXT; // topic, new posts
		
		/* If the last reply time of the topic is less than the user's last activity */
		} else if($topic['reply_time'] < $lastactive) {
			$image = 'Images/'. $img_dir .'/Icons/Status/'.$extra.$lock.'folder.'.$EXT; // topic, no new posts
		
		/* default */
		} else {
			$image = 'Images/'. $img_dir .'/Icons/Status/'.$extra.$lock.'folder.'.$EXT; // topic, no new posts
		}

	/* Default in case */
	} else {
		$image = 'Images/'. $img_dir .'/Icons/Status/'.$extra.'new'.$lock.'folder.'.$EXT; // topic, no new posts
	}
	
	return $image;
}

class TopicsIterator extends FAProxyIterator {
	
	var $result;
	var $session;
	var $img_dir;
	var $forums;
	var $dba;

	function TopicsIterator($result, &$session, $img_dir) {
		
		global $_DBA;

		$this->result			= &$result;
		$this->session			= &$session;
		$this->img_dir			= $img_dir;
		$this->dba				= $_DBA;

		$this->forums			= isset($_COOKIE['forums']) && $_COOKIE['forums'] != NULL && $_COOKIE['forums'] != '' ? @unserialize($_COOKIE['forums']) : array();

		parent::FAProxyIterator($this->result);
	}

	function &current() {
		$temp					= parent::current();

		/* Get this user's last seen time */
		//$last_seen				= is_a($this->session['user'], 'Member') ? iif($this->session['seen'] > $this->session['user']->info['last_seen'], $this->session['seen'], $this->session['user']->info['last_seen']) : $this->session['seen'];
		$last_seen				= time();

		/* Set the topic icons */
		$temp['posticon']		= $temp['posticon'] != '' ? iif(file_exists(FORUM_BASE_DIR .'/tmp/upload/posticons/'. $temp['posticon']), $temp['posticon'], 'clear.gif') : 'clear.gif';
		$temp['topicicon']		= topic_image($temp, &$this->user, $this->img_dir, $last_seen);
		
		/* Set the number of replies */
		$temp['num_replies']	= @(($temp['row_right'] - $temp['row_left'] - 1) / 2);
		
		/* Check if this topic has been read or not */
		if(($temp['created'] > $last_seen && $temp['poster_id'] != $this->session['user']->info['id'])
		|| (isset($this->forums[$temp['forum_id']][$temp['id']]) && $this->forums[$temp['forum_id']][$temp['id']])
			) {
			
			$this->forums[$temp['forum_id']][$temp['id']]	= TRUE;
			
			$temp['name']									= '<strong>'. $temp['name'] .'</strong>';
		}
		
		/* Is this a sticky or an announcement and is it expired? */
		if($temp['topic_type'] > TOPIC_NORMAL && $temp['topic_expire'] > 0) {
			if(($temp['created'] + (3600 * 24 * $temp['topic_expire']) ) > time()) {
				
				$this->dba->executeUpdate("UPDATE ". TOPICS ." SET topic_expire=0,topic_type=". TOPIC_NORMAL ." WHERE topic_id = ". intval($temp['id']));
			}
		}

		/* Should we free the result? */
		if($this->row == $this->size-1) {
			$this->result->freeResult();
			
			/* Reset the forums cookie if we're at the end of the iteration */
			bb_settopic_cache_item('forums', serialize($this->forums), time() + 3600 * 25 * 5);
		}

		return $temp;
	}
}

class TopicIterator extends FAArrayIterator {
	
	var $dba;
	var $result;
	var $users = array();
	var $qp;
	var $sr;

	function TopicIterator($topic, $show_replies = TRUE) {
		
		global $_DBA, $_QUERYPARAMS, $_USERGROUPS;
		
		$this->qp						= $_QUERYPARAMS;
		$this->sr						= (bool)$show_replies;
		$this->dba						= &$_DBA;
		$this->groups					= $_USERGROUPS;

		parent::FAArrayIterator(array($topic));
	}

	function &current() {
		$temp							= parent::current();
		
		$temp['posticon']				= @$temp['posticon'] != '' ? iif(file_exists(FORUM_BASE_DIR .'/tmp/upload/posticons/'. @$temp['posticon']), @$temp['posticon'], 'clear.gif') : 'clear.gif';

		if($temp['poster_id'] > 0) {
			$user						= $this->dba->getRow("SELECT ". $this->qp['user'] . $this->qp['userinfo'] ." FROM ". USERS ." u LEFT JOIN ". USERINFO ." ui ON u.id=ui.user_id WHERE u.id=". intval($temp['poster_id']));
			
			$group						= get_user_max_group($user, $this->groups);
			$user['group_color']		= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
			$user['group_nicename']		= $group['nicename'];
			$user['group_avatar']		= $group['avatar'];
			
			foreach($user as $key => $val)
				$temp['post_user_'. $key] = $val;
			
			/* This array holds all of the userinfo for users that post to this topic */
			$this->users[$user['id']]	= $user;
			
		}
	
	
		/* Do we have any replies? */
		$num_replies					= @(($temp['row_right'] - $temp['row_left'] - 1) / 2);

		if($this->sr && $num_replies > 0) {
			$this->result				= &$this->dba->executeQuery("SELECT ". $this->qp['info'] . $this->qp['reply'] ." FROM ". REPLIES ." r LEFT JOIN ". INFO ." i ON i.id=r.reply_id WHERE r.topic_id = ". intval($temp['id']) ." AND i.created >= ". (3600 * 24 * intval($temp['daysprune'])) ." ORDER BY i.". $temp['sortedby'] ." ". $temp['sortorder'] ." LIMIT ". intval($temp['start']) .", ". intval($temp['postsperpage']));
			
			$temp['replies']			= &new RepliesIterator($this->result, $this->qp, $this->dba, $this->users, $this->groups);

		}

		return $temp;
	}
}


function topic_post_options(&$template, &$user, $forum) {
	
	/** 
	 * Set the posting allowances for a specific forum
	 */
	$template->setVar('forum_user_topic_options', sprintf($template->getVar('L_FORUMUSERTOPICPERMS'),
	iif((get_map($user, 'topics', 'can_add', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'topics', 'can_edit', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'topics', 'can_del', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'attachments', 'can_add', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN'))));

	$template->setVar('forum_user_reply_options', sprintf($template->getVar('L_FORUMUSERREPLYPERMS'),
	iif((get_map($user, 'replies', 'can_add', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'replies', 'can_edit', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'replies', 'can_del', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN'))));
	
	$template->setVar('posting_code_options', sprintf($template->getVar('L_POSTBBCODEOPTIONS'),
	iif((get_map($user, 'html', 'can_add', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif((get_map($user, 'bbcode', 'can_add', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif((get_map($user, 'bbimgcode', 'can_add', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif((get_map($user, 'bbflashcode', 'can_add', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif((get_map($user, 'emoticons', 'can_add', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_OFF'), $template->getVar('L_ON'))));

	return $template;
}

?>