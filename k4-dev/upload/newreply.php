<?php
/**
* k4 Bulletin Board, newreply.php
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
* @version $Id: newreply.php,v 1.3 2005/05/03 21:35:59 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

require 'forum.inc.php';

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS;
		
		/**
		 * Error checking 
		 */

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

		/* Do we have permission to post to this topic in this forum? */
		if($user['perms'] < get_map($user, 'replies', 'can_add', array('forum_id'=>$forum['id']))) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			return $template->setInfo('content', $template->getVar('L_PERMCANTPOST'), FALSE);		
		}

		if(isset($request['r']) && intval($request['r']) != 0) {
			$reply				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". REPLIES ." r LEFT JOIN ". INFO ." i ON r.reply_id = i.id WHERE i.id = ". intval($request['r']));
			
			if(!$reply || !is_array($reply) || empty($reply)) {
				/* set the breadcrumbs bit */
				$template		= BreadCrumbs($template, $template->getVar('L_INVALIDREPLY'));
				$template->setInfo('content', $template->getVar('L_REPLYDOESNTEXIST'), FALSE);
				
				return TRUE;
			} else {
				
				$template->show('parent_id');
				$template->setVar('parent_id', $reply['id']);
			}
		}

		$parent			= isset($reply) && is_array($reply) ? $reply : $topic;
				
		/**
		 * Start setting useful template information
		 */
		

		/* Get and set the emoticons and post icons to the template */
		$emoticons	= &$dba->executeQuery("SELECT * FROM ". EMOTICONS ." WHERE clickable = 1");
		$posticons	= &$dba->executeQuery("SELECT * FROM ". POSTICONS);

		$template->setList('emoticons', $emoticons);
		$template->setList('posticons', $posticons);

		$template->setVar('emoticons_per_row', $template->getVar('smcolumns'));
		$template->setVar('emoticons_per_row_remainder', $template->getVar('smcolumns')-1);
		
		$template	= topic_post_options($template, $user, $forum);

		/* Set the forum and topic info to the template */
		foreach($forum as $key => $val)
			$template->setVar('forum_'. $key, $val);

		/* We set topic information to be reply information */
		foreach($topic as $key => $val) {
			
			/* Omit the body text variable */
			if($key != 'body_text')
				$template->setVar('reply_'. $key, $val);
		}

		/* If this is a quote, put quote tags around the message */
		if(isset($request['quote']) && intval($request['quote']) == 1) {
			$bbcode			= &new BBCodex($user, $parent['body_text'], $forum['id'], TRUE, TRUE, TRUE, TRUE);
			$template->setVar('reply_body_text', '[quote='. $parent['poster_name'] .']'. $bbcode->revert() .'[/quote]');
		}

		/* Set the title variable */
		if(isset($reply))
			$template->setVar('reply_name', $template->getVar('L_RE') .': '. $reply['name']);
		else
			$template->setVar('reply_name', $template->getVar('L_RE') .': '. $topic['name']);

		$template->setVar('newtopic_action', 'newreply.php?act=postreply');

		/* set the breadcrumbs bit */
		$template	= BreadCrumbs($template, $template->getVar('L_POSTREPLY'), $parent['row_left'], $parent['row_right']);
		
		foreach($parent as $key => $val)
			$template->setVar('parent_'. $key, $val);
		
		/* Get the number of replies to this topic */
		$num_replies		= @intval(($topic['row_right'] - $topic['row_left'] - 1) / 2);

		/* Get replies that are above this point */
		if($num_replies > $forum['postsperpage']) {
			
			/* This will get all parent replies */
			$query	= "SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". REPLIES ." r LEFT JOIN ". INFO ." i ON i.id = r.reply_id WHERE i.row_left >= ". $parent['row_left'] ." AND i.row_right <= ". $parent['row_right'] ." AND i.row_type = ". REPLY ." ORDER BY i.created DESC LIMIT 10";
		} else {
			
			/* Get generalized replies */
			$query	= "SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". REPLIES ." r LEFT JOIN ". INFO ." i ON i.id = r.reply_id WHERE r.topic_id = ". $topic['id'] ." AND i.row_type = ". REPLY ." ORDER BY i.created DESC LIMIT 10";
		}
		
		$replies	= &$dba->executeQuery($query);
		
		/* Set the form actiob */
		$template->setVar('newreply_act', 'newreply.php?act=postreply');

		$template->setList('topic_review', new TopicReviewIterator($topic, $replies, $user));

		/* Set the post topic form */
		$template->setFile('content', 'newreply.html');

		return TRUE;
	}
}


$app = new Forum_Controller('forum_base.html');

$app->AddEvent('postreply', new PostReply);
$app->AddEvent('editreply', new EditReply);
$app->AddEvent('updatereply', new UpdateReply);

$app->ExecutePage();

?>