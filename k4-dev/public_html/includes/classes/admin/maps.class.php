<?php
/**
* k4 Bulletin Board, maps.class.php
*
* Copyright (c) 2004, Peter Goodman
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
* @author Geoffrey Goodman
* @author James Logsdon
* @version $Id: maps.class.php,v 1.1 2005/04/05 02:32:37 necrotic Exp $
* @package k42
*/

class AdminMAPSGui extends Event {
	public function Execute(Template $template, Session $session, $request, $dba) {		
		
		if(($session['user'] instanceof Member) && ($session['user']['perms'] >= ADMIN)) {
			
			$maps	= new MAPSIterator($dba->executeQuery("SELECT * FROM ". MAPS ." ORDER BY row_left ASC"));
			$template->setList('maps_list', $maps);
			
			$template->setFile('content', 'admin/admin.html');
			$template->setFile('admin_panel', 'admin/maps_tree.html');
		} else {
			$template->setError('content', $template->getVar('L_YOUNEEDPERMS'));
		}

		return TRUE;
	}
}

class MAPSIterator extends FAArrayIterator {
	
	public function __construct($data = NULL) {
		
		parent::__construct($data);
	}

	public function &current() {
		$temp			= parent::current();
		
		$num_children	= @(($temp['row_right'] - $temp['row_left'] - 1) / 2);
		$temp['level']	= str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $temp['row_level']-1);

		foreach(array('add', 'edit', 'del', 'view') as $method) {
			switch($temp[$method]) {
				case 0: { $temp[$method] = '--'; break; }
			}
		}
		
		$temp['name']	= $temp['inherit'] == 1 ? '<span style="color: green;">'. $temp['name'] .'</span>' : '<span style="color: firebrick;">'. $temp['name'] .'</span>';
				
		return $temp;
	}
}

?>