<?php
/**********************************************************************************
 *     File Arts
 *     Copyright (c) 2005, Geoffrey Goodman, BestWebEver.com
 *********************************************************************************/

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
			$remainder = (isset($element->attribs['remainder']) ? $element->attribs['remainder'] : 0);
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
		if (isset($element->attribs['list']) && isset($element->attribs['var'])) {
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