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
* @version $Id: topics.class.php,v 1.3 2005/04/07 23:33:50 k4st Exp $
* @package k42
*/

class PostTopic extends Event {
	function getNumOnLevel($row_left, $row_right, $level) {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". INFO ." WHERE row_left > $row_left AND row_right < $row_right AND row_level = $level");
	}
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS;
		
		/* Check the request ID */
		if(!isset($request['forum_id']) || !$request['forum_id'] || intval($request['forum_id']) == 0) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			return $template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
		}
			
		$forum				= get_cached_forum($request['forum_id']);
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			return $template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			return $template->setInfo('content', $template->getVar('L_CANTPOSTTOCATEGORY'), FALSE);		
		}

		/* Do we have permission to post to this forum? */
		if($user['perms'] < $user['maps']['forums'][$forum['id']]['topics']['can_add']) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			return $template->setInfo('content', $template->getVar('L_PERMCANTPOST'), FALSE);		
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
		
		/**
		 * Build the queries
		 */

		/* Prepare the queries */
		$update_a			= &$dba->prepareStatement("UPDATE ". INFO ." SET row_right = row_right+2 WHERE row_left < ? AND row_right >= ?");
		$update_b			= &$dba->prepareStatement("UPDATE ". INFO ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= ?");
		$insert_a			= &$dba->prepareStatement("INSERT INTO ". INFO ." (name,parent_id,row_left,row_right,row_type,row_level,created) VALUES (?,?,?,?,?,?,?)");
		$insert_b			= &$dba->prepareStatement("INSERT INTO ". TOPICS ." (topic_id,forum_id,category_id,poster_name,poster_id,body_text) VALUES (?,?,?,?,?,?)");
		
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
		$insert_a->setInt(7, time());
		
		$insert_a->executeUpdate();

		$topic_id			= $dba->insertId();

		topic_id,forum_id,category_id,poster_name,poster_id,body_text
		$insert_b->setInt(1, $topic_id);
		$insert_b->setInt(2, $forum['id']);
		$insert_b->setInt(3, $dba->getValue("SELECT category_id FROM ". FORUMS ." WHERE forum_id = ". $forum['id']));
		$insert_b->setString(4, $user['name']);
		$insert_b->setInt(5, $user['id']);
		$insert_b->setString(6, $body_text);
		
		/* set the breadcrumbs bit */
		$template	= BreadCrumbs($template, $template->getVar('L_POSTTOPIC'), $forum['row_left'], $forum['row_right']);
		

		return TRUE;
	}
}

?>