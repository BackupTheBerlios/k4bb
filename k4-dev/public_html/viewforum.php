<?php
/**
* k4 Bulletin Board, viewforum.php
*
* Copyright (c) 2004, Peter Goodman
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
* @version $Id: viewforum.php,v 1.1 2005/04/05 02:32:31 necrotic Exp $
* @package k42
*/

error_reporting(E_ALL);

require 'forum.inc.php';

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_URL, $_QUERYPARAMS;

		if(!isset($request['id']) || !$request['id'] || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('cotent', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
		} else {
			
			$forum				= getcachedforum($request['id']);

			if(!$forum || !is_array($forum) || empty($forum)) {
				/* set the breadcrumbs bit */
				$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
				$template->setInfo('cotent', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
			} else {
				
				/* Get the users Browsing this category or forum */
				$location_id		= isset($_URL->args['id']) ? $dba->Quote(intval($_URL->args['id'])) : 0;
				
				/* Set the extra SQL query fields to check */
				$extra				= " AND s.location_file = '". $dba->Quote($_URL->file) ."' AND s.location_id = ". $location_id;	

				/* Get the number of people browsing this forum */
				$num_online_total	= $dba->getValue("SELECT COUNT(s.id) FROM ". SESSIONS ." s WHERE s.id != '' $extra");

				$forum_can_view	= $forum['row_type'] & CATEGORY ? $user['maps']['categories'][$forum['id']]['category'. $forum['id']]['view'] : $user['maps']['forums'][$forum['id']]['forum'. $forum['id']]['view'];

				/* If there are more than 0 people browsing the forum, display the stats */
				if($num_online_total > 0 && $forum_can_view <= $user['perms'] && ($forum['row_type'] & CATEGORY || $forum['row_type'] & FORUM)) {
					
					/* Set the file */
					$template->setFile('content_extra', 'users_browsing.html');

					$users_browsing		= &new OnlineUsersIterator($extra);
				
					/* Set the users browsing list */
					$template->setList('users_browsing', $users_browsing);

					$stats = array('num_online_members'	=> Globals::getGlobal('num_online_members'),
									'num_invisible'		=> Globals::getGlobal('num_online_invisible'),
									'num_online_total'	=> $num_online_total
									);
					$element	= $forum['row_type'] & CATEGORY ? 'L_USERSBROWSINGCAT' : 'L_USERSBROWSINGFORUM';
							
					$template->setVar('num_online_members', $stats['num_online_members']);					
					$template->setVar('users_browsing', $template->getVar($element));
					$template->setVar('online_stats',	sprintf($template->getVar('L_USERSBROWSINGSTATS'), $stats['num_online_total'], $stats['num_online_members'], ($stats['num_online_total'] - $stats['num_online_members'] - $stats['num_invisible']), $stats['num_invisible']));
				}

				if($forum_can_view > $user['perms']) {
					/* set the breadcrumbs bit */
					$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
					$template->setInfo('content', $template->getVar('L_PERMCANTVIEW'), FALSE);

				} else {

					/* Set the breadcrumbs bit */
					$template	= BreadCrumbs($template, NULL, $forum['row_left'], $forum['row_right']);
					
					/* Set all of the category/forum info to the template */
					$template->setVarArray($forum);

					/* If we are looking at a category */
					if($forum['row_type'] & CATEGORY) {
						
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
						
						/* Set the number of people viewing the forum in the db */
						//$dba->executeUpdate("UPDATE ". FORUMS ." SET num_viewing = ". intval($num_online_total));
						
						/* If this forum has sub-forums */
						if( (issetforumcacheitem('subforums', $forum['id']) && $forum['subforums'] == 1) || ($dba->GetValue("SELECT COUNT(*) FROM ". INFO ." WHERE parent_id = ". $forum['id'] ." AND row_type = ". FORUM) > 0)) {
							
							/* Cache this forum as having subforums */
							setforumcacheitem('subforums', 1, $forum['id']);
							
							/* Show the table that holds the subforums */
							$template->show('subforums');
							
							/* Set the proper query params */
							$query_params	= $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'];
							
							/* Set the sub-forums list */
							$template->setList('subforums', new ForumsIterator("SELECT $query_params FROM ". INFO ." i LEFT JOIN ". FORUMS ." f ON f.forum_id = i.id WHERE i.row_left > ". $forum['row_left'] ." AND i.row_right < ". $forum['row_right'] ." AND i.row_type = ". FORUM ." AND i.parent_id = ". $forum['id'] ." ORDER BY i.row_order ASC"));
						}
						
						/* Set the topics template to the content variable */
						$template->setFile('content', 'topics.html');

					} else {
						/* set the breadcrumbs bit */
						$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
						$template->setInfo('cotent', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
					}
				}
			}
		}
		
		return TRUE;
	}
}

$app = new Forum_Controller('forum_base.html');

$app->AddEvent('markforums', new MarkCategoryForumsRead);

$app->ExecutePage();

?>