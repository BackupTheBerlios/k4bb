<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     censoring.class.php
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

class AdminAddBadWord extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$word			= htmlspecialchars($request['word']);
			$replacement	= htmlspecialchars($request['replacement']);
			$method			= intval($request['method']);
			if(DBA::Open()->Query("INSERT INTO ". BADWORDS ." (word, replacement, method) VALUES ('{$word}', '{$replacement}', $method)"))
				header("Location: admin.php?act=censoring");
		}
		return TRUE;
	}
}

class AdminUpdateBadWord extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$word			= htmlspecialchars($request['word']);
			$replacement	= htmlspecialchars($request['replacement']);
			$method			= intval($request['method']);
			$id				= intval($request['id']);
			if(DBA::Open()->Query("UPDATE ". BADWORDS ." SET word = '{$word}', replacement = '{$replacement}', method = $method WHERE id = $id"))
				header("Location: admin.php?act=censoring");
		}
		return TRUE;
	}
}

class AdminDeleteBadWord extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$id = intval($request['bwid']);
			if(DBA::Open()->Query("DELETE FROM ". BADWORDS ." WHERE id = $id"))
				header("Location: admin.php?act=censoring");
		}
		return TRUE;
	}
}

?>