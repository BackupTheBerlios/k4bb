<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     dir.class.php
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

class Dir {
	protected $folder;
	protected $dir;
	
	public function __construct($dir = FALSE) {
		if(is_dir($dir)) {
			$this->dir		= $dir;
			$this->folder	= dir($dir);
		}
	}
	static public function Open($dir) {
		return new Dir($dir);
	}
	public function getFiles() {
		$files = array();
		while(FALSE !== ($file = $this->folder->read())) {
			if(!is_dir($this->dir .'/'. $file))
				$files[] = array('name' => $file);
		}
		return $files;
	}
	public function getFolders() {
		$files = array();
		
		while(FALSE !== ($file = $this->folder->read())) {
			if(is_dir($this->dir .'/'. $file) && $file != '.' && $file != '..')
				$files[] = array('name' => ucfirst($file));
		}
		return $files;
	}
	public function getAll() {
		$files = array();
		while(FALSE !== ($file = $this->folder->read())) {
			if($file != '.' && $file != '..')
				$files[] = array('name' => $file);
		}
		return $files;
	}
}

?>