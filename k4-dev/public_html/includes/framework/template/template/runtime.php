<?php
/**********************************************************************************
 *     File Arts
 *     Copyright (c) 2005, Geoffrey Goodman, BestWebEver.com
 *********************************************************************************/

class TPL_Form {
	var $id;

	function TPL_Form($id, $persistent = FALSE) {
		$this->id = $id;
		$name = "form_$id";

		if ($persistent) {
			if (isset($_SESSION[$name])) $_SESSION[$name] = array_merge($_SESSION[$name], $_POST);
			else $_SESSION[$name] = $_POST;
		}
	}
}

class TPL_Context {
	var $contexts;
	var $lists;

	function TPL_Context($context, $lists) {
		$this->contexts[] = $context;
		$this->lists = $lists;
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
			if (!$list->reset())
				return FALSE;

			return TRUE;
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
