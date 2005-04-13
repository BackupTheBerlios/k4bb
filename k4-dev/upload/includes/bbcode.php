<?php

/**
* k4 Bulletin Board, bbcode.php
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
* @version $Id: bbcode.php,v 1.2 2005/04/13 02:52:05 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class FABBTag {
	var $open = FALSE;

	function getOpen($name, $attrib, $body) {
		return $body;
	}

	function getClose($name, $body) {
		return $body;
	}
}

class FABBLinkTag extends FABBTag {
	function getOpen($name, $attrib, $body) {
		if ($attrib)
			return "<a class=\"bbcode\" href=\"$attrib\">$body";

		$param = htmlspecialchars($body, ENT_QUOTES);
		return "<a class=\"bbcode\" href=\"$param\">$body";
	}

	function getClose($name, $body) {
		return "</a>$body";
	}
}

class FABBImgTag extends FABBTag {
	function getOpen($name, $attrib, $body) {
		$param = htmlspecialchars($body, ENT_QUOTES);
		return "<img class=\"bbcode\" src=\"$param\"/>";
	}
}

class FABBColorTag extends FABBTag {
	function getOpen($name, $attrib, $body) {
		return "<span class=\"bbcode\" style=\"color: $attrib;\">$body";
	}

	function getClose($name, $body) {
		return "</span>$body";
	}
}

class FABBSizeTag extends FABBTag {
	function getOpen($name, $attrib, $body) {
		return "<span class=\"bbcode\" style=\"font-size: $attrib;\">$body";
	}

	function getClose($name, $body) {
		return "</span>$body";
	}
}

class FABBListTag extends FABBTag {
	var $stack = array();

	function getOpen($name, $attrib, $body) {
		if ($attrib == '1' || $attrib == 'a') {
			array_push($this->stack, 'ol');
			return "<ol class=\"bbcode\" type=\"$attrib\">$body";
		}

		array_push($this->stack, 'ul');
		return "<ul class=\"bbcode\">$body";
	}

	function getClose($name, $body) {
		$tag = array_pop($this->stack);
		return "</$tag>$body";
	}
}

class FABBItemTag extends FABBTag {
	var $open = TRUE;

	function getOpen($name, $attrib, $body) {
		return "<li class=\"bbcode\">$body";
	}

	function getClose($name, $body) {
		return "</li>$body";
	}
}

class FABBStyleTag extends FABBTag {
	function getOpen($name, $attrib, $body) {
		return "<$name class=\"bbcode\">$body";
	}

	function getClose($name, $body) {
		return "</$name>$body";
	}
}

class BBParser {
	var $tags = array();
	var $open;

	function BBParser() {
		
		global $_SETTINGS;

		$style = &new FABBStyleTag();
		
		if($_SETTING['allowbbcode'] == 1) {

			$this->addTag('i', $style);
			$this->addTag('b', $style);
			$this->addTag('p', $style);
			$this->addTag('u', $style);
			$this->addTag('link', new FABBLinkTag());
			$this->addTag('url', new FABBLinkTag());
			$this->addTag('list', new FABBListTag());
			$this->addTag('*', new FABBItemTag());
			
			if($_SETTING['allowbbimagecode'] == 1)
				$this->addTag('img', new FABBImgTag());
			
			$this->addTag('size', new FABBSizeTag());
			$this->addTag('color', new FABBColorTag());
		}
	}

	function addTag($name, &$tag) {
		$this->tags[$name] = &$tag;
	}

	function &getTag($name) {
		if (isset($this->tags[$name]))
			return $this->tags[$name];

		return new FABBTag();
	}

	function parse($code) {
		$tokens = preg_split('~\[([^\]]+)\]~', $code, -1, PREG_SPLIT_DELIM_CAPTURE);
		$buffer = $tokens[0];

		for ($i = 1; isset($tokens[$i]); $i += 2) {
			$body = $tokens[$i + 1];
			$buffer .= preg_replace('~(/?)([^=]+)(=(.+))?~e', "\$this->parseTag('$2', '$1', '$4', \"$body\")", $tokens[$i]);
		}

		return $buffer;
	}

	function parseTag($name, $closed, $attrib, $body) {
		$name = htmlspecialchars($name);
		$attrib = htmlspecialchars($attrib);

		$tag = &$this->getTag($name);

		if ($this->open && $pos = strpos($body, "\n")) {
			$open = &$this->getTag($this->open);
			$body = str_replace("\n", $open->getClose($this->open, ''), $body);
			$this->open = '';
		}

		if ($closed) {
			return $tag->getClose($name, $body);
		}
		else {
			if ($tag->open) {
				$this->open = $name;

				if ($pos = strpos($body, "\n")) {
					$open = &$this->getTag($this->open);
					$body = str_replace("\n", $open->getClose($this->open, ''), $body);
					$this->open = '';
				}
			}

			return $tag->getOpen($name, $attrib, $body);
		}
	}
}

?>
