<?php
/**********************************************************************************
 *     File Arts
 *     Copyright (c) 2005, Geoffrey Goodman, BestWebEver.com
 *********************************************************************************/

require_once FA_BASE_DIR.'/saxparser.php';

class TPL_Element {}

class TPL_DataElement extends TPL_Element {
	var $data;

	function TPL_DataElement($data) {
		$this->data = $data;
	}
}

class TPL_TagElement extends TPL_Element {
	var $name;
	var $attribs;
	var $children = array();

	function TPL_TagElement($name, $attribs) {
		$this->name = $name;
		$this->attribs = $attribs;
	}

	function addChild(&$element) {
		$this->children[] = &$element;
	}
}

class TPL_RootElement extends TPL_TagElement {
	function TPL_RootElement() {
		$this->name = 'tpl:element';
		$this->attribs = array();
	}
}

class TPL_Parser extends SAXParser {
	var $root;
	var $stack = array();

	function initialize() {
		$this->root = &new TPL_RootElement();

		$this->stack[] = &$this->root;
	}

	function handleCharData($data) {
		$element = &new TPL_DataElement($data);
		$parent = &$this->stack[sizeof($this->stack) - 1];

		$parent->addChild($element);
	}

	function handleOpenTag($name, $attribs) {
		$element = &new TPL_TagElement($name, $attribs);
		$parent = &$this->stack[sizeof($this->stack) - 1];

		$parent->addChild($element);

		$this->stack[] = &$element;
	}

	function handleCloseTag($name) {
		array_pop($this->stack);
	}

	function &getRoot() {
		return $this->root;
	}
}

?>