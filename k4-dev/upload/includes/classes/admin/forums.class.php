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
* @version $Id: forums.class.php,v 1.8 2005/05/11 17:41:36 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class AdminForums extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			global $_QUERYPARAMS;


			$categories = &new AdminCategoriesIterator("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". INFO ." i LEFT JOIN ". CATEGORIES ." c ON c.category_id = i.id WHERE i.row_type = ". CATEGORY ." ORDER BY i.row_order ASC");
			$template->setList('categories', $categories);
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/forums_manage.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminAddForum extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_QUERYPARAMS;

			/* Error checking */
			if(!isset($request['category_id']) || intval($request['category_id']) == 0) {
				$template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);
				return TRUE;
			}

			$category					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". CATEGORIES ." c LEFT JOIN ". INFO ." i ON c.category_id = i.id WHERE i.id = ". intval(@$request['category_id']));			

			if(!is_array($category) || empty($category)) {
				$template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);
				return TRUE;
			}
			
			$template->setVar('category_id', $category['id']);
			
			/* Do we have a parent forum Id? */
			if(isset($request['forum_id']) && intval($request['forum_id']) != 0) {
				$forum					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval(@$request['forum_id']));			

				if(!is_array($forum) || empty($forum)) {
					$template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);
					return TRUE;
				}
				
				$template->setVar('forum_id', $forum['id']);
			}

			$languages					= array();
			
			$dir						= dir(FORUM_BASE_DIR .'/includes/lang');
			
			while(false !== ($file = $dir->read())) {

				if($file != '.' && $file != '..' && $file != 'CVS' && is_dir(FORUM_BASE_DIR .'/includes/lang/'. $file) && is_readable(FORUM_BASE_DIR .'/includes/lang/'. $file)) {
					$languages[]		= array('lang' => $file, 'name' => ucfirst($file));
				}
			}
			
			$languages					= &new FAArrayIterator($languages);

			$template->setList('languages', $languages);

			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/forums_add.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminInsertForum extends Event {

	var $dba;

	function getNumOnLevel($row_left, $row_right, $level) {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". INFO ." WHERE row_left > $row_left AND row_right < $row_right AND row_level = $level");
	}
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_QUERYPARAMS;
				
			$this->dba					= &$dba;

			/* Error checking */
			if(!isset($request['category_id']) || intval($request['category_id']) == 0) {
				$template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);
				return TRUE;
			}
			
			/* Attempt to get this category */
			$category					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". CATEGORIES ." c LEFT JOIN ". INFO ." i ON c.category_id = i.id WHERE i.id = ". intval(@$request['category_id']));			

			if(!is_array($category) || empty($category)) {
				$template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);
				return TRUE;
			}
			
			$forum						= $category;

			/* Do we have a parent forum Id? */
			if(isset($request['forum_id']) && intval($request['forum_id']) != 0) {
				
				/* Attempt to get this forum */
				$forum					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval(@$request['forum_id']));			

				if(!is_array($forum) || empty($forum)) {
					$template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);
					return TRUE;
				}
			}

			/* Set the parent id */
			$parent_id					= $forum['id'];

			/* Error checking on the fields */
			if(!isset($request['name']) || $request['name'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTCATNAME'), TRUE);
				return TRUE;
			}
			if(!isset($request['description']) || $request['description'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTCATDESC'), TRUE);
				return TRUE;
			}
			if(!isset($request['row_order']) || $request['row_order'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTCATORDER'), TRUE);
				return TRUE;
			}
			if(!ctype_digit($request['row_order'])) {
				$template->setInfo('content', $template->getVar('L_INSERTCATORDERNUM'), TRUE);
				return TRUE;
			}

			$abs_right			= $dba->getValue("SELECT row_right FROM ". INFO ." WHERE parent_id = ". intval($forum['id']) ." ORDER BY row_right DESC LIMIT 1");

			/* Find out how many nodes are on the current level */
			$num_on_level		= $this->getNumOnLevel($forum['row_left'], $forum['row_right'], $forum['row_level']+1);
			
			/* If there are more than 1 nodes on the current level */
			if($num_on_level > 0) {
				$left			= $forum['row_right'];
			} else {
				$left			= $forum['row_left'] + 1;
			}

			$right				= $left + 1;
			
			/* Build the queries */
			$update_a			= &$this->dba->prepareStatement("UPDATE ". INFO ." SET row_right = row_right+2 WHERE row_left < ? AND row_right >= ?");
			$update_b			= &$this->dba->prepareStatement("UPDATE ". INFO ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= ?");
			$insert_a			= &$dba->prepareStatement("INSERT INTO ". INFO ." (name,row_left,row_right,row_type,row_level,created,row_order,parent_id) VALUES (?,?,?,?,?,?,?,?)");
			$insert_b			= &$dba->prepareStatement("INSERT INTO ". FORUMS ." (category_id,forum_id,description,pass,is_forum,is_link,link_href,link_show_redirects,forum_rules,topicsperpage,postsperpage,maxpolloptions,defaultlang,moderating_groups,prune_auto,prune_frequency,prune_post_age,prune_post_viewed_age,prune_old_polls,prune_announcements,prune_stickies) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			
			/* Set the update values */
			$update_a->setInt(1, $left);
			$update_a->setInt(2, $left);
			$update_b->setInt(1, $left);

			/* Set the query values */
			$insert_a->setString(1, $request['name']);
			$insert_a->setInt(2, $left);
			$insert_a->setInt(3, $right);
			$insert_a->setInt(4, FORUM);
			$insert_a->setInt(5, $forum['row_level']+1);
			$insert_a->setInt(6, time());
			$insert_a->setInt(7, $request['row_order']);
			$insert_a->setInt(8, $parent_id);
			
			/* Update the information table */
			$update_a->executeUpdate();
			$update_b->executeUpdate();

			/* Add the forum to the info table */
			$insert_a->executeUpdate();
			
			/* Get this forum id */
			$forum_id			= $dba->getInsertId();
			
			$forum_rules		= &new BBCodex(&$user, $request['forum_rules'], FALSE, TRUE, TRUE, TRUE, TRUE);

			/* Build the query for the forums table */
			$insert_b->setInt(1, $category['id']);
			$insert_b->setInt(2, $forum_id);
			$insert_b->setString(3, $request['description']);
			$insert_b->setString(4, $request['pass']);
			$insert_b->setInt(5, iif(intval($request['is_link']) == 1, 0, 1));
			$insert_b->setInt(6, $request['is_link']);
			$insert_b->setString(7, $request['link_href']);
			$insert_b->setInt(8, $request['link_show_redirects']);
			$insert_b->setString(9, $forum_rules->parse());
			$insert_b->setInt(10, $request['topicsperpage']);
			$insert_b->setInt(11, $request['postsperpage']);
			$insert_b->setInt(12, $request['maxpolloptions']);
			$insert_b->setString(13, $request['defaultlang']);
			$insert_b->setString(14, iif((isset($request['moderators']) && is_array($request['moderators']) && !empty($request['moderators'])), serialize(@$request['moderators']), ''));
			$insert_b->setInt(15, $request['prune_auto']);
			$insert_b->setInt(16, $request['prune_frequency']);
			$insert_b->setInt(17, $request['prune_post_age']);
			$insert_b->setInt(18, $request['prune_post_viewed_age']);
			$insert_b->setInt(19, $request['prune_old_polls']);
			$insert_b->setInt(20, $request['prune_announcements']);
			$insert_b->setInt(21, $request['prune_stickies']);

			/* Insert the extra forum info */
			$insert_b->executeUpdate();

			if(!($forum['row_type'] & CATEGORY)) {
				$dba->executeUpdate("UPDATE ". FORUMS ." SET subforums = 1 WHERE forum_id = ". $forum['id']);
			}
			
			$template->setInfo('content', sprintf($template->getVar('L_ADDEDFORUM'), $request['name']), FALSE);
			$template->setRedirect('admin.php?act=forums_insertmaps&id='. $forum_id, 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminInsertForumMaps extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_MAPITEMS, $_QUERYPARAMS;

			if(!isset($request['id']) || intval($request['id']) == 0) {
				$template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);
				return TRUE;
			}

			$forum							= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($request['id']));
			
			if(!is_array($forum) || empty($forum))
				return $template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);

			$parent_id						= $dba->getValue("SELECT id FROM ". MAPS ." WHERE varname = 'forums'");
			
			/* Insert the main forum MAP item */
			$map							= &new AdminInsertMap();
			
			/* Set the default data for this forum MAP element */
			$forum_array					= array_merge(array('name' => $forum['name'], 'varname' => 'forum'. $forum['id'], 'parent_id' => $parent_id), $_MAPITEMS['forum'][0]);
			
			/**
			 * Insert the main forum MAP information
			 */
			
			Error::reset();
			$map->insertNode($forum_array, $forum['category_id'], $forum['id']);

			if(Error::grab()) {
				$error						= &Error::grab();
				$template->setInfo('content', $template->getVar($error->message));
				return TRUE;
			}
			
			$forum_map_id					= $dba->getInsertId();

			/**
			 * Insert the secondary forum MAP information
			 */
			if($forum['is_forum'] == 1) {
				for($i = 1; $i < count($_MAPITEMS['forum']); $i++) {
					
					if(isset($_MAPITEMS['forum'][$i]) && is_array($_MAPITEMS['forum'][$i])) {
						
						$forum_array			= array_merge(array('parent_id' => $forum_map_id), $_MAPITEMS['forum'][$i]);
						
						$forum_array['name']	= $template->getVar('L_'. strtoupper($forum_array['varname']));
						
						Error::reset();
						$map->insertNode($forum_array, $forum['category_id'], $forum['id']);

						if(Error::grab()) {
							$error				= &Error::grab();
							$template->setInfo('content', $template->getVar($error->message));
							return TRUE;
						}
					}
				}
			}

			if(!unlink(CACHE_FILE)) {
				@touch(CACHE_FILE, time()-86400);
			}

			/**
			 * If we've gotten to this point.. redirect
			 */
			$template->setInfo('content', sprintf($template->getVar('L_ADDEDFORUMPERMS'), $forum['name']), FALSE);
			$template->setRedirect('admin.php?act=forums', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminSimpleForumUpdate extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_MAPITEMS, $_QUERYPARAMS;

			if(!isset($request['id']) || intval($request['id']) == 0) {
				$template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);
				return TRUE;
			}

			$forum					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($request['id']));

			if(!is_array($forum) || empty($forum)) {
				$template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);
				return TRUE;
			}

			if(!isset($request['row_order']) || $request['row_order'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTCATORDER'), TRUE);
				return TRUE;
			}

			if(!ctype_digit($request['row_order'])) {
				$template->setInfo('content', $template->getVar('L_INSERTCATORDERNUM'), TRUE);
				return TRUE;
			}

			$update		= &$dba->prepareStatement("UPDATE ". INFO ." SET row_order=? WHERE id=?");
			$update->setInt(1, $request['row_order']);
			$update->setInt(2, $forum['id']);

			$update->executeUpdate();

			$template->setInfo('content', sprintf($template->getVar('L_UPDATEDFORUM'), $forum['name']), FALSE);
			$template->setRedirect('admin.php?act=forums', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminEditForum extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_QUERYPARAMS, $_USERGROUPS;

			if(!isset($request['id']) || intval($request['id']) == 0) {
				$template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);
				return TRUE;
			}

			$forum					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($request['id']));

			if(!is_array($forum) || empty($forum)) {
				$template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);
				return TRUE;
			}
			
			$forum_rules				= &new BBCodex(&$user, $forum['forum_rules'], $forum['id'], TRUE, TRUE, TRUE, TRUE);
			$forum['forum_rules']		= $forum_rules->revert();

			foreach($forum as $key => $val)
				$template->setVar('forum_'. $key, $val);
			
			$languages					= array();
			
			$dir						= dir(FORUM_BASE_DIR .'/includes/lang');
			
			while(false !== ($file = $dir->read())) {
				if($file != '.' && $file != '..' && is_dir(FORUM_BASE_DIR .'/includes/lang/'. $file)) {
					$languages[]		= array('lang' => $file, 'name' => ucfirst($file));
				}
			}

			$groups		= @unserialize($forum['moderating_groups']);
			$groups_str	= '';

			if(is_array($groups)) {
				foreach($groups as $g) {
					if(isset($_USERGROUPS[$g])) {
						$groups_str	.= $g .' ';
					}
				}

				$template->setVar('forum_moderating_groups', iif(strlen($groups_str) > 0, substr($groups_str, 0, -1), ''));
			}

			$languages					= &new FAArrayIterator($languages);

			$template->setList('languages', $languages);

			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/forums_edit.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminUpdateForum extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_QUERYPARAMS;

			/* Error checking on the fields */
			if(!isset($request['id']) || intval($request['id']) == 0) {
				$template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);
				return TRUE;
			}

			$forum					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($request['id']));

			if(!is_array($forum) || empty($forum)) {
				$template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);
				return TRUE;
			}

			if(!isset($request['name']) || $request['name'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTCATNAME'), TRUE);
				return TRUE;
			}
						
			if(!isset($request['description']) || $request['description'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTCATDESC'), TRUE);
				return TRUE;
			}
			
			if(!isset($request['row_order']) || $request['row_order'] == '') {
				$template->setInfo('content', $template->getVar('L_INSERTCATORDER'), TRUE);
				return TRUE;
			}

			if(!ctype_digit($request['row_order'])) {
				$template->setInfo('content', $template->getVar('L_INSERTCATORDERNUM'), TRUE);
				return TRUE;
			}

			/* Build the queries */
			$update_a			= &$dba->prepareStatement("UPDATE ". INFO ." SET name=?,row_order=? WHERE id=?");
			$update_b			= &$dba->prepareStatement("UPDATE ". FORUMS ." SET description=?,pass=?,is_forum=?,is_link=?,link_href=?,link_show_redirects=?,forum_rules=?,topicsperpage=?,postsperpage=?,maxpolloptions=?,defaultlang=?,moderating_groups=?,prune_auto=?,prune_frequency=?,prune_post_age=?,prune_post_viewed_age=?,prune_old_polls=?,prune_announcements=?,prune_stickies=? WHERE forum_id=?");
			$update_c			= &$dba->prepareStatement("UPDATE ". MAPS ." SET name=? WHERE varname=?");

			/* Set the query values */
			$update_a->setString(1, $request['name']);
			$update_a->setInt(2, $request['row_order']);
			$update_a->setInt(3, $forum['id']);
			
			$forum_rules		= &new BBCodex(&$user, $request['forum_rules'], $forum['id'], TRUE, TRUE, TRUE, TRUE);

			/* Build the query for the forums table */
			$update_b->setString(1, $request['description']);
			$update_b->setString(2, $request['pass']);
			$update_b->setInt(3, iif(intval($request['is_link']) == 1, 0, 1));
			$update_b->setInt(4, $request['is_link']);
			$update_b->setString(5, $request['link_href']);
			$update_b->setInt(6, $request['link_show_redirects']);
			$update_b->setString(7, $forum_rules->parse());
			$update_b->setInt(8, $request['topicsperpage']);
			$update_b->setInt(9, $request['postsperpage']);
			$update_b->setInt(10, $request['maxpolloptions']);
			$update_b->setString(11, $request['defaultlang']);
			$update_b->setString(12, iif(isset($request['moderators']) && is_array($request['moderators']) && !empty($request['moderators']), serialize($request['moderators']), ''));
			$update_b->setInt(13, $request['prune_auto']);
			$update_b->setInt(14, $request['prune_frequency']);
			$update_b->setInt(15, $request['prune_post_age']);
			$update_b->setInt(16, $request['prune_post_viewed_age']);
			$update_b->setInt(17, $request['prune_old_polls']);
			$update_b->setInt(18, $request['prune_announcements']);
			$update_b->setInt(19, $request['prune_stickies']);
			$update_b->setInt(20, $forum['id']);
			
			/* Simple update on the maps table */
			$update_c->setString(1, $request['name']);
			$update_c->setString(2, 'forum'. $forum['id']);

			/* Do all of the updates */
			$update_a->executeUpdate();
			$update_b->executeUpdate();
			$update_c->executeUpdate();
			
			$template->setInfo('content', sprintf($template->getVar('L_UPDATEDFORUM'), $forum['name']), FALSE);
			$template->setRedirect('admin.php?act=forums', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminRemoveForum extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($request['id']) || intval($request['id']) == 0) {
				$template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);
				return TRUE;
			}

			$forum					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($request['id']));

			if(!is_array($forum) || empty($forum))
				return $template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);

			$forums			= &$dba->executeQuery("SELECT * FROM ". INFO ." WHERE row_left >= ". intval($forum['row_left']) ." AND row_right <= ". intval($forum['row_right']) ." AND row_type = ". FORUM);
			
			$heirarchy		= &new Heirarchy();
			
			/* Deal with this forum and any sub-forums */
			while($forums->next()) {
				$f				= $forums->current();
				$forum_maps		= $dba->getRow("SELECT * FROM ". MAPS ." WHERE varname = 'forum". $f['id'] ."'");
				$heirarchy->removeNode($forum_maps, MAPS);

				$dba->executeUpdate("DELETE FROM ". FORUMS ." WHERE forum_id=". intval($f['id']));
				$dba->executeUpdate("DELETE FROM ". TOPICS ." WHERE forum_id=". intval($f['id']));
				$dba->executeUpdate("DELETE FROM ". REPLIES ." WHERE forum_id=". intval($f['id']));
			}
			
			/* This will take care of everything in the INFO table */
			$heirarchy->removeNode($forum, INFO);
			
			if(!unlink(CACHE_FILE)) {
				@touch(CACHE_FILE, time()-86400);
			}

			$template->setInfo('content', sprintf($template->getVar('L_REMOVEDFORUM'), $forum['name']), FALSE);
			$template->setRedirect('admin.php?act=forums', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminForumPermissions extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($request['id']) || intval($request['id']) == 0) {
				$template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);
				return TRUE;
			}

			$forum					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($request['id']));

			if(!is_array($forum) || empty($forum)) {
				$template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);
				return TRUE;
			}
			
			foreach($forum as $key => $val)
				$template->setVar('forum_'. $key, $val);
			
			$result					= &$dba->executeQuery("SELECT * FROM ". MAPS ." WHERE forum_id = ". intval($forum['id']) ." ORDER BY row_left ASC");

			$forum_maps				= &new MAPSIterator($result, 2);
			
			$template->setList('forum_maps', $forum_maps);

			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/forums_permissions.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminUpdateForumPermissions extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($request['id']) || intval($request['id']) == 0) {
				$template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);
				return TRUE;
			}

			$forum							= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". FORUMS ." f LEFT JOIN ". INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($request['id']));

			if(!is_array($forum) || empty($forum)) {
				$template->setInfo('content', $template->getVar('L_INVALIDFORUM'), FALSE);
				return TRUE;
			}
			
			foreach($forum as $key => $val)
				$template->setVar('forum_'. $key, $val);
			
			$forum_map						= $dba->getRow("SELECT * FROM ". MAPS ." WHERE varname = 'forum". $forum['id'] ."' AND forum_id = ". intval($forum['id']));
			$forum_maps						= $dba->executeQuery("SELECT * FROM ". MAPS ." WHERE forum_id = ". intval($forum['id']) ." AND row_left >= ". intval($forum_map['row_left']) ." AND row_right <= ". intval($forum_map['row_right']) ." ORDER BY row_left ASC");
			
			/* Loop through the forum map items */
			while($forum_maps->next()) {
				$f							= $forum_maps->current();

				if(isset($request[$f['varname'] .'_can_view']) && isset($request[$f['varname'] .'_can_add']) && isset($request[$f['varname'] .'_can_edit']) && isset($request[$f['varname'] .'_can_del'])) {
					
					if(($request[$f['varname'] .'_can_view'] != $f['can_view']) || ($request[$f['varname'] .'_can_add'] != $f['can_add']) || ($request[$f['varname'] .'_can_edit'] != $f['can_edit']) || ($request[$f['varname'] .'_can_del'] != $f['can_del'])) {

						$update				= &$dba->prepareStatement("UPDATE ". MAPS ." SET can_view=?,can_add=?,can_edit=?,can_del=? WHERE varname=? AND forum_id=?");
						$update->setInt(1, $request[$f['varname'] .'_can_view']);
						$update->setInt(2, $request[$f['varname'] .'_can_add']);
						$update->setInt(3, $request[$f['varname'] .'_can_edit']);
						$update->setInt(4, $request[$f['varname'] .'_can_del']);
						$update->setString(5, $f['varname']);
						$update->setInt(6, $forum['id']);

						$update->executeUpdate();

						unset($update);
					}
				}
			}
			
			if(!unlink(CACHE_FILE)) {
				@touch(CACHE_FILE, time()-86400);
			}

			$template->setInfo('content', sprintf($template->getVar('L_UPDATEDFORUMPERMS'), $forum['name']), FALSE);
			$template->setRedirect('admin.php?act=forums', 3);
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

?>