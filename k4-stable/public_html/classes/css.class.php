<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     css.class.php
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

class CSS {
	protected $dba;
	public function __construct() {
		$this->dba = DBA::Open();
	}
	public function AddClass($name, $properties, $style_id, $description) {
		$style_id = intval($style_id);
		$name = $this->dba->Quote(($name));
		$properties = $this->dba->Quote(str_replace("\r\n", "", $properties));
		$description = $this->dba->Quote(htmlspecialchars($description));
		return $this->dba->Query("INSERT INTO ". CSS ." (name, properties, style_id, description) VALUES ('{$name}', '{$properties}', $style_id, '{$description}')");
	}
	public function UpdateClass($id, $name, $properties) {
		$id = intval($id);
		$name = $this->dba->Quote($name);
		$properties = $this->dba->Quote(str_replace("\r\n", "", $properties));
		$prev = $this->dba->GetRow("SELECT * FROM ". CSS ." WHERE id = $id");
		return $this->dba->Query("UPDATE ". CSS ." SET name = '{$name}', properties = '{$properties}', prev_name = '". $prev['name'] ."', prev_properties = '". $prev['properties'] ."' WHERE id = $id");
	}
	public function Revert($id) {
		return $this->dba->Query("UPDATE ". CSS ." SET name = prev_name, properties = prev_properties, prev_name = '', prev_properties = '' WHERE id = $id");
	}
}

class AdminAddCSSClass extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$css = new CSS;
			if($css->AddClass($request['name'], $request['properties'], $request['style_id'], $request['description']))
				header("Location: admin.php?act=css");
		}
	}
}

class AdminUpdateCSS extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$css = new CSS;
			if($css->UpdateClass($request['id'], $request['name'], $request['properties']))
				header("Location: admin.php?act=css");
		}
		return TRUE;
	}
}

class AdminRevertCSS extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$css = new CSS;
			if($css->Revert(intval($request['id'])))
				header("Location: admin.php?act=css");
		}
		return TRUE;
	}
}
class AdminImportCSS extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$css = new CSS;
			$dba = DBA::Open();
			if(isset($_FILES["imported_file"]["tmp_name"])) {
				$filename = 'Uploads/'. $_FILES["imported_file"]["name"];
				if(move_uploaded_file($_FILES["imported_file"]["tmp_name"], $filename)) {
					//if(is_uploaded_file($filename)) {
						include $filename;
						header("Location: admin.php?act=css");
					//}
				}
			}
		}
		return TRUE;
	}
}

?>