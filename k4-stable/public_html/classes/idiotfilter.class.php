<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     idiotfilter.class.php
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

class IdiotFilter {
	public $str;
	public function __construct($str) {	
		$letters = range('A', 'Z');

		$count = strlen($str);
		$caps_count = 0;
		for ($x = 0; $x < $count; $x++) { 
			if(in_array($str{$x}, $letters))
				$caps_count++;
		}

		if(@(round($caps_count / $count) * 100) >= 55) { // the person is annoying
			$str = ucfirst(strtolower($str));
			$str = preg_replace('~\bi\b~', 'I', $str);
			$str = preg_replace_callback('~([\.\?\!])(.+?)([a-zA-Z]+)\b~s', create_function('$matches', 'return $matches[1]." ".ucfirst($matches[3]);'), $str);
		}

		$this->str = $str;
	}
}

?>