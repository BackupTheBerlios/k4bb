<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     admin.php
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

require 'forum.inc.php';
require 'classes/admin.php';

class DefaultEvent extends Event {
	public function Execute(Template $template, Session $session, $request) {		
		
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			//$template->content = array('file' => 'admin/admin.html');
			//$template->admin_panel = array('file' => 'admin/default.html');
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		return TRUE;
	}
}

$app	= new Forum_Controller('admin/frameset.html');

$app->ExecutePage();

?>