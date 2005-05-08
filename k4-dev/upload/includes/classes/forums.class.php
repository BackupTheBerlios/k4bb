<?php
/**
* k4 Bulletin Board, forums.class.php
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
* @version $Id: forums.class.php,v 1.8 2005/05/08 23:13:21 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

function forum_icon($instance, $temp) {
	
	$icon		= '';
	$return		= '';
	
	/* Set the forum Icon */
	if(isset($_COOKIE['forums'])) {
		
		$forums				= $_COOKIE['forums'] != NULL && $_COOKIE['forums'] != '' ? @unserialize($_COOKIE['forums']) : array();
		
		if(isset($forums[$temp['id']])) {
			
			/* Get the value of the forum cookie */
			$cookie_val		= $forums[$temp['id']];
			
			/* If there are threads stored in this forum or not */
			if(is_array($cookie_val) && !empty($cookie_val)) {
				$icon		= 'on';
			} else {
				$icon		= 'off';
			}
		} else {
			
			if(strftime("%m%d%y", $temp['topic_created']) == strftime("%m%d%y", bbtime()) && $temp['topic_uid'] != $instance->user['id']) {
				
				$icon		= 'on';
			} else {
				$icon		= 'off';
			}

			$forums[$temp['id']]	= array();
			$forums					= serialize($forums);
			
			$return					= $temp['id'];
		}
	} else {
		
		/* If the last thread post time is equal to today */
		if(strftime("%m%d%y", $temp['topic_created']) == strftime("%m%d%y", bbtime()) ) {
			$icon		= 'on';
		} else {
			$icon		= 'off';
		}
		
		$forums		= array($temp['id'] => $temp);
		$forums		= serialize($forums);

		$return		= $temp['id'];
	}

	

	/* Check if this user's perms are less than is needed to post in this forum */

	if(@$instance->user['maps']['forums'][$temp['id']]['can_add'] > $instance->user['perms'])
		$icon			.= '_lock';
	
	/* Return the icon text to add to the IMG tag */
	return array($icon, $return);
}

class MarkForumsRead extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		/* Set the Breadcrumbs bit */
		$template		= BreadCrumbs($template, $template->getVar('L_MARKFORUMSREAD'));
		
		$forums		= array();

		if(isset($request['forums']) && is_array($request['forums'])) {
			foreach($request['forums'] as $forum) {
				$forums[$forum['id']] = array();
			}
		}
		
		/* Serialize the array */
		$forums			= serialize($forums);

		/* Cache some info to set a cookie on the next refresh */
		bb_setcookie_cache('forums', $forums, time() + ini_get('session.gc_maxlifetime'));

		$template->setInfo('content', $template->getVar('L_MARKEDFORUMSREAD'), TRUE);
		$template->setRedirect('index.php', 3);

		return TRUE;
	}
}

class ForumsIterator extends FAProxyIterator {
	
	var $dba;
	var $settings;
	var $do_recures;
	var $user;
	var $result;
	var $forums;
	var $usergroups;
 
	function ForumsIterator($query = NULL, $do_recurse = TRUE) {
		
		global $_SETTINGS, $_DBA, $_QUERYPARAMS, $_USERGROUPS;
		
		$query				= $query == NULL ? "" : $query;
		
		$this->usergroups	= $_USERGROUPS;
		$this->user			= &Globals::getGlobal('user');
		$this->dba			= $_DBA;
		$this->settings		= $_SETTINGS;
		$this->query_params	= $_QUERYPARAMS;
		$this->do_recurse	= $do_recurse;
		$this->result		= &$this->dba->executeQuery($query);

		$this->forums		= isset($_COOKIE['forums']) && $_COOKIE['forums'] != NULL && $_COOKIE['forums'] != '' ? @unserialize($_COOKIE['forums']) : array();

		parent::FAProxyIterator($this->result);
	}

	function &current() {
		$temp	= parent::current();
		
		/* Cache this forum in the session */
		cache_forum($temp);

		/* Set the forum's icon */
		$return				= forum_icon($this, $temp);
		
		$temp['forum_icon']	= $return[0];
		
		/* Set a default cookie with the unread topic id in it */
		if(ctype_digit($return[1])) {
			$this->forums[$temp['id']][$return[1]] = TRUE;
		}

		/* Set a nice representation of what level we're on */
		$temp['level']		= @str_repeat('&nbsp;&nbsp;&nbsp;', $temp['row_level']-2);
						
		/* Should we query down to the next level of forums? */
		if($this->do_recurse) {
			$query_params = $this->query_params['info'] . $this->query_params['forum'];
			
			if($temp['subforums'] > 0 && $this->settings['showsubforums'] == 1) {
				$temp['subforums'] = &new ForumsIterator("SELECT $query_params FROM ". INFO ." i LEFT JOIN ". FORUMS ." f ON f.forum_id = i.id WHERE i.row_left > ". $temp['row_left'] ." AND i.row_right < ". $temp['row_right'] ." AND i.row_type = ". FORUM ." AND i.parent_id = ". $temp['id'] ." ORDER BY i.row_order ASC", FALSE);
			}
		}

		if($temp['moderating_groups'] != '') {
			
			$groups					= @unserialize($temp['moderating_groups']);
			$temp['moderators']		= array();

			if(is_array($groups)) {
				foreach($groups as $g) {
					if(isset($this->usergroups[$g])) {
						$temp['moderators'][]	= $this->usergroups[$g];
					}
				}

				$temp['moderators']	= &new FAArrayIterator($temp['moderators']);
			} else {
				$temp['moderating_groups'] = '';
			}
		}

		/* Should we free the result? */
		if($this->row == $this->size-1) {
			$this->result->freeResult();
			
			/* Set cookies for all of the topics */
			bb_settopic_cache_item('forums', serialize($this->forums), time() + 3600 * 25 * 5);
		}
		
		/* Return the formatted forum info */
		return $temp;
	}
}

class AllForumsIterator extends FAArrayIterator {
	
	var $result;
 
	function AllForumsIterator($forums) {

		parent::FAArrayIterator($forums);
	}

	function &current() {
		$temp	= parent::current();
		
		/* Cache this forum in the session */
		cache_forum($temp);
		
		/* Set a nice representation of what level we're on */
		$temp['indent_level']	= @str_repeat('&nbsp;&nbsp;&nbsp;', $temp['row_level']-1);

		/* Should we free the result? */
		if($this->key == sizeof($this->data))
			$this->result->freeResult();

		/* Return the formatted forum info */
		return $temp;
	}
}


?>