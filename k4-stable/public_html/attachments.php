<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     attachments.php
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


/* This is a nice attachments manager */
class DefaultEvent extends Event {

	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the anceators bar, or moreover, the title :P */
		$template = CreateAncestors($template, $template['L_ATTACHMENTSM']);

		/* Get the variables */
		$file		= $request['file'];
		$temp		= explode("/", $file);
		$temp_dir	= $temp[0];
		$exts		= explode(".", $request['file']);
		$extension	= $exts[count($exts)-1];
		global $lang;
		
		/* Open up the uploads directory and find the directory which matches the posts id */
		if($dir = dir('Uploads/'. $temp_dir)) {
			$array = array();
			while (false !== ($file = $dir->read())) {
				if($file != '.' && $file != '..') {
					$vars = explode('.', $file);
					$ext = $vars[count($vars)-1];
					$array[] = array('name' => $file, 'post_id' => $temp_dir, 'img' => $ext);
				}
			}
			$fullpath = 'Uploads/'.$request['file'];
			$attachment = "";
			
			/* Give a nice way to display each file type */
			switch($extension) {
				/* These will all just fall down onto PNG, which is the only way we will display our images */
				case 'gif': { }
				case 'bmp': { }
				case 'jpg': { }
				case 'jpe': { }
				case 'tiff': { }
				case 'jpeg': { }
				case 'png': { $attachment = '<img src="'. $fullpath .'" alt="" border="1" />'; break; }
				case 'php': { $attachment = highlight_string(file_get_contents($fullpath), TRUE); break; }
				case 'txt': { $attachment = file_get_contents($fullpath); break; }
				case 'phps': {  $attachment = highlight_string(file_get_contents($fullpath), TRUE); break; }
				case 'pdf': { $attachment = '<a href="'.$fullpath.'" target="_blank">'.$lang['L_CLICKHERE'].'</a>'; break; }
				case 'doc': { $attachment = '<a href="'.$fullpath.'" target="_blank">'.$lang['L_CLICKHERE'].'</a>'; break; }
				case 'psd': { $attachment = '<a href="'.$fullpath.'" target="_blank">'.$lang['L_CLICKHERE'].'</a>'; break; }
				case 'rtf': { $attachment = '<a href="'.$fullpath.'" target="_blank">'.$lang['L_CLICKHERE'].'</a>'; break; }
				case 'zip': { $attachment = '<a href="'.$fullpath.'" target="_blank">'.$lang['L_CLICKHERE'].'</a>'; break; }
			}

			$template['attachment'] = $attachment;
			$template->attachments = $array;
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

$app = new Forum_Controller('attachments.html');

$app->ExecutePage();

?>