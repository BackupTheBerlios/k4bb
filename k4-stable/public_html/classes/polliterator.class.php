<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     polliterator.class.php
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

class PollIterator implements Iterator {
	protected $options;
	protected $dba;
	protected $id;

	public function __construct($id) {
		$this->id = $id;
		$this->dba = DBA::Open();
		$this->options = $this->dba->Query("SELECT * FROM ". POLLOPTIONS ." WHERE poll_id = $this->id")->GetIterator();
	}
	
	public function Current() {
		$temp				= $this->options->Current();
		$row				= $this->dba->GetRow("SELECT (SELECT COUNT(id) FROM ". POLLVOTES ." WHERE option_id = ". $temp['id'] ." AND poll_id = $this->id) as num_votes, (SELECT COUNT(id) FROM ". POLLVOTES ." WHERE poll_id = $this->id) as votes_total FROM ". POLLVOTES );
		$temp['name']		= stripslashes($temp['name']);
		$temp['num_votes']	= $row['num_votes'];
		$temp['percentage'] = round(@($row['num_votes'] / $row['votes_total']) * 100);
		return $temp;		
	}
	
	public function Key() {
		return $this->options->Key();
	}
	
	public function Next() {
		return $this->options->Next();
	}
	
	public function Rewind() {
		return $this->options->Rewind();
	}
	
	public function Valid() {
		return $this->options->Valid();
	}
}

?>