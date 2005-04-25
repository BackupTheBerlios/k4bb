<?php
/**
* k4 Bulletin Board, users.class.php
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
* @version $Id: users.class.php,v 1.2 2005/04/25 19:51:54 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

/**
 * Get the highest permissioned group that a user belongs to
 */
function get_user_max_group($temp, $all_groups) {
	$groups				= @unserialize($temp['usergroups']);
			
	if(is_array($groups)) {
		
		
		/**
		 * Loop through all of the groups and all of this users groups
		 * Find the one with the highest permission and use it as the color
		 * for this person's username. The avatar is separate because not all
		 * groups will automatically have avatars, so get the highest possible
		 * set avatar for this user.
		 */
		foreach($groups as $g) {
			
			/* If the group variable isn't set, set it */
			if(!isset($group) && isset($all_groups[$g]))
				$group	= $all_groups[$g];
			
			if(!isset($avatar) && isset($all_groups[$g]) && $all_groups[$g]['avatar'] != '')
				$avatar	= $all_groups[$g]['avatar'];

			/**
			 * If the perms of this group are greater than that of the $group 'prev group', 
			 * set is as this users group 
			 */
			if(@$all_groups[$g]['max_perm'] > @$group['max_perm']) {
				$group	= $all_groups[$g];
				
				/* Give this user an appropriate group avatar */
				if($all_groups[$g]['avatar'] != '')
					$avatar	= $all_groups[$g]['avatar'];
			}
		}
	}
	
	$group['avatar']		= isset($avatar) ? $avatar : '';

	return $group;
}

?>