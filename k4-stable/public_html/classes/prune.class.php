<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     prune.class.php
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

class Prune {
	protected $dba;
	public function __construct() {
		$this->dba = DBA::Open();
	}
	public function KillNode($row) {
		/* Remove the Node entirely */
		$this->dba->Execute("DELETE FROM ". POSTS ." WHERE id = ". $row['id'] );
		/* Fix the tree */
		$left = $row['row_left'];
		$val = @intval(@($row['row_right'] - $row['row_left'] - 1)/2) + 2;

		$this->dba->Execute("UPDATE ". FORUMS ." SET row_right = row_right-$val WHERE row_left < $left AND row_right > $left");
		$this->dba->Execute("UPDATE ". FORUMS ." SET row_left = row_left-$val, row_right=row_right-$val WHERE row_left > $left");
		
		$this->dba->Execute("UPDATE ". POSTS ." SET row_right = row_right-$val WHERE row_left < $left AND row_right > $left");
		$this->dba->Execute("UPDATE ". POSTS ." SET row_left = row_left-$val, row_right=row_right-$val WHERE row_left > $left");
		
		if($row['row_type'] == 2) {
			if($this->dba->GetValue("SELECT thread_id FROM ". FORUMS ." WHERE id = ". $row['parent_id'] ) == $row['id'])
				$this->dba->Query("UPDATE ". FORUMS ." SET thread_id = 0, thread_name = '', thread_uname = '', thread_uid = 0, thread_created = '' WHERE id = ". $row['parent_id'] );
		}
		$this->dba->Query("UPDATE ". USERS ." SET posts = posts-1 WHERE id = ". $row['poster_id'] );
		$this->dba->Query("UPDATE ". FORUMS ." SET posts = posts-1, threads = threads-1 WHERE id = ". $row['parent_id'] );
		return TRUE;
	}
	/* Slightly more intense than pruning, this works for one node only
	@param $type = Thread/post or Forum
				 - 1 for thread
				 - 2 for forum
	*/
	public function KillSingle($row, $type = 1) {
		/* Update the Users table */
		foreach($this->dba->Query("SELECT * FROM ". POSTS ." WHERE row_left >= ". $row['row_left'] ." AND row_right <= ". $row['row_right']) as $p) {
			$this->dba->Execute("UPDATE ". USERS ." SET posts = posts-1 WHERE id = ". $p['poster_id']);
		}

		$num_children = @intval(@($row['row_right'] - $row['row_left'] - 1)/2);

		/* Update the forum */
		if($type == 1)
			$this->dba->Query("UPDATE ". FORUMS ." SET posts = posts-". $num_children ."-1, threads = threads-1 WHERE id = ". $row['forum_id'] );

		/* Remove the Node and all child nodes entirely */
		$this->dba->Execute("DELETE FROM ". FORUMS ." WHERE row_left >= ". $row['row_left'] ." AND row_right <= ". $row['row_right']);
		$this->dba->Execute("DELETE FROM ". POSTS ." WHERE row_left >= ". $row['row_left'] ." AND row_right <= ". $row['row_right']);
		

		/* Fix the tree */
		$left = $row['row_left'];
		
		/* This tells us how much to decrease each left and right values by */
		$val = $num_children + 2;
		$val = $val % 2 == 0 ? $val : $val+1; // Make it an even number

		/* Update the forums table */
		$this->dba->Execute("UPDATE ". FORUMS ." SET row_right = row_right-$val WHERE row_left < $left AND row_right > $left");
		$this->dba->Execute("UPDATE ". FORUMS ." SET row_left = row_left-$val, row_right=row_right-$val WHERE row_left > $left");
		
		/* Update the posts table */
		$this->dba->Execute("UPDATE ". POSTS ." SET row_right = row_right-$val WHERE row_left < $left AND row_right > $left");
		$this->dba->Execute("UPDATE ". POSTS ." SET row_left = row_left-$val, row_right=row_right-$val WHERE row_left > $left");
		
		if($type == 1) {
			/* If this is a thread, and moreover, the last post on the forum, change that */
			if($row['row_type'] == 2) {
				if($this->dba->GetValue("SELECT thread_id FROM ". FORUMS ." WHERE id = ". $row['parent_id'] ) == $row['id'])
						$this->dba->Query("UPDATE ". FORUMS ." SET thread_id = 0, thread_name = '', thread_uname = '', thread_uid = 0, thread_created = '' WHERE id = ". $row['parent_id'] );
				if($row['poll_question'] != '') {
					$this->dba->Execute("DELETE FROM ". POLLOPTIONS ." WHERE poll_id = ". $row['id']);
					$this->dba->Execute("DELETE FROM ". POLLVOTES ." WHERE poll_id = ". $row['id']);
				}
				$this->dba->Execute("DELETE FROM ". RATINGS ." WHERE thread_id = ". $row['id']);
			/* If this is a reply, and if it is the last posted on a thread, change that */
			} else if($row['row_type'] == 4) {
				if($this->dba->GetValue("SELECT reply_id FROM ". POSTS ." WHERE id = ". $row['parent_id'] ) == $row['id'])
					$this->dba->Query("UPDATE ". POSTS ." SET reply_id = 0, reply_uname = '', reply_uid = 0, last_reply = 0 WHERE id = ". $row['parent_id'] );
			}
		}
	}
}

?>