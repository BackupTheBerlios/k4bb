<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     member.php
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

class DefaultEvent extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		// view a member's profile
		if(isset($request['id'])) {
			$id = intval($request['id']);
			
			/* Chck if we're trying to look at the gues user */
			if($id == 0)
				return new Error($template['L_USERDOESNTEXIST'], $template);
			
			/* Get our user from the db */
			$user = DBA::Open()->GetRow("SELECT * FROM ". USERS ." WHERE id = $id");
			
			/* If our user has admin perms, use a different tamplate */
			if($session['user']['perms'] & ADMIN) {
				$template->content = array('file' => 'admin/member.html');
			} else {
				$template->content = array('file' => 'member.html');
			}
			
			/* set the user info template variables */
			$template['id']			= $user['id'];
			$template['name']		= $user['name'];
			$template['email']		= $user['email'];
			$template['posts']		= $user['posts'];
			$template['created']	= relative_time($user['created']);
			$template['rank']		= $user['rank'];

			if($template['displayemails'] == 1)
				$template->email_link = array('hide' => TRUE);
			else if($template['displayemails'] == 0)
				$template->email_address = array('hide' => TRUE);
			
			/* Set the last seen time */
			$user['seen']		= $user['seen'] == 0 ? time() : $user['seen'];
			$user['last_seen']	= $user['last_seen'] == 0 ? time() : $user['last_seen'];
			$template['seen']	= ($user['seen'] >= (time() - Lib::GetSetting('sess.gc_maxlifetime'))) ? relative_time($user['seen']) : relative_time($user['last_seen']);
			
			/* Set the user's online status field on the template */
			if($user['seen'] >= (time() - Lib::GetSetting('sess.gc_maxlifetime')))
				$template['online_status'] = $template['L_ONLINE'];
			else
				$template['online_status'] = $template['L_OFFLINE'];
			

		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class ForumLogin extends Event {
	public function Execute(Template $template, Session $session, $request) {
		

		/* Create the ancestors bar */
		$template = CreateAncestors($template, $template['L_LOGIN']);
		
		/* Check if the user is logged in or not */
		if(!($session['user'] instanceof Member)) {
			$template->content	= array('file' => 'login_form.html');
		} else {
			return new Error($template['L_YOUARELOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class ForumRegister extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_REGISTER']);
		
		if(isset($session['post_vars'])) {
			$template['name'] = @$session['post_vars']['name'];
			$template['email'] = @$session['post_vars']['email'];
			unset($session['post_vars']);
		}
		
		/* Check if the person isn't logged in, and then disply the register form */
		if(!($session['user'] instanceof Member)) {
			$template->content	= array('file' => 'register_form.html');
		} else {
			return new Error($template['L_CANTREGISTERLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class MemberProfile extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_PROFILE']);
		
		/* If the user is allowed to see his/her/any user CP */
		if(($session['user'] instanceof Member)) {
			
			/* Private Messages folder */
			$template->pmsg_folders = new PMFolders;

			/* Assign some template variables */
			$template['id']			= $session['user']['id'];
			$template['username']	= $session['user']['name'];
			$template['email']		= $session['user']['email'];
			$template['homepage']	= $session['user']['homepage'];
			
			if($session['user']['signature'] != '') {
				$parser					= new BBParser($session['user']['signature']);
				$template['signature']	= $parser->Revert($session['user']['signature']);
			} else {
				$template['signature']	= $session['user']['signature'];
			}
			$template['icq']		= $session['user']['icq'];
			$template['aim']		= $session['user']['aim'];
			$template['msn']		= $session['user']['msn'];
			$template['yahoo']		= $session['user']['yahoo'];
			$template['location']	= $session['user']['location'];
			$template['occupation']	= $session['user']['occupation'];
			$template['interests']	= $session['user']['interests'];
			$template['biography']	= $session['user']['biography'];
			
			/* Get the birthday info */
			$template['year']		= $session['user']['birthday'] != 0 ? date("Y", $session['user']['birthday']) : NULL;
			$template['month']		= $session['user']['birthday'] != 0 ? date("n", $session['user']['birthday']) : -1;
			$template['day']		= $session['user']['birthday'] != 0 ? date("j", $session['user']['birthday']) : -1;

			$template->content	= array('file' => 'usercp.html');
			$template->usercp	= array('file' => 'usercp/profile.html');
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class MemberUpdateProfile extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_PROFILE']);
		
		/* If the user is allowed to see his/her/any user CP */
		if(($session['user'] instanceof Member)) {
			
			/* Connect to the db */
			$dba = DBA::Open();

			/* Quote out the REQUEST fields */
			foreach($request as $key=>$val) {
				$request[$key] = $request[$key] != '' ? $dba->Quote($val) : '';
			}
			
			if(check_mail($request['email']) != $request['email'])
				return new Error($template['L_INVALIDEMAIL'], $template);
			
			if($request['signature'] != '') {
				if($template['allowbbcode'] == 1) {
					$parser = new BBParser($request['signature']);
					if($template['allowbbimagecode'] != 1)
						$parser->addOmit('img', 'img');
					$request['signature'] = $parser->Execute();
				}
			}

			if($request['month'] != -1 && $request['day'] != -1 && $request['year'] != '') {
				$birthday = mktime(0,0,0,intval($request['month']),intval($request['day']),intval($request['year']));
			} else {
				$birthday = 0;
			}
			
			if($dba->Query("UPDATE ". USERS ." SET email = '". $request['email'] ."', signature = '". $request['signature'] ."', birthday = '". $birthday ."', homepage = '". $request['homepage'] ."', icq = '". $request['icq'] ."', aim = '". $request['aim'] ."', msn = '". $request['msn'] ."', yahoo = '". $request['yahoo'] ."', location = '". $request['location'] ."', occupation = '". $request['occupation'] ."', interests = '". $request['interests'] ."', biography = '". $request['biography'] ."' WHERE id = ". intval($request['id']) ))
				return new Error($template['L_PROFILESUCCESS'] .'<meta http-equiv="refresh" content="2; url=member.php?act=profile">', $template);

		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class MemberSettings extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_PROFILE']);
		
		/* If the user is allowed to see his/her/any user CP */
		if(($session['user'] instanceof Member)) {
			
			/* Private Messages folder */
			$template->pmsg_folders = new PMFolders;
			
			/* Assign some template variables */
			$template['username']	= $session['user']['name'];
			$template['id']			= $session['user']['id'];

			$template->imagesets	= Dir::Open('Images')->getFolders();
			$template->languages	= Dir::Open('lang')->getFolders();

			$template['imageset']	= $session['user']['imgset'];
			$template['styleset']	= $session['user']['styleset'];
			$template['thread_display'] = @intval($session['user']['thread_display']);
			$template['invisible']	= intval($session['user']['invisible']);

			$template->content	= array('file' => 'usercp.html');
			$template->usercp	= array('file' => 'usercp/settings.html');
			
			$dba = DBA::Open();

			$template->stylesets = $dba->Query("SELECT * FROM ". STYLES);

		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class MemberUpdateSettings extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_PROFILE']);
		
		/* If the user is allowed to see his/her/any user CP */
		if(($session['user'] instanceof Member)) {
			
			/* Connect to the db */
			$dba = DBA::Open();

			/* Quote out the REQUEST fields */
			foreach($request as $key=>$val) {
				$request[$key] = $request[$key] != '' ? $dba->Quote($val) : '';
			}

			if(is_dir('Images/'. $request['imgset'])) {
			
				if($dba->Query("UPDATE ". USERS ." SET thread_display = ". intval($request['thread_display']) .", invisible = ". intval($request['invisible']) .", styleset = ". intval($request['styleset']) .", imgset = '". $request['imgset'] ."' WHERE id = ". intval($request['id']) ))
					return new Error($template['L_SETTINGSSUCCESS'] .'<meta http-equiv="refresh" content="2; url=member.php?act=settings">', $template);
			} else {
				return new Error($template['L_INVALIDIMGSET'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class MemberAvatar extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_PROFILE']);
		
		/* If the user is allowed to see his/her/any user CP */
		if(($session['user'] instanceof Member)) {

			/* Private Messages folder */
			$template->pmsg_folders = new PMFolders;
			
			/* Assign some template variables */
			$template['username']	= $session['user']['name'];
			$template['avatar']		= intval($session['user']['avatar']);
			$template['avatar_img']	= intval($template['avatar']) == 1 ? '<img src="Uploads/Avatars/'. $session['user']['id'] .'.gif" border="0" alt="" />' : NULL;

			$template->content	= array('file' => 'usercp.html');
			$template->usercp	= array('file' => 'usercp/avatar.html');
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class MemberUpdateAvatar extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_PROFILE']);
		
		/* If the user is allowed to see his/her/any user CP */
		if(($session['user'] instanceof Member)) {
			if(isset($request['avatar'])) {
				$use = intval($request['avatar']);
				if($use == 1) {
					if(!file_exists('Uploads/Avatars/'. $session['user']['id'] .'.gif'))
						$use = 0;
				}
				
				if(DBA::Open()->Query("UPDATE ". USERS ." SET avatar = $use WHERE id = ". $session['user']['id'] ))
					return new Error($template['L_AVATARSUCCESS'] .'<meta http-equiv="refresh" content="2; url=member.php?act=avatar">', $template);
			} else {
				return new Error($template['L_ERRORUPDATINGAVATAR'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class MemberPassword extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_PROFILE']);
		
		/* If the user is allowed to see his/her/any user CP */
		if(($session['user'] instanceof Member)) {

			/* Private Messages folder */
			$template->pmsg_folders = new PMFolders;

			$template['username']	= $session['user']['name'];
			$template->content	= array('file' => 'usercp.html');
			$template->usercp	= array('file' => 'usercp/password.html');
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class MemberUpdatePassword extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		$this->dba = DBA::Open();

		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_PROFILE']);
		
		/* Quote all of the REQUEST variables */
		foreach($request as $key => $val) {
			$request[$key] = $this->dba->Quote($val);
		}

		/* If the user is allowed to see his/her/any user CP */
		if(($session['user'] instanceof Member)) {
			if(isset($request['pass']) && isset($request['pass_check']) && isset($request['new_pass']) && isset($request['new_pass_check'])) {
				if($request['pass'] == $request['pass_check'] && md5($request['pass']) == $session['user']['pass']) {
					if($request['new_pass'] == $request['new_pass_check']) {
						$new_pass = md5($request['new_pass']);
						if($this->dba->Query("UPDATE ". USERS ." SET pass = '{$new_pass}' WHERE id = ". $session['user']['id'] ))
							return new Error($template['L_PASSWORDSUCCESS'] .'<meta http-equiv="refresh" content="2; url=member.php?act=profile">', $template);
					} else {
						return new Error($template['L_BADNEWPASSES'], $template);
					}
				} else {
					return new Error($template['L_BADOLDPASSES'], $template);
				}
			} else {
				return new Error($template['L_ERRORUPDATINGPASS'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class MemberSortMenu extends Filter {
	public function Execute(Template $template, Session $session, & $cookie, & $post, & $get) {
		$letters = array();
		
		/* Push the star (*) symbol into the letters array */
		$letters[] = array('name' => '*', 'class' => '', 'action' => new Action('member.php', 'list&amp;start=0&amp;limit=30&amp;sort=*'));

		$self	= basename($_SERVER['PHP_SELF']);
		
		/* Populate the letters array with actual letters */
		foreach(range('A', 'Z') as $key => $val) {
			$letters[]	= array('name' => $val, 'class' => '', 'action' => new Action('member.php', 'list&amp;start=0&amp;limit=30&amp;sort='.$val));
		}

		/* Apply the letters */
		$template->letters = $letters;
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class ForumMemberList extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Figure out some stuff to keep the pagination in top-shape */
		if(isset($request['sort'])) {
			if($request['sort'] == "#") {
				$order ="[a-zA-Z].*$";
				$like = 'REGEXP';
			} else {
				$order = $request['sort'].'%';
				$like = 'LIKE';
			}
		} else {
			$order ="%";
			$like = 'LIKE';
		}

		$template['total_posts'] = DBA::Open()->GetValue("SELECT COUNT(*) FROM ". USERS );

		/* Create the ancestors bar */
		$template = CreateAncestors($template, $template['L_MEMBERLIST']);
		
		if($template['enablememberlist'] == 1) {
			/* Set the template */
			$template->content = array('file' => 'memberlist.html');

			/* Apply the member list Iterator */
			$template->memberlist = new MemeberListIterator($request);
		} else {
			return new Error($template['L_FEATUREDENIED'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class ForumUserGroups extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		
		$ug = new Usergroup($session);

		/* Create the ancestors bar */
		$template = CreateAncestors($template, $template['L_USERGROUPS']);

		/* Set the template */
		$template->content = array('file' => 'usergroups.html');

		/* Set the user groups list */
		$template->usergroups = $ug->getVisibleUsergroups();
		
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class LookupUserGroup extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		
		$ug = new Usergroup($session);

		/* Get the usergroup */
		$group = $ug->getVisibleUsergroup(intval($request['gid']), $template);
		
		/* Create the ancestors bar */
		if(!($group instanceof Error))
			$template = CreateAncestors($template, $group['name']);
		else
			return $group;

		/* Set the template */
		$template->content = array('file' => 'lookup_group.html');
		
		/* Set the template variables to the group's info */
		$template['group_name']			= $group['name'];
		$template['group_id']			= $group['id'];
		$template['mod_name']			= $group['mod_name'];
		$template['mod_id']				= $group['mod_id'];
		$template['group_description']	= $group['description'];
		
		$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15;
		$start = isset($_GET['start']) ? intval($_GET['start']) : NULL;
		$extra = (!is_null($limit) && !is_null($start)) ? "LIMIT ". $start .", ".($limit) : "LIMIT 15";

		/* Get the users in this group */
		$users = DBA::Open()->Query("SELECT * FROM ". USER_IN_GROUP ." WHERE group_id = ". $group['id'] ." AND name != '". $group['mod_name'] ."' $extra");
		
		/* Set the users to a list item */
		$template->users_in_usergroup	= $users;

		/* Tell the template how many users there are in that group */
		$template['num_group_users']	= $users->NumRows();
		
		/* If we're not the user group's moderator, hide some of the mod functions */
		if($session['user']['name'] != $group['mod_name']) {
			$template->add_user			= array('hide' => TRUE);
			$template->delete_user		= array('hide' => TRUE);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class AddUserToGroup extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		

		/* Create the ancestors bar (if we run into any trouble */
		$template = CreateAncestors($template, $template['L_INFORMATION']);
		
		$this->dba = DBA::Open();
		
		/* If we're logged in */
		if($session['user'] instanceof Member) {
			
			$groups = $this->dba->Query("SELECT * FROM ". GROUPS ." WHERE id = ". intval($request['gid']) );

			/* Get the group from the database */
			if($groups->NumRows() == 1) {
				
				/* Set the group variable */
				$group = $groups->FetchRow();

				/* If we fit the profile of the groups moderator */
				if(($group['mod_name'] == $session['user']['name']) && ($group['mod_id'] == $session['user']['id'])) {
					
					/* Get the user's profile that we would like to add */
					$user = $this->dba->GetRow("SELECT * FROM ". USERS ." WHERE name = '". htmlspecialchars($request['username']) ."'");
					
					if(!empty($user) && isset($user['id'])) {
						/* Check if this user is already a part of this group */
						if($this->dba->Query("SELECT * FROM ". USER_IN_GROUP ." WHERE group_id = ". $group['id'] ." AND id = ". $user['id'] )->NumRows() == 0) {
							$ug = new Usergroup($session);
							
							/* Add the user to the group */
							if($ug->AddUserToUsergroup($group['id'], $user['id'], $user['name'])) {
							
								/* Redirect us */
								header("Location: member.php?act=lookup&gid=".$group['id']);
							} else {
								return new Error($template['L_ERRORADDINGUTG'], $template);
							}
						} else {
							return new Error($template['L_USERINGROUP'], $template);
						}
					} else {
						return new Error($template['L_USERDOESNTEXIST'], $template);
					}
				} else {
					return new Error($template['L_YOUNEEDPERMS'], $template);
				}
			} else {
				return new Error($template['L_GROUPDOESNTEXIST'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class DeleteUserFromGroup extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {

		/* Create the ancestors bar (if we run into any trouble */
		$template = CreateAncestors($template, $template['L_INFORMATION']);
		
		$this->dba = DBA::Open();
		
		/* If we're logged in or not */
		if($session['user'] instanceof Member) {
			
			/* Try to get the group */
			$group = $this->dba->GetRow("SELECT * FROM ". GROUPS ." WHERE id = ". intval($request['gid']));
			if(!empty($group) && isset($group['id'])) {	
				/* Check if we fit the moderators profile */
				if(($group['mod_name'] == $session['user']['name']) && ($group['mod_id'] == $session['user']['id'])) {
					$user = $this->dba->GetRow("SELECT * FROM ". USERS ." WHERE id = ". intval($request['uid']) );
					if($this->dba->Query("DELETE FROM ". USER_IN_GROUP ." WHERE group_id = ". $group['id'] ." AND id = ". $user['id'] )) {
						$new_perm = 2;
						foreach($this->dba->Query("SELECT * FROM ". GROUPS ." WHERE id = (SELECT group_id FROM ". USER_IN_GROUP ." WHERE name = '". $user['name'] ."' AND id = ". $user['id'] .")") as $g) {
							$new_perm = $g['permissions'] > $new_perm ? $g['permissions'] : $new_perm;
						}
						if($this->dba->Execute("UPDATE ". USERS ." SET perms = $new_perm WHERE id = ". $user['id']))
							header("Location: member.php?act=lookup&gid=". $group['id']);
					} else {
						return new Error($template['L_ERRORDELUFGROUP'], $template);
					}
				} else {
					return new Error($template['L_YOUNEEDPERMS'], $template);
				}
			} else {
				return new Error($template['L_GROUPDOESNTEXIST'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class EmailUserForm extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {

		/* Create the ancestors bar (if we run into any trouble */
		$template = CreateAncestors($template, $template['L_EMAILUSER']);
		
		$this->dba = DBA::Open();
		
		/* If we're logged in or not */
		if($session['user'] instanceof Member) {
			
			/* Try to get the group */
			$user = $this->dba->GetRow("SELECT * FROM ". USERS ." WHERE id = ". intval(@$request['user']));
			if(!empty($user) && isset($user['id'])) {	
				/* Check if we fit the moderators profile */
				if($session['user'] instanceof Member) {
					$template['username']	= $user['name'];
					$template['id']			= $user['id'];
					if($template['secureemail'] == 1)
						$template->content = array('file' => 'email_form.html');
					else
						return new Error($template['L_FEATUREDENIED'], $template);
				} else {
					return new Error($template['L_YOUNEEDPERMS'], $template);
				}
			} else {
				return new Error($template['L_USERDOESNTEXIST'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class EmailUser extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {

		/* Create the ancestors bar (if we run into any trouble */
		$template = CreateAncestors($template, $template['L_EMAILUSER']);
		
		$this->dba = DBA::Open();
		
		/* If we're logged in or not */
		if($session['user'] instanceof Member) {
			
			/* Try to get the group */
			$user = $this->dba->GetRow("SELECT * FROM ". USERS ." WHERE id = ". intval(@$request['user']));
			if(!empty($user) && isset($user['id'])) {	
				/* Check if we fit the moderators profile */
				if($session['user'] instanceof Member) {
				
					/* additional headers */
					$headers = "From: \"". $session['user']['name'] ." - k4 Bulletin Board Mailer\" <". $session['user']['email'] .">";

					if($template['secureemail'] == 1) {
						if(!@mail($user['email'], htmlspecialchars(stripslashes($request['subject'])), htmlspecialchars(stripslashes($request['message'])), $headers))
							return new Error($template['L_ERROREMAILING'], $template);
					} else {
						return new Error($template['L_FEATUREDENIED'], $template);
					}
					/* Return successful email message */
					return new Error(sprintf($template['L_EMAILSENT'], $user['name']) . '<meta http-equiv="refresh" content="1; url=member.php?id='. $user['id'] .'">', $template);
				} else {
					return new Error($template['L_YOUNEEDPERMS'], $template);
				}
			} else {
				return new Error($template['L_USERDOESNTEXIST'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class MemberRateThread extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {

		/* Create the ancestors bar (if we run into any trouble */
		$template = CreateAncestors($template, $template['L_EMAILUSER']);
		
		$this->dba = DBA::Open();
		
		/* If we're logged in or not */
		if($session['user'] instanceof Member) {
			if(isset($request['id']) && intval($request['id']) != 0) {
				
				$thread = $this->dba->GetRow("SELECT * FROM ". POSTS ." WHERE id = ". intval($request['id']));

				if(!empty($thread) && isset($thread['id'])) {				
					if(isset($request['rating']) && intval($request['rating']) >= 0 && intval($request['rating']) <= 5) {
						if($session['user']['id'] != $thread['poster_id']) {
							try {
								if(@$this->dba->Query("SELECT * FROM ". RATINGS ." WHERE user_id = ". $session['user']['id'] ." AND thread_id = ". intval($request['id']) )->NumRows() == 0) {
									
									@$this->dba->Execute("INSERT INTO ". RATINGS ." (user_id, thread_id, rating) VALUES (". $session['user']['id'] .", ". intval($request['id']) .", ". intval($request['rating']) .")");
									
									/* Return a successful message */
									return new Error($template['L_RATEDTHREAD'] .'<meta http-equiv="refresh" content="1; url=viewthread.php?id='. $request['id'] .'">', $template);
								} else {
									return new Error($template['L_ALREADYRATED'], $template);
								}
							} catch(DBA_Exception $e) {
								return new TplException($e, $template);
							}
						} else {
							return new Error($template['L_CANNOTRATEOWNPOSTS'], $template);
						}
					} else {
						return new Error($template['L_NONEXISTANTTHREAD'], $template);
					}
				} else {
					return new Error($template['L_NEEDCHOOSERATE'], $template);
				}
			} else {
				return new Error($template['L_NONEXISTANTTHREAD'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}
class MemberForumLogin extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {

		/* Create the ancestors bar (if we run into any trouble */
		$template = CreateAncestors($template, $template['L_FORUMLOGIN']);
		
		$this->dba = DBA::Open();
		
		/* If we're logged in or not */
		if($session['user'] instanceof Member) {
			if(isset($request['id']) && intval($request['id']) != 0) {
				$forum = $this->dba->GetRow("SELECT * FROM ". FORUMS ." WHERE id = ". intval($request['id']));
				
				if(!empty($forum) && $forum['private'] == 1) {
					if($this->dba->Quote($request['pass']) == $forum['pass']) {
						$session['forum_logged'] = $forum['id'];
						//$_SESSION[$forum['id']] = TRUE;
						//$session->OffsetSet($forum['id'], TRUE);
						//print_r($session); exit;
						/* Return a successful message */
						return new Error($template['L_LOGGEDINTOFORUM'] .'<meta http-equiv="refresh" content="1; url=viewforum.php?id='. intval($request['id']) .'">', $template);
					} else {
						return new Error($template['L_INVALIDFORUMPASS'], $template);
					}
				} else {
					return new Error($template['L_FORUMDOESNTEXIST'], $template);
				}
			} else {
				return new Error($template['L_FORUMDOESNTEXIST'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

/* Set our wrapper template */
$app	= new Forum_Controller('forum_base.html');

/* Apply all of the events */

$app->AddEvent('email', new EmailUserForm);
$app->AddEvent('email_user', new Emailuser);

$app->AddEvent('login', new ForumLogin);
$app->AddEvent('register', new ForumRegister);

$app->AddEvent('rate_thread', new MemberRateThread);
$app->AddEvent('forum_login', new MemberForumLogin);

/* User CP */
$app->AddEvent('profile', new MemberProfile);
$app->AddEvent('update_profile', new MemberUpdateProfile);
$app->AddEvent('settings', new MemberSettings);
$app->AddEvent('update_settings', new MemberUpdateSettings);
$app->AddEvent('avatar', new MemberAvatar);
$app->AddEvent('update_avatar', new MemberUpdateAvatar);
$app->AddEvent('password', new MemberPassword);
$app->AddEvent('update_password', new MemberUpdatePassword);

/* Private Messaging */
$app->AddEvent('view_folder', new ListMessages);
$app->AddEvent('send_message', new SendMessage);
$app->AddEvent('send_pm', new SaveMessage);
$app->AddEvent('reply_msg', new SaveReplyMessage);
$app->AddEvent('view_msg', new ViewMessage);
$app->AddEvent('buddy_list', new BuddyList);
$app->AddEvent('add_buddy', new AddBuddyToList);
$app->AddEvent('remove_buddy', new RemoveBuddyFromList);
$app->AddEvent('delete_pm', new DeleteMessage);

$app->AddEvent('list', new ForumMemberList);
$app->AddEvent('register_user', new RegisterEvent);
$app->AddEvent('usergroups', new ForumUserGroups);
$app->AddEvent('lookup', new LookupUserGroup);
$app->AddEvent('add_user_to_group', new AddUserToGroup);
$app->AddEvent('dufg', new DeleteUserFromGroup);

$app->AddAction('a_login', new Action('member.php', 'login_user'));
$app->AddAction('a_register', new Action('member.php', 'register_user'));
$app->AddEvent('login_user', new LoginEvent);
$app->AddEvent('logout', new LogoutEvent);
$app->AddEvent('remindme', new RemindMeEvent);
$app->AddEvent('forgotpw', new RestorePwEvent);
$app->AddEvent('resetpw', new ResetPassword);

$app->AddFilter(new MemberSortMenu);

$app->ExecutePage();

?>