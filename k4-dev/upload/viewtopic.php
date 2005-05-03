<?php
/**
* k4 Bulletin Board, viewtopic.php
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
* @version $Id: viewtopic.php,v 1.8 2005/05/03 23:08:23 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

require 'forum.inc.php';

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_URL, $_QUERYPARAMS, $_USERGROUPS;
		
		/**
		 * Error Checking
		 */
		if(!isset($request['id']) || !$request['id'] || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDTOPIC'));
			$template->setInfo('content', $template->getVar('L_TOPICDOESNTEXIST'), FALSE);
		}
		
		/* Get our topic */
		$topic				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($request['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDTOPIC'));
			$template->setInfo('content', $template->getVar('L_TOPICDOESNTEXIST'), FALSE);

			return TRUE;
		}	
		
		if($topic['is_draft'] == 1) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDTOPICVIEW'));
			$template->setInfo('content', $template->getVar('L_CANTVIEWDRAFT'), FALSE);
			
			return TRUE;
		}

		/* Get the current forum */
		$forum				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($topic['forum_id']));

		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);

			return TRUE;
		}

		if(get_map($user, 'forums', 'can_view', array()) > $user['perms'] || get_map($user, 'topics', 'can_view', array('forum_id'=>$forum['id'])) > $user['perms']) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INFORMATION'), $forum['row_left'], $forum['row_right']);
			$template->setInfo('content', $template->getVar('L_PERMCANTVIEWTOPIC'), FALSE);
			
			return TRUE;
		}
		
		/**
		 * Set the new breadcrumbs bit
		 */
		$template			= BreadCrumbs($template, $topic['name'], iif($topic['topic_type'] == TOPIC_GLOBAL, FALSE, $forum['row_left']), iif($topic['topic_type'] == TOPIC_GLOBAL, FALSE, $forum['row_right']));

		/** 
		 * Get the users Browsing this topic 
		 */
		$location_id		= isset($_URL->args['id']) ? $dba->Quote(intval($_URL->args['id'])) : 0;
		
		/* Set the extra SQL query fields to check */
		$extra				= " AND s.location_file = '". $dba->Quote($_URL->file) ."' AND s.location_id = ". $location_id;	
		
		$expired			= time() - ini_get('session.gc_maxlifetime');

		$num_online_total	= $dba->getValue("SELECT COUNT(s.id) FROM ". SESSIONS ." s WHERE s.seen >= $expired $extra");
		
		if($num_online_total > 0) {

			$users_browsing		= &new OnlineUsersIterator($extra);
		
			/* Set the users browsing list */
			$template->setList('users_browsing', $users_browsing);

			$stats = array('num_online_members'	=> Globals::getGlobal('num_online_members') + iif(is_a($session['user'], 'Member') && $_SESS->is_first, 1, 0),
							'num_invisible'		=> Globals::getGlobal('num_online_invisible'),
							'num_online_total'	=> $num_online_total + iif(is_a($session['user'], 'Guest') && $_SESS->is_first, 1, 0)
							);
		

			$template->setVar('num_online_members', $stats['num_online_members']);
			$template->setVar('users_browsing',		$template->getVar('L_USERSBROWSINGTOPIC'));
			$template->setVar('online_stats',		sprintf($template->getVar('L_USERSBROWSINGSTATS'), $stats['num_online_total'], $stats['num_online_members'], ($stats['num_online_total'] - $stats['num_online_members'] - $stats['num_invisible']), $stats['num_invisible']));
		
			/* Set the User's Browsing file */
			$template->setFile('users_browsing', 'users_browsing.html');
		
			$groups				= array();

			/* Set the usergroups legend list */
			foreach($_USERGROUPS as $group) {
				if($group['display_legend'] == 1)
					$groups[]	= $group;
			}

			$groups				= &new FAArrayIterator($groups);
			$template->setList('usergroups_legend', $groups);
		}
		
		/**
		 * Is this topic expired?
		 */
		$extra						= '';
		if($topic['topic_type'] > TOPIC_NORMAL && $topic['topic_expire'] > 0) {
			if(($topic['created'] + (3600 * 24 * $topic['topic_expire']) ) > time()) {
				
				$extra				= ",topic_expire=0,topic_type=". TOPIC_NORMAL;
			}
		}
		
		/* Add the topic info to the template */
		foreach($topic as $key => $val)
			$template->setVar('topic_'. $key, $val);

		/* Update the number of views for this topic */
		$dba->executeUpdate("UPDATE ". TOPICS ." SET views=views+1 $extra WHERE topic_id=". intval($topic['id']));
		
		/* Set query values for when we fetch the replies */
		$topic['postsperpage']		= isset($request['limit']) && ctype_digit($request['limit']) ? intval($request['limit']) : $forum['postsperpage'];
		$topic['daysprune']			= isset($request['daysprune']) && ctype_digit($request['daysprune']) ? iif(($request['daysprune'] == -1), 0, intval($request['daysprune'])) : 0;
		$topic['sortorder']			= isset($request['order']) && ($request['order'] == 'ASC' || $request['order'] == 'DESC') ? $request['order'] : 'ASC';
		$topic['sortedby']			= isset($request['sort']) && in_array($request['sort'], $sort_orders) ? $request['sort'] : 'created';
		$topic['start']				= isset($request['start']) && ctype_digit($request['start']) ? intval($_GET['start']) : 0;
		
		/* set the topic iterator */
		$topic						= &new TopicIterator($topic, TRUE);
		
		$template->setList('topic', $topic);
		
		$template->setFile('content', 'viewtopic.html');

		return TRUE;
	}
}

$app = new Forum_Controller('forum_base.html');

//$app->AddEvent('markforums', new MarkCategoryForumsRead);

$app->ExecutePage();

?>