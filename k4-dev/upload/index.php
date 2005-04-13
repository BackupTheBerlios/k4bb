<?php
/**
* k4 Bulletin Board, index.php
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
* @version $Id: index.php,v 1.4 2005/04/13 02:55:20 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

require 'forum.inc.php';

/*
global $_DBA;
$query = "";

foreach(explode(";", $query) as $q)
	if($q != '')
		$_DBA->executeUpdate($q);
*//*$result = $_DBA->executeQuery("select * from ". MAPS ." ORDER BY row_left ASC");
while($result->next()) {
	$temp = $result->current();
	$query = "INSERT INTO k4_maps (row_left, row_right, row_level, name, varname, is_global, category_id, forum_id, group_id, user_id, can_view, can_add, can_edit, can_del, value)";
	$query .= " VALUES (". $temp['row_left'] .", ". $temp['row_right'] .", ". $temp['row_level'] .", '". $temp['name'] ."', '". $temp['varname'] ."', ". $temp['is_global'] .", ". $temp['category_id'] .", ". $temp['forum_id'] .", ". $temp['group_id'] .", ". $temp['user_id'] .", ". $temp['can_view'] .", ". $temp['can_add'] .", ". $temp['can_edit'] .", ". $temp['can_del'] .", '". @$temp['value'] ."');\r\n";
	echo $query;
}
exit; */

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {

		global $_DATASTORE;
		
		/* Set the breadcrumbs bit */
		$template		= BreadCrumbs($template, $template->getVar('L_HOME'));
		
		//$dba->executeUpdate("update k4_information set row_level = 3 where row_type = 2 and parent_id = 2");
		//$dba->executeQuery("delete from k4_information");
		//$dba->executeQuery("delete from k4_categories");
		//$dba->executeQuery("delete from k4_forums");
		
		/* Set the globals for num_topics and num_replies here */
		Globals::setGlobal('num_topics', 0);
		Globals::setGlobal('num_replies', 0);
		
		/* Set the Categories list */
		$categories = &new CategoriesIterator(NULL);
		$template->setList('categories', $categories);
		
		if(!is_a($session['user'], 'Member')) {
			$template->setVar('welcome_title', sprintf($template->getVar('L_WELCOMETITLE'), $template->getVar('bbtitle')));
			$template->show('welcome_msg');

			$template->setFile('quick_login', 'login_form_quick.html');
		}
		
		/* Set the online users list */
		$online_users						= &new OnlineUsersIterator(NULL);
		$template->setList('online_users', $online_users);

		$newest_user						= $dba->getRow("SELECT name, id FROM ". USERS ." ORDER BY id DESC LIMIT 1");

		$stats = array('num_online_members'	=> Globals::getGlobal('num_online_members'),
						'num_invisible'		=> Globals::getGlobal('num_online_invisible'),
						'num_topics'		=> Globals::getGlobal('num_topics'),
						'num_replies'		=> Globals::getGlobal('num_replies'),
						'num_members'		=> $dba->getValue("SELECT COUNT(*) FROM ". USERS),
						'num_online_total'	=> $dba->getValue("SELECT COUNT(*) FROM ". SESSIONS),
						'newest_uid'		=> $newest_user['id'],
						'newest_user'		=> $newest_user['name']
						);
				
		$template->setVar('num_online_members', $stats['num_online_members']);

		$template->setVar('newest_member',	sprintf($template->getVar('L_NEWESTMEMBER'),		$stats['newest_uid'], $stats['newest_user']));
		$template->setVar('total_users',	sprintf($template->getVar('L_TOTALUSERS'),			$stats['num_members']));
		$template->setVar('total_posts',	sprintf($template->getVar('L_TOTALPOSTS'),			($stats['num_topics'] + $stats['num_replies']), $stats['num_topics'], $stats['num_replies']));
		$template->setVar('online_stats',	sprintf($template->getVar('L_ONLINEUSERSTATS'),		$stats['num_online_total'], $stats['num_online_members'], ($stats['num_online_total'] - $stats['num_online_members'] - $stats['num_invisible']), $stats['num_invisible']));
		$template->setVar('most_users_ever',sprintf($template->getVar('L_MOSTUSERSEVERONLINE'),	$_DATASTORE['maxloggedin']['maxonline'], date("n/j/Y", bbtime($_DATASTORE['maxloggedin']['maxonlinedate'])), date("g:ia", bbtime($_DATASTORE['maxloggedin']['maxonlinedate']))));

		if($stats['num_online_total'] >= $_DATASTORE['maxloggedin']['maxonline']) {
			$maxloggedin	= array('maxonline' => $stats['num_online_total'], 'maxonlinedate' => time());
			$query			= $dba->prepareStatement("UPDATE ". DATASTORE ." SET data = ? WHERE varname = ?");
			
			$query->setString(1, serialize($maxloggedin));
			$query->setString(2, 'maxloggedin');
			$query->executeUpdate();
		}
		
		$template->show('forum_status_icons');

		/* Set the forums template to content variable */
		$template->setFile('content', 'forums.html');
		$template->setFile('forum_info', 'forum_info.html');
		
		return TRUE;
	}
}

$app = new Forum_Controller('forum_base.html');

$app->AddEvent('markforums', new MarkForumsRead);

$app->ExecutePage();

?>