<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     files.php
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

require 'forum.inc.php';

class DefaultEvent extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template = CreateAncestors($template, $template['L_UPLOAD']);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class MemberAvatarSelectFile extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if($session['user'] instanceof Member) {
			/* Create the ancestors bar, this just sets the title basically */
			$template = CreateAncestors($template, $template['L_UPLOAD']);

			/* Tell is what template to use */
			$template->content = array('file' => 'usercp/select_file.html');
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class AdminRankSelectFile extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content = array('file' => 'admin/files/upload_image.html');
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class AdminCSSSelectFile extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content = array('file' => 'admin/files/select_file.html');
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class MemberUploadAvatar extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if($session['user'] instanceof Member) {
			if(isset($_FILES['imgfileselect'])) {
				$file = $_FILES['imgfileselect'];
				if($file['type'] == 'image/gif') {
					if($file['size'] <= 10000) {
						if (move_uploaded_file($file['tmp_name'], "Uploads/Avatars/". $session['user']['id'] .".gif")) {
							list($width, $height) = getimagesize("Uploads/Avatars/". $session['user']['id'] .".gif");
							if($width > 75 || $height > 75) {
								unlink("Uploads/Avatars/". $session['user']['id'] .".gif");
								return new Error($template['L_IMAGETOOLARGE'], $template);
							}
							DBA::Open()->Query("UPDATE ". USERS ." SET avatar = 1 WHERE id = ". $session['user']['id'] );
							$js = $template['L_UPLOADAVATARSUCCESS'];
							$js .= '<script type="text/javascript">';
							$js .= 'opener.document.getElementById(\'avatar\').selectedIndex = 1;';
							$js .= 'opener.document.getElementById(\'user_avatar\').innerHTML = \'<img src="Uploads/Avatars/'. $session['user']['id'] .'.gif" border="0" alt="" />\';';
							$js .= 'window.onload=window.close();</script>';
							return new Error($js, $template);
						} else {
							return new Error($template['L_ERRORUPLOADING'], $template);
						}
					} else {
						return new Error($template['L_AVATARFILETOOBIG'], $template);
					}
				} else {
					return new Error($template['L_FILESNEEDSGIF'], $template);
				}
			} else {
				return new Error($template['L_NEEDSELECTFILE'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class AdminUploadImg extends Event {
	protected $input;
	protected $element;
	public function __construct($input) {
		global $lang;
		$this->input = $input == 1 ? 'bgimgfileselect' : 'imgfileselect';
		$this->element = $this->input == 'bgimgfileselect' ? 'background-image' : $lang['L_RANK'];
	}
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$filetypes = array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'tiff');

			if(isset($_FILES[$this->input]['name'])) {
				$ext = explode(".", $_FILES[$this->input]['name']);
				$ext = strtolower($ext[count($ext)-1]);
				if(in_array($ext, $filetypes)) {
					
					$start	= $this->input == 'imgfileselect' ? '[img]' : NULL;
					$end	= $this->input == 'imgfileselect' ? '[/img]' : NULL;

					/* upload the file */
					if (move_uploaded_file($_FILES[$this->input]['tmp_name'], "Images/". get_setting('template', 'imgfolder') ."/Uploads/". $_FILES[$this->input]['name'])) {
						$js = $template['L_UPLOADIMGSUCCESS'];
						$js .= '<script type="text/javascript">';
						$js .= 'opener.document.getElementById(\''. $this->element .'\').value = \''. $start .'Images/'. get_setting('template', 'imgfolder') .'/Uploads/'. $_FILES[$this->input]['name'] .''. $end .'\';';
						$js .= 'window.onload=window.close();</script>';
						return new Error($js, $template);
					} else {
						return new Error($template['L_ERRORUPLOADING'], $template);
					}
				} else {
					return new Error(sprintf($template['L_INVALIDFILETYPE'], 'GIF, JPG, JPEG, BMP, PNG, TIFF'), $template);
				}
			} else {
				return new Error($template['L_ERRORUPLOADING'], $template);
			}
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class AdminCSSExport extends Event {
	public function remove_lines($str) {
		return str_replace("\n", " ", $str);
	}
	public function proper_quote($str) {
		if(get_setting(get_setting('application', 'dba_name'), 'type') == 'sqlite')
			return str_replace("\''", "''", $str);
		else
			return htmlspecialchars($str, ENT_QUOTES);
	}
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$css = @$_POST['styleset_id'];
			$dba = DBA::Open();
			$styleset = $dba->GetRow("SELECT * FROM ". STYLES ." WHERE id = '". $css ."'");
			$styles = $dba->Query("SELECT * FROM ". CSS ." WHERE style_id = '". $css ."' ORDER BY name ASC");
			$str = "<?php\n\n";
			$str .= "if(isset(\$dba) && isset(\$css)) {\n";
			$str .= "\t\$dba->Query(\"INSERT INTO \". STYLES .\" (name, description) VALUES ('". $styleset['name'] ."', '". $styleset['description'] ."')\");\n\n";
			$str .= "\t\$styleset = \$dba->GetValue(\"SELECT MAX(id) FROM \". STYLES );\n\n";
			foreach($styles as $s) {
				$str .= "\t\$css->AddClass(\"". $dba->Quote($s['name']) ."\", \"". $this->proper_quote($dba->Quote($this->remove_lines($s['properties']))) ."\", \$styleset, \"". $this->proper_quote($dba->Quote($s['description'])) ."\");\n"; //echo '". $s['name'] ."';
			}
			$str .= "\n} else {\n";
			$str .= "\techo 'The \$dba and \$css variables have not been set.';";
			$str .= "}";
			$str .= "\n?>";

			$output_file = 'k4.'. $styleset['name'] .'.php';

			@ob_end_clean();
			@ini_set('zlib.output_compression', 'Off');
			header('Pragma: public');

			header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
			header('Cache-Control: pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
			header('Content-Transfer-Encoding: none');
			header('Content-Type: application/octetstream; name="' . $output_file . '"'); //This should work for IE & Opera
			header('Content-Type: application/octet-stream; name="' . $output_file . '"'); //This should work for the rest
			header('Content-Disposition: inline; filename="' . $output_file . '"');

			echo $str;
			exit;
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}



$app	= new Forum_Controller('admin/files.html');

/* Upload background images for the CSS editor */
$app->AddEvent('upload_file', new AdminCSSSelectFile);
$app->AddEvent('upload_bgimage', new AdminUploadImg(1));

/* Upload user rank images */
$app->AddEvent('rank_upload', new AdminRankSelectFile);
$app->AddEvent('upload_img', new AdminUploadImg(2));

/* Export a css file */
$app->AddEvent('export_css', new AdminCSSExport);

/* Deal with avatar uploads */
$app->AddEvent('avatar_upload', new MemberAvatarSelectFile);
$app->AddEvent('upload_avatar', new MemberUploadAvatar);

$app->ExecutePage();

?>