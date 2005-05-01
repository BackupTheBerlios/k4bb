<?php
/**
* k4 Bulletin Board, topic_review.class.php
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
* @version $Id: topic_review.class.php,v 1.1 2005/05/01 17:42:10 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class TopicReviewIterator extends FAArrayIterator {
	
	var $dba;
	var $result;
	var $users = array();
	var $qp;
	var $user;

	function TopicReviewIterator($topic, &$replies, &$user) {
		
		global $_DBA, $_QUERYPARAMS, $_USERGROUPS;
		
		$this->qp						= $_QUERYPARAMS;
		$this->dba						= &$_DBA;
		$this->groups					= $_USERGROUPS;
		$this->result					= &$replies;
		$this->user						= &$user;

		parent::FAArrayIterator(array($topic));
	}

	function &current() {
		$temp							= parent::current();
		
		$temp['posticon']				= @$temp['posticon'] != '' ? iif(file_exists(FORUM_BASE_DIR .'/tmp/upload/posticons/'. @$temp['posticon']), @$temp['posticon'], 'clear.gif') : 'clear.gif';

		if($temp['poster_id'] > 0) {
			$user						= $this->dba->getRow("SELECT ". $this->qp['user'] . $this->qp['userinfo'] ." FROM ". USERS ." u LEFT JOIN ". USERINFO ." ui ON u.id=ui.user_id WHERE u.id=". intval($temp['poster_id']));
			
			$group						= get_user_max_group($user, $this->groups);
			$user['group_color']		= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
			$user['group_nicename']		= $group['nicename'];
			$user['group_avatar']		= $group['avatar'];
			
			foreach($user as $key => $val)
				$temp['post_user_'. $key] = $val;
			
			/* This array holds all of the userinfo for users that post to this topic */
			$this->users[$user['id']]	= $user;
			
		}
	
	
		/* Do we have any replies? */
		$num_replies					= @(($temp['row_right'] - $temp['row_left'] - 1) / 2);

		if($num_replies > 0) {

			$temp['replies']			= &new RepliesReviewIterator($this->result, $this->qp, $this->dba, $this->users, $this->groups, $this->user);

		}
		
		$this->result->freeResult();

		$bbcode							= &new BBCodex($this->user, $temp['body_text'], $temp['forum_id'], TRUE, TRUE, TRUE, TRUE);
		$temp['reverted_body_text']		= $bbcode->revert();

		return $temp;
	}
}

class RepliesReviewIterator extends FAProxyIterator {
	
	var $result;
	var $session;
	var $img_dir;
	var $forums;
	var $user;

	function RepliesReviewIterator(&$result, $queryparams, &$dba, $users, $groups, &$user) {
		
		$this->users			= $users;
		$this->qp				= $queryparams;
		$this->dba				= &$_DBA;
		$this->result			= &$result;
		$this->groups			= $groups;
		$this->user				= &$user;

		parent::FAProxyIterator($this->result);
	}

	function &current() {
		$temp					= parent::current();
		
		$temp['posticon']		= @$temp['posticon'] != '' ? iif(file_exists(FORUM_BASE_DIR .'/tmp/upload/posticons/'. @$temp['posticon']), @$temp['posticon'], 'clear.gif') : 'clear.gif';

		if($temp['poster_id'] > 0) {
			
			if(!isset($this->users[$temp['poster_id']])) {
			
				$user						= $this->dba->getRow("SELECT ". $this->qp['user'] . $this->qp['userinfo'] ." FROM ". USERS ." u LEFT JOIN ". USERINFO ." ui ON u.id=ui.user_id WHERE u.id=". intval($temp['poster_id']));
				
				$group						= get_user_max_group($user, $this->groups);
				$user['group_color']		= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
				$user['group_nicename']		= $group['nicename'];
				$user['group_avatar']		= $group['avatar'];

				$this->users[$user['id']]	= $user;
			} else {
				
				$user						= $this->users[$temp['poster_id']];
			}

			foreach($user as $key => $val)
				$temp['post_user_'. $key] = $val;
		}

		$bbcode							= &new BBCodex($this->user, $temp['body_text'], $temp['forum_id'], TRUE, TRUE, TRUE, TRUE);
		$temp['reverted_body_text']		= $bbcode->revert();

		/* Should we free the result? */
		if($this->row == $this->size-1) {
			$this->result->freeResult();
		}

		return $temp;
	}
}

?>