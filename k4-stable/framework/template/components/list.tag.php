<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     list.tag.php
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

class TPL_List extends TPL_Component {
	public function __toString() {
		$data	= '';

		if (!isset($this->attribs['id']))
			return $data;

		$data	.= "<?php if (\$template->{$this->id} != NULL): ?>";

		foreach ($this->children as $child)
			$data	.= $child->__toString();

		$data	.= "<?php endif; ?>";

		return $data;
	}
}

class TPL_SubList extends TPL_List {
	public function __toString() {
		$data	= '';

		if (!isset($this->attribs['parent']) || !isset($this->attribs['function']))
			return $data;
		
		$list	= $this->attribs['parent'];
		$func	= $this->attribs['function'];
			
		$data	.= "<?php if (\$template->{$list} && method_exists(\$template->{$list}, '$func') && \$template->{$this->id} = \$template->{$list}->$func()): ?>";

		foreach ($this->children as $child) {
			$data	.= $child->__toString();
		}

		$data	.= "<?php endif; ?>";
		
		return $data;
	}
}

class TPL_ListItem extends TPL_Component {
	public function __toString() {
		$data	= '';
		$list	= $this->FindParent('TPL_List');

		if ($list == FALSE)
			return $data;
		
		$data	.= "<?php \${$list->id} = new CachingIterator(get_iterator(\$template->{$list->id})); ?>";
		$data	.= "<?php foreach(\${$list->id} as \${$this->id}): ?>";
		$data	.= "<?php \$template->Push(\${$this->id}); ?>";

		foreach ($this->children as $child)
			$data	.= $child->__toString();

		$data	.= "<?php \$template->Pop(); ?>";
		$data	.= "<?php endforeach; ?>";

		return $data;
	}
}

class TPL_Alternate extends TPL_Component {
	private $var;

	public	function __construct($name, $attribs, TPL_Tag $parent) {
		if (isset($attribs['var'])) {
			$this->var	= $attribs['var'];
			unset($attribs['var']);
		}

		parent::__construct($name, $attribs, $parent);
	}

	public function __toString() {
		$data	= '';

		if (empty($this->var))
			return $data;

		$size	= sizeof($this->attribs);
		$list	= $this->FindParent('TPL_List');

		if ($list == FALSE)
			return '';

		$data	.= "<?php switch(\${$list->id}->key() % $size): ?>";

		foreach (array_values($this->attribs) as $key => $value)
			$data	.= "<?php case $key: \$template->Push(array('{$this->var}' => '$value')); break; ?>";
		
		$data	.= "<?php default: \$template->Push(array()); ?>";
		$data	.= "<?php endswitch; ?>";

		foreach ($this->children as $child)
			$data	.= $child->__toString();

		$data	.= "<?php \$template->Pop(); ?>";
		
		return $data;
	}
}

class TPL_Default extends TPL_Component {
	public function __toString() {
		$data	= '';
		$list	= $this->FindParent('TPL_List');

		if ($list == FALSE)
			return '';

		$data	.= "<?php if (!\${$list->id}->key()): ?>";

		foreach ($this->children as $child)
			$data	.= $child->__toString();

		$data	.= "<?php endif; ?>";

		return $data;
	}
}

class TPL_Separator extends TPL_Component {
	public function __toString() {
		$data	= '';
		$list	= $this->FindParent('TPL_List');

		if ($list == FALSE)
			return '';

		$data	.= "<?php if (\${$list->id}->HasNext()): ?>";

		foreach ($this->children as $child)
			$data	.= $child->__toString();

		$data	.= "<?php endif; ?>";

		return $data;
	}
}

?>