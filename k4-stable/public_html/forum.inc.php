<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     forum.inc.php
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

/* ************************************ DO NOT CHANGE THE VERSION ****************************/
define('VERSION', '1.3');

/* do Checks if k4 is installed or not */
if(file_exists('Install')) {
	header("Location: Install/install.php");
	exit();
} else if(!file_exists('install.lock.php')) {
	exit('k4 has not been properly installed.');
}

/* Require some files */

include 'install.lock.php';
require PC_REL_URL;
require 'tables.inc.php';
require 'classes.php';
require 'permissions.inc.php';

require 'lang/'. get_setting('config', 'lang') .'/lang.php';
require 'settings.inc.php';


/* Create the ancestors bar */
function CreateAncestors($template, $row = FALSE, $suspend = 0) {
	$dba = DBA::Open();

	//$base = $dba->GetRow("SELECT * FROM ". FORUMS ." WHERE row_left = 1");

	$id = !isset($_GET['id']) ? 1 : intval($_GET['id']);

	if(isset($row) && is_array($row)) {
		$template->ancestors			= new ForumAncestors($id, FALSE, $suspend);
		$template['current_location']	= stripslashes($row['name']);
		$template['title']				= stripslashes($row['name']);
	} else if(isset($row) && is_string($row)) {
		$template->ancestors			= array(array('name'=>$template['bbtitle'],'id'=>1));
		$template['current_location']	= stripslashes($row);
		$template['title']				= stripslashes($row);
	} else {
		$template->ancestors			= array(array('name'=>$template['bbtitle'],'id'=>1));
		$template['current_location']	= $template['L_HOME'];
		$template['title']				= $template['bbtitle'];
	}

	/* Set the allaround needed forum name and description */
	$template['forum_name']			= stripslashes($template['bbtitle']);
	$template['forum_description']	= stripslashes($template['bbdescription']);
	
	return $template;

}

/* The ancestors function which is used when viewing a single thread */
function ThreadAncestors($template, $suspend) {
	$forum	= new Forum;
	
	$id = isset($_GET['id']) ? intval($_GET['id']) : 1; // todo error checking
	$row = DBA::Open()->GetRow("SELECT * FROM ". POSTS ." WHERE id = $id");
	
	$template['current_location'] = stripslashes($row['name']);
	$template['title']		      = stripslashes($row['name']);
	
	$template->ancestors = new ForumAncestors($id, "SELECT * FROM ". FORUMS ." WHERE row_left < (SELECT row_left FROM ". POSTS ." WHERE id = ". $id .") AND row_right > (SELECT row_right FROM ". POSTS ." WHERE id = ". $id. ")", $suspend);

	return $template;
}

/* This adds the language pack to the $template variable basically */
class LanguageFilter extends Filter {
	public function Execute(Template $template, Session $session, & $cookie, & $post, & $get) {
		global $lang;
		if(is_array($lang) && !empty($lang)) {
			foreach($lang as $key => $val) {
				$template[$key] = $val;
			}
		} else {
			$template = array();
		}
	}
}

/* This will check if the user is not logged in, and then will validate the forum's cookies to try to auto log him in */
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

class DefaultVars extends Filter {
	public function Execute(Template $template, Session $session, & $cookie, & $post, & $get) {
		//$forum	= new Forum;
		
		if($session['user'] instanceof Member)
			$template['user_name'] = $session['user']['name'];

		$template = CreateAncestors($template);
		
		return TRUE;
	}
}

/* Add the filters */
class Forum_Controller extends Controller {
	protected $template;
	protected $timer;

	public function __construct($template) {

		$this->timer = new Timer;
		
		if (!class_exists('DefaultEvent'))
			exit('Yahtzee!');

		parent::__construct(new DefaultEvent);

		$this->template	= new Template($template);

		$this->AddFilter(new LanguageFilter);
		$this->AddFilter(new LoginFilter);
		$this->AddFilter(new DefaultVars);
	}

	public function AddAction($name, Action $action) {
		$this->template[$name]	= $action;
	}

	public function ExecutePage() {
		//$timer		= new Timer;
		$session	= new Session;
		
		$dba = DBA::Open();
		global $settings;
		
		foreach($settings as $key=>$val)
			$this->template[$key] = $val;
		$this->template['date']			= strftime("%a, %B %d");
		$this->template['num_queries']	= $dba->NumQueries();
		$this->template['IMG_DIR']		= !($session['user'] instanceof Member) ? $this->template['imageset'] : $session['user']['imgset'];
		$this->template['VERSION']		= VERSION;
		
		if($session['user'] instanceof Member) {
			if($this->template['checknewpm'] == 1) {			
				if($dba->GetValue("SELECT COUNT(*) FROM ". PMSGS ." WHERE (poster_id = ". $session['user']['id'] ." AND saved = 1 AND new_reply = 1) OR ((member_id = ". $session['user']['id'] ." OR member_id = 0) AND member_has_read = 0)") == 0)
					$this->template->new_pms = array('hide' => TRUE);
			}
		} else {
			$this->template->new_pms = array('hide' => TRUE);
		}
		/* Navigation */
		if($this->template['enablememberlist'] == 0)
			$this->template->memberlist_link = array('hide' => TRUE);
		if($this->template['calendarenabled'] == 0)
			$this->template->calendar_link = array('hide' => TRUE);

		$default_theme					= $this->template['styleset'];
		$styleset						= !($session['user'] instanceof Member) ? $default_theme : $dba->GetValue("SELECT name FROM ". STYLES ." WHERE id = ". $session['user']['styleset']);
		$this->template['imageset']		= !($session['user'] instanceof Member) ? $this->template['imageset'] : $session['user']['imgset'];

		$this->template->css_styles		= $dba->Query("SELECT * FROM ". CSS ." WHERE style_id = (SELECT id FROM ". STYLES ." WHERE name = '". $styleset ."') ORDER BY name ASC");
		
		$this->template['load_time']	= $this->timer->__toString();

		parent::Execute($this->template, $session);
	}
}



?>