<?php
/**
* k4 Bulletin Board, heirarchy.php
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
* @version $Id: heirarchy.php,v 1.6 2005/05/24 20:03:26 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

function remove_item($id, $id_type) {
	
	global $_DBA, $_QUERYPARAMS, $_DATASTORE;
	
	$heirarchy  = &new Heirarchy();
	
	/* Start the transaction */
	$_DBA->beginTransaction();
	
	switch($id_type) {
		case 'topic_id': {
			
			/* Get the row */
			$info		= $_DBA->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". INFO ." i LEFT JOIN ". TOPICS ." t ON t.topic_id=i.id WHERE i.id=". intval($id));

			/* The number of replies that this topic has */
			$num_replies		= @intval(($info['row_right'] - $info['row_left'] - 1) / 2);
		
			/* Get that last topic in this forum that's not this topic */
			$last_topic			= $_DBA->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE i.id <> ". intval($info['id']) ." AND t.is_draft=0 AND t.queue=0 AND t.display=1 AND t.forum_id=". intval($info['forum_id']) ." ORDER BY i.created DESC LIMIT 1");
			$last_topic			= !$last_topic || !is_array($last_topic) ? array() : $last_topic;
			
			/* Get that last post in this forum that's not part of/from this topic */
			$last_post			= $_DBA->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". REPLIES ." r LEFT JOIN ". INFO ." i ON r.reply_id = i.id WHERE r.topic_id <> ". intval($info['id']) ." AND r.forum_id=". intval($info['forum_id']) ." ORDER BY i.created DESC LIMIT 1");
			$last_post			= !$last_post || !is_array($last_post) ? $last_topic : $last_post;
			
			/**
			 * Update the forum and the datastore
			 */
						
			$forum_update		= &$_DBA->prepareStatement("UPDATE ". FORUMS ." SET topics=topics-1,posts=posts-?,replies=replies-?,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
			$datastore_update	= &$_DBA->prepareStatement("UPDATE ". DATASTORE ." SET data=? WHERE varname=?");
				
			/* Set the forum values */
			$forum_update->setInt(1, intval($num_replies)+1);
			$forum_update->setInt(2, intval($num_replies));
			$forum_update->setInt(3, @$last_topic['created']);
			$forum_update->setString(4, @$last_topic['name']);
			$forum_update->setString(5, @$last_topic['poster_name']);
			$forum_update->setInt(6, @$last_topic['id']);
			$forum_update->setInt(7, @$last_topic['poster_id']);
			$forum_update->setString(8, @$last_topic['posticon']);
			$forum_update->setInt(9, @$last_post['created']);
			$forum_update->setString(10, @$last_post['name']);
			$forum_update->setString(11, @$last_post['poster_name']);
			$forum_update->setInt(12, @$last_post['id']);
			$forum_update->setInt(13, @$last_post['poster_id']);
			$forum_update->setString(14, @$last_post['posticon']);
			$forum_update->setInt(15, @$info['forum_id']);
			
			/* Set the datastore values */
			$datastore					= $_DATASTORE['forumstats'];
			$datastore['num_topics']	= $_DBA->getValue("SELECT COUNT(*) FROM ". TOPICS ." WHERE is_draft = 0 AND queue = 0 AND display = 1") - 1;
			$datastore['num_replies']	= $_DBA->getValue("SELECT COUNT(*) FROM ". REPLIES ." WHERE is_draft = 0") - intval($num_replies);
			
			$datastore_update->setString(1, serialize($datastore));
			$datastore_update->setString(2, 'forumstats');
			
			/* Execute the forum and datastore update queries */
			$forum_update->executeUpdate();
			$datastore_update->executeUpdate();

			/**
			 * Change user post counts
			 */
			
			/* Update the user that posted this topic */
			if($info['poster_id'] > 0)
				$_DBA->executeUpdate("UPDATE ". USERINFO ." SET num_posts=num_posts-1 WHERE user_id=". intval($info['poster_id']));

			$users						= array();
			
			/* Only if there are more than 0 replies should we update post counts */
			if(intval($num_replies) > 0) {
				
				/* Get all of the replies */
				$replies				= $_DBA->executeQuery("SELECT poster_id FROM ". REPLIES ." WHERE topic_id = ". intval($info['id']));
				
				while($replies->next()) {
					$reply				= $replies->current();
					
					if(!isset($users[$reply['poster_id']]) && $reply['poster_id'] > 0)
						$users[$reply['poster_id']] = 1;
					
					$users[$reply['poster_id']] += 1;
				}
				
				/* Memory saving */
				$replies->freeResult();
				unset($replies);

				/* Update all of the users that posted */
				if(count($users) > 0) {
					
					/* Loop through the users and change their post counts */
					foreach($users as $user_id => $num_posts) {
						$_DBA->executeUpdate("UPDATE ". USERINFO ." SET num_posts=num_posts-". intval($num_posts) ." WHERE user_id=". intval($user_id));
					}
				}

				/* Memory saving */
				unset($users);
			}
			
			$_DBA->executeUpdate("DELETE FROM ". TOPICS ." WHERE topic_id = ". intval($id));
			$_DBA->executeUpdate("DELETE FROM ". REPLIES ." WHERE topic_id = ". intval($id));
			$_DBA->executeUpdate("DELETE FROM ". SUBSCRIPTIONS ." WHERE topic_id = ". intval($id));
			$_DBA->executeUpdate("DELETE FROM ". MAILQUEUE ." WHERE row_id = ". intval($id) ." AND row_type = ". TOPIC);
			
			break;
		}
	}
	
	/* Remove all of the nodes from the tree now */
	$heirarchy->removeNode($info, INFO);
	
	/* Commit the transaction */
	$_DBA->commitTransaction();
	
	return TRUE;
}

class Heirarchy {
	
	var $dba;

	function Heirarchy() {
		global $_DBA;

		$this->dba	= &$_DBA;
	}
	function allocateSpace($row_left, $row_right, $destination, $table) {
		$space_needed	= $row_right - $row_left;
		
		$right			= $destination['row_right'] + $space_needed + 1;

		$this->dba->executeUpdate("UPDATE ". $table ." SET row_right = ". $right ." WHERE id = ". intval($destination['id']));
	}
	function removeNode($info, $table) {
		$descendants = (($info['row_right'] - $info['row_left'] - 1) / 2) + 2;
		$descendants = $descendants % 2 == 0 ? $descendants : $descendants+1; // Make it an even number
		
		/**
		 * Create the Queries
		 */
		$delete		= &$this->dba->prepareStatement("DELETE FROM ". $table ." WHERE row_left >= ? AND row_right <= ?");
		$update_a	= &$this->dba->prepareStatement("UPDATE ". $table ." SET row_right = row_right-? WHERE row_left < ? AND row_right > ?");
		$update_b	= &$this->dba->prepareStatement("UPDATE ". $table ." SET row_left = row_left-?, row_right=row_right-? WHERE row_left > ?");
		
		/**
		 * Populate the queries
		 */
		$delete->setInt(1, $info['row_left']);
		$delete->setInt(2, $info['row_right']);

		$update_a->setInt(1, $descendants);
		$update_a->setInt(2, $info['row_left']);
		$update_a->setInt(3, $info['row_left']);

		$update_b->setInt(1, $descendants);
		$update_b->setInt(2, $descendants);
		$update_b->setInt(3, $info['row_left']);
		
		/**
		 * Execute the queries
		 */
		$delete->executeUpdate();
		$update_a->executeUpdate();
		$update_b->executeUpdate();
	}
	function moveUp($parent_node, $table) {

		$num_children		= @(($parent_node['row_right'] - $parent_node['row_left'] - 1) / 2);
		
		if($num_children > 0) {

			/**
			 * Create the Queries
			 */
			$delete				= &$this->dba->prepareStatement("DELETE FROM ". $table ." WHERE row_left = ? AND row_right = ?");
			$update_a			= &$this->dba->prepareStatement("UPDATE ". $table ." SET row_left=row_left-1, row_right=row_right-1 WHERE row_left > ? AND row_right < ?");
			$update_b			= &$this->dba->prepareStatement("UPDATE ". $table ." SET row_right=row_right-2 WHERE row_right > ?");
			
			/**
			 * Populate the queries
			 */
			$delete->setInt(1, $parent_node['row_left']);
			$delete->setInt(2, $parent_node['row_right']);

			$update_a->setInt(1, $parent_node['row_left']);
			$update_a->setInt(2, $parent_node['row_right']); // row_left ?

			$update_b->setInt(1, $parent_node['row_right']);
			
			/**
			 * Execute the queries
			 */
			$delete->executeUpdate();
			$update_a->executeUpdate();
			$update_b->executeUpdate();
		
		} else {
			$this->removeNode($parent_node, $table);	
		}
	}
}

?>