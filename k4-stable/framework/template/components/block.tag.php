<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     block.tag.php
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

class TPL_Block extends TPL_Component {
	public	function __toString() {
		$data	= '';
		// workout for the hide function
		/* $data	.= "<?php if ((!isset(\$template->{$this->id}[0]) && !isset(\$template->{$this->id}[1]) && (\$template->{$this->id}[0] != 'hide') && (\$template->{$this->id}[1] != 1))): ?>"; */
		
		$data	.= "<?php if (!isset(\$template->{$this->id}['hide']) || !\$template->{$this->id}['hide']): ?>";
		$data	.= "<?php \$template->Push(\$template->{$this->id}); ?>";

		foreach ($this->children as $child)
			$data	.= $child->__toString();
		
		$data	.= "<?php \$template->Pop(); ?>";
		$data	.= "<?php endif; ?>";

		return $data;
	}
}

class TPL_Import extends TPL_Component {
	public	function __toString() {
		$data	= '';

		$data	.= "<?php if (isset(\$template->{$this->id}['file']) && \$t = new Template(\$template->{$this->id}['file'])): ?>";
		$data	.= "<?php \$template->Push(\$template->{$this->id}); ?>";
		$data	.= "<?php \$t->Import(\$template); ?>";
		$data	.= "<?php \$template->Pop(); ?>";
		$data	.= "<?php endif; ?>";

		return $data;
	}
}

class TPL_Include extends TPL_Component {
	private $file;

	public	function __toString() {
		$data	= '';

		if (!isset($this->attribs['file']))
			return $data;

		$data	.= "<?php \$template->Push(\$template->{$this->id}); ?>";
		$data	.= "<?php \$t = new Template('{$this->attribs['file']}'); ?>";
		$data	.= "<?php \$t->Import(\$template); ?>";
		$data	.= "<?php \$template->Pop(); ?>";

		return $data;
	}
}

?>