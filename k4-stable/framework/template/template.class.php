<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     template.class.php
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

class TPL_Exception extends PC_Exception {
	public function __construct($message, $file = NULL, $line = NULL) {
		parent::__construct($message);

		if ($file != NULL)
			$this->file	= $file;

		if ($line != NULL)
			$this->line	= $line;

	}
}

class Template implements ArrayAccess {
	private	$source;
	private	$compiled;
	private	$buffer;

	private $components	= array();
	private $variables	= array();

	private $stack	= array();

	public	function __construct($filename) {
		global $settings;
		$templateurl = append_slash(get_setting('template', 'path')) . append_slash($settings['templateset']); // get_setting('template', 'tplfolder')
		$this->source	= $templateurl . 'source/' . $filename;
		$this->compiled	= $templateurl . 'compiled/' . $filename;

		if(!assert(is_readable($this->source)))
			echo "Cannot find the template: $filename";
		if(!assert(is_writable(dirname($this->compiled))))
			echo "Cannot write to the compiled template directory";
	}

	public function __get($name) {
		if (isset($this->components[$name]))
			return $this->components[$name];
	}

	public function __set($name, $value) {
		if (is_array($value)) {
			$value	= new ArrayObject($value);
		}
		if ($value instanceof Traversable || $value instanceof ArrayAccess) {
			return $this->components[$name]	= $value;
		}
	}

	private function Compile() {
		$parser	= new TPL_Parser;
		$root	= $parser->Parse($this->source);
		
		$buffer	= $root->__toString();

		$buffer	= preg_replace('/{\$([^}]+)}/', '<?php echo $template["$1"]; ?>', $buffer);
		$buffer	= str_replace('?><?php', '', $buffer);

		if (!file_exists(dirname($this->compiled))) {
			if (!is_writable(dirname($this->compiled) . '/..'))
				throw new TPL_Exception("Unable to created compiled directory [" . dirname($this->compiled) . "]");

			mkdir(dirname($this->compiled), 0777);
		}

		$fp	= fopen($this->compiled, 'wb');

		if ($fp	== FALSE)
			throw new TPL_Exception("Unable to write compiled template [{$this->compiled}]");

		fwrite($fp, $buffer);
		fclose($fp);

		return TRUE;
	}

	public function Import(Template $template) {
		if (!$this->IsCompiled()) {
			$this->Compile();
		}

		if(!$this->Push($this->variables))
			throw new TPL_Exception("Unable to apply template variables.");

		if (is_readable($this->compiled)) {
			include $this->compiled;
		} else {
			throw new TPL_Exception("Unable to read compiled template [{$this->compiled}]");
		}

		return TRUE;
	}

	public function IsCompiled() {
		if (!file_exists($this->compiled)
			|| filemtime($this->source) > filemtime($this->compiled)
			|| get_setting('template', 'force_compile')) {

			return FALSE;
		}

		return TRUE;
	}

	public function OffsetExists($offset) {
		return TRUE;
	}

	public function OffsetGet($offset) {
		for (end($this->stack); $context = current($this->stack); prev($this->stack)) {
			if (isset($context[$offset])) {
				$value	= $context[$offset];

				return $value;
			}
		}

		return '';
	}

	public function OffsetSet($offset, $value) {
		if (is_scalar($value) || method_exists($value, '__tostring'))
			return $this->stack[0][$offset]	= $value;
	}

	public function OffsetUnset($offset) {
		if (isset($this->stack[0][$offset]))
			unset($this->stack[0][$offset]);
	}

	public function Pop() {
		array_pop($this->stack);
	}

	public function Push($context) {
		if (!empty($context) && array_access($context))
			array_push($this->stack, $context);
		else
			array_push($this->stack, new ArrayObject());

		return TRUE;
	}
	public function Render() {
		if(!$this->Import($this))
			throw new TPL_Exception("Unable to Render the selected template.");
	}

}

?>
