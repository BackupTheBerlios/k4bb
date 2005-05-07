<?php
/**
* k4 Bulletin Board, profilefields.class.php
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
* @version $Id: profilefields.class.php,v 1.1 2005/05/07 15:31:21 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class AdminUserProfileFields extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			$fields			= &$dba->executeQuery("SELECT * FROM ". PROFILEFIELDS ." ORDER BY display_order ASC");

			$template->setList('fields', $fields);
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/profilefields_manage.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminAddUserField extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/profilefields_add1.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminAddUserFieldTwo extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			$types = array('text', 'textarea', 'select', 'multiselect', 'radio', 'checkbox');
			
			if(!isset($request['inputtype']) || $request['inputtype'] == '' || !in_array($request['inputtype'], $types)) {
				$template->setInfo('content', $template->getVar('L_NEEDFIELDINPUTTYPE'), TRUE);
				return TRUE;
			}

			$template->show($request['inputtype']);
			$template->setVar('inputtype', $request['inputtype']);

			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/profilefields_add2.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminInsertUserField extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			$types			= array('text', 'textarea', 'select', 'multiselect', 'radio', 'checkbox');
			
			if(!isset($request['inputtype']) || $request['inputtype'] == '' || !in_array($request['inputtype'], $types)) {
				$template->setInfo('content', $template->getVar('L_NEEDFIELDINPUTTYPE'), TRUE);
				return TRUE;
			}

			$last_field		= $dba->getValue("SELECT name FROM ". PROFILEFIELDS ." ORDER BY name DESC LIMIT 1");
			
			if(!$last_field || $last_field == '') {
				$name		= 'field1';
			} else {
				$name		= 'field'. (intval(substr($last_field, -1)) + 1);
			}
						
			$insert			= &$dba->prepareStatement("INSERT INTO ". PROFILEFIELDS ." (name,title,description,default_value,inputtype,user_maxlength,inputoptions,min_perm,display_register,display_profile,display_topic,display_post,display_memberlist,display_image,display_size,display_rows,display_order,is_editable,is_private,is_required,special_pcre) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

			$insert->setString(1, $name);
			$insert->setString(2, @$request['title']);
			$insert->setString(3, @$request['description']);
			$insert->setString(4, @$request['default_value']);
			$insert->setString(5, @$request['inputtype']);
			$insert->setInt(6, iif(intval(@$request['user_maxlength']) > 0, intval(@$request['user_maxlength']), 255));
			$insert->setString(7, iif(isset($request['inputoptions']) && @$request['inputoptions'] != '', serialize(explode('\r\n', preg_replace("~(\r|\n|\r\n)~is", "\r\n", @$request['inputoptions']))), ''));
			$insert->setInt(8, @$request['min_perm']);
			$insert->setInt(9, iif(isset($request['display_register']) && @$request['display_register'] == 'yes', 1, 0));
			$insert->setInt(10, iif(isset($request['display_profile']) && @$request['display_profile'] == 'yes', 1, 0));
			$insert->setInt(11, iif(isset($request['display_topic']) && @$request['display_topic'] == 'yes', 1, 0));
			$insert->setInt(12, iif(isset($request['display_post']) && @$request['display_post'] == 'yes', 1, 0));
			$insert->setInt(14, iif(isset($request['display_memberlist']) && @$request['display_memberlist'] == 'yes', 1, 0));
			$insert->setString(13, @$request['display_image']);
			$insert->setInt(15, @$request['display_size']);
			$insert->setInt(16, @$request['display_rows']);
			$insert->setInt(17, @$request['display_order']);
			$insert->setInt(18, @$request['is_editable']);
			$insert->setInt(19, @$request['is_private']);
			$insert->setInt(20, @$request['is_required']);
			$insert->setString(21, @$request['special_pcre']);
			
			/* Execute a query with no buffering and no result handling */
			if(!$dba->query("SELECT ". $name ." FROM ". USERINFO ." LIMIT 1")) {
				$update_type	= "ADD";
			} else {
				$update_type	= "CHANGE ". $name;
			}
			
			if($request['inputtype'] != 'textarea') {
				$params			= "VARCHAR(". iif(intval(@$request['user_maxlength']) > 0, intval(@$request['user_maxlength']), 255) .") NOT NULL DEFAULT '". htmlentities(@$request['default_value'], ENT_QUOTES) ."'";
			} else if($request['inputtype'] == 'textarea') {
				$params			= "TEXT";
			}
			
			/* If there is a problem altering the userinfo table, don't continue past this point. */
			error::reset();
			$dba->alterTable(USERINFO, "$update_type $name $params");
			$error				= error::grab();
			if($error) {
				$template->setInfo('content', sprintf($template->getVar('L_ERRORADDPROFILEFIELD'), $request['title'], $error->message .' Line: '. $error->line .', File: '. basename($error->filename)), FALSE);
				return TRUE;
			}

			$insert->executeUpdate();
			
			/* Remove our cache file so it may be recreated */
			unlink(CACHE_FILE);

			$template->setInfo('content', sprintf($template->getVar('L_ADDEDPROFILEFIELD'), $request['title']), FALSE);
			$template->setRedirect('admin.php?act=userfields', 3);
			
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminRemoveUserField extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			if(!isset($request['field']) || $request['field'] == '') {
				$template->setInfo('content', $template->getVar('L_INVALIDUSERFIELD'), TRUE);
				return TRUE;
			}
			
			$field		= $dba->getRow("SELECT * FROM ". PROFILEFIELDS ." WHERE name = '". $dba->quote($request['field']) ."'");
			
			if(!$field || !is_array($field) || empty($field)) {
				$template->setInfo('content', $template->getVar('L_INVALIDUSERFIELD'), TRUE);
				return TRUE;
			}

			if(!$dba->query("SELECT ". $field['name'] ." FROM ". USERINFO ." LIMIT 1")) {

				/* Delete the profile field version of this because obviously it shouldn't exist */
				$dba->executeUpdate("DELETE FROM ". PROFILEFIELDS ." WHERE name = '". $dba->quote($field['name']) ."'");
				
				$template->setInfo('content', $template->getVar('L_INVALIDUSERFIELD'), TRUE);
				return TRUE;
			}
			
			/* Remove the field */
			error::reset();
			$dba->alterTable(USERINFO, "DROP ". $dba->quote($field['name']));
			$error		= error::grab();
			if($error) {
				$template->setInfo('content', sprintf($template->getVar('L_ERRORDELPROFILEFIELD'), $request['title'], $error->message .' Line: '. $error->line .', File: '. basename($error->filename)), FALSE);
				return TRUE;
			}			
			
			/* Remove the last of the profile field info if we've made it this far */
			$dba->executeUpdate("DELETE FROM ". PROFILEFIELDS ." WHERE name = '". $dba->quote($field['name']) ."'");
			
			/* Remove the cache file so it may be remade */
			unlink(CACHE_FILE);	

			$template->setInfo('content', sprintf($template->getVar('L_REMOVEDPROFILEFIELD'), $field['title']), FALSE);
			$template->setRedirect('admin.php?act=userfields', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminEditUserField extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			$types = array('text', 'textarea', 'select', 'multiselect', 'radio', 'checkbox');
			
			if(!isset($request['inputtype']) || $request['inputtype'] == '' || !in_array($request['inputtype'], $types)) {
				$template->setInfo('content', $template->getVar('L_NEEDFIELDINPUTTYPE'), TRUE);
				return TRUE;
			}

			$template->show($request['inputtype']);
			$template->setVar('inputtype', $request['inputtype']);

			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/profilefields_add2.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

?>