<?php
/**
* k4 Bulletin Board, parser.php
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
* @version $Id: parser.php,v 1.1 2005/04/05 03:21:47 k4st Exp $
* @package k42
*/

require_once TPL_BASE_DIR .'/saxparser.php';

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

class TPL_Lexer {
	
	function initialize() {
	}
	
	function finalize() {
	}
	
	function parse($buffer) {
		$this->initialize();
		
		$split = preg_split('~(</?[a-z]+:[a-z]+(?:[\s][^>]+)?(?:[\s]/)?>)~', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);
		$count = count($split);
		
		//print_R($split);
		
		for ($i = 0; $i < $count; $i++) {
			if ($i % 2) {
				$this->handleTag($split[$i]);
			} else {
				$this->handleCharData($split[$i]);
			}
		}

		$this->finalize();
	}
	
	function handleTag($buffer) {
		// Grab the opening slash, group and name of the tag as well as the attribute string and the closing slash
		preg_match('~<(/)?([a-z]+:[a-z]+)((?:[\s][a-zA-Z]+="[^"]*")*)[\s]?(/)?>~', $buffer, $matches);

		$name		= $matches[2];
		$attribs	= array();
		
		// Parse through the tag's attributes
		if ($count = preg_match_all('~\s([a-zA-Z]+)="([^"]*)"~', $matches[3], $attrib_matches)) {
			for ($i = 0; $i < $count; $i++)
				$attribs[$attrib_matches[1][$i]] = $attrib_matches[2][$i];
		}

		if ($matches[1] == '/') {
			$this->handleCloseTag($name);
		} else if(@$matches[4] == '/' || @$matches[5] == '/') {
			$this->handleOpenTag($name, $attribs);
			$this->handleCloseTag($name);
		} else {
			$this->handleOpenTag($name, $attribs);
		}
	}
}

class TPL_Parser extends TPL_Lexer {
	var $root;
	var $stack = array();

	function initialize() {
		$this->root = &new TPL_RootElement();

		$this->stack = array();
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