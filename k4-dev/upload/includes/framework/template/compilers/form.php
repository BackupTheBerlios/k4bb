<?php
/**
* k4 Bulletin Board, form.php
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
* @version $Id: form.php,v 1.2 2005/04/13 02:54:29 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class Form_Form_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		$attribs = $this->getAttribString($element->attribs, 'persistent');

		return "<form$attribs onsubmit=\"return checkForm(this);\" onreset=\"resetErrors();\"><script type=\"text/javascript\">resetErrors();</script>";
	}

	function getClose(&$element) {
		return "</form>";
	}
}

class Form_Error_Compiler extends TPL_Tag_Compiler {
	function getVerificationJs(&$element) {
		if (isset($element->attribs['for'])) {
			$id = $element->attribs['id'];
			$class = (isset($element->attribs['setclass'])) ? $element->attribs['setclass'] : '';
			$for = $element->attribs['for'];
			if (isset($element->attribs['regex'])) {
				$regex = str_replace(str_repeat(chr(92), 2), str_repeat(chr(92), 3), addslashes($element->attribs['regex']));
				return "<script type=\"text/javascript\">addVerification('$for', '$regex', '$id', '$class');</script>";
			}
			if (isset($element->attribs['match'])) {
				$match = $element->attribs['match'];
				return "<script type=\"text/javascript\">addCompare('$for', '$match', '$id', '$class');</script>";
			}
		}
		return "<h1>Missing FOR for form:error</h1>";
	}

	function getOpen(&$element) {
		if (isset($element->attribs['id'])) {
			$id = $element->attribs['id'];
			$js = $this->getVerificationJs($element);
			return "$js<div id=\"$id\" style=\"display: none;\">";
		}
		return "<h1>Missing ID for form:error</h1>";
	}
	function getClose(&$element) {
		if (isset($element->attribs['id'])) {
			return "</div>";
		}
	}
}

class Form_Message_Compiler extends TPL_Tag_Compiler {
	function getMessageJs(&$element) {
		if (isset($element->attribs['for'])) {
			$id = $element->attribs['id'];
			$for = $element->attribs['for'];
			return "<script type=\"text/javascript\">addMessage('$for', '$id');</script>";
		}
		return "<h1>Missing FOR for form:message</h1>";
	}
	function getOpen(&$element) {
		if (isset($element->attribs['id'])) {
			$id = $element->attribs['id'];
			$js = $this->getMessageJs($element);
			return "$js<div id=\"$id\" style=\"display: block;\">";
		}
		return "<h1>Missing ID for form:message</h1>";
	}
	function getClose(&$element) {
		if (isset($element->attribs['id'])) {
			return "</div>";
		}
	}
}

?>
