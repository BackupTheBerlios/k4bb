<?php
/**********************************************************************************
 *     File Arts
 *     Copyright (c) 2005, Geoffrey Goodman, BestWebEver.com
 *********************************************************************************/

require TPL_BASE_DIR.'/parser.php';

class TPL_Element_Compiler {
	function getOpen(&$tag) {
		return "";
	}

	function getClose(&$tag) {
		return "";
	}
}

class TPL_Tag_Compiler extends TPL_Element_Compiler {
	function getAttribString($attribs, $exclude = NULL) {
		$buffer = '';
		$exclude = array_slice(func_get_args(), 1);

		foreach ($attribs as $name => $value) {
			if (in_array($name, $exclude))
				continue;
			$buffer .= " $name=\"$value\"";
		}

		return $buffer;
	}
	function getOpen(&$tag) {
		$attribs = $this->getAttribString($tag->attribs);

		if (empty($tag->children))
			return "<{$tag->name}$attribs />";

		return "<{$tag->name}$attribs>";
	}
	function getClose(&$tag) {
		if (empty($tag->children))
			return "";

		return "</{$tag->name}>";
	}
}

class TPL_Data_Compiler extends TPL_Element_Compiler {
	function getOpen(&$element) {
		return $element->data;
	}
}

class TPL_Compiler {
	var $tag_compiler;
	var $data_compiler;
	var $compilers = array();

	function TPL_Compiler() {
		$this->tag_compiler = &new TPL_Tag_Compiler();
		$this->data_compiler = &new TPL_Data_Compiler();

		$basedir = FA_BASE_DIR.'/template/compilers/';
		$dir = dir($basedir);

		while (($file = $dir->read()) !== FALSE) {
			if ($file != '.' && $file != '..' && !is_dir($basedir.$file))
				require_once $basedir.$file;
		}

		$dir->close();
	}

	function compile($element) {
		$compiler = &$this->getCompiler($element);

		$buffer = $compiler->getOpen($element);

		if (is_a($element, 'TPL_TagElement')) {
			foreach ($element->children as $child)
				$buffer .= $this->compile($child);
		}

		$buffer .= $compiler->getClose($element);

		return $buffer;
	}

	function &getCompiler($element) {
		if (is_a($element, 'TPL_TagElement')) {
			if (isset($this->compilers[$element->name]))
				return $this->compilers[$element->name];

			$class = implode('_', explode(':', $element->name)) . '_Compiler';
			
			if (class_exists($class)) {
				$compiler = &new $class();
				$this->compilers[$element->name] = $compiler;

				return $compiler;
			}

			return $this->tag_compiler;
		}

		return $this->data_compiler;
	}
}

?>