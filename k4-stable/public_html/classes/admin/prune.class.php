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

class AdminPrunePosts extends Event {

	public function Execute(Template $template, Session $session, $request) {
		
		$this->dba = DBA::Open();
		$template = CreateAncestors($template, $template['L_ADMINPANEL']);
		if(($session['user'] instanceof Member) && ($session['user']['perms'] & ADMIN)) {
		
			if(isset($request['forum']) && isset($request['days'])) {
				
				/* Turn the board off for safety reasons */
				$this->dba->Execute("UPDATE ". SETTING ." SET value = '0' WHERE varname = 'bbactive'");

				$days = intval($request['days']);
				$forum = intval($request['forum']);
				
				$created = $days == 0 ? time() : (time() - ($days * 24 * 3600));
				$prune = new Prune;

				if($forum == -1) {
					foreach($this->dba->Query("SELECT * FROM ". POSTS ." WHERE row_status != 2 AND row_status != 3 AND row_type = 2 AND row_right-row_left-1 = 0 AND created <= ". $created ) as $post) {
						$prune->KillNode($post);
					}
				} else {
					foreach($this->dba->Query("SELECT * FROM ". POSTS ." WHERE parent_id = ". $forum ." AND row_status != 2 AND row_status != 3 AND row_type = 2 AND row_right-row_left-1 = 0 AND created <= ". $created ) as $post) {
						$prune->KillNode($post);
					}
				}

				/* Turn the board back on */
				$this->dba->Execute("UPDATE ". SETTING ." SET value = '1' WHERE varname = 'bbactive'");
				
				return new Error($template['L_PRUNESUCCESS'] .'<meta http-equiv="refresh" content="2; url=admin.php?act=prune">', $template);

			} else {
				return new Error($template['L_FORUMDOESNTEXIST'], $template);
			}
		
		}
		return TRUE;
	}

}

?>