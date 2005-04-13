<?php
/**
* k4 Bulletin Board, emoticons.class.php
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
* @version $Id: emoticons.class.php,v 1.2 2005/04/13 02:52:47 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class AdminEmoticons extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			$icons			= &$dba->executeQuery("SELECT * FROM ". EMOTICONS);

			$template->setList('emoticons', $icons);
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/emoticons_manage.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminAddEmoticon extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
						
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/emoticons_add.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminInsertEmoticon extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			/**		
			 * Error checking on all fields :P
			 */
			if(!isset($request['description']) || $request['description'] == '')
				return $template->setInfo('content', $template->getVar('L_INSERTICONDESC'), TRUE);

			if(!isset($request['typed']) || $request['typed'] == '')
				return $template->setInfo('content', $template->getVar('L_INSERTICONTYPED'), TRUE);
			
			if(!isset($request['image_browse']) && !isset($_FILES['image_upload']))
				return $template->setInfo('content', $template->getVar('L_NEEDCHOOSEICONIMG'), TRUE);
			
			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload']))
				$filename	= $_FILES['image_upload']['tmp_name'];
			
			if(isset($request['image_browse']) && $request['image_browse'] != '')
				$filename	= $request['image_browse'];
			else
				return $template->setInfo('content', $template->getVar('L_NEEDCHOOSEICONIMG'), TRUE);
			

			$file_ext		= explode(".", $filename);
			$exts			= array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'tiff');
			
			if(count($file_ext) >= 2) {
				$file_ext		= $file_ext[count($file_ext) - 1];

				if(!in_array(strtolower($file_ext), $exts))
					return $template->setInfo('content', $template->getVar('L_INVALIDICONEXT'), TRUE);
			} else {
				return $template->setInfo('content', $template->getVar('L_INVALIDICONEXT'), TRUE);
			}
			
			/**
			 * Add the icon finally
			 */
			$query		= &$dba->prepareStatement("INSERT INTO ". EMOTICONS ." (description, typed, image, clickable) VALUES (?,?,?,?)");
			$query->setString(1, $request['description']);
			$query->setString(2, $request['typed']);
			$query->setString(3, $filename);
			$query->setInt(4, @$request['clickable']);

			$query->executeUpdate();

			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload'])) {
				$dir		= FORUM_BASE_DIR . '/tmp/upload/emoticons';
				
				@chmod($dir, 0777);
				@move_uploaded_file($_FILES['image_upload']['tmp_name'], $dir .'/'. $filename);
			}

			$template->setInfo('content', $template->getVar('L_ADDEDEMOTICON'), TRUE);
			$template->setRedirect('admin.php?act=emoticons', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminRemoveEmoticon extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setInfo('content', $template->getVar('L_EMOTCIONDOESNTEXIST'), FALSE);	

			$icon			= $dba->getRow("SELECT * FROM ". EMOTICONS ." WHERE id = ". intval($request['id']));
			
			if(!is_array($icon) || empty($icon))
				return $template->setInfo('content', $template->getVar('L_EMOTICONDOESNTEXIST'), FALSE);
			
			/* Remove the icon from the db */
			$dba->executeUpdate("DELETE FROM ". EMOTICONS ." WHERE id = ". intval($icon['id']));
			
			/* Remove the actual icon */
			$dir		= FORUM_BASE_DIR . '/tmp/upload/emoticons';

			@chmod($dir);
			@unlink($dir .'/'. $icon['image']);
			
			$template->setInfo('content', $template->getVar('L_REMOVEDPOSTICON'), TRUE);
			$template->setRedirect('admin.php?act=posticons', 3);
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminEditEmoticon extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setInfo('content', $template->getVar('L_POSTICONSDOESNTEXIST'), FALSE);	

			$icon			= $dba->getRow("SELECT * FROM ". EMOTICONS ." WHERE id = ". intval($request['id']));
			
			if(!is_array($icon) || empty($icon))
				return $template->setInfo('content', $template->getVar('L_EMOTCIONDOESNTEXIST'), FALSE);

			foreach($icon as $key => $val) {
				$template->setVar('icon_'. $key, $val);
			}
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/emoticons_edit.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminUpdateEmoticon extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			/**		
			 * Error checking on all fields :P
			 */

			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setInfo('content', $template->getVar('L_EMOTCIONDOESNTEXIST'), FALSE);

			$icon			= $dba->getRow("SELECT * FROM ". EMOTICONS ." WHERE id = ". intval($request['id']));
			
			if(!is_array($icon) || empty($icon))
				return $template->setInfo('content', $template->getVar('L_EMOTCIONDOESNTEXIST'), FALSE);

			if(!isset($request['description']) || $request['description'] == '')
				return $template->setInfo('content', $template->getVar('L_INSERTICONDESC'), TRUE);

			if(!isset($request['typed']) || $request['typed'] == '')
				return $template->setInfo('content', $template->getVar('L_INSERTICONTYPED'), TRUE);
			
			if(!isset($request['image_browse']) && !isset($_FILES['image_upload']))
				return $template->setInfo('content', $template->getVar('L_NEEDCHOOSEICONIMG'), TRUE);
			
			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload']))
				$filename	= $_FILES['image_upload']['tmp_name'];
			
			if(isset($request['image_browse']) && $request['image_browse'] != '')
				$filename	= $request['image_browse'];
			else
				return $template->setInfo('content', $template->getVar('L_NEEDCHOOSEICONIMG'), TRUE);
			

			$file_ext		= explode(".", $filename);
			$exts			= array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'tiff');
			
			if(count($file_ext) >= 2) {
				$file_ext		= $file_ext[count($file_ext) - 1];

				if(!in_array(strtolower($file_ext), $exts))
					return $template->setInfo('content', $template->getVar('L_INVALIDICONEXT'), TRUE);
			} else {
				return $template->setInfo('content', $template->getVar('L_INVALIDICONEXT'), TRUE);
			}
			
			/**
			 * Add the icon finally
			 */
			$query		= &$dba->prepareStatement("UPDATE ". EMOTICONS ." SET description=?,typed=?,image=?,clickable=? WHERE id=?");
			$query->setString(1, $request['description']);
			$query->setString(2, $request['typed']);
			$query->setString(3, $filename);
			$query->setInt(4, @$request['clickable']);
			$query->setInt(5, $icon['id']);

			$query->executeUpdate();

			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload'])) {
				$dir		= FORUM_BASE_DIR . '/tmp/upload/emoticons';
				
				@chmod($dir, 0777);
				@move_uploaded_file($_FILES['image_upload']['tmp_name'], $dir .'/'. $filename);
			}

			$template->setInfo('content', $template->getVar('L_UPDATEDEMOTICON'), TRUE);
			$template->setRedirect('admin.php?act=emoticons', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminUpdateEmoticonClick extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setInfo('content', $template->getVar('L_POSTICONSDOESNTEXIST'), FALSE);	

			$icon			= $dba->getRow("SELECT * FROM ". EMOTICONS ." WHERE id = ". intval($request['id']));
			
			if(!is_array($icon) || empty($icon))
				return $template->setInfo('content', $template->getVar('L_EMOTCIONDOESNTEXIST'), FALSE);

			$clickable		= $icon['clickable'] == 1 ? 0 : 1;

			$dba->executeUpdate("UPDATE ". EMOTICONS ." SET clickable = ". intval($clickable) ." WHERE id = ". intval($icon['id']));
			
			$template->setInfo('content', $template->getVar('L_UPDATEDEMOCLICK'), TRUE);
			$template->setRedirect('admin.php?act=emoticons', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

?>