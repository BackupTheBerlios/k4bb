<?php
/**********************************************************************************
 *     File Arts
 *     Copyright (c) 2005, Geoffrey Goodman, BestWebEver.com
 *********************************************************************************/

abstract class FAIterator {
	public function &current() {}
	public function hasNext() {}
	public function key() {}
	public function &next() {}
	public function reset() {}
}

class FAArrayIterator extends FAIterator {
	var $data = array();
	var $key = -1;

	public function __construct($data = NULL) {
		if ($data === NULL) $data = array();

		assert('is_array($data)');

		$this->data = array_values($data);
	}

	public function &current() {
		return $this->data[$this->key];
	}
	public function hasNext() {
		return ($this->key + 1 < sizeof($this->data));
	}
	public function key() {
		return $this->key;
	}
	public function &next() {
		if ($this->hasNext()) {
			$this->key++;
			return $this->current();
		}
	}
	public function reset() {
		$this->key = -1;

		return TRUE;
	}
}

class FAProxyIterator extends FAIterator {
	var $it;

	function FAProxyIterator(&$it) {
		$this->it = &$it;
	}

	function &current() {
		return $this->it->current();
	}

	function hasNext() {
		return $this->it->hasNext();
	}

	function key() {
		return $this->it->key();
	}

	function &next() {
		if ($this->hasNext()) {
			$this->it->next();
			return $this->current();
		}
	}

	function reset() {
		return $this->it->reset();
	}
}

?>
