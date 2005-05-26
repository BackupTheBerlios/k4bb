<?php
/**
* k4 Bulletin Board, viewforum.php
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
* @version $Id: viewforum.php,v 1.19 2005/05/26 18:34:54 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

ob_start();

require 'forum.inc.php';

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_URL, $_QUERYPARAMS, $_USERGROUPS, $_SESS, $_ALLFORUMS;

		if(!isset($request['id']) || !$request['id'] || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template				= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
			return TRUE;
		}
			
		/* Get the current forum/category */
		$forum					= $_ALLFORUMS[$request['id']];
		$query					= $forum['row_type'] & FORUM ? "SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($request['id']) : "SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". CATEGORIES ." c LEFT JOIN ". INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($request['id']);
		$forum					= $dba->getRow($query);

		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);

			return TRUE;
		}

		if($forum['row_type'] == FORUM && @$forum['is_link'] == 1) {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'), $forum['row_left'], $forum['row_right']);
			$template->setInfo('content', $template->getVar('L_REDIRECTING'));
			if($forum['link_show_redirects'] == 1) {
				$template->setRedirect('redirect.php?id='. $forum['id'], 3);
			} else {
				$template->setRedirect($forum['link_href'], 3);
			}
			return TRUE;
		}
			
		/* Set the extra SQL query fields to check */
		$extra				= " AND s.location_file = '". $dba->Quote($_URL->file) ."' AND s.location_id = ". intval($forum['id']);	
		
		$forum_can_view		= $forum['row_type'] & CATEGORY ? get_map($user, 'categories', 'can_view', array()) : get_map($user, 'forums', 'can_view', array());
		
		$expired			= time() - ini_get('session.gc_maxlifetime');

		$num_online_total	= $dba->getValue("SELECT COUNT(s.id) as num_online_total FROM ". SESSIONS ." s WHERE s.seen >= $expired $extra");
				
		/* If there are more than 0 people browsing the forum, display the stats */
		if($num_online_total > 0 && $forum_can_view <= $user['perms'] && ($forum['row_type'] & CATEGORY || $forum['row_type'] & FORUM)) {

			$users_browsing			= &new OnlineUsersIterator($extra);
		
			/* Set the users browsing list */
			$template->setList('users_browsing', $users_browsing);

			$stats = array('num_online_members'	=> Globals::getGlobal('num_online_members'),
							'num_invisible'		=> Globals::getGlobal('num_online_invisible'),
							'num_online_total'	=> $num_online_total
							);
			
			$stats['num_guests']	= ($stats['num_online_total'] - $stats['num_online_members'] - $stats['num_invisible']);

			$element				= $forum['row_type'] & CATEGORY ? 'L_USERSBROWSINGCAT' : 'L_USERSBROWSINGFORUM';
					
			$template->setVar('num_online_members', $stats['num_online_members']);
			$template->setVar('users_browsing',		$template->getVar($element));
			$template->setVar('online_stats',		sprintf($template->getVar('L_USERSBROWSINGSTATS'), $stats['num_online_total'], $stats['num_online_members'], $stats['num_guests'], $stats['num_invisible']));
		
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

		if($forum_can_view > $user['perms']) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'), $forum['row_left'], $forum['row_right']);
			$template->setInfo('content', $template->getVar('L_PERMCANTVIEW'), FALSE);
			
			return TRUE;
		}

		/* Set the breadcrumbs bit */
		$template	= BreadCrumbs($template, NULL, $forum['row_left'], $forum['row_right']);
		
		/* Set all of the category/forum info to the template */
		$template->setVarArray($forum);

		/* If we are looking at a category */
		if($forum['row_type'] & CATEGORY) {
			
			if(get_map($user, 'categories', 'can_view', array()) > $user['perms']) {
				/* set the breadcrumbs bit */
				$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'), $forum['row_left'], $forum['row_right']);
				$template->setInfo('content', $template->getVar('L_PERMCANTVIEW'));
				
				return TRUE;
			}

			/* Set the proper query params */
			$query_params	= $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'];

			/* Set the Categories list */
			$categories = &new CategoriesIterator("SELECT $query_params FROM ". INFO ." i LEFT JOIN ". CATEGORIES ." c ON c.category_id = i.id WHERE i.row_type = ". CATEGORY ." AND i.row_left = ". $forum['row_left'] ." AND i.row_right = ". $forum['row_right'] ." AND i.id = ". $forum['id'] ." ORDER BY i.row_order ASC");
			$template->setList('categories', $categories);

			/* Hide the welcome message at the top of the forums.html template */
			$template->hide('welcome_msg');
			
			/* Show the forum status icons */
			$template->show('forum_status_icons');

			/* Show the 'Mark these forums Read' link */
			$template->show('mark_these_forums');
			
			/* Set the forums template to content variable */
			$template->setFile('content', 'forums.html');
		
		/* If we are looking at a forum */
		} else if($forum['row_type'] & FORUM) {						
			
			/* Add the forum info to the template */
			foreach($forum as $key => $val)
				$template->setVar('forum_'. $key, $val);
			
			/* If this forum has sub-forums */
			if( (isset_forum_cache_item('subforums', $forum['id']) && $forum['subforums'] == 1)) {
				
				/* Cache this forum as having subforums */
				set_forum_cache_item('subforums', 1, $forum['id']);
				
				/* Show the table that holds the subforums */
				$template->show('subforums');
				
				/* Set the proper query params */
				$query_params	= $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'];
				
				/* Set the sub-forums list */
				$template->setList('subforums', new ForumsIterator("SELECT $query_params FROM ". INFO ." i LEFT JOIN ". FORUMS ." f ON f.forum_id = i.id WHERE i.row_left > ". $forum['row_left'] ." AND i.row_right < ". $forum['row_right'] ." AND i.row_type = ". FORUM ." AND i.parent_id = ". $forum['id'] ." ORDER BY i.row_order ASC"));
				$template->setFile('content', 'subforums.html');
			}

			if(get_map($user, 'topics', 'can_view', array('forum_id'=>$forum['id'])) > $user['perms']) {
				/* set the breadcrumbs bit */
				$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'), $forum['row_left'], $forum['row_right']);
				$template->setInfo('content_extra', $template->getVar('L_CANTVIEWFORUMTOPICS'), FALSE);
			
				return TRUE;
			}
			
			
			/**
			 * Forum settings
			 */

			/* Set the topics template to the content variable */
			$template->setFile('content_extra', 'topics.html');
			
			/* Set what this user can/cannot do in this forum */
			$template->setVar('forum_user_topic_options', sprintf($template->getVar('L_FORUMUSERTOPICPERMS'),
			iif((get_map($user, 'topics', 'can_add', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
			iif((get_map($user, 'topics', 'can_edit', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
			iif((get_map($user, 'topics', 'can_del', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
			iif((get_map($user, 'attachments', 'can_add', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN'))));

			$template->setVar('forum_user_reply_options', sprintf($template->getVar('L_FORUMUSERREPLYPERMS'),
			iif((get_map($user, 'replies', 'can_add', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
			iif((get_map($user, 'replies', 'can_edit', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
			iif((get_map($user, 'replies', 'can_del', array('forum_id'=>$forum['id'])) > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN'))));
			
			/* Create an array with all of the possible sort orders we can have */						
			$sort_orders		= array('name', 'reply_time', 'num_replies', 'views', 'reply_uname', 'rating');
			
			
			/**
			 * Pagination
			 */

			/* Create the Pagination */
			$resultsperpage		= $forum['topicsperpage'];
			$num_results		= $forum['topics'];

			$perpage			= isset($request['limit']) && ctype_digit($request['limit']) && intval($request['limit']) > 0 ? intval($request['limit']) : $resultsperpage;
			$num_pages			= ceil($num_results / $perpage);
			$page				= isset($request['page']) && ctype_digit($request['page']) && intval($request['page']) > 0 ? intval($request['page']) : 1;
			$pager				= &new TPL_Paginator($_URL, $num_results, $page, $perpage);
			
			if($num_results > $perpage) {
				$template->setPager('topics_pager', $pager);
			}

			/* Get the topics for this forum */
			$daysprune			= isset($request['daysprune']) && ctype_digit($request['daysprune']) ? iif(($request['daysprune'] == -1), 0, intval($request['daysprune'])) : 30;
			$sortorder			= isset($request['order']) && ($request['order'] == 'ASC' || $request['order'] == 'DESC') ? $request['order'] : 'DESC';
			$sortedby			= isset($request['sort']) && in_array($request['sort'], $sort_orders) ? $request['sort'] : 'created';
			$start				= ($page - 1) * $perpage;
			
			if($forum['topics'] > 0) {

				/**
				 * Topic Setting
				 */

				/* get the topics */
				$topics				= &$dba->prepareStatement("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE i.created>=? AND t.is_draft=0 AND t.queue = 0 AND t.display = 1 AND i.row_type=". TOPIC ." AND t.forum_id = ". intval($forum['id']) ." AND (t.topic_type <> ". TOPIC_GLOBAL ." AND t.topic_type <> ". TOPIC_ANNOUNCE ." AND t.topic_type <> ". TOPIC_STICKY ." AND t.is_feature = 0) ORDER BY $sortedby $sortorder LIMIT ?,?");
				
				/* Set the query values */
				$topics->setInt(1, $daysprune * (3600 * 24));
				$topics->setInt(2, $start);
				$topics->setInt(3, $perpage);
				
				/* Execute the query */
				$result				= &$topics->executeQuery();
				
				/* Apply the topics iterator */
				$it					= &new TopicsIterator($result, &$session, $template->getVar('IMG_DIR'), $forum);
				$template->setList('topics', $it);
				

				/**
				 * Get announcement/global topics
				 */
				if($page == 1) {
					$announcements		= &$dba->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE t.is_draft=0 AND t.queue = 0 AND t.display = 1 AND i.row_type=". TOPIC ." AND t.forum_id = ". intval($forum['id']) ." AND (t.topic_type = ". TOPIC_GLOBAL ." OR t.topic_type = ". TOPIC_ANNOUNCE .") ORDER BY i.created DESC");
					if($announcements->numrows() > 0) {
						$a_it				= &new TopicsIterator($announcements, &$session, $template->getVar('IMG_DIR'), $forum);
						$template->setList('announcements', $a_it);
					}
				}
				
				/**
				 * Get sticky/feature topics
				 */
				$importants			= &$dba->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE t.is_draft=0 AND t.queue = 0 AND t.display = 1 AND i.row_type=". TOPIC ." AND t.forum_id = ". intval($forum['id']) ." AND (t.topic_type <> ". TOPIC_GLOBAL ." AND t.topic_type <> ". TOPIC_ANNOUNCE .") AND (t.topic_type = ". TOPIC_STICKY ." OR t.is_feature = 1) ORDER BY i.created DESC");
				if($importants->numrows() > 0) {
					$i_it				= &new TopicsIterator($importants, &$session, $template->getVar('IMG_DIR'), $forum);
					$template->setList('importants', $i_it);
				}
				
				/* Outside valid page range, redirect */
				if(!$pager->hasPage($page) && $num_results > $resultsperpage) {
					$template->setVar('topics_message', $template->getVar('L_PASTPAGELIMIT'));
					$template->setRedirect('viewforum.php?id='. $forum['id'] .'&limit='. $perpage .'&page='. $num_pages, 3);
					return TRUE;
				}
			}

			/* If there are no topics, set the right messageto display */
			if($forum['topics'] <= 0) {
				$template->show('no_topics');
				$template->setVar('topics_message', iif($daysprune == 0, $template->getVar('L_NOPOSTSINFORUM'), sprintf($template->getVar('L_FORUMNOPOSTSSINCE'), $daysprune)));
				return TRUE;
			}
			
			/**
			 * Moderator functions
			 */
			$template->setVar('modpanel', 0);

			if(is_moderator($user, $forum))
				$template->setVar('modpanel', 1);
			
		} else {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);

			return TRUE;
		}
				
		/* Add the cookies for this forum's topics */
		bb_execute_topiccache();

		return TRUE;
	}
}

$app = new Forum_Controller('forum_base.html');

$app->AddEvent('markforums', new MarkCategoryForumsRead);
$app->AddEvent('track', new SubscribeForum);
$app->AddEvent('untrack', new UnsubscribeForum);

$app->ExecutePage();

ob_flush();

?>