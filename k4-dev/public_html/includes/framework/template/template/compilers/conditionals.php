<?php
/**
* k4 Bulletin Board, conditionals.php
*
* Copyright (c) 2004, Peter Goodman
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
* @version $Id: conditionals.php,v 1.1 2005/04/05 02:32:41 necrotic Exp $
* @package k42
*/

class If_Compiler extends TPL_Tag_Compiler {
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
		if(isset($element->attribs['var']) && array_key_exists($this->keys[1], $this->attribs) )
			return "<?php endif; ?>";
	}
}

class ElseIf_Compiler extends TPL_Tag_Compiler {
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
	function getClose(&$element) {
		return "";
	}
}

class Else_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		return "<?php else: ?>";
	}
	function getClose(&$element) {
		return "";
	}
}

class Maps_If_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {

		if((isset($element->attribs['var']) || isset($element->attribs['group']) || isset($element->attribs['category']) || isset($element->attribs['forum'])) && isset($element->attribs['method'])) {
			
			$query = "TRUE";

			$attribs = array('groups' => 'group', 'categories' => 'category', 'forums' => 'forum', 'users' => 'user');
			
			/* Add a checker in for the attribute var */
			if(isset($element->attribs['var']))
				$query .= " && (isset(\$_SESSION['user']['maps']['". $element->attribs['var'] ."']) && \$_SESSION['user']['maps']['". $element->attribs['var'] ."']['". $element->attribs['method'] ."'] <= \$_SESSION['user']['perms'])";
			
			/* These attributes are special because they need $context->getVar() and the attrib name in them */
			foreach($attribs as $key => $val) {
				if(isset($element->attribs[$val])) {

					/* are we looking for the generalized category, or variables within? */
					$attrib		= isset($element->attribs['var']) ? NULL : $val;
					$variable	= isset($element->attribs['var']) ? $element->attribs['var'] : "'$attrib'. \$context->getVar('". $element->attribs[$attrib] ."')";

					/* Make the *query* to check the permissions */
					$query .= " && (isset(\$_SESSION['user']['maps']['". $key ."'][\$context->getVar('". $element->attribs[$attrib] ."')][$variable]['". $element->attribs['method'] ."'])";
					$query .= " && \$_SESSION['user']['maps']['". $key ."'][\$context->getVar('". $element->attribs[$attrib] ."')][$variable]['". $element->attribs['method'] ."'] <= \$_SESSION['user']['perms'])";
				}
			}
			//echo '<pre>'. $query .'</pre>';
			return "<?php if($query): ?>";
		}
		return "<h1>Missing (VAR, CATEGORY, FORUM, GROUP) or METHOD for conditional MAPS statement.</h1>";
	}
	function getClose(&$element) {
		return "<?php endif; ?>";
	}
}

?>