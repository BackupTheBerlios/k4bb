<?php
/**********************************************************************************
 *     File Arts
 *     Copyright (c) 2005, Geoffrey Goodman, BestWebEver.com
 *********************************************************************************/

require_once FA_BASE_DIR.'/iterator.php';
require_once FA_BASE_DIR.'/url.php';

class TPL_PageIterator extends FAIterator {
	var $current;
	var $pager;
	var $before;
	var $after;

	function TPL_PageIterator(&$pager, $before, $after) {
		$this->pager = &$pager;

		if ($before == 'all')
			$before = $pager->page_num - 1;
		if ($after == 'all')
			$after = $pager->count - $pager->page_num;

		//echo "before: $before, after: $after";

		$this->before = $before;
		$this->after = $after;
		$this->reset();
	}

	function &current() {
		return array('pagelink' => $this->pager->getPage($this->current), 'pagenum' => $this->current);
	}

	function hasNext() {
		if ($this->current - $this->pager->page_num >= $this->after)
			return FALSE;
		if ($this->pager->hasPage($this->current + 1) !== FALSE)
			return TRUE;
	}

	function key() {
		return $this->current;
	}

	function &next() {
		if ($this->hasNext()) {
			$this->current++;
			return $this->current();
		}
	}

	function reset() {
		$this->current = $this->pager->page_num - $this->before - 1;
		if ($this->current < 1)
			$this->current = 0;

		return TRUE;
	}
}

class TPL_Paginator {
	var $base_url;
	var $count;
	var $page_size;
	var $page_num;

	function TPL_Paginator($base_url, $count, $page_num, $page_size = 15) {
		assert(is_a($base_url, 'Url'));

		$this->base_url = $base_url;
		$this->count = $count;
		$this->page_size = $page_size;
		$this->page_num = $page_num;
	}

	function getPage($page) {
		if ($this->hasPage($page)) {
			$url = $this->base_url;
			$url->args['page'] = $page;
			$url->args['size'] = $this->page_size;

			return $url->__toString();
		}
	}

	function getFirst() {
		$page = 1;
		if ($this->hasPage($page) && $page != $this->page_num)
			return array('pagenum' => $page, 'pagelink' => $this->getPage($page));
	}

	function getLast() {
		$page = ceil($this->count / $this->page_size);
		if ($this->hasPage($page) && $page != $this->page_num)
			return array('pagenum' => $page, 'pagelink' => $this->getPage($page));
	}

	function getNext($n = 1) {
		$page = $this->page_num + $n;
		if ($this->hasPage($page))
			return array('pagenum' => $page, 'pagelink' => $this->getPage($page));
	}

	function getPrev($n = 1) {
		$page = $this->page_num - $n;
		if ($this->hasPage($page))
			return array('pagenum' => $page, 'pagelink' => $this->getPage($page));
	}

	function hasPage($page) {
		$start = ($page - 1) * $this->page_size;
		if ($start >= 0 && $start < $this->count)
			return TRUE;

		return FALSE;
	}

	function &getIterator($before, $after) {
		return new TPL_PageIterator($this, $before, $after);
	}
}

?>