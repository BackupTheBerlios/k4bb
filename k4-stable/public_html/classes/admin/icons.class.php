<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     icons.class.php
 *     Copyright (c) 2004, Peter Goodman

 *     Permission is hereby granted, free of charge, to any person obtaining 
 *     a copy of this software and associated documentation files (the 
 *     "Software"), to deal in the Software without restriction, including 
 *     without limitation the rights to use, copy, modify, merge, publish, 
 *     distribute, sublicense, and/or sell copies of the Software, and to 
 *     permit persons to whom the Software is furnished to do so, subject to 
 *     the following conditions:

 *     The above copyright notice and this permission notice shall be 
 *     included in all copies or substantial portions of the Software.

 *     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, 
 *     EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 
 *     MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 *     NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS 
 *     BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN 
 *     ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
 *     CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE 
 *     SOFTWARE.
 *********************************************************************************/

error_reporting(E_STRICT | E_ALL);

class AdminUpdateIcon extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			$dba			= DBA::Open();

			$table			= ($request['act'] == 'updatepi') ? POSTICONS : EMOTICONS;
			$img_location	= ($table == POSTICONS) ? 'PostIcons' : 'Emoticons';
			
			if(is_int(@$request['id']) && @$request['id'] != 0 && isset($request['id']) && $request['id'] != '') { 
				$id				= intval($request['id']);
				$description	= $dba->Quote($request['description']);
				
				if($_FILES['upload']['error'] == 0) {
					move_uploaded_file($_FILES['upload']['tmp_name'], $rel_dir.'Images/'. get_setting('template', 'imgfolder') .'/Icons/'. $img_location .'/'. $_FILES['upload']['name']);
					$image		= $_FILES['upload']['name'];
				} else {
					$image		= $dba->Quote($request['image']);
				}
				if($dba->Query("UPDATE ". $table ." SET image = '{$image}', description = '{$description}' WHERE id = $id"))
					header("Location: admin.php?act=icons");
			} else {
				return new Error($template['L_INVALIDICONID'], $template);
			}
		}
	}
}

class AdminAddIcon extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			$dba			= DBA::Open();

			$table			= ($request['act'] == 'addpi') ? POSTICONS : EMOTICONS;
			$img_location	= ($table == POSTICONS) ? 'PostIcons' : 'Emoticons';
			
			$description	= $dba->Quote($request['description']);

			if($_FILES['upload']['error'] == 0) {
				$image		= $_FILES['upload']['name'];
				move_uploaded_file($_FILES['upload']['tmp_name'], $rel_dir.'Images/'. get_setting('template', 'imgfolder') .'/Icons/'. $img_location .'/'. $_FILES['upload']['name']);
			} else {
				$image		= $dba->Quote($request['current_images']);
			}
			if($table == POSTICONS) {
				if($dba->Query("INSERT INTO ". $table ." (image, description) VALUES ('{$image}', '{$description}')"))
					header("Location: admin.php?act=icons");
			} else {
				$typed = htmlspecialchars($request['typed']);
				if($dba->Query("INSERT INTO ". $table ." (image, description, typed) VALUES ('{$image}', '{$description}', '{$typed}')"))
					header("Location: admin.php?act=icons");
			}
		}
	}
}

class AdminDeleteIcon extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$table = ($request['act'] == 'deletepi') ? POSTICONS : EMOTICONS;
			if(is_int(@$request['id']) && @$request['id'] != 0 && isset($request['id']) && $request['id'] != '') { 
				$id = intval($request['id']);
				if(DBA::Open()->Query("DELETE FROM ". $table ." WHERE id = $id"))
					header("Location: admin.php?act=icons");
			} else {
				return new Error($template['L_INVALIDICONID'], $template);
			}
		}
	}
}

?>