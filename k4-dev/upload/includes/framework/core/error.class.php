<?php
/**
* k4 Bulletin Board, error.class.php
*
* Copyright (c) 2005, Geoffrey Goodman
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
* @author Geoffrey Goodman
* @version $Id: error.class.php,v 1.3 2005/04/13 02:53:33 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class FAError {
	var $message;
	var $filename;
	var $line;

	function FAError($message, $filename = "", $line = "") {
		$this->message = $message;
		$this->filename = $filename;
		$this->line = $line;
	}

	function getArray() {
		return array('message' => $this->message, 'filename' => $this->filename, 'line' => $this->line);
	}
}

class FAErrorStack {
	var $errors = array();

	function reset() {
		$this->errors = array();
	}

	function push($error) {
		return array_push($this->errors, $error);
	}

	function &pop() {
		return array_pop($this->errors);
	}
}

class Error {
	function &getStack() {
		static $instance = NULL;

		if ($instance == NULL)
			$instance = new FAErrorStack();

		return $instance;
	}

	function reset() {
		$stack = &Error::getStack();
		$stack->reset();
	}

	function grab($type = 'FAError') {
		$stack = &Error::getStack();

		for ($i = sizeof($stack->errors) - 1; $i >= 0; $i--) {
			if (is_a($stack->errors[$i], $type))
				return $stack->errors[$i];
		}
	}

	function pitch(&$error) {
		assert(is_a($error, 'FAError') || die("Thrown errors must inherit from FAError"));

		$stack = &Error::getStack();
		$stack->push($error);

		return FALSE;
	}
}

?>
