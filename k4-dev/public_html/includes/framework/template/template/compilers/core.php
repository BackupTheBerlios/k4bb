<?php

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
		return "<?php \$date = ob_get_contents(); ob_end_clean(); echo strftime(\$format, intval(\$date)); ?>";
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
