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
* @version $Id: runtime.php,v 1.1 2005/04/05 03:21:47 k4st Exp $
* @package k42
*/

class TPL_Context {
	var $contexts;
	var $lists;
	var $session;

	function TPL_Context($context, $lists) {
		$this->contexts[]	= $context;
		$this->lists		= $lists;
		$this->session		= &Globals::getGlobal('session');
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
/*
class TPL_Context {
	var $contexts;
	var $blocks;
	var $lists;
	var $pagers;

	function TPL_Context($base, $lists, $blocks, $pagers) {
		$this->contexts[] = $base;
		$this->lists = $lists;
		$this->blocks = $blocks;
		$this->pagers = $pagers;
	}

	function addList($name, &$list) {
		if (is_a($list, 'FAIterator')) {
			$list->reset();
			$this->lists[$name] = &$list;

			return TRUE;
		}
	}

	function &getForm($id, $persistent) {
		return new TPL_Form($id, $persistent);
	}

	function &getList($name) {
		if (isset($this->lists[$name]))
			return $this->lists[$name];
	}

	function &getPager($name) {
		if (isset($this->pagers[$name]))
			return $this->pagers[$name];
	}

	function getVar($var) {
		end($this->contexts);
		do {
			$context = current($this->contexts);
			if (isset($context[$var])) {
				return $context[$var];
			}
		} while (prev($this->contexts));
	}

	function isVisible($block) {
		return (isset($this->blocks[$block]) && $this->blocks[$block] == 'hidden') ? FALSE : TRUE;
	}

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

	function push($array) {
		if (!is_array($array))
			return FALSE;

		$this->contexts[] = $array;

		return TRUE;
	}
	function pop() {
		array_pop($this->contexts);

		return TRUE;
	}
	function resetList($name) {
		if (is_object($list = &$this->getList($name))) {
			if (!$list->reset())
				return FALSE;

			return TRUE;
		}
	}

	function setVar($key, $value) {
		$this->contexts[0][$key] = $value;
	}
}*/

?>