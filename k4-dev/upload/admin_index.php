<?php
/**
* k4 Bulletin Board, admin_index.php
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
* @version $Id: admin_index.php,v 1.1 2005/04/05 03:10:22 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

require 'forum.inc.php';

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		if($user['perms'] >= ADMIN) {
			$template->setVar('current_location', $template->getVar('L_ADMINISTRATION'));
		} else {
			$template		= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setFilename('forum_base.html');
			$template->setInfo('content', $template->getVar('L_YOUNEEDPERMS'), FALSE);
		}

		return TRUE;
	}
}

$app = new Forum_Controller('admin/admin_frameset.html');

$app->ExecutePage();

?>