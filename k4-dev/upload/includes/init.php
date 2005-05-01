<?php
/**
* k4 Bulletin Board, init.php
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
* @version $Id: init.php,v 1.8 2005/05/01 17:37:10 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

require FORUM_BASE_DIR. '/includes/debug.php';
require FORUM_BASE_DIR. '/includes/framework/pagecraft.php';

/* Functions */
require FORUM_BASE_DIR. '/includes/cache.php';
require FORUM_BASE_DIR. '/includes/common.php';
require FORUM_BASE_DIR. '/includes/maps.php';
require FORUM_BASE_DIR. '/includes/heirarchy.php';
require FORUM_BASE_DIR. '/includes/bbcode.php';

/* Classes */
require FORUM_BASE_DIR. '/includes/classes/breadcrumbs.class.php';
require FORUM_BASE_DIR. '/includes/classes/categories.class.php';
require FORUM_BASE_DIR. '/includes/classes/forums.class.php';
require FORUM_BASE_DIR. '/includes/classes/topics.class.php';
require FORUM_BASE_DIR. '/includes/classes/topic_review.class.php';
require FORUM_BASE_DIR. '/includes/classes/replies.class.php';
require FORUM_BASE_DIR. '/includes/classes/users.class.php';
require FORUM_BASE_DIR. '/includes/classes/online_users.class.php';
require FORUM_BASE_DIR. '/includes/classes/globals.class.php';
require FORUM_BASE_DIR. '/includes/classes/member/login.class.php';

/* Admin Classes */
require FORUM_BASE_DIR. '/includes/classes/admin/maps.class.php';
require FORUM_BASE_DIR. '/includes/classes/admin/posticons.class.php';
require FORUM_BASE_DIR. '/includes/classes/admin/emoticons.class.php';
require FORUM_BASE_DIR. '/includes/classes/admin/files.class.php';
require FORUM_BASE_DIR. '/includes/classes/admin/categories.class.php';
require FORUM_BASE_DIR. '/includes/classes/admin/forums.class.php';
require FORUM_BASE_DIR. '/includes/classes/admin/usergroups.class.php';

/* Sessions */
include FORUM_BASE_DIR.	'/includes/session.php';

?>