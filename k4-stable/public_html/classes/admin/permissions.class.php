<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     permissions.class.php
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

class AdminModPermissions extends Event {
	public function Execute(Template $template, Session $session, $request) {		
		$this->dba = DBA::Open();
		
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content = array('file' => 'admin/admin.html');
			$template->admin_panel = array('file' => 'admin/mod_permissions.html');

			if(isset($request['forum']) && intval($request['forum']) != 0) {
				$forum = $this->dba->GetRow("SELECT * FROM ". FORUMS ." WHERE id = ". intval($request['forum']) );
				
				/* Set the template variables */
				$template['forum_id']		= $forum['id'];
				$template['can_view']		= $forum['can_view'];
				$template['can_read']		= $forum['can_read'];
				$template['can_post']		= $forum['can_post'];
				$template['can_reply']		= $forum['can_reply'];
				$template['can_edit']		= $forum['can_edit'];
				$template['can_sticky']		= $forum['can_sticky'];
				$template['can_announce']	= $forum['can_announce'];
				$template['can_vote']		= $forum['can_vote'];
				$template['can_pollcreate'] = $forum['can_pollcreate'];
				$template['can_attach']		= $forum['can_attach'];
			} else {
				return new Error($template['L_FORUMDOESNTEXIST'], $template);
			}
		}
		return TRUE;
	}
}

class AdminUpdatePermissions extends Event {
	public function Execute(Template $template, Session $session, $request) {		
		
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$forum = new Forum;
			if($forum->setForumpermissions(intval($request['forum_id']), $request)) {
				header("Location: admin.php?act=permissions");
			}
		}
		return TRUE;
	}
}

?>