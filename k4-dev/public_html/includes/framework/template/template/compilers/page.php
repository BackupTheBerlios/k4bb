<?php
/**********************************************************************************
 *     File Arts
 *     Copyright (c) 2005, Geoffrey Goodman, BestWebEver.com
 *********************************************************************************/

class Page_Navigator_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		if (isset($element->attribs['id'])) {
			$pager = $element->attribs['id'];
			return "<?php if (\$pager = &\$context->getPager(\"$pager\")): ?>";
		}
		return "<h1>Missing id</h1>";
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
		return "<h1>Missing id, before and after</h1>";
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