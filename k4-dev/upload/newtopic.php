<?php
/**
* k4 Bulletin Board, newtopic.php
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
* @version $Id: newtopic.php,v 1.2 2005/04/06 00:48:48 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

require 'forum.inc.php';

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_URL, $_QUERYPARAMS;
		
		/* Check the request ID */
		if(!isset($request['id']) || !$request['id'] || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			return $template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
		}
			
		$forum				= get_cached_forum($request['id']);
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			return $template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			return $template->setInfo('content', $template->getVar('L_CANTPOSTTOCATEGORY'), FALSE);		
		}

		/* Do we have permission to post to this forum? */
		if($user['perms'] < $user['maps']['forums'][$forum['id']]['topics']['can_add']) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			return $template->setInfo('content', $template->getVar('L_PERMCANTPOST'), FALSE);		
		}
				
		/**
		 * Start setting useful template information
		 */
		

		/* Get and set the emoticons and post icons to the template */
		$emoticons	= &$dba->executeQuery("SELECT * FROM ". EMOTICONS ." WHERE clickable = 1");
		$posticons	= &$dba->executeQuery("SELECT * FROM ". POSTICONS);

		$template->setList('emoticons', $emoticons);
		$template->setList('posticons', $posticons);

		$template->setVar('emoticons_per_row', $template->getVar('smcolumns'));
		$template->setVar('emoticons_per_row_remainder', $template->getVar('smcolumns')-1);
		
		/* Set the forum info to the template */
		foreach($forum as $key => $val)
			$template->setVar('forum_'. $key, $val);

		/* set the breadcrumbs bit */
		$template	= BreadCrumbs($template, $template->getVar('L_POSTTOPIC'), $forum['row_left'], $forum['row_right']);
		
		/* Set the post topic form */
		$template->setFile('content', 'newtopic.html');

		return TRUE;
	}
}


$app = new Forum_Controller('forum_base.html');

$app->ExecutePage();

?>