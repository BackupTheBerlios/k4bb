<?php
/**
* k4 Bulletin Board, files.class.php
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
* @version $Id: files.class.php,v 1.1 2005/04/05 03:19:35 k4st Exp $
* @package k42
*/

class AdminFileBrowser extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {		
		
		if(is_a($session['user'], 'Member') && ($user['perms'] >= ADMIN)) {
			
			$template->setVar('current_location', $template->getVar('L_FILEBROWSER'));
			$template->setVar('opener_input', @$request['input']);
			$template->setVar('selected', @$request['selected']);

			$directory		= FORUM_BASE_DIR . DIRECTORY_SEPARATOR . @$request['dir'];

			if(!isset($request['dir']) || $request['dir'] == '' || !file_exists($directory) || !is_dir($directory))
				return $template->setInfo('content', sprintf($template->getVar('L_DIRECTORYDOESNTEXIST'), FORUM_BASE_DIR . DIRECTORY_SEPARATOR . $dir), FALSE);
			
			$filetypes	= array('html' => array('HTM', 'HTML', 'JS'),
								'php' => array('PHP'),
								'img' => array('GIF', 'PNG', 'TIFF', 'JPG', 'JPEG', 'BMP', 'ICO'));
			
			$filetype	= (!isset($request['filetype']) || $request['filetype'] == '') && !array_key_exists(@$request['filetype'], $filetypes) ? FALSE : $request['filetype'];

			$dir		= dir($directory);

			$files		= array();
			
			while(false !== ($file = $dir->read())) {
				
				if($file != '.' && $file != '..') {
					
					if(!is_dir($directory . DIRECTORY_SEPARATOR . $file)) {
						
						$temp = array();

						/* Get File extension */
						$exts					= explode(".", $file);
						$temp['fileext']		= count($exts) < 2 ? '' : strtoupper($exts[count($exts)-1]);
						
						$temp['shortname']		= $file;
						$temp['filename']		= $request['dir'] . '/' . $file;
						$temp['file']			= $exts[0];

						if(in_array($temp['fileext'], $filetypes['html']))
							$temp['filetype']	= 'html';
						else if(in_array($temp['fileext'], $filetypes['php']))
							$temp['filetype']	= 'php';
						else if(in_array($temp['fileext'], $filetypes['img']))
							$temp['filetype']	= 'img';
						else
							$temp['filetype']	= '';

						if(!$filetype)
							$files[]			= $temp;
						else if($temp['filetype'] == $filetype)
							$files[]			= $temp;
					}
				}
			}

			$files		= &new FAArrayIterator($files);

			$template->setList('files_list', $files);
			
			$template->setFile('content', 'admin/file_browser.html');
		
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

?>