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
* @version $Id: viewforum.php,v 1.9 2005/04/20 20:35:13 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

require 'forum.inc.php';

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_URL, $_QUERYPARAMS, $_USERGROUPS;

		if(!isset($request['id']) || !$request['id'] || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template				= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
		} else {
			
			/* Get the current forum/category */
			$forum					= get_cached_forum($request['id']);
			$query					= $forum['row_type'] & FORUM ? "SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($request['id']) : "SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". CATEGORIES ." c LEFT JOIN ". INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($request['id']);
			$forum					= $dba->getRow($query);

			if(!$forum || !is_array($forum) || empty($forum)) {
				/* set the breadcrumbs bit */
				$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
				$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);

				return TRUE;
			} else {
				
				/* Get the users Browsing this category or forum */
				$location_id		= isset($_URL->args['id']) ? $dba->Quote(intval($_URL->args['id'])) : 0;
				
				/* Set the extra SQL query fields to check */
				$extra				= " AND s.location_file = '". $dba->Quote($_URL->file) ."' AND s.location_id = ". $location_id;	

				$forum_can_view		= $forum['row_type'] & CATEGORY ? $user['maps']['categories'][$forum['id']]['can_view'] : $user['maps']['forums'][$forum['id']]['can_view'];
				
				$expired			= time() - ini_get('session.gc_maxlifetime');

				$num_online_total	= $dba->getValue("SELECT COUNT(s.id) as num_online_total FROM ". SESSIONS ." s WHERE s.seen >= $expired $extra");
				
				/* If there are more than 0 people browsing the forum, display the stats */
				if($num_online_total > 0 && $forum_can_view <= $user['perms'] && ($forum['row_type'] & CATEGORY || $forum['row_type'] & FORUM)) {

					$users_browsing		= &new OnlineUsersIterator($extra);
				
					/* Set the users browsing list */
					$template->setList('users_browsing', $users_browsing);

					$stats = array('num_online_members'	=> Globals::getGlobal('num_online_members'),
									'num_invisible'		=> Globals::getGlobal('num_online_invisible'),
									'num_online_total'	=> $num_online_total
									);
					
					$element	= $forum['row_type'] & CATEGORY ? 'L_USERSBROWSINGCAT' : 'L_USERSBROWSINGFORUM';
							
					$template->setVar('num_online_members', $stats['num_online_members']);
					$template->setVar('users_browsing',		$template->getVar($element));
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

				if($forum_can_view > $user['perms']) {
					/* set the breadcrumbs bit */
					$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'), $forum['row_left'], $forum['row_right']);
					$template->setInfo('content', $template->getVar('L_PERMCANTVIEW'), FALSE);
					
					return TRUE;
				} else {

					/* Set the breadcrumbs bit */
					$template	= BreadCrumbs($template, NULL, $forum['row_left'], $forum['row_right']);
					
					/* Set all of the category/forum info to the template */
					$template->setVarArray($forum);

					/* If we are looking at a category */
					if($forum['row_type'] & CATEGORY) {
						
						if($user['maps']['categories'][$forum['id']]['can_view'] > $user['perms']) {
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

						if($user['maps']['forums'][$forum['id']]['topics']['can_view'] > $user['perms']) {
							/* set the breadcrumbs bit */
							$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'), $forum['row_left'], $forum['row_right']);
							$template->setInfo('content_extra', $template->getVar('L_CANTVIEWFORUMTOPICS'), FALSE);
						
							return TRUE;
						}
						
						/* Set what this user can/cannot do in this forum */
						$template->setVar('forum_user_topic_options', sprintf($template->getVar('L_FORUMUSERTOPICPERMS'),
						iif(($user['maps']['forums'][$forum['id']]['topics']['can_add'] > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
						iif(($user['maps']['forums'][$forum['id']]['topics']['can_edit'] > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
						iif(($user['maps']['forums'][$forum['id']]['topics']['can_del'] > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
						iif(($user['maps']['forums'][$forum['id']]['attachments']['can_add'] > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN'))));

						$template->setVar('forum_user_reply_options', sprintf($template->getVar('L_FORUMUSERREPLYPERMS'),
						iif(($user['maps']['forums'][$forum['id']]['replies']['can_add'] > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
						iif(($user['maps']['forums'][$forum['id']]['replies']['can_edit'] > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
						iif(($user['maps']['forums'][$forum['id']]['replies']['can_del'] > $user['perms']), $template->getVar('L_CANNOT'), $template->getVar('L_CAN'))));
						
						/* Create an array with all of the possible sort orders we can have */						
						$sort_orders		= array('name', 'reply_time', 'num_replies', 'views', 'reply_uname', 'rating');
						
						/* Get the topics for this forum */
						$topicsperpage		= isset($request['limit']) && ctype_digit($request['limit']) ? intval($request['limit']) : $forum['topicsperpage'];
						$daysprune			= isset($request['daysprune']) && ctype_digit($request['daysprune']) ? iif(($request['daysprune'] == -1), 0, intval($request['daysprune'])) : 30;
						$sortorder			= isset($request['order']) && ($request['order'] == 'ASC' || $request['order'] == 'DESC') ? $request['order'] : 'DESC';
						$sortedby			= isset($request['sort']) && in_array($request['sort'], $sort_orders) ? $request['sort'] : 'created';
						$start				= isset($request['start']) && ctype_digit($request['start']) ? intval($_GET['start']) : NULL;
						
						/* Create the query */
						$topics				= &$dba->prepareStatement("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE i.created>=? AND i.row_left > ". intval($forum['row_left']) ." AND i.row_right < ". intval($forum['row_right']) ." AND t.is_draft = 0 ORDER BY $sortedby $sortorder LIMIT ?,?");
						
						/* Set the query values */
						$topics->setInt(1, $daysprune * (3600 * 24));
						$topics->setInt(2, $start);
						$topics->setInt(3, $topicsperpage);
						
						/* Execute the query */
						$result				= &$topics->executeQuery();
						
						/* If there are no topics, set the right messageto display */
						if($result->numrows() == 0) {
							$template->setVar('topics_message', iif($daysprune == 0, $template->getVar('L_NOPOSTSINFORUM'), sprintf($template->getVar('L_FORUMNOPOSTSSINCE'), $daysprune)));
						}
						
						/* Apply the topics iterator */
						$it					= &new TopicsIterator($result, &$session, $template->getVar('IMG_DIR'));

						$template->setList('topics', $it);

						/* Set the topics template to the content variable */
						$template->setFile('content_extra', 'topics.html');

					} else {
						/* set the breadcrumbs bit */
						$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
						$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);

						return TRUE;
					}
				}
			}
		}
		
		/* Add the cookies for this forum's topics */
		bb_execute_topiccache();

		return TRUE;
	}
}

$app = new Forum_Controller('forum_base.html');

$app->AddEvent('markforums', new MarkCategoryForumsRead);

$app->ExecutePage();

?>