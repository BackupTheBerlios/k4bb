<?php
/**
* k4 Bulletin Board, conditionals.php
*
* Copyright (c) 2005, Peter Goodman
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
* @author Peter Goodman
* @version $Id: conditionals.php,v 1.1 2005/04/05 03:21:59 k4st Exp $
* @package k42
*/

class If_If_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {

		$this->attribs = array(
						'eq'		=> '==',
						'noteq'		=> '!=',
						'modulo'	=> '%',
						'greater'	=> '>',
						'geq'		=> '>=',
						'less'		=> '<',
						'lesseq'	=> '<=');
		
		$this->keys = array_keys($element->attribs);

		if(isset($element->attribs['var']) && array_key_exists($this->keys[1], $this->attribs) ) {
			return "<?php if(\$context->getVar('". $element->attribs['var'] ."') ". $this->attribs[$this->keys[1]] ." '". $element->attribs[$this->keys[1]] ."'): ?>";			

			return "<h1>Missing EQ or NOTEQ, MODULO, GREATER, GEQ, LESS OR LESSEQ for conditional IF statement.</h1>";
		}
		return "<h1>Missing VAR, EQ or NOTEQ, MODULO, GREATER, GEQ, LESS OR LESSEQ for conditional IF statement.</h1>";
	}
	function getClose(&$element) {
		//if(isset($element->attribs['var']) && array_key_exists($this->keys[1], $this->attribs) )
			return "<?php endif; ?>";
	}
}

class Else_If_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {

		$this->attribs = array(
						'eq'		=> '==',
						'noteq'		=> '!=',
						'modulo'	=> '%',
						'greater'	=> '>',
						'geq'		=> '>=',
						'less'		=> '<',
						'lesseq'	=> '<=');
		
		$this->keys = array_keys($element->attribs);

		if(isset($element->attribs['var']) && array_key_exists($this->keys[1], $this->attribs) ) {
			return "<?php else if(\$context->getVar('". $element->attribs['var'] ."') ". $this->attribs[$this->keys[1]] ." '". $element->attribs[$this->keys[1]] ."'): ?>";			

			return "<h1>Missing EQ or NOTEQ, MODULO, GREATER, GEQ, LESS OR LESSEQ for conditional ELSEIF statement.</h1>";
		}
		return "<h1>Missing VAR, EQ or NOTEQ, MODULO, GREATER, GEQ, LESS OR LESSEQ for conditional ELSEIF statement.</h1>";
	}
}

class If_Else_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		return "<?php else: ?>";
	}
}

class Maps_If_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {

		if((isset($element->attribs['var']) || isset($element->attribs['group']) || isset($element->attribs['category']) || isset($element->attribs['forum'])) && isset($element->attribs['method'])) {
			
			$query = "TRUE";

			$attribs = array('groups' => 'group', 'categories' => 'category', 'forums' => 'forum', 'users' => 'user');
			
			
			if((isset($element->attribs['group']) || isset($element->attribs['category']) || isset($element->attribs['forum'])) && isset($element->attribs['method'])) {

				/* These attributes are special because they need $context->getVar() and the attrib name in them */
				foreach($attribs as $key => $val) {
					if(isset($element->attribs[$val])) {


						//$variable	= isset($element->attribs['var']) ? $element->attribs['var'] : "'$attrib'. \$context->getVar('". $element->attribs[$attrib] ."')";
						
						$var		= "";
						if(isset($element->attribs['var']))
							$var	= "['". $element->attribs['var'] ."']";

						/* Make the *query* to check the permissions */
						$query .= " && (isset(\$context->session['user']->info['maps']['". $key ."'][\$context->getVar('". $element->attribs[$val] ."')]". $var ."['". $element->attribs['method'] ."'])";
						$query .= " && \$context->session['user']->info['maps']['". $key ."'][\$context->getVar('". $element->attribs[$val] ."')]". $var ."['". $element->attribs['method'] ."'] <= \$context->session['user']->info['perms'])";
					}
				}
			} else if(isset($element->attribs['var']))
				$query .= " && (isset(\$context->session['user']->info['maps']['". $element->attribs['var'] ."']) && \$context->session['user']->info['maps']['". $element->attribs['var'] ."']['". $element->attribs['method'] ."'] <= \$context->session['user']->info['perms'])";

			return "<?php if($query): ?>";
		}
		return "<h1>Missing (VAR, CATEGORY, FORUM, GROUP) or METHOD for conditional MAPS statement.</h1>";
	}
	function getClose(&$element) {
		return "<?php endif; ?>";
	}
}

class Maps_Else_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		return "<?php else: ?>";
	}
}

?>