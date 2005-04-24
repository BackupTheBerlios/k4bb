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
* @version $Id: topics.class.php,v 1.7 2005/04/24 02:11:13 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

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
			$template->setInfo('content', $template->getVar('L_CANTPOSTTOCATEGORY'), FALSE);
			return TRUE;
		}

		/* Do we have permission to post to this forum? */
		if($user['perms'] < $user['maps']['forums'][$forum['id']]['topics']['can_add']) {
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

			/**
			 * Build the queries
			 */

			/* Prepare the queries */
			$update_a			= &$dba->prepareStatement("UPDATE ". INFO ." SET row_right = row_right+2 WHERE row_left < ? AND row_right >= ?");
			$update_b			= &$dba->prepareStatement("UPDATE ". INFO ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= ?");
			$insert_a			= &$dba->prepareStatement("INSERT INTO ". INFO ." (name,parent_id,row_left,row_right,row_type,row_level,created) VALUES (?,?,?,?,?,?,?)");
			$insert_b			= &$dba->prepareStatement("INSERT INTO ". TOPICS ." (topic_id,forum_id,category_id,poster_name,poster_id,body_text,posticon,disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls,is_draft) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			
			/* Set the insert variables needed */
			$update_a->setInt(1, $left);
			$update_a->setInt(2, $left);
			$update_b->setInt(1, $left);

			$update_a->executeUpdate();
			$update_b->executeUpdate();
			
			/* Set the inserts for adding the actual node */
			$insert_a->setString(1, $request['name']);
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
			$insert_b->setString(4, $user['name']);
			$insert_b->setInt(5, $user['id']);
			$insert_b->setString(6, $body_text);
			$insert_b->setString(7, @$request['posticon']);
			$insert_b->setInt(8, iif((isset($request['disable_html']) && $request['disable_html'] == 'on'), 1, 0));
			$insert_b->setInt(9, iif((isset($request['disable_bbcode']) && $request['disable_bbcode'] == 'on'), 1, 0));
			$insert_b->setInt(10, iif((isset($request['disable_emoticons']) && $request['disable_emoticons'] == 'on'), 1, 0));
			$insert_b->setInt(11, iif((isset($request['enable_sig']) && $request['enable_sig'] == 'on'), 0, 1));
			$insert_b->setInt(12, iif((isset($request['disable_areply']) && $request['disable_areply'] == 'on'), 1, 0));
			$insert_b->setInt(13, iif((isset($request['disable_aurls']) && $request['disable_aurls'] == 'on'), 1, 0));
			$insert_b->setInt(14, iif($request['submit'] == $template->getVar('L_SAVEDRAFT'), 1, 0));

			$insert_b->executeUpdate();
			
			/** 
			 * Update the forum, and update the datastore 
			 */

			//topic_created,topic_name,topic_uname,topic_id,topic_uid,post_created,post_name,post_uname,post_id,post_uid
			$forum_update		= &$dba->prepareStatement("UPDATE ". FORUMS ." SET topics=topics+1,posts=posts+1,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=? WHERE forum_id=?");
			$datastore_update	= &$dba->prepareStatement("UPDATE ". DATASTORE ." SET data=? WHERE varname=?");
			$user_update		= $dba->executeUpdate("UPDATE ". USERINFO ." SET num_posts=num_posts+1 WHERE user_id=". intval($user['id']));
			
			/* If this isn't a draft, update the forums and datastore tables */
			if($request['submit'] != $template->getVar('L_SAVEDRAFT')) {
			
				/* Set the forum values */
				$forum_update->setInt(1, $created);
				$forum_update->setString(2, $request['name']);
				$forum_update->setString(3, $user['name']);
				$forum_update->setInt(4, $topic_id);
				$forum_update->setInt(5, $user['id']);
				$forum_update->setInt(6, $created);
				$forum_update->setString(7, $request['name']);
				$forum_update->setString(8, $user['name']);
				$forum_update->setInt(9, $topic_id);
				$forum_update->setInt(10, $user['id']);
				$forum_update->setInt(11, $forum['id']);
				
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
				$template->setInfo('content', sprintf($template->getVar('L_ADDEDTOPIC'), $request['name'], $forum['name']));
				$template->setRedirect('viewtopic.php?id='. $topic_id, 3);
			} else {
				/* Redirect the user */
				$template->setInfo('content', sprintf($template->getVar('L_SAVEDDRAFTTOPIC'), $request['name'], $forum['name']));
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

			$template = topic_post_options($template, $user, $forum);
						
			$topic_preview	= array(
								'name' => $request['name'],
								'body_text' => $body_text,
								'poster_name' => $user['name'],
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
			$template->setInfo('content', $template->getVar('L_CANTPOSTTOCATEGORY'), FALSE);
			return TRUE;
		}

		/* Do we have permission to post to this forum? */
		if($user['perms'] < $user['maps']['forums'][$forum['id']]['topics']['can_add']) {
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
			
			$update_a->setString(1, $request['name']);
			$update_a->setInt(2, $created);
			$update_a->setInt(3, $draft['id']);
			
			$update_b->setString(1, $body_text);
			$update_b->setString(2, @$request['posticon']);
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

			$forum_update		= &$dba->prepareStatement("UPDATE ". FORUMS ." SET topics=topics+1,posts=posts+1,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=? WHERE forum_id=?");
			$datastore_update	= &$dba->prepareStatement("UPDATE ". DATASTORE ." SET data=? WHERE varname=?");
			$user_update		= $dba->executeUpdate("UPDATE ". USERINFO ." SET num_posts=num_posts+1 WHERE user_id=". intval($user['id']));	
				
			/* Set the forum values */
			$forum_update->setInt(1, $created);
			$forum_update->setString(2, $request['name']);
			$forum_update->setString(3, $user['name']);
			$forum_update->setInt(4, $draft['id']);
			$forum_update->setInt(5, $user['id']);
			$forum_update->setInt(6, $created);
			$forum_update->setString(7, $request['name']);
			$forum_update->setString(8, $user['name']);
			$forum_update->setInt(9, $draft['id']);
			$forum_update->setInt(10, $user['id']);
			$forum_update->setInt(11, $forum['id']);
			
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
			$template->setInfo('content', sprintf($template->getVar('L_ADDEDTOPIC'), $request['name'], $forum['name']));
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
			
			$template	= topic_post_options($template, $user, $forum);

			/* Set the forum info to the template */
			foreach($forum as $key => $val)
				$template->setVar('forum_'. $key, $val);
			
			$template->setVar('newtopic_action', 'newtopic.php?act=postdraft');

			$template = topic_post_options($template, $user, $forum);
						
			$topic_preview	= array(
								'id' => @$draft['id'],
								'name' => $request['name'],
								'body_text' => $body_text,
								'poster_name' => $user['name'],
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
			
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_POSTTOPIC'), $forum['row_left'], $forum['row_right']);
			
			/* Set the post topic form */
			$template->setFile('preview', 'post_preview.html');
			$template->setFile('content', 'newtopic.html');
		}

		return TRUE;
	}
}

function topic_image($topic, &$user, $img_dir, $lastactive) {
	global $settings;

	/* The dot means that this user posted this topic */
	if($user['name'] == $topic['poster_name'])
		$extra = 'dot_';
	else
		$extra = NULL;
	
	$EXT						= 'gif';
	
	$lock						= intval(@$topic['row_locked']) == 1 ? 'lock' : NULL;
		
	/* Set the number of replies that this topic has */
	$topic_num_replies			= @(($topic['row_right'] - $topic['row_left'] - 1) / 2);

	/* If this topic is a Sticky */
	if(@$topic['row_status'] == 2) {
		
		/* If the last reply time is greater than the user's last activity */
		if($topic['reply_time'] >= $lastactive)
			$image = 'Images/'. $img_dir .'/Icons/Status/newsticky.'.$EXT;
		else
			$image = 'Images/'. $img_dir .'/Icons/Status/sticky.'.$EXT;
	
	/* If this topic is aan Announcement */
	} else if(@$topic['row_status'] == 3) {

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

	function TopicsIterator($result, &$session, $img_dir) {
		
		$this->result			= &$result;
		$this->session			= &$session;
		$this->img_dir			= $img_dir;

		$this->forums			= isset($_COOKIE['forums']) && $_COOKIE['forums'] != NULL && $_COOKIE['forums'] != '' ? @unserialize($_COOKIE['forums']) : array();

		parent::FAProxyIterator($this->result);
	}

	function &current() {
		$temp					= parent::current();

		/* Get this user's last seen time */
		$last_seen				= is_a($this->session['user'], 'Member') ? iif($this->session['seen'] > $this->session['user']->info['last_seen'], $this->session['seen'], $this->session['user']->info['last_seen']) : $this->session['seen'];
		
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
	var $preview;

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
		
		if($temp['poster_id'] > 0) {
			$user						= $this->dba->getRow("SELECT ". $this->qp['user'] . $this->qp['userinfo'] ." FROM ". USERS ." u LEFT JOIN ". USERINFO ." ui ON u.id=ui.user_id WHERE u.id=". intval($temp['poster_id']));
			
			$group						= get_user_max_group($user, $this->groups);
			$user['color']				= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];

			foreach($user as $key => $val)
				$temp['post_user_'. $key] = $val;
			
			/* This array holds all of the userinfo for users that post to this topic */
			$this->users[$user['id']]	= $user;
			
		}
	
	
		/* Do we have any replies? */
		$num_replies					= @(($temp['row_right'] - $temp['row_left'] - 1) / 2);

		if($this->sr && $num_replies > 0) {
			
			$this->result				= &$this->dba->executeQuery("SELECT ". $this->qp['info'] . $this->qp['reply'] ." FROM ". INFO ." i LEFT JOIN ". REPLIES ." ON i.id=r.reply_id WHERE r.created >= ". (3600 * 24 * intval($temp['daysprune'])) ." ORDER BY ". $temp['sortedby'] ." ". $temp['sortorder'] ." LIMIT ". intval($temp['start']) .", ". intval($temp['postsperpage']));

			$temp['replies']			= &new RepliesIterator($this->result, $this->qp, $this->dba, $this->users, $this->groups);

		}
		return $temp;
	}
}

class RepliesIterator extends FAProxyIterator {
	
	var $result;
	var $session;
	var $img_dir;
	var $forums;

	function RepliesIterator(&$result, $queryparams, &$dba, $users, $groups) {
		
		$this->users			= $users;
		$this->qp				= $queryparams;
		$this->dba				= &$_DBA;
		$this->result			= &$result;
		$this->groups			= $groups;

		parent::FAProxyIterator($this->result);
	}

	function &current() {
		$temp					= parent::current();

		if($temp['poster_id'] > 0) {
			
			if(!isset($this->users[$temp['poster_id']])) {
			
				$user						= $this->dba->getRow("SELECT ". $this->qp['user'] . $this->qp['userinfo'] ." FROM ". USERS ." u LEFT JOIN ". USERINFO ." ui ON u.id=ui.user_id WHERE u.id=". intval($temp['poster_id']));
				
				$group						= get_user_max_group($user, $this->groups);
				$user['color']				= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
				
				$this->users[$user['id']]	= $user;
			} else {
				
				$user						= $this->users[$temp['poster_id']];
			}

			foreach($user as $key => $val)
				$temp['post_user_'. $key] = $val;
		}

		/* Should we free the result? */
		if($this->row == $this->size-1) {
			$this->result->freeResult();
		}

		return $temp;
	}
}

function topic_post_options(&$template, &$user, $forum) {
	
	/** 
	 * Set the posting allowances for a specific forum
	 */
	$template->setVar('forum_user_topic_options', sprintf($template->getVar('L_FORUMUSERTOPICPERMS'),
	iif(($user['maps']['forums'][$forum['id']]['topics']['can_add'] > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif(($user['maps']['forums'][$forum['id']]['topics']['can_edit'] > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif(($user['maps']['forums'][$forum['id']]['topics']['can_del'] > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif(($user['maps']['forums'][$forum['id']]['attachments']['can_add'] > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN'))));

	$template->setVar('forum_user_reply_options', sprintf($template->getVar('L_FORUMUSERREPLYPERMS'),
	iif(($user['maps']['forums'][$forum['id']]['replies']['can_add'] > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif(($user['maps']['forums'][$forum['id']]['replies']['can_edit'] > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif(($user['maps']['forums'][$forum['id']]['replies']['can_del'] > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN'))));
	
	$template->setVar('posting_code_options', sprintf($template->getVar('L_POSTBBCODEOPTIONS'),
	iif(($user['maps']['forums'][$forum['id']]['html']['can_add'] > $user['perms']), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif(($user['maps']['forums'][$forum['id']]['bbcode']['can_add'] > $user['perms']), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif(($user['maps']['forums'][$forum['id']]['bbimgcode']['can_add'] > $user['perms']), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif(($user['maps']['forums'][$forum['id']]['bbflashcode']['can_add'] > $user['perms']), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif(($user['maps']['forums'][$forum['id']]['emoticons']['can_add'] > $user['perms']), $template->getVar('L_OFF'), $template->getVar('L_ON'))));

	return $template;
}

?>