<?php
/**
* k4 Bulletin Board, posticons.php
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
* @version $Id: posticons.class.php,v 1.1 2005/04/05 03:19:35 k4st Exp $
* @package k42
*/

class AdminPostIcons extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			$icons			= &$dba->executeQuery("SELECT * FROM ". POSTICONS);

			$template->setList('posticons', $icons);
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/posticons_manage.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminAddPostIcon extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
						
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/posticons_add.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminInsertPostIcon extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			/**		
			 * Error checking on all _three_ fields :P
			 */
			if(!isset($request['description']) || $request['description'] == '')
				return $template->setInfo('content', $template->getVar('L_INSERTICONDESC'), TRUE);
			
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
			$query		= &$dba->prepareStatement("INSERT INTO ". POSTICONS ." (description, image) VALUES (?,?)");
			$query->setString(1, $request['description']);
			$query->setString(2, $filename);

			$query->executeUpdate();

			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload'])) {
				$dir		= FORUM_BASE_DIR . '/tmp/upload/posticons';
				
				@chmod($dir, 0777);
				@move_uploaded_file($_FILES['image_upload']['tmp_name'], $dir .'/'. $filename);
			}

			$template->setInfo('content', $template->getVar('L_ADDEDPOSTICON'), TRUE);
			$template->setRedirect('admin.php?act=posticons', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminRemovePostIcon extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setInfo('content', $template->getVar('L_POSTICONDOESNTEXIST'), FALSE);	

			$icon			= $dba->getRow("SELECT * FROM ". POSTICONS ." WHERE id = ". intval($request['id']));
			
			if(!is_array($icon) || empty($icon))
				return $template->setInfo('content', $template->getVar('L_POSTICONDOESNTEXIST'), FALSE);
			
			/* Remove the icon from the db */
			$dba->executeUpdate("DELETE FROM ". POSTICONS ." WHERE id = ". intval($icon['id']));
			
			/* Remove the actual icon */
			$dir		= FORUM_BASE_DIR . '/tmp/upload/posticons';

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

class AdminEditPostIcon extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setInfo('content', $template->getVar('L_POSTICONDOESNTEXIST'), FALSE);	

			$icon			= $dba->getRow("SELECT * FROM ". POSTICONS ." WHERE id = ". intval($request['id']));
			
			if(!is_array($icon) || empty($icon))
				return $template->setInfo('content', $template->getVar('L_POSTICONDOESNTEXIST'), FALSE);

			foreach($icon as $key => $val) {
				$template->setVar('icon_'. $key, $val);
			}
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/posticons_edit.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class AdminUpdatePostIcon extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			/**		
			 * Error checking on all _three_ fields :P
			 */

			if(!isset($request['id']) || intval($request['id']) == 0)
				return $template->setInfo('content', $template->getVar('L_POSTICONDOESNTEXIST'), FALSE);	

			$icon			= $dba->getRow("SELECT * FROM ". POSTICONS ." WHERE id = ". intval($request['id']));
			
			if(!is_array($icon) || empty($icon))
				return $template->setInfo('content', $template->getVar('L_POSTICONDOESNTEXIST'), FALSE);

			if(!isset($request['description']) || $request['description'] == '')
				return $template->setInfo('content', $template->getVar('L_INSERTICONDESC'), TRUE);
			
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
			$query		= &$dba->prepareStatement("UPDATE ". POSTICONS ." SET description=?,image=? WHERE id=?");
			$query->setString(1, $request['description']);
			$query->setString(2, $filename);
			$query->setInt(3, $icon['id']);

			$query->executeUpdate();

			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload'])) {
				$dir		= FORUM_BASE_DIR . '/tmp/upload/posticons';
				
				@chmod($dir, 0777);
				@move_uploaded_file($_FILES['image_upload']['tmp_name'], $dir .'/'. $filename);
			}

			$template->setInfo('content', $template->getVar('L_UPDATEDPOSTICON'), TRUE);
			$template->setRedirect('admin.php?act=posticons', 3);

		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

?>