<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     options.class.php
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

class SettingsList implements Iterator {
	protected $settinggroups;
	protected $temp;
	protected $dba;

	public function __construct() {
		$this->dba = DBA::Open();
		$query = $this->dba->Query("SELECT * FROM ". SETTING_GROUP );
		
		$this->settinggroups = $query->GetIterator();
	}
	
	public function Current() {
		$this->temp = $this->settinggroups->Current();
		return $this->temp;
	}
	
	public function Key() {
		return $this->settinggroups->Key();
	}
	
	public function Next() {
		return $this->settinggroups->Next();
	}
	
	public function Rewind() {
		return $this->settinggroups->Rewind();
	}
	
	public function Valid() {
		return $this->settinggroups->Valid();
	}
	
	public function GetChildren() {
		$temp = $this->temp;
		return new OptionSettings($temp);
	}
}

class OptionSettings implements Iterator {
	protected $setting;
	protected $temp;
	protected $dba;

	public function __construct($temp) {
		$this->dba = DBA::Open();
		$query = $this->dba->Query("SELECT * FROM ". SETTING ." WHERE settinggroupid = ". $temp['id'] ." ORDER BY displayorder ASC");
		
		$this->setting = $query->GetIterator();
	}
	
	public function Current() {
		$temp = $this->setting->Current();
		if($temp['optioncode'] == 'yesno')
			$temp['input'] = '<select name="'. $temp['varname'] .'" id="'. $temp['varname'] .'"><option value="1">YES</option><option value="0">NO</option></select><script type="text/javascript">setIndex(\''. $temp['value'] .'\', \''. $temp['varname'] .'\');</script>';
		else if($temp['optioncode'] == 'textarea')
			$temp['input'] = '<textarea name="'. $temp['varname'] .'" rows="4" style="width:95%">'. $temp['value'] .'</textarea>';
		else
			$temp['input'] = '<input type="text" name="'. $temp['varname'] .'" value="'. $temp['value'] .'" />';
		return $temp;
	}
	
	public function Key() {
		return $this->setting->Key();
	}
	
	public function Next() {
		return $this->setting->Next();
	}
	
	public function Rewind() {
		return $this->setting->Rewind();
	}
	
	public function Valid() {
		return $this->setting->Valid();
	}
}

class AdminUpdateOptions extends Event {
	public function Execute(Template $template, Session $session, $request) {
		
		$dba = DBA::Open();
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			/*
			$base = Array("name", "description", "closeforums", "closedmessage");
			$forums = Array("threadsperpage", "postsperpage", "maxpolloptions", "allowbbcode", "allowsmilies", "allowposticons", "allowhtml", "allowedhtml", "allowsignatures", "allowavatars", "canpostpolls");
			$all = array_merge($base, $forums);
			$form_errors = '';
			// todo error checking on all options

			$name				= htmlspecialchars($request['name']);
			$description		= htmlspecialchars($request['description']);
			$closeforums		= intval($request['closeforums']);
			$closedmessage		= htmlspecialchars($request['closedmessage']);
			
			$threadsperpage		= intval($request['threadsperpage']);
			$postsperpage		= intval($request['postsperpage']);
			$maxpolloptions		= intval($request['maxpolloptions']);
			$allowbbcode		= intval($request['allowbbcode']);
			$allowsmilies		= intval($request['allowsmilies']);
			$allowposticons		= intval($request['allowposticons']);
			$allowhtml			= intval($request['allowhtml']);
			$allowedhtml		= htmlspecialchars($request['allowedhtml']);
			$allowsignatures	= intval($request['allowsignatures']);
			$allowavatars		= intval($request['allowavatars']);
			$canpostpolls		= intval($request['canpostpolls']);

			ModConfig::ResetVar('pagecraft', array('styleset' => intval($request['styleset']), 'tplfolder' => htmlentities($request['folder']), 'imgfolder' => htmlentities($request['imgfolder'])), FALSE);

			$dba->Query("UPDATE ". FORUMS ." SET name = '{$name}', description = '{$description}', closeforums = $closeforums, closedmessage = '{$closedmessage}' WHERE row_left = 1");
			$dba->Query("UPDATE ". FORUMS ." SET threadsperpage = $threadsperpage, postsperpage = $postsperpage, maxpolloptions = $maxpolloptions, allowbbcode = $allowbbcode, allowsmilies = $allowsmilies, allowposticons = $allowposticons, allowhtml = $allowhtml, allowedhtml = '{$allowedhtml}', allowsignatures = $allowsignatures, allowavatars = $allowavatars, can_pollcreate = $canpostpolls WHERE row_left >= 1");
			*/
			foreach($dba->Query("SELECT * FROM ". SETTING ." WHERE settinggroupid = ". $request['settinggroupid']) as $setting) {
				$new_val = is_numeric($request[$setting['varname']]) ? intval($request[$setting['varname']]) : "'". $dba->Quote($request[$setting['varname']]) ."'";
				try {
					@$dba->Query("UPDATE ". SETTING ." SET value = $new_val WHERE varname = '". $setting['varname'] ."'");
				} catch(DBA_Exception $e) {
					return new TplException($e, $template);
				}
			}

			/* If we've gotten to this point, reload the page to our recently updated options :) */
			return new Error($template['L_OPTIONSUPDATED'] . '<meta http-equiv="refresh" content="1; url=admin.php?act=options">', $template);

			header("Location: admin.php?act=options");
		}
		return TRUE;
	}
}

?>