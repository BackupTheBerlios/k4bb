<?php
/**
* k4 Bulletin Board, categories.class.php
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
* @version $Id: categories.class.php,v 1.2 2005/04/11 02:16:54 k4st Exp $
* @package k42
*/

class MarkCategoryForumsRead extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		
		if(!isset($request['id']) || !$request['id'] || intval($request['id']) == 0) {
			/* set the breadcrumbs bit */
			$template		= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
			$template->setInfo('cotent', $template->getVar('L_INVALIDFORUMASREAD'), FALSE);
		} else {
			
			$forum			= $dba->getRow("SELECT * FROM ". INFO ." WHERE id = ". intval($request['id']));
			
			if(!$forum || !is_array($forum) || empty($forum)) {
				/* set the breadcrumbs bit */
				$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
				$template->setInfo('cotent', $template->getVar('L_INVALIDFORUMASREAD'), FALSE);
			} else {
				
				if($forum['row_type'] & CATEGORY) {
					
					/* Set the Breadcrumbs bit */
					$template					= BreadCrumbs($template, $template->getVar('L_MARKFORUMSREAD'), $forum['row_left'], $forum['row_right']);
					
					/* Get the forums of this Category */
					$result						= $dba->executeQuery("SELECT * FROM ". INFO ." WHERE row_left > ". $forum['row_left'] ." AND row_right < ". $forum['row_right'] ." AND row_type = ". FORUM);
					
					$forums						= isset($request['forums']) && $request['forums'] != null && $request['forums'] != '' ? @unserialize($request['forums']) : array();

					/* Loop through the forums */
					while($result->next()) {
						
						$temp = $result->current();
						
						$forums[$temp['id']]	= array();
						
					}
					
					$forums						= serialize($forums);

					/* Cache some info to set a cookie on the next refresh */
					bb_setcookie_cache('forums', $forums, time() + ini_get('session.gc_maxlifetime'));

					$template->setInfo('content', sprintf($template->getVar('L_MARKEDFORUMSREADCAT'), $forum['name']), TRUE);
					$template->setRedirect('viewforum.php?id='. $forum['id'], 3);

				} else {
					/* set the breadcrumbs bit */
					$template	= BreadCrumbs($template, $template->getVar('L_INVALIDFORUM'));
					$template->setInfo('content', $template->getVar('L_INVALIDFORUMASREAD'), FALSE);
				}
			}
		}

		return TRUE;
	}
}

class CategoriesIterator extends FAProxyIterator {
	var $dba;
	var $result;

	function CategoriesIterator($query = NULL) {
		global $_CONFIG, $_DBA, $_QUERYPARAMS;
		
		$this->query_params	= $_QUERYPARAMS;
		
		$query_params		= $this->query_params['info'] . $this->query_params['category'];

		$query				= $query == NULL ? "SELECT $query_params FROM ". INFO ." i LEFT JOIN ". CATEGORIES ." c ON c.category_id = i.id AND i.row_type = ". CATEGORY ." ORDER BY i.row_order ASC" : $query;
		
		$this->result		= &$_DBA->executeQuery($query);

		parent::FAProxyIterator($this->result);
	}

	function &current() {
		$temp = parent::current();
		
		cache_forum($temp);
		
		if(($temp['row_right'] - $temp['row_left'] - 1) > 0) {
			
			$query_params	= $this->query_params['info'] . $this->query_params['forum'];

			$temp['forums'] = &new ForumsIterator("SELECT $query_params FROM ". INFO ." i LEFT JOIN ". FORUMS ." f ON f.forum_id = i.id WHERE i.row_left > ". $temp['row_left'] ." AND i.row_right < ". $temp['row_right'] ." AND i.row_type = ". FORUM ." AND i.parent_id = f.category_id ORDER BY i.row_order ASC");
		}

		/* Should we free the result? */
		if($this->row == $this->size-1)
			$this->result->freeResult();

		return $temp;
	}
}

?>