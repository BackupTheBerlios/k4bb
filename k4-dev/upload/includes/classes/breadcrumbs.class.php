<?php
/**
* k4 Bulletin Board, breadcrumbs.class.php
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
* @author Geoffrey Goodman
* @version $Id: breadcrumbs.class.php,v 1.5 2005/05/16 02:11:54 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

function BreadCrumbs(&$template, $location = NULL, $row_left = FALSE, $row_right = FALSE) {
	
	global $_DBA;

	if($location != NULL && !$row_left && !$row_right) {
		
		$template->setVar('current_location', $location);
	
	} else if($row_left && $row_right) {
		
		$query = $_DBA->prepareStatement("SELECT * FROM ". INFO ." WHERE row_left <= ? AND row_right >= ? ORDER BY row_left ASC");
		$query->setInt(1, intval($row_left));
		$query->setInt(2, intval($row_right));
		
		$breadcrumbs	= array();

		$result			= &$query->executeQuery();

		while($result->next()) {
			$current	= $result->current();

			switch($current['row_type']) {
				/* Categories and forums */
				case 1:
				case 2: {
					$current['location'] = 'viewforum.php?id='. $current['id'];
					break;
				}
				/* Thread */
				case 4: {
					$current['location'] = 'viewtopic.php?id='. $current['id'];
					break;
				}
				/* Reply */
				case 8: {
					$current['location'] = 'findpost.php?id='. $current['id'];
					break;
				}
				/* Gallery Category */
				case 16: {
					$current['location'] = 'viewgallery.php?id='. $current['id'];
					break;
				}
				/* Gallery Image */
				case 32: {
					$current['location'] = 'viewimage.php?id='. $current['id'];
					break;
				}
			}

			$breadcrumbs[] = $current;
		}
		
		/* Free up some memory */
		$result->freeResult();
		
		/* Check if we have a preset location or not */
		if($location == NULL) {
			$current_location = array_pop($breadcrumbs);
			$template->setVar('current_location', $current_location['name']);
		} else {
			$template->setVar('current_location', $location);
		}

		/* Set the Breadcrumbs list */
		$template->setList('breadcrumbs', new FAArrayIterator($breadcrumbs));
	}

	return $template;
}

?>