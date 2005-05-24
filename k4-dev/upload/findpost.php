<?php
/**
* k4 Bulletin Board, findpost.php
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
* @version $Id: findpost.php,v 1.4 2005/05/24 20:09:16 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

ob_start();

require 'forum.inc.php';

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS;
		
		$next		= FALSE;
		$prev		= FALSE;

		if(isset($request['next']) && intval($request['next']) == 1)
			$next = TRUE;
		if(isset($request['prev']) && intval($request['prev']) == 1)
			$prev = TRUE;

		/**
		 * Error Checking
		 */
		if(!isset($request['id']) || !$request['id'] || intval($request['id']) <= 0) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDPOST'));
			$template->setInfo('content', $template->getVar('L_POSTDOESNTEXIST'), FALSE);
		}

		$post	= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] ." FROM ". INFO ." i WHERE i.id = ". intval($request['id']));
		
		if(!is_array($post) || !$post || empty($post)) {
			
			if($next || $prev)
				header("Location: ". referer());

			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDPOST'));
			$template->setInfo('content', $template->getVar('L_POSTDOESNTEXIST'), FALSE);
		}

		if($post['row_type'] != TOPIC && $post['row_type'] != REPLY) {
			
			if($next || $prev)
				header("Location: ". referer());

			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDPOST'));
			$template->setInfo('content', $template->getVar('L_POSTDOESNTEXIST'), FALSE);
		}
		
		/* If this is a topic */
		if($post['row_type'] == TOPIC) {
			
			/**
			 * We don't error check here because that would just be redundant. There is already
			 * error checking in viewtopic.php, and it will make sure that this isn't a draft,
			 * its info exits, etc.
			 */
			
			header("Location: viewtopic.php?id=". $post['id']);

		/* If this is a reply */	
		} else {
			
			if($next || $prev)
				header("Location: ". referer());

			$reply				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". REPLIES ." r LEFT JOIN ". INFO ." i ON i.id=r.reply_id WHERE r.reply_id = ". intval($post['id']));
			
			if(!$reply || !is_array($reply) || empty($reply)) {
				/* set the breadcrumbs bit */
				$template		= BreadCrumbs($template, $template->getVar('L_INVALIDPOST'));
				$template->setInfo('content', $template->getVar('L_POSTDOESNTEXIST'), FALSE);

				return TRUE;
			}

			$topic				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($reply['topic_id']));
			
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
				$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
				return TRUE;
			}
			

			$num_replies		= @intval(($topic['row_right'] - $topic['row_left'] - 1) / 2);
			
			/* If the number of replies on this topic is greater than the posts per page for this forum */
			if($num_replies > $forum['postsperpage']) {
				
				$whereinline	= $dba->getValue("SELECT COUNT(r.reply_id) FROM ". REPLIES ." r LEFT JOIN ". INFO ." i ON i.id = r.reply_id WHERE r.topic_id = ". intval($reply['topic_id']) ." AND i.created < ". intval($reply['created']) ." ORDER BY i.created ASC");
				
				$page		= ceil($whereinline / $forum['postsperpage']);
				$page		= $page <= 0 ? 1 : $page;

				header("Location: viewtopic.php?id=". $topic['id'] ."&page=". intval($page) ."&limit=". $forum['postsperpage'] ."&order=ASC&sort=created&daysprune=0#". $post['id']);
				exit;

			} else {
				header("Location: viewtopic.php?id=". $topic['id'] ."#". $post['id']);
				exit;
			}
		}

		return TRUE;
	}
}

$app = new Forum_Controller('forum_base.html');

$app->ExecutePage();

ob_flush();

?>