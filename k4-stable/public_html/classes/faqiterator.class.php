<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     faqiterator.class.php
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

class FAQCatIterator implements Iterator {
	protected $categories;
	protected $temp;
	protected $revert;
	public function __construct($revert) {
		$this->revert = $revert;
		$this->categories = DBA::Open()->Query("SELECT * FROM ". FAQ_CATEGORIES )->GetIterator();
	}
	
	public function Current() {
		$this->temp = $this->categories->Current();
		return $this->categories->Current();
	}
	
	public function Key() {
		return $this->categories->Key();
	}
	
	public function Next() {
		return $this->categories->Next();
	}
	
	public function Rewind() {
		return $this->categories->Rewind();
	}
	
	public function Valid() {
		return $this->categories->Valid();
	}
	
	public function GetChildren() {
		return new FAQIterator($this->temp, $this->revert);
	}
}

class FAQIterator implements Iterator {
	protected $item;
	protected $revert;
	public function __construct($row, $revert) {
		$this->revert = $revert;
		$this->item = DBA::Open()->Query("SELECT * FROM ". FAQ ." WHERE parent_id = ". $row['id'] )->GetIterator();
	}	
	public function Current() {
		$temp = $this->item->Current();
		if($this->revert == 1) {
			$parser = new BBParser($temp['answer']);
			$temp['answer'] = $parser->Revert(stripslashes($temp['answer']));
		}

		$temp['question'] = stripslashes($temp['question']);
		$temp['answer'] = stripslashes($temp['answer']);
		return $temp;
	}
	
	public function Key() {
		return $this->item->Key();
	}
	
	public function Next() {
		return $this->item->Next();
	}
	
	public function Rewind() {
		return $this->item->Rewind();
	}
	
	public function Valid() {
		return $this->item->Valid();
	}
}

?>