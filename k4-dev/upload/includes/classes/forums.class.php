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
* @version $Id: forums.class.php,v 1.1 2005/04/05 03:19:18 k4st Exp $
* @package k42
*/


function forum_icon($instance, $temp) {
	
	$icon = '';

	/* Set the forum Icon */
	if(isset($_COOKIE['forums']['forum'. $temp['id']])) {
		
		/* Get the value of the forum cookie */
		$cookie_val	= unserialize($_COOKIE['forum'. $temp['id']]);
		
		/* If there are threads stored in this forum or not */
		if(is_array($cookie_val) && !empty($cookie_val)) {
			$icon		= 'on';
		} else {
			$icon		= 'off';
		}
	} else {
		
		/* If the last thread post time is equal to today */
		if(strftime("%m%d%y", $temp['topic_created']) == strftime("%m%d%y", bbtime()) ) {
			$icon		= 'on';
		} else {
			$icon		= 'off';
		}
		
		/* Set a cookie to be cached in the session to be executed on the next refresh,
		The cookie will expire when the session is meant to expire */
		bb_setcookie_cache('forums[forum'. $temp['id'] .']', 'a:0:{}', ini_get('session.gc_maxlifetime'));
	}

	/* Check if this user's perms are less than is needed to post in this forum */

	if($instance->user['maps']['forums'][$temp['id']]['can_add'] > $instance->user['perms'])
		$icon			.= '_lock';
	
	/* Return the icon text to add to the IMG tag */
	return $icon;
}

class MarkForumsRead extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		/* Set the Breadcrumbs bit */
		$template		= BreadCrumbs($template, $template->getVar('L_MARKFORUMSREAD'));
		
		if(isset($request['forums']) && is_array($request['forums'])) {
			foreach($request['forums'] as $key => $val) {

				/* Cache some info to set a cookie on the next refresh */
				bb_setcookie_cache('forums['. $key .']', 'a:0:{}', ini_get('session.gc_maxlifetime'));
			}
		}
		$template->setInfo('content', $template->getVar('L_MARKEDFORUMSREAD'), TRUE);
		$template->setRedirect($_SERVER['HTTP_REFERER'], 3);

		return TRUE;
	}
}

class ForumsIterator extends FAProxyIterator {
	
	var $dba;
	var $settings;
	var $do_recures;
	var $user;
 
	function ForumsIterator($query = NULL, $do_recurse = TRUE) {
		
		global $_SETTINGS, $_DBA, $_QUERYPARAMS;
		
		$query				= $query == NULL ? "" : $query;
		
		$this->user			= &Globals::getGlobal('user');
		$this->dba			= $_DBA;
		$this->settings		= $_SETTINGS;
		$this->query_params	= $_QUERYPARAMS;
		$this->do_recurse	= $do_recurse;

		parent::FAProxyIterator($this->dba->executeQuery($query));
	}

	function &current() {
		$temp	= parent::current();

		cache_forum($temp);

		/* Set the forum's icon */
		$temp['forum_icon']	= forum_icon($this, $temp);

		/* Increment the number of topics and replies */
		if(Globals::is_set('num_topics') && Globals::is_set('num_replies')) {
			Globals::setGlobal('num_topics', Globals::getGlobal('num_topics') + $temp['topics']);
			Globals::setGlobal('num_replies', Globals::getGlobal('num_replies') + $temp['replies']);
		}
		
		// %D would work as well
		$temp['post_created'] = strftime("%m.%d.%y", bbtime($temp['post_created']));
		
		if($this->do_recurse) {
			$query_params = $this->query_params['info'] . $this->query_params['forum'];
			
			if($temp['subforums'] > 0 && $this->settings['showsubforums'] == 1) {
				$temp['subforums'] = &new ForumsIterator("SELECT $query_params FROM ". INFO ." i LEFT JOIN ". FORUMS ." f ON f.forum_id = i.id WHERE i.row_left > ". $temp['row_left'] ." AND i.row_right < ". $temp['row_right'] ." AND i.row_type = ". FORUM ." AND i.parent_id = ". $temp['id'] ." ORDER BY i.row_order ASC", FALSE);
			}
		}
		return $temp;
	}
}

?>