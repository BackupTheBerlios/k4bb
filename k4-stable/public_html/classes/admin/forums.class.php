<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     forums.class.php
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

/* The Event Classes */
class AdminAddCategory extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			if(!$request['name'])
				return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_NAME']), $template);

			$name = DBA::Open()->Quote($request['name']);
			$forum = new Forum;
			if($forum->addNode($name, 1))
				header("Location: admin.php?act=categories");
		}
		return TRUE;
	}
}
class AdminUpdate extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			if(!$request['name'])
				return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_NAME']), $template);
			if(!$request['position'])
				return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_POSITION']), $template);
			if(!$request['id'])
				return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_ID']), $template);
			
			$dba = DBA::Open();

			$name			= BB::Open($dba->Quote($request['name']), FALSE, TRUE, FALSE)->Execute();
			$description	= isset($request['description']) ? BB::Open($dba->Quote($request['description']))->Execute() : NULL;
			$position		= intval($request['position']);
			$id				= intval($request['id']);
			$act			= isset($request['description']) ? 'forums' : 'categories';
			$extra			= '';
			if($act == 'forums')
				$extra = ', is_link = '. intval($request['is_link']) .', link_href = \''. $dba->Quote($request['link_href']) .'\', private = '. intval($request['private']) .', pass = \''. $dba->Quote($request['pass']) .'\' ';
			if($dba->Query("UPDATE ". FORUMS ." SET name = '{$name}', description = '{$description}', f_order = $position $extra WHERE id = $id"))
				header("Location: admin.php?act=$act");
		}
		return TRUE;
	}
}
class AdminSuspend extends Event {
	protected $suspension;
	public function __construct($suspension) {
		$this->suspension = $suspension;
	}
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			
			//if((!isset($request['categories']) && !@$request['categories']) || (!isset($request['forums']) && !@$request['forums']))
			//	return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_FORUM']), $template);

			$id = isset($request['categories']) ? intval($request['categories']) : intval($request['forums']);
			$generic = DBA::Open()->GetRow("SELECT * FROM ". FORUMS ." WHERE id = $id");
			if(isset($request['forums'])) {
				$category = DBA::Open()->GetRow("SELECT * FROM ". FORUMS ." WHERE id = ". $generic['parent_id'] );
				if($this->suspension == 0) { //unsuspend
					if($category['suspend'] == 1)
						$this->suspension = 1;
				}
				$act = 'forums';
			} else {
				$act = 'categories';
			}
			if(DBA::Open()->Query("UPDATE ". FORUMS ." SET suspend = $this->suspension WHERE row_left >= ". $generic['row_left'] ." AND row_right <= ". $generic['row_right'] ))
				header("Location: admin.php?act=$act");
		}
		return TRUE;
	}
}

class AdminLockForum extends Event {
	protected $lock;
	public function __construct($lock) {
		$this->lock = $lock;
	}
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {

			if(!$request['forums'])
				return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_FORUM']), $template);

			$id = intval($request['forums']);
			if(DBA::Open()->Query("UPDATE ". FORUMS ." SET row_lock = '{$this->lock}' WHERE id = '{$id}'"))
				header("Location: admin.php?act=forums");
		}
		return TRUE;
	}
}

class AdminAddForum extends Event {
	public function Execute(Template $template, Session $session, $request) {
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
			$forum = new Forum;

			if(!$request['name'])
				return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_NAME']), $template);
			if(!$request['description'])
				return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_DESCRIPTION']), $template);
			if(!$request['parent_id'])
				return new Error(sprintf($template['L_REQUIREDFIELDSSF'], $template['L_CHILDOF']), $template);
			
			$dba = DBA::Open();
			
			if($forum->addNode($dba->Quote($request['name']), intval($request['parent_id']), $dba->Quote($request['description']), intval($request['suspend']), intval($request['lock']), FALSE, intval($request['is_link']), $dba->Quote($request['link_href']), intval($request['private']), $dba->Quote($request['pass'])))
				header("Location: admin.php?act=forums");
		}
		return TRUE;
	}
}

class AdminJustForums implements Iterator {
	protected $forums;
	public function __construct($level, $extra) {
		$this->forums = DBA::Open()->Query("SELECT * FROM ". FORUMS ." WHERE row_left != '1' AND $level $extra ORDER BY f_order ASC")->GetIterator();
	}
	
	public function Current() {
		$temp = $this->forums->Current();
		$temp['name'] = stripslashes($temp['name']);
		return $temp;
	}
	
	public function Key() {
		return $this->forums->Key();
	}
	
	public function Next() {
		return $this->forums->Next();
	}
	
	public function Rewind() {
		return $this->forums->Rewind();
	}
	
	public function Valid() {
		return $this->forums->Valid();
	}
}

class AdminAllForums implements Iterator {
	protected $forums;
	public function __construct() {
		$this->forums = DBA::Open()->Query("SELECT * FROM ". FORUMS ." WHERE row_left != '1' ORDER BY row_left ASC")->GetIterator();
	}
	
	public function Current() {
		$temp = $this->forums->Current();
		$temp['name'] = stripslashes($temp['name']);
		$temp['indent_level'] = str_repeat(" - ", $temp['row_level']);
		return $temp;
	}
	
	public function Key() {
		return $this->forums->Key();
	}
	
	public function Next() {
		return $this->forums->Next();
	}
	
	public function Rewind() {
		return $this->forums->Rewind();
	}
	
	public function Valid() {
		return $this->forums->Valid();
	}
}

class AdminEditForums implements Iterator {
	protected $forums;
	protected $temp;
	public function __construct() {
		$this->forums = DBA::Open()->Query("SELECT * FROM ". FORUMS ." WHERE row_left != '1' AND row_level = '1' ORDER BY f_order ASC")->GetIterator();
	}
	
	public function Current() {
		$this->temp = $this->forums->Current();
		$temp = $this->forums->Current();
		
		$temp['name']			= BB::Open('')->Revert(stripslashes($temp['name']));
		$temp['description']	= BB::Open('')->Revert(stripslashes($temp['description']));

		return $temp;
	}
	
	public function Key() {
		return $this->forums->Key();
	}
	
	public function Next() {
		return $this->forums->Next();
	}
	
	public function Rewind() {
		return $this->forums->Rewind();
	}
	
	public function Valid() {
		return $this->forums->Valid();
	}
	public function GetChildren() {
		return new AdminEditForumsChildren(DBA::Open()->Query("SELECT * FROM ". FORUMS ." WHERE	row_left > '". $this->temp['row_left'] ."' AND row_right < '". $this->temp['row_right'] ."' ORDER BY f_order ASC"));
	}
}

class AdminEditForumsChildren implements Iterator {
	protected $forums;
	public function __construct($query) {
		$this->forums = $query->GetIterator();
	}
	
	public function Current() {
		$temp = $this->forums->Current();
		
		$temp['name']			= BB::Open('')->Revert(stripslashes($temp['name']));
		$temp['description']	= BB::Open('')->Revert(stripslashes($temp['description']));

		return $temp;
	}
	
	public function Key() {
		return $this->forums->Key();
	}
	
	public function Next() {
		return $this->forums->Next();
	}
	
	public function Rewind() {
		return $this->forums->Rewind();
	}
	
	public function Valid() {
		return $this->forums->Valid();
	}
}


?>