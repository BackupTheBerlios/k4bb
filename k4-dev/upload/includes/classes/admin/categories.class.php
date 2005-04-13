<?php
/**
* k4 Bulletin Board, categories.class.php
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
* @version $Id: categories.class.php,v 1.2 2005/04/13 02:52:47 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class AdminCategories extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			global $_QUERYPARAMS;


			$categories			= &$dba->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". INFO ." i LEFT JOIN ". CATEGORIES ." c ON c.category_id = i.id AND i.row_type = ". CATEGORY ." ORDER BY i.row_order ASC");

			$template->setList('categories', $categories);
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/categories_manage.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminAddCategory extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
						
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/categories_add.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminInsertCategory extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			/* Error checking on the fields */
			if(!isset($request['name']) || $request['name'] == '')
				return $template->setInfo('content', $template->getVar('L_INSERTCATNAME'), TRUE);
						
			if(!isset($request['description']) || $request['description'] == '')
				return $template->setInfo('content', $template->getVar('L_INSERTCATDESC'), TRUE);
			
			if(!isset($request['row_order']) || $request['row_order'] == '')
				return $template->setInfo('content', $template->getVar('L_INSERTCATORDER'), TRUE);

			if(!is_numeric($request['row_order']))
				return $template->setInfo('content', $template->getVar('L_INSERTCATORDERNUM'), TRUE);
			
			$abs_right			= $dba->getValue("SELECT row_right FROM ". INFO ." WHERE row_type = ". CATEGORY ." ORDER BY row_right DESC LIMIT 1");

			$left				= $abs_right && $abs_right !== false && $abs_right != 0 ? $abs_right+1 : 1;
			$right				= $left + 1;
			
			/* Build the queries */
			$insert_a			= &$dba->prepareStatement("INSERT INTO ". INFO ." (name,row_left,row_right,row_type,row_level,created,row_order) VALUES (?,?,?,?,?,?,?)");
			$insert_b			= &$dba->prepareStatement("INSERT INTO ". CATEGORIES ." (category_id,description) VALUES (?,?)");
			
			/* Set the query values */
			$insert_a->setString(1, $request['name']);
			$insert_a->setInt(2, $left);
			$insert_a->setInt(3, $right);
			$insert_a->setInt(4, CATEGORY);
			$insert_a->setInt(5, 1);
			$insert_a->setInt(6, time());
			$insert_a->setInt(7, $request['row_order']);
			
			/* Add the category to the info table */
			$insert_a->executeUpdate();
			
			$category_id		= $dba->getInsertId();

			/* Build the query for the categories table */
			$insert_b->setInt(1, $category_id);
			$insert_b->setString(2, $request['description']);
			
			/* Insert the extra category info */
			$insert_b->executeUpdate();
			
			$template->setInfo('content', sprintf($template->getVar('L_ADDEDCATEGORY'), $request['name']), FALSE);
			$template->setRedirect('admin.php?act=categories_insertmaps&id='. $category_id, 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminInsertCategoryMaps extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_MAPITEMS, $_QUERYPARAMS;

			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);			

			$category					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". CATEGORIES ." c LEFT JOIN ". INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($request['id']));

			if(!is_array($category) || empty($category))
				return $template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);

			$parent_id					= $dba->getValue("SELECT id FROM ". MAPS ." WHERE varname = 'categories'");

			/* Insert the main category MAP item */
			$map						= &new AdminInsertMap();
			
			/* Set the default data for this category MAP element */
			$category_array				= array_merge(array('name' => $category['name'], 'varname' => 'category'. $category['id'], 'parent_id' => $parent_id), $_MAPITEMS['category'][0]);
			
			/**
			 * Insert the main category MAP information
			 */

			Error::reset();
			$map->insertNode($category_array, $category['id']);

			if(Error::grab()) {
				$error					= &Error::grab();
				return $template->setError('content', $template->getVar($error->message));
			}
			
			$category_map_id			= $dba->getInsertId();

			/**
			 * Insert the secondary category MAP information
			 */
			for($i = 1; $i < count($_MAPITEMS['category'])-1; $i++) {
				
				if(isset($_MAPITEMS['category'][$i]) && is_array($_MAPITEMS['category'][$i])) {

					$category_array			= array_merge(array('parent_id' => $category_map_id), $_MAPITEMS['category'][$i]);
					
					$category_array['name']	= $template->getVar('L_'. strtoupper($category_array['varname']));

					Error::reset();
					$map->insertNode($category_array, $category['id']);

					if(Error::grab()) {
						$error				= &Error::grab();
						return $template->setError('content', $template->getVar($error->message));
					}
				}
			}
			
			/**
			 * If we've gotten to this point.. redirect
			 */
			$template->setInfo('content', sprintf($template->getVar('L_ADDEDCATEGORYPERMS'), $category['name']), FALSE);
			$template->setRedirect('admin.php?act=categories', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminSimpleCategoryUpdate extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_MAPITEMS, $_QUERYPARAMS;

			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);			

			$category					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". CATEGORIES ." c LEFT JOIN ". INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($request['id']));

			if(!is_array($category) || empty($category))
				return $template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);

			if(!isset($request['row_order']) || $request['row_order'] == '')
				return $template->setInfo('content', $template->getVar('L_INSERTCATORDER'), TRUE);

			if(!is_numeric($request['row_order']))
				return $template->setInfo('content', $template->getVar('L_INSERTCATORDERNUM'), TRUE);

			$update		= &$dba->prepareStatement("UPDATE ". INFO ." SET row_order=? WHERE id=?");
			$update->setInt(1, $request['row_order']);
			$update->setInt(2, $category['id']);

			$update->executeUpdate();

			$template->setInfo('content', sprintf($template->getVar('L_UPDATEDCATEGORY'), $category['name']), FALSE);
			$template->setRedirect('admin.php?act=categories', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminEditCategory extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);			

			$category					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". CATEGORIES ." c LEFT JOIN ". INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($request['id']));

			if(!is_array($category) || empty($category))
				return $template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);			
			
			foreach($category as $key => $val)
				$template->setVar('category_'. $key, $val);
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/categories_edit.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminUpdateCategory extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_QUERYPARAMS;

			/* Error checking on the fields */
			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);			

			$category					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". CATEGORIES ." c LEFT JOIN ". INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($request['id']));

			if(!is_array($category) || empty($category))
				return $template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);

			if(!isset($request['name']) || $request['name'] == '')
				return $template->setInfo('content', $template->getVar('L_INSERTCATNAME'), TRUE);
						
			if(!isset($request['description']) || $request['description'] == '')
				return $template->setInfo('content', $template->getVar('L_INSERTCATDESC'), TRUE);
			
			if(!isset($request['row_order']) || $request['row_order'] == '')
				return $template->setInfo('content', $template->getVar('L_INSERTCATORDER'), TRUE);

			if(!is_numeric($request['row_order']))
				return $template->setInfo('content', $template->getVar('L_INSERTCATORDERNUM'), TRUE);
						
			/* Build the queries */
			$update_a			= &$dba->prepareStatement("UPDATE ". INFO ." SET name=?,row_order=? WHERE id=?");
			$update_b			= &$dba->prepareStatement("UPDATE ". CATEGORIES ." SET description=? WHERE category_id=?");
			$update_c			= &$dba->prepareStatement("UPDATE ". MAPS ." SET name=? WHERE varname=?");

			/* Set the query values */
			$update_a->setString(1, $request['name']);
			$update_a->setInt(2, $request['row_order']);
			$update_a->setInt(3, $category['id']);
			
			/* Build the query for the categories table */
			$update_b->setString(1, $request['description']);
			$update_b->setInt(2, $category['id']);
			
			/* Simple update on the maps table */
			$update_c->setString(1, $request['name']);
			$update_c->setString(2, 'category'. $category['id']);

			/* Do all of the updates */
			$update_a->executeUpdate();
			$update_b->executeUpdate();
			$update_c->executeUpdate();
			
			$template->setInfo('content', sprintf($template->getVar('L_UPDATEDCATEGORY'), $category['name']), FALSE);
			$template->setRedirect('admin.php?act=categories', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminRemoveCategory extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);			

			$category					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". CATEGORIES ." c LEFT JOIN ". INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($request['id']));

			if(!is_array($category) || empty($category))
				return $template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);			
			
			$category_maps	= $dba->getRow("SELECT * FROM ". MAPS ." WHERE varname = 'category". $category['id'] ."'");

			$delete_a		= &$dba->prepareStatement("DELETE FROM ". CATEGORIES ." WHERE category_id=?");
			$delete_b		= &$dba->prepareStatement("DELETE FROM ". FORUMS ." WHERE category_id=?");
			$delete_c		= &$dba->prepareStatement("DELETE FROM ". TOPICS ." WHERE category_id=?");
			$delete_d		= &$dba->prepareStatement("DELETE FROM ". REPLIES ." WHERE category_id=?");
			
			$heirarchy		= &new Heirarchy();
			$heirarchy->removeNode($category, INFO);
			$heirarchy->removeNode($category_maps, MAPS);

			$delete_a->setInt(1, $category['id']);
			$delete_b->setInt(1, $category['id']);
			$delete_c->setInt(1, $category['id']);
			$delete_d->setInt(1, $category['id']);

			$delete_a->executeUpdate();
			$delete_b->executeUpdate();
			$delete_c->executeUpdate();
			$delete_d->executeUpdate();
			
			$template->setInfo('content', sprintf($template->getVar('L_REMOVEDCATEGORY'), $category['name']), FALSE);
			$template->setRedirect('admin.php?act=categories', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminCategoryPermissions extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);			

			$category					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". CATEGORIES ." c LEFT JOIN ". INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($request['id']));

			if(!is_array($category) || empty($category))
				return $template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);			
			
			foreach($category as $key => $val)
				$template->setVar('category_'. $key, $val);
			
			$category_maps				= &new MAPSIterator($dba->executeQuery("SELECT * FROM ". MAPS ." WHERE category_id = ". intval($category['id']) ." AND forum_id = 0 ORDER BY row_left ASC"), 2);
			
			$template->setList('category_maps', $category_maps);

			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/categories_permissions.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminUpdateCategoryPermissions extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);			

			$category					= $dba->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". CATEGORIES ." c LEFT JOIN ". INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($request['id']));

			if(!is_array($category) || empty($category))
				return $template->setInfo('content', $template->getVar('L_INVALIDCATEGORY'), FALSE);			
			
			foreach($category as $key => $val)
				$template->setVar('category_'. $key, $val);
			
			$category_map				= $dba->getRow("SELECT * FROM ". MAPS ." WHERE varname = 'category". $category['id'] ."' AND category_id = ". intval($category['id']));
			$category_maps				= $dba->executeQuery("SELECT * FROM ". MAPS ." WHERE category_id = ". intval($category['id']) ." AND row_left >= ". intval($category_map['row_left']) ." AND row_right <= ". intval($category_map['row_right']) ." ORDER BY row_left ASC");

			while($category_maps->next()) {
				$c						= $category_maps->current();

				if(isset($request[$c['varname'] .'_can_view']) && isset($request[$c['varname'] .'_can_add']) && isset($request[$c['varname'] .'_can_edit']) && isset($request[$c['varname'] .'_can_del'])) {
					
					if(($request[$c['varname'] .'_can_view'] != $c['can_view']) || ($request[$c['varname'] .'_can_add'] != $c['can_add']) || ($request[$c['varname'] .'_can_edit'] != $c['can_edit']) || ($request[$c['varname'] .'_can_del'] != $c['can_del'])) {

						$update				= &$dba->prepareStatement("UPDATE ". MAPS ." SET can_view=?,can_add=?,can_edit=?,can_del=? WHERE varname=?");
						$update->setInt(1, $request[$c['varname'] .'_can_view']);
						$update->setInt(2, $request[$c['varname'] .'_can_add']);
						$update->setInt(3, $request[$c['varname'] .'_can_edit']);
						$update->setInt(4, $request[$c['varname'] .'_can_del']);
						$update->setString(5, $c['varname']);

						$update->executeUpdate();

						unset($update);
					}
				}
			}
			
			$template->setInfo('content', sprintf($template->getVar('L_UPDATEDCATEGORYPERMS'), $category['name']), FALSE);
			$template->setRedirect('admin.php?act=categories', 3);
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminCategoriesIterator extends FAProxyIterator {
	var $dba;
	var $result;

	function AdminCategoriesIterator($query = NULL) {
		global $_CONFIG, $_DBA, $_QUERYPARAMS;
		
		$this->query_params	= $_QUERYPARAMS;
		
		$query_params		= $this->query_params['info'] . $this->query_params['category'];

		$query				= $query == NULL ? "SELECT $query_params FROM ". INFO ." i LEFT JOIN ". CATEGORIES ." c ON c.category_id = i.id AND i.row_type = ". CATEGORY ." ORDER BY i.row_order ASC" : $query;
		
		$this->result		= &$_DBA->executeQuery($query);

		parent::FAProxyIterator($this->result);
	}

	function &current() {
		$temp = parent::current();
		
		if(($temp['row_right'] - $temp['row_left'] - 1) > 0) {
			
			$query_params	= $this->query_params['info'] . $this->query_params['forum'];

			$temp['forums'] = &new ForumsIterator("SELECT $query_params FROM ". INFO ." i LEFT JOIN ". FORUMS ." f ON f.forum_id = i.id WHERE i.row_left > ". $temp['row_left'] ." AND i.row_right < ". $temp['row_right'] ." AND i.row_type = ". FORUM ." ORDER BY i.row_left, i.row_order ASC");
		}

		/* Should we free the result? */
		if($this->row == $this->size-1)
			$this->result->freeResult();

		return $temp;
	}
}

?>