<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     error.class.php
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

/* Basic error, uses the template to change the content variable */
class Error {
	public function __construct($message, $template) {
		$template->content = array('file' => 'error.html');
		$template['header'] = $template['L_INFORMATION'];
		$template['information'] = $message;
		
		return TRUE;
	}
}

/* A singleton to use when we don't have the $template var handy :) */
class SetError {
	public $message;
	private function __construct() {
		return TRUE;
	}
	static public function Set($message) {
		$error = new SetError;
		$error->message = $message;

		return $error;
	}
}

/* This will take in exceptions and fancy them up :) */
class TplException {
	public $str;
	public function __construct($e, $template) {
		$template->content = array('file' => 'error.html');
		$template['header'] = $template['L_ERROR'];
		$template['information'] =  '<strong>' . $e->getMessage() .'</strong><br /><br />'. $template['L_IN'] .': '. $e->getFile() .'<br />'. $template['L_ON_LINE'] .': '. $e->__toString() .'<br /><br />'. $template['L_STACKTRACE'] .':<br /><div style="overflow:auto;overflow-x:scroll;width:95%;height:90px;border:1px solid #c8c8c8;">'. str_replace("\n", "<br /><br />", $e->getTraceAsString()) .'</div>';

		return TRUE;
	}
}

?>