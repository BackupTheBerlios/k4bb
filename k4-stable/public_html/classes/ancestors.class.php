<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     ancestors.class.php
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

class ForumAncestors implements Iterator {
	protected $forums;
	protected $depth;

	public function __construct($id, $query = FALSE, $suspend) {
		$query = !$query ? "SELECT * FROM ". FORUMS ." WHERE row_left < (SELECT row_left FROM ". FORUMS ." WHERE id = $id) AND row_right > (SELECT row_right FROM ". FORUMS ." WHERE id = $id) AND suspend <= $suspend ORDER BY row_left ASC" : $query;
		$this->forums = DBA::Open()->Query($query)->GetIterator();
	}
	
	public function Current() {
		$temp				= $this->forums->Current();

		$temp['name']		= stripslashes($temp['name']);
		if($temp['row_left'] == 1) {
			global $settings;
			$temp['name'] = $settings['bbtitle'];
		}

		return $temp;
	}
	
	public function Key() {
		return $this->forums->Key();
	}
	
	public function Next() {
		return $this->forums->Next();
	}
	
	public function Rewind() {
		return $this->forums->Rewind();
	}
	
	public function Valid() {
		return $this->forums->Valid();
	}
}

?>