<?php
/**
* k4 Bulletin Board, forum.inc.php
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
* @version $Id: forum.inc.php,v 1.5 2005/05/12 01:33:21 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

/* General Constants */
define('VERSION',			'2.0');
define('IN_K4',				TRUE);
define('FORUM_BASE_DIR',	dirname(__FILE__));

/* Require some files */
require FORUM_BASE_DIR. '/config.php';
require FORUM_BASE_DIR. '/includes/init.php';


/* Set some ini variables for the output of argument separators - REQUIRED */
ini_set('arg_separator.output', '&amp;');
ini_set('url_rewriter.tags', 'a=href,area=href,frame=src,input=src,fieldset=');

/* Turn off those nasty quotes.. mwahaha */
set_magic_quotes_runtime(0);

/**
 * Controller to add events to, this is the 
 * frontend to the acutal Controller class
 *
 * @author					Peter Goodman
 * @param mixed template	Template instance
 * @param mixed session		Session instance
 * @param mixed timer		Timer instance, gets
 *							the page load time
 * @see Controller
 */
class Forum_Controller extends Controller {
	var $template;
	var $session;
	var $timer;
	
	/**
	 * Forum_Controller constructor
	 * @param string template	The base template to use for
	 *							the specific page.
	 */
	function Forum_Controller($template) {

		/* Globalize the settings and config arrays */
		global $_SETTINGS, $_USERGROUPS, $_ALLFORUMS;

		/* Make sure the default event class exists */
		if (!class_exists('DefaultEvent'))
			exit('Yahtzee!');
		
		/* Call the Controller Constructor */
		parent::Controller(new DefaultEvent);

		/* Create a new instance of Template */
		error::reset();
		$this->template		= &new Template($template);
		
		if(error::grab())
			critical_error();

		/* Set all of the setting values to the template */
		$this->template->setVarArray($_SETTINGS);
		
		/* Set the Jump To Box */
		$jump_to			= &new AllForumsIterator($_ALLFORUMS);
		$this->template->setList('all_forums', $jump_to);
		
		/* Add the usergroups to the template */
		$usergroups			= &new FAArrayIterator($_USERGROUPS);
		$this->template->setList('usergroups', $usergroups);

	}
	
	/**
	 * Add a premade action variable to the template
	 * @param string name		The name of the action, what needs
	 *							to be called in the GET variables
	 * @param mixed action		The action object for this specific action
	 */
	function AddAction($name, &$action) {
		$this->template->setVar($name, $action);
	}

	function ExecutePage() {
		
		$this->template->setVar('VERSION',		VERSION);
		
		parent::Execute($this->template);
	}
}



?>