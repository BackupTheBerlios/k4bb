<?php
/**
* k4 Bulletin Board, member.php
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
* @version $Id: member.php,v 1.5 2005/05/16 02:10:03 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

ob_start();

require 'forum.inc.php';

class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_USERGROUPS, $_USERFIELDS;
		

		/**
		 * Error checking on this member
		 */
		if(!isset($request['id']) || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_USERDOESNTEXIST'), TRUE);
			return TRUE;
		}

		$member = $dba->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". USERS ." u LEFT JOIN ". USERINFO ." ui ON u.id = ui.user_id WHERE u.id = ". intval($request['id']));

		if(!$member || !is_array($member) || empty($member)) {
			/* set the breadcrumbs bit */
			$template	= BreadCrumbs($template, $template->getVar('L_INFORMATION'));
			$template->setInfo('content', $template->getVar('L_USERDOESNTEXIST'), TRUE);
			return TRUE;
		}
		
		$member['num_topics']		= $dba->getValue("SELECT COUNT(*) FROM ". TOPICS ." WHERE poster_id = ". intval($member['id']));
		$member['num_replies']		= $dba->getValue("SELECT COUNT(*) FROM ". REPLIES ." WHERE poster_id = ". intval($member['id']));
		
		/**
		 * Get and set some user/forum statistics
		 */
		$user_created				= (time() - iif($member['created'] != 0, $member['created'], time()));
		$postsperday				= $user_created != 0 ? round((($member['num_posts'] / ($user_created / 86400))), 3) : 0;
		
		$member['posts_per_day']	= sprintf($template->getVar('L_POSTSPERDAY'), $postsperday );

		$num_posts					= ($_DATASTORE['forumstats']['num_topics'] + $_DATASTORE['forumstats']['num_replies']);
		$member['posts_percent']	= $num_posts != 0 && $member['num_posts'] != 0 ? sprintf($template->getVar('L_OFTOTALPOSTS'), round((($member['num_posts'] / $num_posts) * 100), 3) .'%' ) : sprintf($template->getVar('L_OFTOTALPOSTS'), '0%');
		
		$group						= get_user_max_group($member, $_USERGROUPS);
		$member['group_color']		= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
		
		$member['online']			= (time() - ini_get('session.gc_maxlifetime')) > $member['seen'] ? 'offline' : 'online';
		
		$groups						= $member['usergroups'] != '' ? iif(!unserialize($member['usergroups']), force_usergroups($member), unserialize($member['usergroups'])) : array();
		

		/**
		 * Get and set the user groups for this member
		 */
		$usergroups					= array();
		foreach($groups as $id) {
			if(isset($_USERGROUPS[$id]) && is_array($_USERGROUPS[$id]) && !empty($_USERGROUPS[$id])) {
				$usergroups[]		= $_USERGROUPS[$id];
			}
		}

		$template->setList('member_usergroups', new FAArrayIterator($usergroups));

		foreach($member as $key => $val)
			$template->setVar('member_'. $key, $val);
		
		/**
		 * Get the custom user fields for this member
		 */
		$fields = array();
		foreach($_USERFIELDS as $field) {
				
			if($field['display_profile'] == 1) {

				if(isset($member[$field['name']]) && $member[$field['name']] != '') {
					switch($field['inputtype']) {
						default:
						case 'text':
						case 'textarea':
						case 'select': {
							$field['value']		= $member[$field['name']];
							break;
						}
						case 'multiselect':
						case 'radio':
						case 'check': {
							$field['value']		= implode(", ", iif(!unserialize($member[$field['name']]), array(), unserialize($member[$field['name']])));
							break;
						}
					}
					$fields[] = $field;
				}
			}
		}

		if(count($fields) > 0) {
			if($fields % 2 == 1) {
				$fields[count($fields)-1]['colspan'] = 2;
			}

			$template->setList('member_profilefields', new FAArrayIterator($fields));
		}
		
		/**
		 * Set the info we need
		 */
		$template	= BreadCrumbs($template, $template->getVar('L_USERPROFILE'));
		$template->setFile('content', 'member_profile.html');
		
		return TRUE;
	}
}

class ForumLogin extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		/* Create the ancestors bar */
		$template = BreadCrumbs($template, $template->getVar('L_LOGIN'));
		
		/* Check if the user is logged in or not */
		if(!is_a($session['user'], 'Member')) {
			
			
			$template->setFile('content', 'login_form.html');
		
		} else {
			$template->setInfo('content', $template->getVar('L_YOUARELOGGEDIN'));
		}
		
		return TRUE;
	}
}

/* Set our wrapper template */
$app	= new Forum_Controller('forum_base.html');

/* Apply all of the events */
$app->AddEvent('login', new ForumLogin);
$app->AddEvent('register', new ForumRegisterUser);
$app->AddEvent('register_user', new ForumInsertUser);
$app->AddEvent('activate_accnt', new ValidateUserByEmail);
$app->AddEvent('login_user', new LoginEvent);
$app->AddEvent('logout', new LogoutEvent);
$app->AddEvent('remindme', new RemindMeEvent);
$app->AddEvent('mail', new EmailUser);
$app->AddEvent('email_user', new SendEmailToUser);
$app->AddEvent('findposts', new FindPostsByUser);

$app->ExecutePage();

ob_flush();

?>