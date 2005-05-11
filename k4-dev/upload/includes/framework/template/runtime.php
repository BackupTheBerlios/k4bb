<?php
/**
* k4 Bulletin Board, runtime.php
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
* @version $Id: runtime.php,v 1.4 2005/05/11 17:57:24 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class TPL_Context {
	var $contexts;
	var $lists;
	var $session;

	function TPL_Context($context, $lists) {
		$this->contexts[]	= $context;
		$this->lists		= $lists;
		$this->session		= &$_SESSION;
	}

	// Context variables

	function getVar($var) {
		end($this->contexts);
		do {
			$context = current($this->contexts);
			if (isset($context[$var])) {
				return $context[$var];
			}
		} while (prev($this->contexts));
	}

	function addVar($key, $value) {
		$this->contexts[0][$key] = $value;
	}

	// Entering and leaving contexts

	function push($array) {
		if (!is_array($array))
			return FALSE;

		array_push($this->contexts, $array);

		return TRUE;
	}

	function pop() {
		array_pop($this->contexts);

		return TRUE;
	}

	// List getter and setter

	function &getList($name) {
		if (isset($this->lists[$name]))
			return $this->lists[$name];
	}

	function addList($name, &$list) {
		if (is_a($list, 'FAIterator')) {
			$list->reset();
			$this->lists[$name] = &$list;

			return TRUE;
		}
	}

	// List runtime functionality

	function listKey($name) {
		if (is_object($list = &$this->getList($name))) {
			return $list->key();
		}
	}

	function &listNext($name) {
		if (is_object($list = &$this->getList($name))) {
			if (!$list->hasNext())
				return FALSE;

			return $list->next();
		}
	}

	function listReset($name) {
		if (is_object($list = &$this->getList($name))) {
			return $list->reset();
		}
	}
}

?>