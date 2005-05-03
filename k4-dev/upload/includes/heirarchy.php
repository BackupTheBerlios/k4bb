<?php
/**
* k4 Bulletin Board, heirarchy.php
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
* @version $Id: heirarchy.php,v 1.5 2005/05/03 21:37:22 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class Heirarchy {
	
	var $dba;

	function Heirarchy() {
		global $_DBA;

		$this->dba	= &$_DBA;
	}
	function removeNode($info, $table) {
		$descendants = (($info['row_right'] - $info['row_left'] - 1) / 2) + 2;
		$descendants = $descendants % 2 == 0 ? $descendants : $descendants+1; // Make it an even number
		
		/**
		 * Create the Queries
		 */
		$delete		= &$this->dba->prepareStatement("DELETE FROM ". $table ." WHERE row_left >= ? AND row_right <= ?");
		$update_a	= &$this->dba->prepareStatement("UPDATE ". $table ." SET row_right = row_right-? WHERE row_left < ? AND row_right > ?");
		$update_b	= &$this->dba->prepareStatement("UPDATE ". $table ." SET row_left = row_left-?, row_right=row_right-? WHERE row_left > ?");
		
		/**
		 * Populate the queries
		 */
		$delete->setInt(1, $info['row_left']);
		$delete->setInt(2, $info['row_right']);

		$update_a->setInt(1, $descendants);
		$update_a->setInt(2, $info['row_left']);
		$update_a->setInt(3, $info['row_left']);

		$update_b->setInt(1, $descendants);
		$update_b->setInt(2, $descendants);
		$update_b->setInt(3, $info['row_left']);
		
		/**
		 * Execute the queries
		 */
		$delete->executeUpdate();
		$update_a->executeUpdate();
		$update_b->executeUpdate();
	}
	function moveUp($parent_node, $table) {

		$num_children		= @(($parent_node['row_right'] - $parent_node['row_left'] - 1) / 2);
		
		if($num_children > 0) {

			/**
			 * Create the Queries
			 */
			$delete				= &$this->dba->prepareStatement("DELETE FROM ". $table ." WHERE row_left = ? AND row_right = ?");
			$update_a			= &$this->dba->prepareStatement("UPDATE ". $table ." SET row_left=row_left-1, row_right=row_right-1 WHERE row_left > ? AND row_right < ?");
			$update_b			= &$this->dba->prepareStatement("UPDATE ". $table ." SET row_right=row_right-2 WHERE row_left > ?");
			
			/**
			 * Populate the queries
			 */
			$delete->setInt(1, $parent_node['row_left']);
			$delete->setInt(2, $parent_node['row_right']);

			$update_a->setInt(1, $parent_node['row_left']);
			$update_a->setInt(2, $parent_node['row_right']); // row_left ?

			$update_b->setInt(1, $parent_node['row_right']);
			
			/**
			 * Execute the queries
			 */
			$delete->executeUpdate();
			$update_a->executeUpdate();
			$update_b->executeUpdate();
		
		} else {
			$this->removeNode($parent_node, $table);	
		}
	}
}

?>