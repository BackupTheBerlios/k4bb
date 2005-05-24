<?php
/**
* k4 Bulletin Board, lazyload.php
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
* @version $Id: lazyload.php,v 1.2 2005/05/24 20:03:26 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}
/*
class LazyLoad {

	var $dba;
	var $loads;
	var $load_num;
	var $load;

	function LazyLoad() {
		global $_DBA, $_LAZYLOAD;
		
		$this->dba		= &$_DBA;
		$this->loads	= $_LAZYLOAD;
		$this->load_num	= FALSE;
	}
	
	//
	// Find out which 'lazy' load this user should start with
	//
	function getLoad() {
		foreach($this->loads as $l) {
			if($l['load_current'] < $l['load_num']) {
				
				// Update the load current factor with the load interval _right_ away
				$this->dba->executeUpdate("UPDATE ". LAZYLOAD ." SET load_current=load_current+". intval($l['load_interval']));
				
				// Set which load this user will be dealing with
				return $this->load_num = $l['id'];
			
			// Weed out any finished loads
			} else {

				// Set the load status to completed, and clear the load args to save sql memory
				$this->dba->executeUpdate("UPDATE ". LOADTRACKER ." SET load_status = 'completed' WHERE load_id = ". l($load['id']));
				$this->dba->executeUpdate("UPDATE ". LAZYLOAD ." SET load_args = '' WHERE id = ". intval($l['id']));
				
				if(!@touch(CACHE_FILE, time()-86460)) {
					@unlink(CACHE_FILE);
				}
			}
		}
	}

	//
	// * Find out what parts of this load this user should execute
	//
	function executeLoad() {

		$this->getLoad();
		
		if($this->load_num) {

			$load		= $this->loads[$this->load_num];
			
			$args		= explode($load['load_separator'], $load['load_args']);
			
			for($i = $load['load_current']; $i < ($load['load_current'] + $load['load_interval']); $i++) {
				
				if(isset($args[$i]) && $args[$i] != '') {
					
					if($load['load_type'] == 'SQL') {

						@$this->dba->executeUpdate($args[$i]);
					} else if($load['load_type'] == 'PHP') {

						@eval($args[$i]);
					}
				}
			}
			
			// Set the load status to completed
			if(($load['load_current'] + $load['load_interval']) > $load['load_num']) {

				// Set the load status to completed, and clear the load args to save sql memory
				$this->dba->executeUpdate("UPDATE ". LOADTRACKER ." SET load_status = 'completed' WHERE load_id = ". intval($load['id']));
				$this->dba->executeUpdate("UPDATE ". LAZYLOAD ." SET load_args = '' WHERE id = ". intval($load['id']));
			
				if(!@touch(CACHE_FILE, time()-86460)) {
					@unlink(CACHE_FILE);
				}
			}
		}

		return TRUE;
	}
}

*/


/**
 * This sets the mail queue items for emails that need to be sent out
 * because people have posted a topic in a forum
 */
function set_send_topic_mail($forum_id, $poster_name) {

	global $_DBA, $_QUERYPARAMS, $_SETTINGS, $lang;

	if(ctype_digit($forum_id) && intval($forum_id) != 0) {
		
		$forum				= $_DBA->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($forum_id));
		
		if(is_array($forum) && !empty($forum)) {
			

			/**
			 * Get the subscribers of this topic
			 */
			$users			= &$_DBA->executeQuery("SELECT * FROM ". SUBSCRIPTIONS ." WHERE topic_id = 0 AND forum_id = ". intval($forum['id']) ." AND requires_revisit = 0");
			
			$subscribers	= array();

			while($users->next()) {
				
				$u				= $users->current();
				$subscribers[]	= array('name' => $u['user_name'], 'id' => $u['id'], 'email' => $u['email'], 'poster_name' => $poster_name);

			}
			
			/* Memory Saving */
			$users->freeResult();
			unset($users);

			/**
			 * Insert the data into the mail queue
			 */
			$subject			= $lang['L_TOPICPOSTIN'] .": ". $forum['name'];
			$message			= sprintf($lang['L_FORUMSUBSCRIBEEMAIL'], "%s", "%s", $forum['name'], $forum['id'], $_SETTINGS['bbtitle'], $forum['id']);
			$userinfo			= serialize($subscribers);
			
			$insert				= &$_DBA->prepareStatement("INSERT INTO ". MAILQUEUE ." (subject,message,row_id,row_type,userinfo) VALUES (?,?,?,?,?)");
			$insert->setString(1, $subject);
			$insert->setString(2, $message);
			$insert->setInt(3, $forum['id']);
			$insert->setInt(4, FORUM);
			$insert->setString(5, $userinfo);

			$insert->executeUpdate();
			
			/* Memory saving */
			unset($_SETTINGS, $lang, $_QUERYPARAMS, $_DBA);

			if(!@touch(CACHE_EMAIL_FILE, time()-86460)) {
				@unlink(CACHE_EMAIL_FILE);
			}
		}
	}
}

/**
 * This sets the mail queue items for emails that need to be sent out
 * because people have replied to a topic
 */
function set_send_reply_mail($topic_id) {

	global $_DBA, $_QUERYPARAMS, $_SETTINGS, $lang;

	if(ctype_digit($topic_id) && intval($topic_id) != 0) {
		
		$topic				= $_DBA->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($topic_id));
		
		if(is_array($topic) && !empty($topic)) {
			

			/**
			 * Get the subscribers of this topic
			 */
			$users			= &$_DBA->executeQuery("SELECT * FROM ". SUBSCRIPTIONS ." WHERE topic_id = ". intval($topic['id']) ." AND requires_revisit = 0");
			
			$subscribers	= array();

			while($users->next()) {
				
				$u				= $users->current();
				$subscribers[]	= array('name' => $u['user_name'], 'id' => $u['id'], 'email' => $u['email'], 'poster_name' => $topic['poster_name'], 'topic_id' => $topic['id']);
			}
			
			/* Memory Saving */
			$users->freeResult();
			unset($users);

			/**
			 * Insert the data into the mail queue
			 */
			$subject			= $lang['L_REPLYTO'] .": ". $topic['name'];
			$message			= sprintf($lang['L_TOPICSUBSCRIBEEMAIL'], "%s", "%s", $topic['name'], $topic['id'], $_SETTINGS['bbtitle'], $topic['id']);
			$userinfo			= serialize($subscribers);
			
			$insert				= &$_DBA->prepareStatement("INSERT INTO ". MAILQUEUE ." (subject,message,row_id,row_type,userinfo) VALUES (?,?,?,?,?)");
			$insert->setString(1, $subject);
			$insert->setString(2, $message);
			$insert->setInt(3, $topic['id']);
			$insert->setInt(4, TOPIC);
			$insert->setString(5, $userinfo);

			$insert->executeUpdate();
			
			/* Memory saving */
			unset($_SETTINGS, $lang, $_QUERYPARAMS, $_DBA);

			if(!@touch(CACHE_EMAIL_FILE, time()-86460)) {
				@unlink(CACHE_EMAIL_FILE);
			}
		}
	}
}

/**
 * Execute our mail queue by sending out an appropriate amount of emails at once
 */
function execute_mail_queue() {
	global $_DBA, $_MAILQUEUE, $_SETTINGS;
	
	if(is_array($_MAILQUEUE) && !empty($_MAILQUEUE)) {
		array_values($_MAILQUEUE);
		
		$page				= &new Url(forum_url());
		$page->args			= array();
		$page->file			= FALSE;
		$page->path			= FALSE;
		$page->anchor		= FALSE;
		$page->scheme		= FALSE;

		if(isset($_MAILQUEUE[0])) {
			
			$queue			= $_MAILQUEUE[0];
			
			$users			= unserialize($_MAILQUEUE[0]['userinfo']);

			if(is_array($users) && !empty($users)) {
				
				/* Reset the starting point of this array */
				$users		= array_values($users);
				$count		= count($users);
				$user_query	= '';

				/* Loop through the users */
				for($i = 0; $i < EMAIL_INTERVAL; $i++) {
					
					if(isset($users[$i]) && is_array($users[$i]) && intval($users[$i]['id']) != 0) {
						
						if($users[$i]['name'] != $users[$i]['poster_name']) {

							$message	= sprintf($_MAILQUEUE[0]['message'], $users[$i]['name'], $users[$i]['poster_name']);

							/* Email our user */
							@mail($users[$i]['email'], $_MAILQUEUE[0]['subject'], $message, "From: \"". $_SETTINGS['bbtitle'] ." Forums\" <noreply@". $page->__toString() .">");
							
							unset($users[$i]);
						}

						$user_query	.= $i == 0 ? 'user_id = '. intval($users[$i]['id']) : ' OR user_id = '. intval($users[$i]['id']);
					}

				}

				/* Update the subscriptions 'requires revisit' field */
				$_DBA->executeUpdate("UPDATE ". SUBSCRIPTIONS ." SET requires_revisit = 1 WHERE topic_id = ". $queue['row_id'] ." AND (". $user_query .")");
				
				/* If we have finished with this queue item */
				if($count <= EMAIL_INTERVAL) {
					$_DBA->executeUpdate("DELETE FROM ". MAILQUEUE ." WHERE id = ". intval($_MAILQUEUE[0]['id']));
				} else {
					
					$users		= array_values($users);
					$update		= $_DBA->prepareStatement("UPDATE ". MAILQUEUE ." SET userinfo=? WHERE id=?");
					$update->setString(1, serialize($users));
					$update->setInt(2, $_MAILQUEUE[0]['id']);
					$update->executeUpdate();
				}
				
				/* Reset the filetime on our email cache file */
				if(!@touch(CACHE_EMAIL_FILE, time()-86460)) {
					@unlink(CACHE_EMAIL_FILE);
				}

			} else {
				$_DBA->executeUpdate("DELETE FROM ". MAILQUEUE ." WHERE id = ". intval($_MAILQUEUE[0]['id']));
					
				/* Reset the filetime on our email cache file */
				if(!@touch(CACHE_EMAIL_FILE, time()-86460)) {
					@unlink(CACHE_EMAIL_FILE);
				}
			}
		}
	}
}

/**
 * Execute our topic queue to delete moderated topics
 */
function execute_topic_queue() {
	global $_DBA, $_TOPICQUEUE;
	
	if(is_array($_TOPICQUEUE) && !empty($_TOPICQUEUE)) {
		array_values($_TOPICQUEUE);
		
		if(isset($_TOPICQUEUE[0])) {
			
			$queue			= $_TOPICQUEUE[0];
			
			$topics			= unserialize($_TOPICQUEUE[0]['topicinfo']);
			
			if(is_array($topics) && !empty($topics)) {
				
				/* Reset the starting point of this array */
				$topics		= array_values($topics);
				$count		= count($topics);
				
				/* Loop through the users */
				for($i = 0; $i < TOPIC_INTERVAL; $i++) {
					
					if(isset($topics[$i]) && intval($topics[$i]) != 0) {
						
						/* Remove this topic */
						remove_item(intval($topics[$i]), 'topic_id');
						
						unset($topics[$i]);
					}

				}
				
				/* If we have finished with this queue item */
				if($count <= TOPIC_INTERVAL) {
					$_DBA->executeUpdate("DELETE FROM ". TOPICQUEUE ." WHERE id = ". intval($_TOPICQUEUE[0]['id']));
				} else {
					
					$topics		= array_values($topics);
					$update		= $_DBA->prepareStatement("UPDATE ". TOPICQUEUE ." SET topicinfo=? WHERE id=?");
					$update->setString(1, serialize($topics));
					$update->setInt(2, $_TOPICQUEUE[0]['id']);
					$update->executeUpdate();
				}
				
				/* Reset the filetime on our email cache file */
				if(!@touch(CACHE_TOPIC_FILE, time()-86460)) {
					@unlink(CACHE_TOPIC_FILE);
				}

			} else {
				$_DBA->executeUpdate("DELETE FROM ". TOPICQUEUE ." WHERE id = ". intval($_TOPICQUEUE[0]['id']));
					
				/* Reset the filetime on our email cache file */
				if(!@touch(CACHE_TOPIC_FILE, time()-86460)) {
					@unlink(CACHE_TOPIC_FILE);
				}
			}
		}
	}
}

?>