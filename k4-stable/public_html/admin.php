<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     admin.php
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
require 'classes/admin.php';

class DefaultEvent extends Event {
	public function Execute(Template $template, Session $session, $request) {		
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content = array('file' => 'admin/admin.html');
			$template->admin_panel = array('file' => 'admin/default.html');
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		return TRUE;
	}
}

class AdminMenu extends Event {
	public function Execute(Template $template, Session $session, $request) {		
		
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content = array('file' => 'admin/admin_menu.html');
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		return TRUE;
	}
}

class AdminHead extends Event {
	public function Execute(Template $template, Session $session, $request) {		
		
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content = array('file' => 'admin/admin_head.html');
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		return TRUE;
	}
}

class AdminCategories extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content = array('file' => 'admin/admin.html');
			$template->admin_panel = array('file' => 'admin/categories.html');
			$template->suspend_categories = new AdminJustForums("row_level = 1", "AND suspend != 1");
			$template->unsuspend_categories = new AdminJustForums("row_level = 1", "AND suspend = 1");
			$template->quick_jump = new AdminJustForums("row_level = 1", "");
			$template->categories = new AdminEditForums;
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class AdminForums extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content = array('file' => 'admin/admin.html');
			$template->admin_panel = array('file' => 'admin/forums.html');
			$template->suspend_forums = new AdminJustForums("row_level > 1", "AND suspend != 1");
			$template->unsuspend_forums = new AdminJustForums("row_level > 1", "AND suspend = 1");
			$template->lock_forums = new AdminJustForums("row_level > 1", "AND row_lock != 1");
			$template->unlock_forums = new AdminJustForums("row_level > 1", "AND row_lock = 1");
			$template->forums_list = new AdminAllForums;
			$template->categories = new AdminEditForums;
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class AdminGroups extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content = array('file' => 'admin/admin.html');
			$template->admin_panel = array('file' => 'admin/groups.html');
			$template->groups = DBA::Open()->Query("SELECT * FROM ". GROUPS );
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class AdminCensoring extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content = array('file' => 'admin/admin.html');
			$template->admin_panel = array('file' => 'admin/censoring.html');
			$template->badwords = DBA::Open()->Query("SELECT * FROM ". BADWORDS );
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class AdminIcons extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content = array('file' => 'admin/admin.html');
			$template->admin_panel = array('file' => 'admin/icons.html');
			$template->posticons = DBA::Open()->Query("SELECT * FROM ". POSTICONS );
			$template->emoticons = DBA::Open()->Query("SELECT * FROM ". EMOTICONS );
			
			/* Set the posticons */
			$dir = dir('Images/'. get_setting('template', 'imgfolder') .'/Icons/PostIcons');
			$files = array();
			while(false !== ($file = $dir->read())) {
				if(!is_dir($file) && ($file != 'clear.gif' && $file != 'index.html'))
					$files[] = array('file' => $file);
			}
			$template->icon_images = $files;
			/* Set the emoticons */
			$dir = dir('Images/'. get_setting('template', 'imgfolder') .'/Icons/Emoticons');
			$files = array();
			while(false !== ($file = $dir->read())) {
				if(!is_dir($file) && ($file != 'clear.gif' && $file != 'index.html'))
					$files[] = array('file' => $file);
			}
			$template->emo_images = $files;			
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class AdminFAQ extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content = array('file' => 'admin/admin.html');
			$template->admin_panel = array('file' => 'admin/faq.html');
			$template->emoticons = $this->dba->Query("SELECT * FROM ". EMOTICONS );
			$template->all_faq = new FAQCatIterator(1);		
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class AdminCSS extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content = array('file' => 'admin/admin.html');
			$template->admin_panel = array('file' => 'admin/css.html');
			
			// I hate this annoying way of having to do stuff, however the only way to keep this stuff going once is with an iterator
			$template->cssstyles		= $this->dba->Query("SELECT * FROM ". STYLES ." ORDER BY name DESC");
			$template->stylesets		= $this->dba->Query("SELECT * FROM ". STYLES ." ORDER BY name DESC");
			$template->stylesetd		= $this->dba->Query("SELECT * FROM ". STYLES ." ORDER BY name DESC");
			$template->css_stylesets	= $this->dba->Query("SELECT * FROM ". STYLES ." ORDER BY name DESC");
			$template->export_styleset	= $this->dba->Query("SELECT * FROM ". STYLES ." ORDER BY name DESC");
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class AdminDeleteStyleSet extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			if($this->dba->Query("DELETE FROM ". CSS ." WHERE style_id = ". intval($request['id']) )) {
				if($this->dba->Query("DELETE FROM ". STYLES ." WHERE id = ". intval($request['id']) )) {
					header("Location: admin.php?act=css");
				}
			}
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class AdminAddStyleSet extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			foreach($request as $key=>$val) {
				$request[$key] = $this->dba->Quote($val);
			}
			
			/* If we can get the styleset */
			if($styleset = $this->dba->GetRow("SELECT * FROM ". STYLES ." WHERE id = ". intval($request['id']) )) {
				
				/* Set some nice variable names */
				$name			= $request['name'];
				$description	= $request['description'];
				
				/* If we have added the styleset successfully */
				if($this->dba->Query("INSERT INTO ". STYLES ." (name, description) VALUES ('{$name}', '{$description}')")) {
					
					/* Get the styleset id that we just made */
					$style_id = $this->dba->getValue("SELECT MAX(id) FROM ". STYLES );
					
					/* Now take the default style set we chose and copy its vales to our new styleset */
					foreach($this->dba->Query("SELECT * FROM ". CSS ." WHERE style_id = ". $styleset['id'] ) as $row) {
						$this->dba->Query("INSERT INTO ". CSS ." (style_id, name, properties, description) VALUES ('". $style_id ."', '". $this->dba->Quote($row['name']) ."', '". $this->dba->Quote($row['properties']) ."', '". $this->dba->Quote($row['description']) ."')");
					}

					/* Refresh the page */
					header("Location: admin.php?act=css");
				}
			}
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class AdminSelectCSS extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			/* Base template within forum_base.html */
			$template->content		= array('file' => 'admin/admin.html');

			/* Template to choose which css style to edit */
			$template->admin_panel	= array('file' => 'admin/css/selectcss.html');

			/* Set the list of CSS styles from this styleset */
			$template->cssstyles	= $this->dba->Query("SELECT * FROM ". CSS ." WHERE style_id = ". intval($request['id']) ." ORDER BY name DESC");
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

/* Display the CSS editor when editing a single Class */
class AdminCSSEditor extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();

		/* Ancestors bar */
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);

		/* Check if the person is an admin */
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			$template->content				= array('file' => 'admin/admin.html');
			
			if(isset($request['id']) && intval($request['id']) != 0) {

				/* Set the class name & properties */
				$css							= $this->dba->GetRow("SELECT * FROM ". CSS ." WHERE id = ". intval($request['id']) );
				$template['class_name']			= $css['name'];
				
				/* Extra properties to this class */
				$template['properties']			= $css['properties'];
				$template['class_id']			= $css['id'];
				$template['class_description']	= $css['description'];
				
				/* Can we revert this class to its previous state? */
				if(!$css['prev_name'])
					$template->revert_css		= array('hide' => TRUE);

				/* Import all of the Advanced CSS Editor templtes */
				$template->admin_panel			= array('file' => 'admin/css/editor.html');
				$template->color_picker			= array('file' => 'admin/css/colorpicker.html');
				$template->css_type				= array('file' => 'admin/css/type.html');
				$template->css_background		= array('file' => 'admin/css/background.html');
				$template->css_block			= array('file' => 'admin/css/block.html');
				$template->css_box				= array('file' => 'admin/css/box.html');
				$template->css_border			= array('file' => 'admin/css/border.html');
				$template->css_list				= array('file' => 'admin/css/list.html');
				$template->css_positioning		= array('file' => 'admin/css/positioning.html');
				$template->css_extensions		= array('file' => 'admin/css/extensions.html');
			} else {
				return new Error($template['L_CANNOTUSEFEATURE'], $template);
			}
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

/* Display the options Page */
class AdminOptions extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();
		
		/* Nice ancestors bar */
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		
		/* Check user permissions */
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			/* Set the templates */
			$template->content		= array('file' => 'admin/admin.html');
			//$template->admin_panel	= array('file' => 'admin/options.html');
			$template->admin_panel	= array('file' => 'admin/forum_options.html');
			$setting_groups = new SettingsList;
			$template->setting_groups = $setting_groups;
			
			/* Get all of our template sets */
			$templates				= array();
			$fullpath				= get_setting('template', 'path');
			$dir = dir($fullpath);
			while(false !== ($folder = $dir->Read())) {
				if(is_dir(append_slash($fullpath) . $folder) && $folder != '.' && $folder != '..')
					$templates[]	= array('name' => $folder);
			}
			
			/* Get all of our image sets */
			$img_folders = array();
			$fullpath = append_slash(get_setting('config', 'forumurl')) . 'Images';
			$dir = dir($fullpath);
			while(false !== ($folder = $dir->Read())) {
				if(is_dir(append_slash($fullpath) . $folder) && $folder != '.' && $folder != '..')
					$img_folders[] = array('name' => $folder);
			}
			
			/* Get and set the forums options values */
			foreach( $this->dba->GetRow("SELECT * FROM ". FORUMS ." WHERE row_left = 1") as $key=>$val) {
				$template[$key] = $val;
			}

			/* Set the current styleset */
			$template['styleset']	= get_setting('theme', 'styleset');

			/* Set the list of stylesets */
			$template->stylesets	= $this->dba->Query("SELECT * FROM ". STYLES ." ORDER BY name DESC");
			
			/* Set the template and image sets lists */
			$template->templates	= $templates;
			$template->img_folders	= $img_folders;
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}
/*
class AdminManageSQL extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();

		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$template->content = array('file' => 'admin/admin.html');
			$template->admin_panel = array('file' => 'admin/sql.html');
			
			$db_type = get_setting(get_setting('application', 'dba_name'), 'type');
			switch($db_type) {
				case 'sqlite': {
					$sql = "SELECT * FROM sqlite_master WHERE (type = 'table')";
					break;
				}
				case 'mysql': {
					$sql = "SHOW TABLES";
					break;
				}
				case 'pgsql': {
					$sql = NULL;
					break;
				}
			}
			$tables = $this->dba->Query($sql);
			$template->tables = $tables;
			$template['dba_name'] = get_setting('application', 'dba_name') . '&nbsp;('. $tables->NumRows() .')';
		}
		// Set the number of queries 
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}
*/
class AdminPermissions extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();
		
		/* Ancestors Bar */
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		
		/* Check for permissions */
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			/* Set the templates */
			$template->content		= array('file' => 'admin/admin.html');
			$template->admin_panel	= array('file' => 'admin/permissions.html');
			
			/* Set the forums list */
			$template->forums		= new AdminJustForums("row_level > 1", "");
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}
class AdminPrune extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();

		/* Ancestors Bar */
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		
		/* Check permissions */
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			/* Set the templates */
			$template->content		= array('file' => 'admin/admin.html');
			$template->admin_panel	= array('file' => 'admin/prune.html');

			/* Set the list of forums of which we can prune */
			$template->forums		= $this->dba->Query("SELECT * FROM ". FORUMS ." WHERE row_level > 1 AND row_left != 1 AND suspend != 1");
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}
class AdminUsers extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();

		/* Ancestors bar */
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		
		/* Check permissions */
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			/* Set the templates */
			$template->content = array('file' => 'admin/admin.html');
			$template->admin_panel = array('file' => 'admin/users.html');
			
			/* Set the dissallowed user name list and the banned users list */
			$template->disallowed_nicks = $this->dba->Query("SELECT * FROM ". BADNAMES);
			$template->banned_users = $this->dba->Query("SELECT * FROM ". USERS ." WHERE banned = 1");
		} else {
			return new Error($template['L_CANNOTUSEFEATURE'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}
class AdminRanks extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();

		/* Ancestors Bar */
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);

		/* Check permissions */
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			/* Set the templates */
			$template->content		= array('file' => 'admin/admin.html');
			$template->admin_panel	= array('file' => 'admin/ranks.html');

			/* Set the groups and ranks lists */
			$template->groups		= new GroupsIterator;
			$template->ranks		= new RanksIterator;
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}
class AdminGlobalPM extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();

		/* Ancestors Bar */
		$template = CreateAncestors($template, $template['L_GLOBALPM']);

		/* Check permissions */
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			/* Set the post icons and the emoticons */
			$template->posticons = DBA::Open()->Query("SELECT * FROM ". POSTICONS );
			$template->emoticons = DBA::Open()->Query("SELECT * FROM ". EMOTICONS );
			
			/* Set the templates */
			$template->content		= array('file' => 'admin/admin.html');
			$template->admin_panel	= array('file' => 'admin/globalpm.html');
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}
class AdminSaveGlobalPM extends Event {
	protected $dba;
	
	protected function getNumOnLevel() {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". PMSGS ." WHERE level = 1");
	}
	public function Execute(Template $template, Session $session, $request) {
		/* Can we pm? */
		if($template['enablepms'] == 1) {
			/* Create the ancestors bar (if we run into any trouble */
			$template = CreateAncestors($template, $template['L_SAVEMESSAGE']);

			/* Open a connection to the database */
			$this->dba = DBA::Open();
			
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

			try {
				/* Make room for the pm in the pms table by updating the right values */
				@$this->dba->Query("UPDATE ". PMSGS ." SET row_right = row_right+2 WHERE row_left < $left AND row_right >= $left"); // Good
				
				/* Keep updating the pms table by changing all of the necessary left AND right values */
				@$this->dba->Query("UPDATE ". PMSGS ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= $left"); // Good
				
				/* Finally insert our thread into the Posts table */
				@$this->dba->Query("INSERT INTO ". PMSGS ." (row_left, row_right, name, body_text, created, poster_name, poster_id, member_id, member_name, icon) VALUES ($left, $right, '". $request['name'] ."', '". $request['message'] ."', ". $time .", '', 0, 0, '', '{$posticon}')");
			} catch(DBA_Exception $e) {
				return new TplException($e, $template);
			}
			
			/* If we've gotten to this point, reload the page to our recently added thread :) */
			return new Error($template['L_SENTPMESSAGE'] . '<meta http-equiv="refresh" content="2; url=admin.php?act=globalpm">', $template);
		} else {
			return new Error($template['L_FEATUREDENIED'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}
class AdminDeleteNode extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();

		/* Ancestors Bar */
		$template = CreateAncestors($template, $template['L_DELETENODE']);

		/* Check permissions */
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			/* Set the templates */
			$template->content		= array('file' => 'admin/admin.html');
			
			if(isset($request['type']) && intval($request['type']) != 0 && (intval($request['type']) == 1 || intval($request['type']) == 2) && isset($request['id']) && intval($request['id']) != 0) {
				
				/* Deletion message and table to use */
				$message	= array(1 => $template['L_DELETEDPOSTTHREAD'], 2 => $template['L_DELETEDFORUMCAT']);
				$table		= intval($request['type']) == 1 ? POSTS : FORUMS;

				$row		= $this->dba->GetRow("SELECT * FROM ". $table ." WHERE id = ". intval($request['id']));
			
				if(!empty($row) && isset($row['id'])) {
					$prune = new Prune;

					/* Turn the board off for safety reasons */
					$this->dba->Execute("UPDATE ". SETTING ." SET value = '0' WHERE varname = 'bbactive'");
					
					/* Remove the node */
					$prune->KillSingle($row, intval($request['type']));

					/* Turn the board back on */
					$this->dba->Execute("UPDATE ". SETTING ." SET value = '1' WHERE varname = 'bbactive'");
					
					return new Error($message[intval($request['type'])] .'<meta http-equiv="refresh" content="2; url=index.php">', $template);

				} else {
					return new Error($template['L_ERRORUSINGFEATURE'], $template);
				}
			} else {
				return new Error($template['L_ERRORUSINGFEATURE'], $template);
			}

		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}
class LockThread extends Event {
	protected $dba;
	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();

		/* Ancestors Bar */
		$template = CreateAncestors($template, $template['L_LOCKTHREAD']);

		/* Check permissions */
		if(($session['user'] instanceof Member) && ($session['user']['perms'] >= MOD)) {
			
			/* Set the templates */
			$template->content		= array('file' => 'admin/admin.html');
			
			$row = $this->dba->GetRow("SELECT * FROM ". POSTS ." WHERE id = ". intval($request['id']));
		
			if(!empty($row) && isset($row['id'])) {
				/* Lock the thread */
				$this->dba->Execute("UPDATE ". POSTS ." SET row_locked = 1 WHERE id = ". $row['id']);
				
				return new Error($template['L_LOCKEDTHREAD'] .'<meta http-equiv="refresh" content="2; url=viewforum.php?id='. $row['forum_id'] .'">', $template);

			} else {
				return new Error($template['L_INVALIDTHREADID'], $template);
			}
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}
/* Add all of the events */

$app	= new Forum_Controller('admin/null.html');

/* Stuff */
$app->AddEvent('head', new AdminHead);
$app->AddEvent('nav', new AdminMenu);

/* Forum Options */
$app->AddEvent('options', new AdminOptions);
$app->AddEvent('update_options', new AdminUpdateOptions);

/* Categories Stuff */
$app->AddEvent('categories', new AdminCategories);
$app->AddEvent('add_cat', new AdminAddCategory);

/* Generic Stuff */
$app->AddEvent('suspend', new AdminSuspend(1));
$app->AddEvent('unsuspend', new AdminSuspend(0));
$app->AddEvent('update', new AdminUpdate);
$app->AddEvent('delete_single', new AdminDeleteNode);

/* Forums Stuff */
$app->AddEvent('forums', new AdminForums);
$app->AddEvent('lock', new AdminLockForum(1));
$app->AddEvent('unlock', new AdminLockForum(0));
$app->AddEvent('addforum', new AdminAddForum);

/* Users Stuff */
$app->AddEvent('users', new AdminUsers);
$app->AddEvent('update_user', new AdminUpdateUser);
$app->AddEvent('add_badname', new AdminAddBadName);
$app->AddEvent('update_daun', new AdminUpdateBadNames);
$app->AddEvent('remove_badnick', new AdminRemoveBadName);
$app->AddEvent('ban_user', new AdminBanUser(1));
$app->AddEvent('unban_user', new AdminBanUser(0));
$app->AddEvent('redirect_eu', new AdminRedirectEditUser);

/* Groups Stuff */
$app->AddEvent('groups', new AdminGroups);
$app->AddEvent('addgroup', new AdminAddGroup);
$app->AddEvent('editgroup', new AdminUpdateGroup);
$app->AddEvent('delgroup', new AdminDeleteGroup);

/* Censoring Stuff */
$app->AddEvent('censoring', new AdminCensoring);
$app->AddEvent('addbw', new AdminAddBadWord);
$app->AddEvent('deletebw', new AdminDeleteBadWord);
$app->AddEvent('updatebw', new AdminUpdateBadWord);

/* Post icons & Emoticons */
$app->AddEvent('icons', new AdminIcons);
// Posticons
$app->AddEvent('updatepi', new AdminUpdateIcon);
$app->AddEvent('addpi', new AdminAddIcon);
$app->AddEvent('deletepi', new AdminDeleteIcon);
// Emoticons
$app->AddEvent('updateemo', new AdminUpdateIcon);
$app->AddEvent('addemo', new AdminAddIcon);
$app->AddEvent('deleteemo', new AdminDeleteIcon);

/* FAQ related stuff */
$app->AddEvent('faq', new AdminFAQ);
$app->AddEvent('add_faqcat', new AdminAddFAQCat);
$app->AddEvent('del_faqcat', new AdminDelFAQCat);
$app->AddEvent('add_faq', new AdminAddFAQ);
$app->AddEvent('update_faq', new AdminAddFAQ);
$app->AddEvent('del_faq', new AdminDelFAQ);

/* CSS editor stuff */
$app->AddEvent('css', new AdminCSS);
$app->AddEvent('add_style', new AdminAddStyleSet);
$app->AddEvent('delete_style', new AdminDeleteStyleSet);
$app->AddEvent('edit_style', new AdminSelectCSS);
$app->AddEvent('edit_css', new AdminCSSEditor);
$app->AddEvent('update_css', new AdminUpdateCSS);
$app->AddEvent('revert_css', new AdminRevertCSS);
$app->AddEvent('add_cssstyle', new AdminAddCSSClass);
$app->AddEvent('import_style', new AdminImportCSS);

/* Permissions Management */
$app->AddEvent('permissions', new AdminPermissions);
$app->AddEvent('mod_perms', new AdminModPermissions);
$app->AddEvent('update_perms', new AdminUpdatePermissions);

/* Post pruning */
$app->AddEvent('prune', new AdminPrune);
$app->AddEvent('do_prune', new AdminPrunePosts);
$app->AddEvent('lock_thread', new LockThread);

/* User Ranks */
$app->AddEvent('ranks', new AdminRanks);
$app->AddEvent('add_rank', new AdminAddRank(FALSE));
$app->AddEvent('update_rank', new AdminAddRank(TRUE));

/* Private Messaging */
$app->AddEvent('globalpm', new AdminGlobalPM);
$app->AddEvent('send_globalpm', new AdminSaveGlobalPM);

/* CMS */
$app->AddEvent('filemanager', new FileManager);
$app->AddEvent('cms_help', new AdminCMSHelp);

$app->ExecutePage();

?>