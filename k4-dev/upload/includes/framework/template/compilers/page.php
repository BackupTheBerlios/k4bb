<?php
/**
* k4 Bulletin Board, page.php
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
* @version $Id: page.php,v 1.1 2005/04/05 03:21:59 k4st Exp $
* @package k42
*/

class Page_Navigator_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		if (isset($element->attribs['id'])) {
			$pager = $element->attribs['id'];
			return "<?php if (\$pager = &\$template->getPager(\"$pager\")): ?>";
		}
		return "<h1>Missing ID for page:navigator</h1>";
	}
	function getClose(&$element) {
		if (isset($element->attribs['id'])) {
			return "<?php endif; ?>";
		}
	}
}

class Page_First_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		return "<?php if (\$context->push(\$pager->getFirst())): ?>";
	}
	function getClose(&$element) {
		return "</a><?php \$context->pop(); endif; ?>";
	}
}

class Page_Prev_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		return "<?php if (\$context->push(\$pager->getPrev())): ?>";
	}
	function getClose(&$element) {
		return "</a><?php \$context->pop(); endif; ?>";
	}
}

class Page_Next_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		return "<?php if (\$context->push(\$pager->getNext())): ?>";
	}
	function getClose(&$element) {
		return "</a><?php \$context->pop(); endif; ?>";
	}
}

class Page_Last_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		return "<?php if (\$context->push(\$pager->getLast())): ?>";
	}
	function getClose(&$element) {
		return "</a><?php \$context->pop(); endif; ?>";
	}
}

class Page_List_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		if (isset($element->attribs['id']) && isset($element->attribs['before']) && isset($element->attribs['after'])) {
			$list = $element->attribs['id'];
			$before = $element->attribs['before'];
			$after = $element->attribs['after'];
			return "<?php if (\$context->addList(\"$list\", \$pager->getIterator(\"$before\", \"$after\"))): ?>";
		}
		return "<h1>Missing ID, BEFORE and AFTER for page:list</h1>";
	}
	function getClose(&$element) {
		if (isset($element->attribs['id']) && isset($element->attribs['before']) && isset($element->attribs['after'])) {
			return "<?php endif; ?>";
		}
	}
}

class Page_Link_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		$attribs = $this->getAttribString($element->attribs);
		return "<?php if (\$pager->page_num != \$context->getVar(\"pagenum\")): ?><a$attribs href=\"<?php echo \$context->getVar(\"pagelink\"); ?>\"><?php else: ?><span$attribs><?php endif; ?>";
	}
	function getClose(&$element) {
		return "<?php if (\$pager->page_num != \$context->getVar(\"pagenum\")): ?></a><?php else: ?></span><?php endif; ?>";
	}
}

?>