<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     forum.class.php
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

require_once('forumlist.class.php');

class Forum {
	protected $dba;
	protected $dscn = 1;

	public function __construct($dscn_id = FALSE) {
		$this->dba = DBA::Open();
		$this->dscn = !$dscn_id ? 1 : intval($dscn_id);
	}

	public function getForums($suspend = 0) {
		return $this->dba->Query("SELECT * FROM ". FORUMS ." WHERE suspend = $suspend ORDER BY row_left ASC");
	}
	
	public function getCategory($id) {
		// seems stupid how I'm using nested sets, but that the iterators take them like shit, so most o
		return $this->dba->Query("SELECT * FROM ". FORUMS ." WHERE row_left >= (SELECT row_left FROM ". FORUMS ." WHERE id = $id) AND row_right <= (SELECT row_right FROM ". FORUMS ." WHERE id = $id) AND id != 1 AND suspend != 1 ORDER BY row_left ASC LIMIT 1");
	}

	public function getThreadPath($left, $right) {
		return $this->dba->Query("SELECT * FROM ". FORUMS ." WHERE row_left < $left AND row_right > $right ORDER BY row_left ASC");
	}

	public function getForum($id) {
		return $this->dba->GetRow("SELECT * FROM ". FORUMS ." WHERE id = $id");
	}

	public function getChildren($left, $right) {
		return $this->dba->Query("SELECT * FROM ". FORUMS ." WHERE row_left > $left AND row_right < $right");
	}
	public function setForum($name, $description) {
		$name			= BB::Open(stripslashes($name))->Execute();
		$description	= BB::Open(stripslashes($description))->Execute();
		
		return $this->dba->Query("INSERT INTO ". FORUMS ." (name, description, row_left, row_right) VALUES ('{$name}', '{$description}', 1, 2)");
	}
	public function setForumPermissions($forum_id, $request) {
		$dba = DBA::Open();
		foreach($request as $key=>$val) {
			$request['key'] = $dba->Quote(intval($val));
		}
		if($dba->Query("UPDATE ". FORUMS ." SET can_attach = ". $request['can_attach'] .", can_view = ". $request['can_view'] .", can_read = ". $request['can_read'] .", can_post = ". $request['can_post'] .", can_reply = ". $request['can_reply'] .", can_edit = ". $request['can_edit'] .", can_sticky = ". $request['can_sticky'] .", can_announce = ". $request['can_announce'] .", can_vote = ". $request['can_vote'] .", can_pollcreate = ". $request['can_pollcreate'] ." WHERE id = $forum_id"))
			return TRUE;
		else
			return FALSE;
	}

	private function getNumOnLevel($parent_id) {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". FORUMS ." WHERE parent_id = $parent_id");
	}
	public function addNode($name, $parent_id, $description = FALSE, $suspend = FALSE, $locked = FALSE, $position = FALSE, $is_link = FALSE, $link_href = FALSE, $private = FALSE, $password = FALSE) {
		$stack			= $this->getForums();
		$name			= BB::Open($name, FALSE, TRUE, FALSE)->Execute();
		$description	= isset($description) ? BB::Open($description)->Execute() : NULL;
		$suspend		= isset($suspend) ? intval($suspend) : 0;
		$lock			= isset($locked) ? intval($locked) : 0;
		$link_href		= isset($link_href) ? $link_href : '';
		$password		= isset($password) ? $password : '';
		
		$parent = $this->dba->GetRow("SELECT * FROM ". FORUMS ." WHERE id = $parent_id");

		if($parent_id != 1) {
			if((($parent['row_right'] - $parent['row_left'] - 1) / 2) > 0) {
				$left = $parent['row_right'];
			} else {
				if($this->getNumOnLevel($parent_id) > 0) {
					$left = $parent['row_right'];
				} else {
					$left = $parent['row_left']+1;
				}
			}
			$depth = $parent['row_level']+1;
			$right = $left+1;
			
			/* Auto set the order for this node */
			if($depth > 1) {
				$top = $this->dba->GetRow("SELECT * FROM ". FORUMS ." WHERE row_right <= $left ORDER BY row_left ASC LIMIT 1");

				$temp_order = $this->dba->GetValue("SELECT MAX(f_order) FROM ". FORUMS ." WHERE row_left >= ". $top['row_left'] ." AND row_right <= ". $top['row_right'] );
			}

			$order = $temp_order == 0 ? 1 : $temp_order+1;
			
			$position = $position != FALSE ? intval($position) : $order;
			
			try {
				
				@$this->dba->Query("UPDATE ". FORUMS ." SET row_right = row_right+2, subforums = subforums+1 WHERE row_left < $left AND row_right >= $left"); // Good
						
				@$this->dba->Query("UPDATE ". FORUMS ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= $left"); // Good
				
				@$this->dba->Query("INSERT INTO ". FORUMS ." (row_left, row_right, name, description, parent_id, row_level, f_order, suspend, row_lock, is_link, link_href, private, pass) VALUES ($left, $right, '{$name}', '{$description}', $parent_id, $depth, $position, $suspend, $lock, $is_link, '{$link_href}', $private, '{$password}')");
			
			} catch(DBA_Exception $e) {
				exit('<pre>'. $e->getMessage() .'</pre>');
			}

		} else {
			$right = $this->dba->GetValue("SELECT row_right FROM ". FORUMS ." WHERE row_left = 1")-1;
			$last_child = $this->dba->GetRow("SELECT * FROM ". FORUMS ." WHERE row_right = $right");
			if(is_array($last_child) && !empty($last_child)) {
				$left = $last_child['row_right']+1;
				$right = $left+1;
			} else {
				$left = 2;
				$right = 3;
			}
			$depth = 1;
			$temp_order = $this->dba->GetValue("SELECT MAX(f_order) FROM ". FORUMS ." WHERE row_level = 1");
			$order = !$temp_order ? 1 : $temp_order+1;
			
			$this->dba->Query("UPDATE ". FORUMS ." SET row_right = row_right+2 WHERE row_left = 1");
			$this->dba->Query("INSERT INTO ". FORUMS ." (name, row_left, row_right, parent_id, row_level, f_order) VALUES ('{$name}', $left, $right, $parent_id, $depth, $order)");
		}
		return TRUE;
	}

}

?>