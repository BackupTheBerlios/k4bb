<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     index.php
 *     Copyright (c) 2004, Peter Goodman

 *     Permission is hereby granted, free of charge, to any person obtaining 
 *     a copy of this software and associated documentation files (the 
 *     "Software"), to deal in the Software without restriction, including 
 *     without limitation the rights to use, copy, modify, merge, publish, 
 *     distribute, sublicense, and/or sell copies of the Software, and to 
 *     permit persons to whom the Software is furnished to do so, subject to 
 *     the following conditions:

 *     The above copyright notice and this permission notice shall be 
 *     included in all copies or substantial portions of the Software.

 *     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, 
 *     EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 
 *     MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 *     NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS 
 *     BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN 
 *     ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
 *     CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE 
 *     SOFTWARE.
 *********************************************************************************/

error_reporting(E_STRICT | E_ALL);

require 'forum.inc.php';

class DefaultEvent extends Event {
	public function Execute(Template $template, Session $session, $request) {	
		$forum	= new Forum;
		//foreach(DBA::Open()->Query("SELECT * FROM ". SESSIONS) as $s) { print_r($s); }
		//$rows = $forum->getForums((@$session['user']['perms'] & ADMIN));

		/* Set the templates */
		$template->content	= array('file' => 'forums.html');
		if($template['WOLenable'] == 1)
			$template->users_online = array('file' => 'online_users.html');
		if($template['showbirthdays'] == 1)
			$template->todays_bdays = array('file' => 'birthdays.html');
		
		$suspend = ($session['user']['perms'] & ADMIN) ? 1 : 0;
		/* Set the forums and categoris */
		$template->categories = new ForumList(FALSE,$suspend);
		
		/* Set the title of the page */
		$template['title'] = $template['L_HOME'];
		
		$expired = time() - Lib::GetSetting('sess.gc_maxlifetime');
		
		/* Display message for unlogged users */
		if($session['user'] instanceof Guest) {
			$template['welcome_title']	= sprintf($template['L_WELCOMETITLE'], $template['forum_description']); // you can use forum_name here instead
			$template['welcome_msg']	= $template['L_WELCOMEMESSAGE'];
		} else {
			$template->welcome_msg = array('hide' => TRUE);
		}
		
		/* Set the online users list */
		if($template['displayloggedin'] == 1)
			$template->online_users = new Online_Users;
		if($template['showbirthdays'] == 1)
			$template->birthdays = new Birthdays;
		
		//DBA::Open()->Execute("delete from k4_pmsgs");
		//echo DBA::Open()->GetValue("select count(*) from k4_pmsgs");
		//print_r(DBA::Open()->GetRow("SELECT * FROM ". FORUMS ." WHERE row_left = 1"));
		
		//foreach(DBA::Open()->Query("SELECT * FROM ". POSTS ." WHERE row_left < 0 OR row_right < 0") as $t) { print_r($t); }

		$stats = DBA::Open()->GetRow("SELECT (SELECT COUNT(*) FROM ". USERS ." WHERE invisible = 0) AS num_members, (SELECT COUNT(s.uid) FROM ". USERS ." u, ". SESSIONS ." s WHERE u.invisible = 1 AND u.id = s.uid) AS num_invisible, (SELECT MAX(id) FROM ". USERS .") AS newest_uid, (SELECT name FROM ". USERS ." ORDER BY created DESC LIMIT 1) AS newest_user, (SELECT COUNT(*) FROM ". USERS ." WHERE seen >= $expired) AS num_online, (SELECT COUNT(*) FROM ". POSTS .") AS num_articles, (SELECT COUNT(*) FROM ". SESSIONS .") AS num_total FROM ". USERS );

		/* Set the board statistics */
		$template['newest_member'] = sprintf($template['L_NEWESTMEMBER'], $stats['newest_uid'], $stats['newest_user']);
		$template['total_posts'] = sprintf($template['L_TOTALPOSTS'], $stats['num_articles']);
		$template['total_users'] = sprintf($template['L_TOTALUSERS'], $stats['num_members']);
		$guests = ($stats['num_total'] - $stats['num_online']) < 0 ? 0 : ($stats['num_total'] - $stats['num_online']);
		$guests = $template['WOLguests'] == 1 ? $guests : '--';
		$template['online_stats'] = sprintf($template['L_ONLINEUSERSTATS'], $stats['num_total'], $stats['num_online'], $guests, $stats['num_invisible']);
	
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

$app	= new Forum_Controller('forum_base.html');

$app->ExecutePage();

?>