<?php
/**
* k4 Bulletin Board, controller.class.php
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
* @author Geoffrey Goodman
* @version $Id: controller.class.php,v 1.3 2005/04/13 02:53:33 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

/**
 * Controller class; amalgamates the events, session and template variables
 * @author					Geoffrey Goodman
 * @param mixed default		The default event to be handled
 * @param array events		An array of the events of the 
 *							current file calling the constructor
 * @param array filters		An array of the filters being applied
 *							to the current instance of the controller
 * @param array	get			The $_GET variables
 * @param array post		The $_POST variables
 * @param array cookie		The $_COOKIE variables
 * @param array session		The $session variables
 * @param mixed timer		The Timer object
 */
class Controller {
	var $default;

	var $events				= array();
	var $filters			= array();

	var $timer;

	var $get;
	var $post;
	var $cookie;
	var $session;
	
	/**
	 * @param mixed default		The default event to be called
	 * @see Event
	 */
	function Controller(&$default) {
		$this->default	= &$default;
		
		$this->cookie	= $_COOKIE;
		$this->post		= $_POST;
		$this->get		= $_GET;
	}
	
	/**
	 * Function to add an event to the events array
	 * @param string action		The action that needs to be called that
	 *							will trigger this event
	 * @param mixed event		The event to be called when the action is triggered
	 * @return boolean true
	 * @see Event
	 */
	function AddEvent($action, &$event) {
		if(is_a($event, 'Event'))
			$this->events[$action]	= &$event;

		return TRUE;
	}
	
	/**
	 * Function to add a filter to the controller
	 * @param mixed filter	The class that this filter uses
	 * @return				boolean true
	 * @see Filter
	 */
	function AddFilter(&$filter) {
		if(is_a($filter, 'Filter'))
			$this->filters[]	= &$filter;

		return TRUE;
	}
	
	/**
	 * The function which calls everything from the controller
	 * and render the template(s).
	 * @param mixed template	The template variable, holds all current
	 *							template information
	 * @param mixed session		The session variable, holds all current
	 *							session information
	 * @return					method template render
	 * @see Template
	 * @see Session
	 */
	function Execute(&$template) {
		
		global $_DBA;

		/**
		 * General Variable Setting
		 */
		
		/* Start the timer */
		$this->timer		= &new Timer;

		/* Merge the post and get arrays */
		$request			= array_merge($this->get, $this->post, $this->cookie);
		$result				= FALSE;
		
		/* Get the act var */
		$act_var			= get_setting('application', 'action_var') or
		$act_var			= 'act';

		/* get the session and user variables */
		$session			= &Globals::getGlobal('session');
		$user				= &Globals::getGlobal('user');
		
		/**
		 * Member/Guest Settings
		 */

		/* Figure out which styleset, imageset and templateset to use */
		$styleset			= (is_a($session['user'], 'Member') && $user['styleset'] != '') || (is_a($session['user'], 'Guest') && $user['styleset'] != '') ? $user['styleset'] : $template->getVar('styleset');
		$imageset			= is_a($session['user'], 'Member') && $user['imgset'] != '' ? $user['imgset'] : $template->getVar('imageset');
		$templateset		= is_a($session['user'], 'Member') && $user['tplset'] != '' ? $user['tplset'] : $template->getVar('templateset');
		
		/* Set the style, template and image sets */
		$this->template->setVar('css_styles', get_cached_styleset($styleset, $template->getVar('styleset')));
		
		$template_dir		= FORUM_BASE_DIR . DIRECTORY_SEPARATOR . 'templates'. DIRECTORY_SEPARATOR;
		$imgs_dir			= FORUM_BASE_DIR . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;

		/* Should we get the template set that goes with this styleset? */
		$templateset		= is_dir($template_dir . $styleset) ? $template_dir . $styleset : $template_dir . $templateset;
		
		/* Should we get the image set that goes with this styleset? */
		$imageset			= is_dir($imgs_dir . $styleset) ? $styleset : $imageset;
		
		/* Check to see if our templates directory exists */
		if(!is_dir($templateset))
			exit('Invalid template set for: '. $templateset);
		
		/* Check to see if our images directory exists */
		if(!is_dir($imgs_dir . $imageset))
			exit('Invalid image set for: '. $imageset);

		/* Set the template an image sets */
		$this->template->setDirname($templateset);
		$this->template->setVar('IMG_DIR', $imageset);

		/* Determine which language to get, and then include the appropriate file */
		$language			= is_a($session['user'], 'Member') ? $user['language'] : strtolower(get_setting('application', 'lang'));
		
		/* Check to see if this is an invalid language file */
		if(!file_exists(FORUM_BASE_DIR. '/includes/lang/'. $language .'/lang.php'))
			exit('Invalid Language file.');
		
		/* Require the lnaguage file */
		require FORUM_BASE_DIR. '/includes/lang/'. $language .'/lang.php';

		/* Check if the language function exists */
		if(!function_exists('return_language'))
			exit('Invalid Language file.');
		
		/* Get the language pack into a variable array */			
		$lang				= return_language();

		/* Set the locale to which language we are using */
		setlocale(LC_ALL, $lang['locale']);

		/* Set the language array */
		$template->setVarArray($lang);
		
		/* Memory Saving */
		unset($lang);

		
		/**
		 * Event Execution
		 */

		/* get the result of our event call */
		if (isset($request[$act_var]) && isset($this->events[$request[$act_var]]))
			$result	= $this->events[$request[$act_var]]->Execute(&$template, $request, &$_DBA, &$session, &$user);
		
		/* If the result is false, execute our defaultevent class */
		if ($result	== FALSE)
			$this->default->Execute(&$template, $request, &$_DBA, &$session, &$user);
		

		/**
		 * User Information
		 */

		/* Clear the session and user variables */
		$session					= &Globals::getGlobal('session');
		$user						= &Globals::getGlobal('user');
		

		/**
		 * Filters
		 */

		/* Apply each Filter to the request */
		for($i = 0; $i < count($this->filters); $i++)
			$this->filters[$i]->Execute(&$template, &$session, $this->cookie, $this->post, $this->get);
		

		/* If the user is logged in, set all of his user info to the template */
		if(is_a($session['user'], 'Member'))
			foreach($user as $key => $val)
				$this->template->setVar('user_'. $key, $val);

		/* Set the number of queries */
		$template->setVar('num_queries', $_DBA->num_queries);
		
		/* Set the Load time */
		$this->template->setVar('load_time', $this->timer->__toString());

		/* Render the template */
		$template->Render();
	}
}

/**
 * @author			Geoffrey Goodman
 * @param template	Template instance, passes the template
 *					information to the event
 * @param session	Session instance, passes the session
 *					information to the event
 * @param request	Passes the request array to the event
 * @see Template
 * @see Session
 * @see Database
 */
class Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) { return TRUE; }
}

/**
 * @author			Geoffrey Goodman
 * @param template	Template instance, passes the template
 *					information to the filter
 * @param session	Session instance, passes the session
 *					information to the event
 * @param cookie	Reference to the cookie array
 * @param post		Reference to the post array
 * @param get		Reference to the get array
 * @see Template
 * @see Session
 */
class Filter {
	function Execute(&$template, &$cookie, &$post, &$get) { return TRUE; }
}

?>