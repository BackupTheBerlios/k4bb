<?php
/**
* k4 Bulletin Board, list.php
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
* @version $Id: list.php,v 1.1 2005/04/05 03:21:59 k4st Exp $
* @package k42
*/

class List_List_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		if (isset($element->attribs['id'])) {
			$list = $element->attribs['id'];
			return "<?php if (\$context->listReset(\"$list\")): ?>";
		}
		return "<h1>Missing ID for list:list</h1>";
	}
	function getClose(&$element) {
		if (isset($element->attribs['id'])) {
			return "<?php endif; ?>";
		}
	}
}

class List_Sublist_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		if (isset($element->attribs['id']) && isset($element->attribs['column'])) {
			//$list = $element->attribs['list'];
			$sublist = $element->attribs['id'];
			$column = $element->attribs['column'];
			return "<?php if (\$context->addList(\"$sublist\", \$context->getVar(\"$column\"))): ?>";
		}
		return "<h1>Missing ID and COLUMN for list:sublist</h1>";
	}
	function getClose(&$element) {
		if (isset($element->attribs['id']) && isset($element->attribs['column'])) {
			return "<?php endif; ?>";
		}
	}
}

class List_Item_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		if (isset($element->attribs['list'])) {
			$list = $element->attribs['list'];
			return "<?php while(\$context->push(\$context->listNext(\"$list\"))): ?>";
		}
		return "<h1>Missing LIST for list:item</h1>";
	}
	function getClose(&$element) {
		if (isset($element->attribs['list'])) {
			return "<?php \$context->pop(); endwhile; ?>";
		}
	}
}

class List_Separator_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		if (isset($element->attribs['list'])) {
			$list = $element->attribs['list'];
			return "<?php if (is_object(\$list = &\$context->getList(\"$list\")) && \$list->hasNext()): ?>";
		}
		return "<h1>Missing LIST for list:separator</h1>";
	}
	function getClose(&$element) {
		if (isset($element->attribs['list'])) {
			return "<?php endif; ?>";
		}
	}
}

class List_Alternate_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		if (isset($element->attribs['list'])) {
			$list = $element->attribs['list'];
			$count = (isset($element->attribs['count']) ? $element->attribs['count'] : 1);
			//$count = preg_replace('/{\$([a-zA-Z_\.]+?)}/e', '$this->context->getVar("$1")', $count);
			$remainder = (isset($element->attribs['remainder']) ? $element->attribs['remainder'] : 0);
			//$remainder = preg_replace('/{\$([a-zA-Z_\.]+?)}/e', '$this->context->getVar("$1")', $remainder);
			return "<?php if (\$context->listKey(\"$list\") % $count == $remainder): ?>";
		}
		return "<h1>Missing LIST for list:alternate</h1>";
	}
	function getClose(&$element) {
		if (isset($element->attribs['list'])) {
			return "<?php endif; ?>";
		}
	}
}

class List_Switch_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		if (isset($element->attribs['list'], $element->attribs['var'])) {
			$list = $element->attribs['list'];
			$var = $element->attribs['var'];
			$switch = $element->attribs; unset($switch['list']); unset($switch['var']); $switch = array_values($switch);
			$count = sizeof($switch);
			$buffer = "<?php switch(\$context->listKey(\"$list\") % $count): ?>";
			foreach ($switch as $key => $value) {
				$buffer .= "<?php case $key: \$context->push(array('$var' => '$value')); break; ?>";
			}
			$buffer .= "<?php default: \$context->push(array()); endswitch; ?>";
			return $buffer;
		}
		return "<h1>Missing LIST for list:switch</h1>";
	}
	function getClose(&$element) {
		if (isset($element->attribs['list']) && isset($element->attribs['var'])) {
			return "<?php \$context->pop(); ?>";
		}
	}
}

class List_Default_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		if (isset($element->attribs['list'])) {
			$list = $element->attribs['list'];
			return "<?php if (is_object(\$list = &\$context->getList(\"$list\")) && !\$list->hasNext()): ?>";
		}
		return "<h1>Missing LIST for list:default</h1>";
	}
	function getClose(&$element) {
		if (isset($element->attribs['list'])) {
			return "<?php endif; ?>";
		}
	}
}

?>