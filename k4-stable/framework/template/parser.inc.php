<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     parser.inc.php
 *     Copyright (c) 2004, Peter Goodman

 *     Permission is hereby granted, free of charge, to any person obtaining 
 *     a copy of this software and associated documentation files (the 
 *     "Software"), to deal in the Software without restriction, including 
 *     without limitation the rights to use, copy, modify, merge, publish, 
 *     distribute, sublicense, and/or sell copies of the Software, and to 
 *     permit persons to whom the Software is furnished to do so, subject to 
 *     the following conditions:

 *     The above copyright notice and this permission notice shall be 
 *     included in all copies or substantial portions of the Software.

 *     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, 
 *     EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 
 *     MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 *     NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS 
 *     BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN 
 *     ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
 *     CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE 
 *     SOFTWARE.
 *********************************************************************************/

error_reporting(E_STRICT | E_ALL);

abstract class TPL_Element {
	abstract public function __toString();
}

class TPL_Data extends TPL_Element {
	public $data	= '';

	public function __construct($data, TPL_Tag $parent) {
		$this->data	= $data;
	}

	public function __toString() {
		return $this->data;
	}
}

class TPL_Tag extends TPL_Element implements ArrayAccess {
	protected $name;
	protected $attribs;
	protected $children;
	protected $parent;

	public function __construct($name, $attribs, TPL_Tag $parent) {
		$this->name		= $name;
		$this->attribs	= $attribs;
		$this->children	= array();
		$this->parent	= $parent;
	}

	public function __toString() {
		$attribs	= '';

		foreach ($this->attribs as $key => $value) {
			$attribs	.= " $key=\"$value\"";
		}

		if (empty($this->children)) {
			return "<{$this->name}$attribs />";
		} else {
			$data	= '';

			foreach ($this->children as $child) {
				$data	.= $child->__toString();
			}

			return "<{$this->name}$attribs>$data</{$this->name}>";
		}
	}

	public function AddChild(TPL_Element $child) {
		$this->children[]	= $child;
	}

	public function FindChild($id) {
		foreach ($this->children as $child) {
			if ($child instanceof TPL_Tag) {
				if (isset($child['id']) && $child['id'] == $id) {
					return $child;
				} else if ($result = $child->FindChild($id)) {
					return $result;
				}
			}
		}
	}

	public function FindParent($class) {
		if ($this->parent instanceof $class)
			return $this->parent;

		if ($this->parent instanceof TPL_Tag)
			return $this->parent->FindParent($class);

		return FALSE;
	}

	public function OffsetExists($offset) {
		return isset($this->attribs[$offset]);
	}

	public function OffsetGet($offset) {
		return $this->attribs[$offset];
	}

	public function OffsetSet($offset, $value) {
		return $this->attribs[$offset]	= $value;
	}

	public function OffsetUnset($offset) {
		unset($this->attribs[$offset]);
	}
}

class TPL_Root extends TPL_Tag {
	public function __construct($name, $attribs, TPL_Parser $parser) {
		//$parser->SetRoot($this);
	}

	public function __toString() {
		$data	= '';

		foreach ($this->children as $child) {
			$data	.= $child->__toString();
		}

		return $data;
	}
}

abstract class TPL_Component extends TPL_Tag {
	public $id	= '';

	public function __construct($name, $attribs, TPL_Tag $parent) {
		parent::__construct($name, $attribs, $parent);

		static $count	= 0;

		if (isset($attribs['id'])) {
			$this->id	= $attribs['id'];
		} else {
			$this->id	= 'obj' . $count++;
		}
	}
}

class TPL_ParserException extends PC_Exception {
	public function __construct($message, $error) {
		parent::__construct("$message [$error]");
		return TRUE;
	}
	public function __toString() {
		return '<strong>['. $this->getMessage() .']</strong><br /><br /><pre>'. $this->getTraceAsString() .'</pre>';
	}
}

class TPL_Parser {
	private $components	= array();
	private $root;
	private $stack;

	public function __construct() {
		$ini	= PC_CONFIG_DIR . '/components.ini.php';

		if (file_exists($ini) && is_readable($ini)) {
			$tags	= parse_ini_file($ini);

			foreach ($tags as $name => $classinfo) {
				$classinfo	= explode('@', $classinfo);

				if (isset($classinfo[1]))
					//define_class($classinfo[0], dirname(__FILE__) . '/' . $classinfo[1]);
					include_once dirname(__FILE__) . '/' . $classinfo[1];

				$this->components[$name]	= $classinfo[0];
			}
		}
	}

	private function GetClass($name) {
		if (isset($this->components[$name]))
			return $this->components[$name];

		return 'TPL_Tag';
	}

	public function HandleClose($parser, $name) {
		$element	= array_pop($this->stack);
		$parent		= end($this->stack);

		$parent->AddChild($element);
	}

	public function HandleData($parser, $data) {
		$parent		= end($this->stack);
		$element	= new TPL_Data($data, $parent);

		$parent->AddChild($element);
	}

	public function HandleOpen($parser, $name, $attribs) {
		$class		= $this->GetClass($name);
		$parent		= end($this->stack);
		$element	= new $class($name, $attribs, $parent);

		array_push($this->stack, $element);
	}

	public function Parse($filename) {
		$this->Reset();

		if (!file_exists($filename) || !is_readable($filename))
			return FALSE;

		$data	= file_get_contents($filename);

		//Parse contents to change all entities to ISO-8859-1
		$trans	= array_flip(get_html_translation_table(HTML_ENTITIES));
		$data	= preg_replace('/(&[a-z]+;)/e', '"&#" . ord($trans["$1"]) . ";"', $data);
		$data	= "<PCROOT>$data</PCROOT>";

		$parser	= xml_parser_create('ISO-8859-1');
		
		xml_set_object($parser, $this);
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($parser, 'HandleOpen', 'HandleClose');
		xml_set_character_data_handler($parser, 'HandleData');

		$result	= xml_parse($parser, $data, true);

		if ($result == FALSE) {
			//XML parser error
			$error		= xml_error_string(xml_get_error_code($parser));
			//30 characters around the location of the error
			$context	= substr($data, xml_get_current_byte_index($parser) - 15, 30);

			throw new TPL_ParserException("$error [$context]", $filename, xml_get_current_line_number($parser));
		}

		xml_parser_free($parser);

		return $this->root;
	}

	public function Reset() {
		$this->stack		= array($this);

		$this->components['PCROOT']	= 'TPL_Root';
	}

	public function AddChild(TPL_Root $root) {
		$this->root	= $root;
	}
}

?>