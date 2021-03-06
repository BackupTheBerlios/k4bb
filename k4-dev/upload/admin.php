<?php
/**
* k4 Bulletin Board, admin.php
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
* @version $Id: admin.php,v 1.9 2005/05/16 02:10:03 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

ob_start();

require 'forum.inc.php';

@set_time_limit(120);

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			$template->setFile('content', 'admin/admin.html');
			
			
		} else {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setFile('content', 'login_form.html');
			$template->show('no_perms');
			return TRUE;
		}

		return TRUE;
	}
}

class AdminMenu extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			$template->setFile('content', 'admin/admin_menu.html');
			$template->hide('copyright');
		} else {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setFile('content', 'login_form.html');
			$template->show('no_perms');
			return TRUE;
		}

		return TRUE;
	}
}

class AdminHead extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			$template->setFile('content', 'admin/admin_head.html');
			$template->hide('copyright');
		} else {
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setFile('content', 'login_form.html');
			$template->show('no_perms');
			return TRUE;
		}

		return TRUE;
	}
}

$app = new Forum_Controller('admin/admin_base.html');

/* Things in the Frameset */
$app->AddEvent('admin_header', new AdminHead);
$app->AddEvent('admin_navigation', new AdminMenu);

/* Admin File Browser */
$app->AddEvent('file_browser', new AdminFileBrowser);

/* The GUI for the MAPS permission system */
$app->AddEvent('permissions_gui', new AdminMapsGui);
$app->AddEvent('maps_inherit', new AdminMapsInherit);
$app->AddEvent('maps_update', new AdminMapsUpdate);
$app->AddEvent('maps_add', new AdminMapsAddNode);
$app->AddEvent('maps_insert', new AdminMapsInsertNode);
$app->AddEvent('maps_remove', new AdminMapsRemoveNode);

/* Post Icons */
$app->AddEvent('posticons', new AdminPostIcons);
$app->AddEvent('posticons_add', new AdminAddPostIcon);
$app->AddEvent('posticons_edit', new AdminEditPostIcon);
$app->AddEvent('posticons_insert', new AdminInsertPostIcon);
$app->AddEvent('posticons_remove', new AdminRemovePostIcon);
$app->AddEvent('posticons_update', new AdminUpdatePostIcon);

/* Emoticons */
$app->AddEvent('emoticons', new AdminEmoticons);
$app->AddEvent('emoticons_add', new AdminAddEmoticon);
$app->AddEvent('emoticons_edit', new AdminEditEmoticon);
$app->AddEvent('emoticons_insert', new AdminInsertEmoticon);
$app->AddEvent('emoticons_remove', new AdminRemoveEmoticon);
$app->AddEvent('emoticons_update', new AdminUpdateEmoticon);
$app->AddEvent('emoticons_clickable', new AdminUpdateEmoticonClick);

/* Categories */
$app->AddEvent('categories', new AdminCategories);
$app->AddEvent('categories_add', new AdminAddCategory);
$app->AddEvent('categories_insert', new AdminInsertCategory);
$app->AddEvent('categories_insertmaps', new AdminInsertCategoryMaps);
$app->AddEvent('categories_simpleupdate', new AdminSimpleCategoryUpdate);
$app->AddEvent('categories_edit', new AdminEditCategory);
$app->AddEvent('categories_update', new AdminUpdateCategory);
$app->AddEvent('categories_remove', new AdminRemoveCategory);
$app->AddEvent('categories_permissions', new AdminCategoryPermissions);
$app->AddEvent('categories_updateperms', new AdminUpdateCategoryPermissions);

/* Forums */
$app->AddEvent('forums', new AdminForums);
$app->AddEvent('forums_add', new AdminAddForum);
$app->AddEvent('forums_insert', new AdminInsertForum);
$app->AddEvent('forums_insertmaps', new AdminInsertForumMaps);
$app->AddEvent('forums_simpleupdate', new AdminSimpleForumUpdate);
$app->AddEvent('forums_edit', new AdminEditForum);
$app->AddEvent('forums_update', new AdminUpdateForum);
$app->AddEvent('forums_remove', new AdminRemoveForum);
$app->AddEvent('forums_permissions', new AdminForumPermissions);
$app->AddEvent('forums_updateperms', new AdminUpdateForumPermissions);

/* User Groups */
$app->AddEvent('usergroups', new AdminUserGroups);
$app->AddEvent('usergroups_add', new AdminAddUserGroup);
$app->AddEvent('usergroups_insert', new AdminInsertUserGroup);
$app->AddEvent('usergroups_remove', new AdminRemoveUserGroup);
$app->AddEvent('usergroups_edit', new AdminEditUserGroup);
$app->AddEvent('usergroups_update', new AdminUpdateUserGroup);

/* Profile Fields */
$app->AddEvent('userfields', new AdminUserProfileFields);
$app->AddEvent('userfields_add', new AdminAddUserField);
$app->AddEvent('userfields_add2', new AdminAddUserFieldTwo);
$app->AddEvent('userfields_insert', new AdminInsertUserField);
$app->AddEvent('userfields_remove', new AdminRemoveUserField);
$app->AddEvent('userfields_edit', new AdminEditUserField);
$app->AddEvent('userfields_update', new AdminUpdateUserField);
$app->AddEvent('userfields_simpleupdate', new AdminSimpleUpdateUserFields);

/* Disallowed User Names */
$app->AddEvent('usernames', new AdminBadUserNames);
$app->AddEvent('usernames_insert', new AdminInsertBadUserName);
$app->AddEvent('usernames_update', new AdminUpdateBadUserName);
$app->AddEvent('usernames_remove', new AdminRemoveBadUserName);

$app->ExecutePage();

ob_flush();

?>