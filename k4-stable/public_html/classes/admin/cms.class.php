<?php

class AdminCMSHelp extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content		= array('file' => 'admin/admin.html');
			$template->admin_panel = array('file' => 'admin/cms/help.html');
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		return TRUE;
	}
}

?>