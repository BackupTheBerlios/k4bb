<?php
/**
* k4 Bulletin Board, files.php
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
* @author Geoffrey Goodman
* @author James Logsdon
* @version $Id: redirect.php,v 1.1 2005/05/16 02:10:03 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

ob_start();

require 'forum.inc.php';

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS;
				
		if(!isset($request['id']) || !$request['id'] || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template				= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);
			return TRUE;
		}
			
		/* Get the current forum/category */
		$forum					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($request['id']));

		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('content', $template->getVar('L_FORUMDOESNTEXIST'), FALSE);

			return TRUE;
		}

		if($forum['is_link'] == 1) {
			if($forum['is_forum'] == 1) {
				if(($forum['row_right'] - $forum['row_left']) > 0) {
					header("Location: viewforum.php?id=". intval($forum['id']));
				}
			}

			if(!isset($forum['link_href']) || $forum['link_href'] == '') {
				$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
				$template->setInfo('content', $template->getVar('L_INVALIDLINKFORUM'));
				return TRUE;
			}

			$dba->executeUpdate("UPDATE ". FORUMS ." SET link_redirects=link_redirects+1 WHERE forum_id=". intval($forum['id']));

			header("Location: ". $forum['link_href']);

		} else {
			header("Location: viewforum.php?id=". intval($forum['id']));
		}
		
		return TRUE;
	}
}

$app	= new Forum_Controller('forum_base.html');

$app->ExecutePage();

ob_flush();

?>