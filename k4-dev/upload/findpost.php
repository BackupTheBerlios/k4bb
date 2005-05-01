<?php
/**
* k4 Bulletin Board, findpost.php
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
* @version $Id: findpost.php,v 1.1 2005/05/01 17:47:03 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

require 'forum.inc.php';

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS;
		
		/**
		 * Error Checking
		 */
		if(!isset($request['id']) || !$request['id'] || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDPOST'));
			$template->setInfo('content', $template->getVar('L_POSTDOESNTEXIST'), FALSE);
		}

		$post	= $dba->getRow("SELECT "> $_QUERYPARAMS['info'] ." FROM ". INFO ." i WHERE i.id = ". intval($request['id']));
		
		if($post['row_type'] != TOPIC && $post['row_type'] != REPLY) {
			
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDPOST'));
			$template->setInfo('content', $template->getVar('L_POSTDOESNTEXIST'), FALSE);
		}
		
		/* If this is a topic */
		if($post['row_type'] == TOPIC) {
			
			$topic				= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". TOPICS ." t LEFT JOIN ". INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($post['id']));
			
			/* Make sure that this topic isn't a draft */
			if($topic['is_draft'] == 1) {
				/* set the breadcrumbs bit */
				$template		= BreadCrumbs($template, $template->getVar('L_INVALIDTOPICVIEW'));
				$template->setInfo('content', $template->getVar('L_CANTVIEWDRAFT'), FALSE);
				
				return TRUE;
			}

		/* If this is a reply */	
		} else {
			
		}

		return TRUE;
	}
}

$app = new Forum_Controller('forum_base.html');

$app->ExecutePage();

?>