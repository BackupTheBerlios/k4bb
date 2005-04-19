<?php
/**
* k4 Bulletin Board, core.php
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
* @version $Id: core.php,v 1.3 2005/04/19 21:53:01 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class Core_Set_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		if (isset($element->attribs['name']) && isset($element->attribs['value'])) {
			$name = $element->attribs['name'];
			$value = $element->attribs['value'];

			return "<?php \$context->push(array('$name' => '$value')); ?>";
		}
		return "<h1>Missing NAME or VALUE for core:set</h1>";
	}
	function getClose(&$element) {
		if (isset($element->attribs['name']) && isset($element->attribs['value']))
			return "<?php \$context->pop(); ?>";
	}
}

class Core_Block_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		$id = (isset($element->attribs['id'])) ? $element->attribs['id'] : '';
		$default = (isset($element->attribs['hidden'])) ? 'FALSE' : 'TRUE';

		return "<?php if (\$template->isVisible('$id', $default)): ?>";
	}
	function getClose(&$element) {
		return "<?php endif; ?>";
	}
}

class Core_Date_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		$format = (isset($element->attribs['format'])) ? $element->attribs['format'] : "%x";

		return "<?php ob_start(); \$format = \"$format\"; ?>";
	}

	function getClose(&$element) {
		return "<?php \$date = ob_get_contents(); ob_end_clean(); echo strftime(\$format, intval(bbtime(\$date))); ?>";
	}
}

class Core_Import_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		if (isset($element->attribs['id'])) {
			$file = (isset($element->attribs['file'])) ? $element->attribs['file'] : '';
			$buffer = "<?php if (\$file = \$template->getFile(\"{$element->attribs['id']}\", \"$file\")): ?>";
			$buffer .= "<?php \$template->setFilename(\$file); ?>";
			$buffer .= "<?php \$template->render(); endif; ?>";
			return $buffer;
		}
		return "<h1>Missing ID for core:import</h1>";
	}
}

class Core_Truncate_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		if (isset($element->attribs['length'])) {
			$length = $element->attribs['length'];
	    $append = (isset($element->attribs['append'])) ? $element->attribs['append'] : "&hellip;";

	    return "<?php ob_start(); \$length = \"$length\"; \$append = \"$append\"; ?>";
	  }
	  return "<h1>Missing LENGTH for core:truncate</h1>";
	}

	function getClose(&$element) {
		if (isset($element->attribs['length'])) {
			return "<?php \$string = ob_get_contents(); ob_end_clean(); echo (strlen(\$string) > \$length) ? substr(\$string, 0, \$length).\$append : \$string; ?>";
		}
	}
}

class Core_Tag_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		if (isset($element->attribs['name'], $element->attribs['type'])) {
			$name = $element->attribs['name'];
			$attribs = $this->getAttribString($element->attribs, 'name', 'type');
			
			switch ($element->attribs['type']) {
				case 'open': return "<$name$attribs>";
				case 'close': return "</$name>";
				default: return "<h1>Invalid TYPE for core:tag</h1>";
			}
		}
		return "<h1>Missing NAME or TYPE for core:tag</h1>";
	}
}

class Textarea_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		$attribs = $this->getAttribString($element->attribs);
		return "<textarea$attribs>";
	}

	function getClose(&$element) {
		return "</textarea>";
	}
}

?>
