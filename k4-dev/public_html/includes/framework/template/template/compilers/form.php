<?php

class Form_Form_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		$attribs = $this->getAttribString($element->attribs, 'persistent');
		//$js = file_get_contents(TPL_BASE_DIR.'/form_check.html');
		return "<form$attribs onload=\"resetErrors();\" onsubmit=\"return checkForm(this);\" onreset=\"resetErrors();\">";
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
