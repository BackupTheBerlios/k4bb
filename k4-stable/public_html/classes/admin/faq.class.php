<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     faq.class.php
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

class AdminAddFAQCat extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			if(!isset($request['name']) || !@$request['name'])
				return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_NAME']), $template);
			
			$name = htmlspecialchars($request['name']);
			if(DBA::Open()->Query("INSERT INTO ". FAQ_CATEGORIES ." (name) VALUES ('{$name}')"))
				header("Location: admin.php?act=faq");
		}
	}
}

class AdminDelFAQCat extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			if(!isset($request['id']) || !@$request['id'])
				return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_NAME']), $template);
			
			$id = intval($request['parent_id']);

			if(DBA::Open()->Query("DELETE FROM ". FAQ_CATEGORIES ." WHERE id = $id")) {
				if(DBA::Open()->Query("DELETE FROM ". FAQ ." WHERE parent_id = $id"))	
					header("Location: admin.php?act=faq");
			}
		}
	}
}

class AdminDelFAQ extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			if(!isset($request['id']) || !@$request['id'])
				return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_NAME']), $template);
						
			$id = intval($request['id']);
			
			if(DBA::Open()->Query("DELETE FROM ". FAQ ." WHERE parent_id = $id"))	
				header("Location: admin.php?act=faq");
		}
	}
}

class AdminAddFAQ extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			if(!isset($request['parent_id']) || !@$request['parent_id'])
				return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_CATEGORY']), $template);
			if(!isset($request['question']) || !@$request['question'])
				return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_QUESTION']), $template);
			if(!isset($request['question']) || !@$request['message'])
				return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_ANSWER']), $template);
			
			$dba		= DBA::Open();

			$parent_id	= intval($request['parent_id']);
			$question	= $dba->Quote($request['question']);
			$parser		= new BBParser($request['message']);
			$answer		= $dba->Quote($parser->Execute());
			if(intval($request['add']) == 1) {
				if($dba->Query("INSERT INTO ". FAQ ." (parent_id, question, answer) VALUES ($parent_id, '{$question}', '{$answer}')"))
					header("Location: admin.php?act=faq");
			} else {
				$id = intval($request['id']);
				if($dba->Query("UPDATE ". FAQ ." SET parent_id = $parent_id, question = '{$question}', answer = '{$answer}' WHERE id = $id"))
					header("Location: admin.php?act=faq");
			}
		}
	}
}

?>