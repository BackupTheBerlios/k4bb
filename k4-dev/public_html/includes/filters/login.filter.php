<?php

/**
 * This will check if the user is not logged in, and 
 * then will validate the forum's cookies to try to 
 * auto log him in
 * @author		Geoffrey Goodman
 * @author		Peter Goodman
 * @see			Filter
 */
class LoginFilter extends Filter {
	public function Execute(Template $template, Session $session, & $cookie, & $post, & $get) {
		if (!$session['user'] instanceof User) {
			$session['user']	= new Guest;

			$id	= $session['user']->ValidateLoginKey($cookie);

			if ($id	!== FALSE) {
				$session['rememberme']	= 'on';
				$session['user']		= new Member($id);
				$session['user']->Login();
			}
		}

		if ($session['rememberme'] == 'on') {
			unset($session['rememberme']);

			setcookie('name', $session['user']['name'], time() + (3600 * 24 * 60));
			setcookie('key', $session['user']->GenerateLoginKey(), time() + (3600 * 24 * 60));
		}

		if ($session['rememberme'] == 'off') {
			unset($session['rememberme']);

			setcookie('name', '', time() - 3600);
			setcookie('key', '', time() - 3600);
		}

		return TRUE;
	}
}

?>