<?php

class ListMessages extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_LISTMESSAGES']);
		
		$dba = DBA::Open();

		/* If the user is allowed to see his/her/any user CP */
		if(($session['user'] instanceof Member)) {

			if(isset($request['id']) && intval($request['id']) != 0) {
			
				/* Private Messages folder */
				$template->pmsg_folders = new PMFolders;

				$num_messages		= $dba->GetValue("SELECT COUNT(*) FROM ". PMSGS ." WHERE (poster_id = ". $session['user']['id'] ." AND saved = 1) OR (member_id = ". $session['user']['id'] .")");
				$folder				= $dba->GetRow("SELECT * FROM ". PMSG_FOLDERS ." WHERE id = ". intval($request['id']) ." AND user_id = 0 OR user_id = ". $session['user']['id']);
				
				if(!empty($folder) && isset($folder['id'])) {
					
					/* Assign some template variables */
					$template['pmsg_stats'] = sprintf($template['L_PMSGSTATS'], $num_messages, $template['pmquota']);
					$template['width']		= round(($num_messages / $template['pmquota']) * 100);
					$template['width']		= $template['width'] > 100 ? 100 : $template['width'];
					$template['folder']		= $folder['name'];
					
					$template->pmessages = new PMMessages($folder['id']);
					$template->content	= array('file' => 'usercp.html');
					$template->usercp	= array('file' => 'usercp/listmessages.html');
				} else {
					return new Error($template['L_FOLDERDOESNTEXIST'], $template);
				}
			} else {
				return new Error($template['L_FOLDERDOESNTEXIST'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		return TRUE;
	}
}

class SendMessage extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_SENDMESSAGE']);
		
		$dba = DBA::Open();

		/* If the user is allowed to see his/her/any user CP */
		if(($session['user'] instanceof Member)) {
			
			if($template['enablepms'] == 1) {
				/* Private Messages folder */
				$template->pmsg_folders = new PMFolders;

				/* Hide specific fun features if they are not allowed */
				if($template['privallowbbcode'] == 0)
					$template->bbcode = array('hide' => TRUE);
				if($template['privallowsmilies'] == 0)
					$template->smilies = array('hide' => TRUE);
				if($template['privallowicons'] == 0)
					$template->post_icons = array('hide' => TRUE);

				/* Set the post icons and the emoticons */
				$template->posticons = DBA::Open()->Query("SELECT * FROM ". POSTICONS );
				$template->emoticons = DBA::Open()->Query("SELECT * FROM ". EMOTICONS );

				if(!isset($request['do'])) {
					$template['act']			= 'send_pm';
				} else if($request['do'] == 'reply') {
					$template['act']			= 'reply_msg';
					$template->post_options		= array('hide' => true);
					$template->post_icons		= array('hide' => true);
					$template->forward_username = array('hide' => true);
					$template['msg_id']			= intval($request['id']);
					$msg = $dba->GetRow("SELECT * FROM ". PMSGS ." WHERE id = ". intval($request['id']));

					$bbcode						= new BBParser(NULL, TRUE);
					$template['subject']		= 'Re: '. stripslashes($msg['name']);
					$template['message']		= '[quote='. $msg['poster_name'] .']'. $bbcode->Revert($msg['body_text']) .'[/quote]';
				} else if($request['do'] == 'forward') {
					$template['act']			= 'send_pm';
					$template->post_options		= array('hide' => true);
					$msg = $dba->GetRow("SELECT * FROM ". PMSGS ." WHERE id = ". intval($request['id']));
					$bbcode						= new BBParser(NULL, TRUE);
					$template['subject']		= 'Fwd: '. stripslashes($msg['name']);
					$template['message']		= '[quote='. $msg['poster_name'] .']'. $bbcode->Revert($msg['body_text']) .'[/quote]';
				}

				/* Set the Buddy List */
				$template->buddy_list = new FriendsList;
						
				/* Assign some template variables */
				$template->content	= array('file' => 'usercp.html');
				$template->usercp	= array('file' => 'usercp/sendmessage.html');
			} else {
				return new Error($template['L_FEATUREDENIED'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		return TRUE;
	}
}

class BuddyList extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_BUDDYLIST']);
		
		$dba = DBA::Open();

		/* If the user is allowed to see his/her/any user CP */
		if(($session['user'] instanceof Member)) {
			
			/* Private Messages folder */
			$template->pmsg_folders = new PMFolders;

			if($template['enablepms'] == 1) {
				/* Set the Buddy List */
				$template->buddy_list = new FriendsList;
				$template->enemy_list = new EnemyList;
						
				/* Assign some template variables */
				$template->content	= array('file' => 'usercp.html');
				$template->usercp	= array('file' => 'usercp/buddylist.html');
			} else {
				return new Error($template['L_FEATUREDENIED'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		return TRUE;
	}
}
class AddBuddyToList extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_ADDBUDDYTOLIST']);
		
		$dba = DBA::Open();

		/* If the user is allowed to see his/her/any user CP */
		if(($session['user'] instanceof Member)) {
			
			if($template['enablepms'] == 1) {

				/* Private Messages folder */
				$template->pmsg_folders = new PMFolders;

				$buddy = $dba->GetRow("SELECT * FROM ". USERS ." WHERE name = '". $dba->Quote($request['user_name']) ."'");
				if(!empty($buddy) && isset($buddy['id'])) {
					if($dba->Query("SELECT * FROM ". PMSG_LIST ." WHERE member_list_id = ". $session['user']['id'] ." AND user_name = '". $buddy['name'] ."'")->NumRows() == 0) {
						if($dba->Execute("INSERT INTO ". PMSG_LIST ." (member_list_id, user_id, user_name, user_liked) VALUES (". $session['user']['id'] .", ". $buddy['id'] .", '". $buddy['name'] ."', ". intval(@$request['user_liked']) .")"))
							return new Error($template['L_USERADDEDTOBL'] . '<meta http-equiv="refresh" content="1; url=member.php?act=buddy_list">', $template);
					} else {
						return new Error($template['L_USERONBUDDYLIST'], $template);
					}
				} else {
					return new Error($template['L_USERDOESNTEXIST'], $template);
				}
			} else {
				return new Error($template['L_FEATUREDENIED'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		return TRUE;
	}
}
class RemoveBuddyFromList extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_REMOVEBUDDY']);
		
		$dba = DBA::Open();

		/* If the user is allowed to see his/her/any user CP */
		if(($session['user'] instanceof Member)) {
			
			if($template['enablepms'] == 1) {

				/* Private Messages folder */
				$template->pmsg_folders = new PMFolders;

				$buddy = $dba->GetRow("SELECT * FROM ". USERS ." WHERE id = ". intval(@$request['id']) ."");
				if(!empty($buddy) && isset($buddy['id'])) {
					if($dba->Query("SELECT * FROM ". PMSG_LIST ." WHERE member_list_id = ". $session['user']['id'] ." AND user_name = '". $buddy['name'] ."'")->NumRows() > 0) {
						if($dba->Execute("DELETE FROM ". PMSG_LIST ." WHERE member_list_id = ". $session['user']['id'] ." AND user_id = ". $buddy['id'] ))
							return new Error($template['L_REMOVEDBUDDY'] . '<meta http-equiv="refresh" content="1; url=member.php?act=buddy_list">', $template);
					} else {
						return new Error($template['L_USERNOTONBUDDYLIST'], $template);
					}
				} else {
					return new Error($template['L_USERDOESNTEXIST'], $template);
				}
			} else {
				return new Error($template['L_FEATUREDENIED'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		return TRUE;
	}
}
class SaveMessage extends Event {
	protected $dba;
	
	protected function getNumOnLevel() {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". PMSGS ." WHERE level = 1");
	}
	public function Execute(Template $template, Session $session, $request) {
		/* Can we pm? */
		if($template['enablepms'] == 1) {
			/* Set the post vars session */
			$session['post_vars'] = $request;
			
			/* Create the ancestors bar (if we run into any trouble */
			$template = CreateAncestors($template, $template['L_SAVEMESSAGE']);

			/* Open a connection to the database */
			$this->dba = DBA::Open();

			/* Set the a variable to this user's permissions and id */
			$user_perms = isset($session['user']['perms']) ? $session['user']['perms'] : ALL;
			$user_id	= $session['user']['id'];

			/* Parse the Message */
			$request['message'] = substr($request['message'], 0, $template['pmmaxchars']);
			$parser = new BBParser($request['message'], FALSE, TRUE, TRUE, array('allowbbcode' => $template['privallowbbcode'], 'allowsmilies' => $template['privallowsmilies']));
			$request['message'] = $parser->Execute();
			
			/* Quote all of the REQUEST variables */
			foreach($request as $key => $val) {
				$request[$key] = $this->dba->Quote($val);
			}
			
			/* Set the post icon */
			if(isset($request['posticon']) && intval($request['posticon']) != 0 && $request['posticon'] != '-1' && $template['privallowicons'] == 1) {
				try {
					$posticon = $this->dba->GetValue("SELECT image FROM ". POSTICONS ." WHERE id = ". intval($request['posticon']) );
				} catch(DBA_Exception $e) {
					$posticon = 'clear.gif';
				}
			} else {
				$posticon = 'clear.gif';
			}

			/* Get the message which will be to the left of this one */
			$before = $this->dba->GetRow("SELECT * FROM ". PMSGS ." ORDER BY row_right DESC LIMIT 1");
			
			/* Get the number of pms on the same level as this one */
			if($this->getNumOnLevel() > 0) {
				$left = $before['row_right']+1;
			} else {
				$left = 1;
			}

			/* Set the right value */
			$right = $left+1;
			
			/* Timestamp */
			$time = time();

			$user_to = $this->dba->GetRow("SELECT * FROM ". USERS ." WHERE name = '". $request['member_name'] ."'");

			if(!empty($user_to) && isset($user_to['id'])) {

				$user_num_pms	= $this->dba->GetValue("SELECT COUNT(*) FROM ". PMSGS ." WHERE (poster_id = ". $user_to['id'] ." AND saved = 1) OR (member_id = ". $user_to['id'] .")");
				$queued			= $user_num_pms >= $template['pmquota'] ? 1 : 0;
				
				$errors			= '';

				try {
					/* Check if we're on the recievers black list */
					if($this->dba->Query("SELECT * FROM ". PMSG_LIST ." WHERE member_list_id = ". $user_to['id'] ." AND user_id = ". $session['user']['id'] ." AND user_liked = 0")->NumRows() == 0) {
						/* Make room for the pm in the pms table by updating the right values */
						@$this->dba->Query("UPDATE ". PMSGS ." SET row_right = row_right+2 WHERE row_left < $left AND row_right >= $left"); // Good
						
						/* Keep updating the pms table by changing all of the necessary left AND right values */
						@$this->dba->Query("UPDATE ". PMSGS ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= $left"); // Good
						
						/* Finally insert our thread into the Posts table */
						@$this->dba->Query("INSERT INTO ". PMSGS ." (row_left, row_right, name, body_text, created, poster_name, poster_id, member_id, member_name, saved, queued, icon) VALUES ($left, $right, '". $request['name'] ."', '". $request['message'] ."', ". $time .", '". $session['user']['name'] ."', ". $session['user']['id'] .", ". $user_to['id'] .", '". $user_to['name'] ."', 0, ". $queued .", '". $posticon ."')");
					} else {
						$errors = $template['L_MESSAGENOTSETNSAVED'] .'<br /><br />';
					}
					/* Save a copy in our sent items folder */
					if(isset($request['save']) && $request['save'] == 1) {
						/* Get the message which will be to the left of this one */
						$before = $this->dba->GetRow("SELECT * FROM ". PMSGS ." ORDER BY row_right DESC LIMIT 1");
						/* Get the number of pms on the same level as this one */
						if($this->getNumOnLevel() > 0) {
							$left = $before['row_right'] + 1;
						} else {
							$left = 1;
						}
						$right = $left+1;
						
						/* Make room for the thread in the Forums table by updating the right values */
						@$this->dba->Query("UPDATE ". PMSGS ." SET row_right = row_right+2 WHERE row_left < $left AND row_right >= $left"); // Good
						
						/* Keep updating the Forums table by changing all of the necessary left AND right values */
						@$this->dba->Query("UPDATE ". PMSGS ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= $left"); // Good
						
						/* Finally insert our thread into the Posts table */
						@$this->dba->Query("INSERT INTO ". PMSGS ." (row_left, row_right, name, body_text, created, poster_name, poster_id, member_id, member_name, saved, queued, folder_id, icon, member_has_read) VALUES ($left, $right, '". $request['name'] ."', '". $request['message'] ."', ". $time .", '". $session['user']['name'] ."', ". $session['user']['id'] .", ".  $session['user']['id'] .", '".  $session['user']['name'] ."', 1, ". $queued .", 2, '". $posticon ."', 1)");
					}

				} catch(DBA_Exception $e) {
					return new TplException($e, $template);
				}
				
				/* Assuming that we've made it this far, unset the post vars session */
				unset($session['post_vars']);

				/* If we've gotten to this point, reload the page to our recently added thread :) */
				return new Error($errors . $template['L_SENTPMESSAGE'] . '<meta http-equiv="refresh" content="2; url=member.php?act=view_folder&id=1">', $template);
			} else {
				return new Error($template['L_USERDOESNTEXIST'], $template);
			}
		} else {
			return new Error($template['L_FEATUREDENIED'], $template);
		}
		return TRUE;
	}
}

class SaveReplyMessage extends Event {
	protected $dba;
	public function getNumOnLevel($parent_id) {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". PMSGS ." WHERE parent_id = $parent_id");
	}
	public function Execute(Template $template, Session $session, $request) {
		/* Can we pm? */
		if($template['enablepms'] == 1) {
			/* Create the ancestors bar (if we run into any trouble */
			$template = CreateAncestors($template, $template['L_SAVEMESSAGE']);

			/* Open a connection to the database */
			$this->dba = DBA::Open();

			/* Set the a variable to this user's permissions and id */
			$user_perms = isset($session['user']['perms']) ? $session['user']['perms'] : ALL;
			$user_id	= $session['user']['id'];

			/* Parse the Message */
			$request['message'] = substr($request['message'], 0, $template['pmmaxchars']);
			$parser = new BBParser($request['message'], FALSE, TRUE, TRUE, array('allowbbcode' => $template['privallowbbcode'], 'allowsmilies' => $template['privallowsmilies']));
			$request['message'] = $parser->Execute();
			
			/* Quote all of the REQUEST variables */
			foreach($request as $key => $val) {
				$request[$key] = $this->dba->Quote($val);
			}
			
			/* Set the post icon */
			if(isset($request['posticon']) && intval($request['posticon']) != 0 && $request['posticon'] != '-1' && $template['privallowicons'] == 1) {
				try {
					$posticon = $this->dba->GetValue("SELECT image FROM ". POSTICONS ." WHERE id = ". intval($request['posticon']) );
				} catch(DBA_Exception $e) {
					$posticon = 'clear.gif';
				}
			} else {
				$posticon = 'clear.gif';
			}
			try {
				/* Get the message which we are replying to */
				$before = $this->dba->GetRow("SELECT * FROM ". PMSGS ." WHERE id = ". intval($request['msg_id']));
				
				/* Get the TOP message that is being replies to */
				$top	= $this->dba->GetRow("SELECT * FROM ". PMSGS ." WHERE row_left >= ". $before['row_left'] ." AND row_right <= ". $before['row_right'] ." ORDER BY row_left ASC LIMIT 1");
			} catch(DBA_Exception $e) {
				return new TplException($e, $template);
			}
			/* Get the number of replies on the same level as this */
			if($this->getNumOnLevel($before['id']) > 0) {
				$left = $before['row_right'];
			} else {
				$left = $before['row_left']+1;
			}

			/* Set the level and right value */
			$right = $left+1;			
			$level = $before['id'] == $top['id'] ? 1 : $before['level']+1;
			
			/* Timestamp */
			$time = time();

			$user_to = $this->dba->GetRow("SELECT * FROM ". USERS ." WHERE name = '". $before['poster_name'] ."'");

			if(!empty($user_to) && isset($user_to['id'])) {

				$user_num_pms	= $this->dba->GetValue("SELECT COUNT(*) FROM ". PMSGS ." WHERE (poster_id = ". $user_to['id'] ." AND saved = 1) OR (member_id = ". $user_to['id'] .")");
				$queued			= $user_num_pms >= $template['pmquota'] ? 1 : 0;
				
				$errors			= '';

				try {
					/* Check if we're not on the recievers black list */
					if($this->dba->Query("SELECT * FROM ". PMSG_LIST ." WHERE member_list_id = ". $user_to['id'] ." AND user_id = ". $session['user']['id'] ." AND user_liked = 0")->NumRows() == 0) {
						/* Make room for the pm in the pms table by updating the right values */
						@$this->dba->Query("UPDATE ". PMSGS ." SET row_right = row_right+2 WHERE row_left < $left AND row_right >= $left"); // Good
						
						/* Keep updating the pms table by changing all of the necessary left AND right values */
						@$this->dba->Query("UPDATE ". PMSGS ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= $left"); // Good
						
						/* Finally insert our thread into the Posts table */
						@$this->dba->Query("INSERT INTO ". PMSGS ." (row_left, row_right, name, body_text, created, poster_name, poster_id, member_id, member_name, level, msg_id, parent_id, member_has_read) VALUES ($left, $right, '". $request['name'] ."', '". $request['message'] ."', ". $time .", '". $session['user']['name'] ."', ". $session['user']['id'] .", ". $user_to['id'] .", '". $user_to['name'] ."', $level, ". $top['id'] .", ". $before['id'] .", 0)");
						
						/* Update the top node */
						//$top = @$this->dba->GetRow("SELECT * FROM ". PMSGS ." WHERE row_left <= $left AND row_right >= $right ORDER BY row_left ASC LIMIT 1");
						@$this->dba->Execute("UPDATE ". PMSGS ." SET new_reply = 1, member_has_read = 0 WHERE id = ". $top['id']);
					} else {
						$errors = $template['L_MESSAGENOTSETNSAVED'] .'<br /><br />';
					}
				} catch(DBA_Exception $e) {
					return new TplException($e, $template);
				}

				/* If we've gotten to this point, reload the page to our recently added thread :) */
				return new Error($errors . $template['L_SENTPMESSAGE'] . '<meta http-equiv="refresh" content="1; url=member.php?act=view_msg&id='. $top['id'] .'">', $template);
			} else {
				return new Error($template['L_USERDOESNTEXIST'], $template);
			}
		} else {
			return new Error($template['L_FEATUREDENIED'], $template);
		}
		return TRUE;
	}
}
class DeleteMessage extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		/* Can we pm? */
		if($template['enablepms'] == 1) {
			/* Create the ancestors bar (if we run into any trouble */
			$template = CreateAncestors($template, $template['L_DELETEMESSAGE']);

			/* Open a connection to the database */
			$this->dba = DBA::Open();

			/* Quote all of the REQUEST variables */
			foreach($request as $key => $val) {
				$request[$key] = $this->dba->Quote($val);
			}
			$pm = $this->dba->GetRow("SELECT * FROM ". PMSGS ." WHERE id = ". $request['id']);
			if(!empty($pm) && isset($pm['id']) && $pm['poster_id'] != 0) {
				if(@(($pm['row_right']-$pm['row_left']-1)/2) == 0) {
					try {
						/* Delete the message */
						@$this->dba->Execute("DELETE FROM ". PMSGS ." WHERE id = ". $pm['id']);
					} catch(DBA_Exception $e) {
						return new TplException($e, $template);
					}

					/* If we've gotten to this point, reload the page to our recently added thread :) */
					return new Error($template['L_DELETEDPMESSAGE'] . '<meta http-equiv="refresh" content="1; url=member.php?act=view_folder&id=1">', $template);
				} else {
					return new Error($template['L_CANTDELETEMESSAGE'], $template);
				}
			} else {
				return new Error($template['L_MSGDOESNTEXIST'], $template);
			}
		} else {
			return new Error($template['L_FEATUREDENIED'], $template);
		}
		return TRUE;
	}
}
class ViewMessage extends Event {
	public function Execute(Template $template, Session $session, $request) {

		/* Create the ancestors Bar */
		$template = CreateAncestors($template, $template['L_VIEWMESSAGE']);

		/* If the user is allowed to see his/her/any user CP */
		if(($session['user'] instanceof Member)) {

			if(isset($request['id']) && intval($request['id']) != 0) {
				
				$dba = DBA::Open();

				$msg = $dba->GetRow("SELECT * FROM ". PMSGS ." WHERE id = ". intval($request['id']) ." AND ((saved = 1 AND poster_id = ". $session['user']['id'] .") OR (saved = 0 AND member_id = ". $session['user']['id'] ." OR member_id = 0))");

				/* ReCreate the ancestors Bar */
				$template = CreateAncestors($template, $template['L_VIEWMESSAGE'] .' - '. stripslashes($msg['name']));
			
				/* Private Messages folder */
				$template->pmsg_folders = new PMFolders;

				if(!empty($msg) && isset($msg['id'])) {
					
					/* Set the messages */
					$template->message = new PMMessage($msg);
					
					/* Set the Buddy List */
					$template->buddy_list = new FriendsList;

					/* Set the files */
					$template->content	= array('file' => 'usercp.html');
					$template->usercp	= array('file' => 'usercp/viewmessage.html');

					/* Set this and all sub messages to read */
					$dba->Execute("UPDATE ". PMSGS ." SET member_has_read = 1, new_reply = 0 WHERE row_left >= ". $msg['row_left'] ." AND row_right <= ". $msg['row_right']);
				
				} else {
					return new Error($template['L_MSGDOESNTEXIST'], $template);
				}
			} else {
				return new Error($template['L_FOLDERDOESNTEXIST'], $template);
			}
		} else {
			return new Error($template['L_NEEDLOGGEDIN'], $template);
		}
		return TRUE;
	}
}

class PMMessages implements Iterator {
	protected $msgs;
	protected $dba;
	protected $session;
	protected $lang;
	public function __construct($folder_id) {
		global $settings;
		global $lang;
		$this->settings = $settings;
		$this->session	= $_SESSION;
		$this->dba		= DBA::Open();
		$this->lang		= $lang;
		$limit			= isset($_GET['limit']) ? intval($_GET['limit']) : NULL;
		$start			= isset($_GET['start']) ? intval($_GET['start']) : NULL;
		$extra			= (!is_null($limit) && !is_null($start)) ? "LIMIT ". $start .", ".($limit) : "LIMIT ". $this->settings['maxthreads'];
		$this->msgs = $this->dba->Query("SELECT * FROM ". PMSGS ." WHERE (member_id = ". $this->session['user']['id'] ." OR member_id = 0) AND level = 1 AND folder_id = $folder_id ORDER BY new_reply DESC, created DESC $extra")->GetIterator();
	}
	
	public function Current() {
		$temp = $this->msgs->Current();
		
		$temp['classname']		= $temp['new_reply'] == 1 || $temp['member_has_read'] == 0 ? 'alt1' : 'alt3';
		$temp['name']			= strlen($temp['name']) > 40 ? substr($temp['name'], 0, 27) .'...' : $temp['name'];
		$temp['name']			= $temp['new_reply'] == 1 || $temp['member_has_read'] == 0 ? '<strong>'. $temp['name'] .'</strong>' : $temp['name'];
		$temp['num_children']	= @($temp['row_right'] - $temp['row_left'] - 1) / 2;
		$temp['created']		= relative_time($temp['created']);

		if($temp['num_children'] == 0 && $temp['poster_id'] != 0)
			$temp['delete'] = '<a href="member.php?act=delete_pm&amp;id='. $temp['id'] .'" style="text-decoration:underline;">'. $this->lang['L_DELETE'] .'</a>';

		$temp['poster_name']	= $temp['poster_id'] == 0 ? $this->lang['L_ADMINISTRATOR'] : $temp['poster_name'];
		return $temp;
	}
	
	public function Key() {
		return $this->msgs->Key();
	}
	
	public function Next() {
		return $this->msgs->Next();
	}
	
	public function Rewind() {
		return $this->msgs->Rewind();
	}
	
	public function Valid() {
		return $this->msgs->Valid();
	}
}

class PMMessage implements Iterator {
	protected $msg;
	protected $dba;
	protected $session;
	protected $temp;
	public function __construct($msg) {
		global $lang;
		$this->lang = $lang;
		$this->session = $_SESSION;
		$this->dba = DBA::Open();
		$this->msg	= $this->dba->Query("SELECT * FROM ". PMSGS ." WHERE row_left <= ". $msg['row_left'] ." AND row_right >= ". $msg['row_right'] ." ORDER BY row_left ASC LIMIT 1")->GetIterator();
	}
	
	public function Current() {
		$temp = $this->msg->Current();
		
		//print_r($temp);
		//print_r($this->dba->GetRow("SELECT * FROM ". PMSGS ." WHERE id = 2"));
		//$this->dba->GetRow("UPDATE ". PMSGS ." set row_left = 2, row_right = 3 where id = 2");

		$temp['num_children']		= ($temp['row_right'] - $temp['row_left'] - 1) / 2;
		$temp['display']			= $temp['num_children'] == 0 ? 'block' : 'none';
		
		if($temp['poster_id'] != 0) {
			$user = $this->dba->GetRow("SELECT * FROM ". USERS ." WHERE id = ". $temp['poster_id'] );
		
			if($user['seen'] >= (time() - Lib::GetSetting('sess.gc_maxlifetime')))
				$temp['online_status']	= $this->lang['L_ONLINE'];
			else
				$temp['online_status']	= $this->lang['L_OFFLINE'];

			$temp['user_num_posts']		= $user['posts'];
			$temp['user_rank']			= $user['rank'];

			$temp['avatar']				= $user['avatar'] != '' && $user['avatar'] != 0 ? '<img src="Uploads/Avatars/'. $user['id'] .'.gif" border="0" alt="" />' : NULL;
			$temp['signature']			= $user['signature'] != '' && $temp['allow_sigs'] == 1 ? '<br /><br />'. stripslashes($user['signature']) : NULL;
			$temp['name']				= stripslashes($temp['name']);
		} else {
			$temp['poster_name']		= $this->lang['L_ADMINISTRATOR'];
			$temp['online_status']		= '--';
			$temp['user_num_posts']		= '--';
			$temp['user_rank']			= '--';
		}
		$temp['created']			= relative_time($temp['created']);
		
		$bbcode						= new BBParser(stripslashes($temp['body_text']), TRUE);
		$temp['body_text']			= $bbcode->QuickExecute();

		$temp['count']				= 1;
		
		$this->temp = $temp;
		return $temp;
	}
	
	public function Key() {
		return $this->msg->Key();
	}
	
	public function Next() {
		return $this->msg->Next();
	}
	
	public function Rewind() {
		return $this->msg->Rewind();
	}
	
	public function Valid() {
		return $this->msg->Valid();
	}
	public function GetChildren() {
		return new MessageReplies($this->temp);
	}
}

class MessageReplies implements Iterator {
	protected $post_info;
	protected $pm;
	protected $lang;
	protected $count = 1;
	protected $dba;
	protected $session;
	protected $settings;

	public function __construct($pm) {
		global $lang;
		global $settings;
		$this->lang = $lang;
		$this->dba = DBA::Open();
		$this->session = $_SESSION;
		$this->settings = $settings;
		$this->pm = $pm;
		$this->post_info = $this->dba->Query("SELECT * FROM ". PMSGS ." WHERE row_left > ". $pm['row_left'] ." AND row_right < ". $pm['row_right'] ." AND saved = 0")->GetIterator();
	}
	public function Current() {
		$row = $this->post_info->Current();
		$this->count++;
		$row['count'] = $this->count;
		$row['created'] = relative_time($row['created']);
		
		if($row['poster_id'] != 0) {
			$user = $this->dba->GetRow("SELECT * FROM ". USERS ." WHERE id = ". $row['poster_id'] );
			if($user['seen'] >= (time() - Lib::GetSetting('sess.gc_maxlifetime')))
				$row['online_status']	= $this->lang['L_ONLINE'];
			else
				$row['online_status']	= $this->lang['L_OFFLINE'];

			$row['user_num_posts']		= $user['posts'];
			$row['user_rank']			= $user['rank'] != '' ? $user['rank'] : '--';
			$row['avatar']				= $user['avatar'] != '' && $user['avatar'] != 0 ? '<img src="Uploads/Avatars/'. $user['id'] .'.gif" border="0" alt="" />' : ' ';
			$row['signature']			= $user['signature'] != '' && !is_null($user['signature']) && $row['allow_sigs'] == 1 ? '<br /><br />'. stripslashes($user['signature']) : ' ';
		} else {
			$row['poster_name']			= $this->lang['L_ADMINISTRATOR'];
			$row['online_status']		= '--';
			$row['user_num_posts']		= '--';
			$row['user_rank']			= '--';
		}	
		$row['name']				= stripslashes($row['name']);
		$row['name']				= $row['member_has_read'] == 0 ? '<span class="text-decoration:italic;">'. $row['name'] .'</span>' : $row['name'];

		$row['display']				= $this->pm['num_children'] == $this->count-1 || $row['member_has_read'] == 0 ? 'block' : 'none';
		
		$bbcode						= new BBParser(stripslashes($row['body_text']), TRUE);
		//$row['quoted_text']			= str_replace("\r\n", '\n', addslashes($bbcode->Revert($row['body_text'])));	
		$row['body_text']			= $bbcode->QuickExecute();

		return $row;		
	}
	
	public function Key() {
		return $this->post_info->Key();
	}
	
	public function Next() {
		return $this->post_info->Next();
	}
	
	public function Rewind() {
		return $this->post_info->Rewind();
	}
	
	public function Valid() {
		return $this->post_info->Valid();
	}
}

class PMFolders implements Iterator {
	protected $msgs;
	protected $dba;
	protected $session;
	public function __construct() {
		$this->session = $_SESSION;
		$this->dba = DBA::Open();
		$this->msgs = $this->dba->Query("SELECT * FROM ". PMSG_FOLDERS ." WHERE user_id = 0 OR user_id = ". $this->session['user']['id'])->GetIterator();
	}
	
	public function Current() {
		return $this->msgs->Current();
	}
	
	public function Key() {
		return $this->msgs->Key();
	}
	
	public function Next() {
		return $this->msgs->Next();
	}
	
	public function Rewind() {
		return $this->msgs->Rewind();
	}
	
	public function Valid() {
		return $this->msgs->Valid();
	}
}

class FriendsList implements Iterator {
	protected $friends;
	protected $dba;
	protected $session;
	public function __construct() {
		$this->session = $_SESSION;
		$this->dba = DBA::Open();
		$this->friends = $this->dba->Query("SELECT * FROM ". PMSG_LIST ." WHERE member_list_id = ". $this->session['user']['id'] ." AND user_liked = 1")->GetIterator();
	}
	
	public function Current() {
		return $this->friends->Current();
	}
	
	public function Key() {
		return $this->friends->Key();
	}
	
	public function Next() {
		return $this->friends->Next();
	}
	
	public function Rewind() {
		return $this->friends->Rewind();
	}
	
	public function Valid() {
		return $this->friends->Valid();
	}
}
class EnemyList implements Iterator {
	protected $friends;
	protected $dba;
	protected $session;
	public function __construct() {
		$this->session = $_SESSION;
		$this->dba = DBA::Open();
		$this->friends = $this->dba->Query("SELECT * FROM ". PMSG_LIST ." WHERE member_list_id = ". $this->session['user']['id'] ." AND user_liked = 0")->GetIterator();
	}
	
	public function Current() {
		return $this->friends->Current();
	}
	
	public function Key() {
		return $this->friends->Key();
	}
	
	public function Next() {
		return $this->friends->Next();
	}
	
	public function Rewind() {
		return $this->friends->Rewind();
	}
	
	public function Valid() {
		return $this->friends->Valid();
	}
}

?>