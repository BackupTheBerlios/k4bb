<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     navigator.tag.php
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

class TPL_Navigator extends TPL_Component {
	public function __toString() {
		$data	= '';
		
		if(isset($this->attribs['use']))
			$limit = "\$template['". $this->attribs['use'] ."']";
		if ((!isset($limit) || !isset($this->attribs['limit'])) || !isset($this->attribs['list_id']))
			return $data;
		
		$limit		= !isset($limit) ? "intval(". $this->attribs['limit'] .")" : $limit;
		$before		= !isset($this->attribs['before']) ? 3 : $this->attribs['before'];
		$after		= !isset($this->attribs['after']) ? 3 : $this->attribs['after'];
		$list		= $this->attribs['list_id'];
		
		//echo $limit;

		$data	.= "<?php \$limit = !isset(\$_GET['limit']) ? ". $limit ." : intval(\$_GET['limit']); ?>\n";
		$data	.= "<?php \$before = {$before}; ?>\n";
		$data	.= "<?php \$after = {$after}; ?>\n";
		$data	.= "<?php \$id = intval(@\$_GET['id']); ?>\n";
		$data	.= "<?php \$start = intval(@\$_GET['start']); ?>\n";
		$data	.= "<?php \$php_self = \$_SERVER['PHP_SELF']; ?>\n";
		$data	.= "<?php if (\$template->{$list} && \$template->{$this->id} = \$template->{$list} && (\$template['total_posts'] > \$limit)): ?>\n";
		$data	.= "<?php \$pages = @ceil(\$template['total_posts'] / \$limit); ?>\n";
		$data	.= "<?php if(\$pages > 1): ?>\n";

		foreach ($this->children as $child) {
			$data	.= $child->__toString();
		}

		$data	.= "<?php else: echo '<td class=\"alt2\">1</td>'; endif; else: echo '<td class=\"alt2\">1</td>'; endif; ?>";
		
		return $data;
	}
}
class TPL_ListFirst extends TPL_Navigator {
	public function __toString() {
		$data	= '';

		if (!isset($this->attribs['class']))
			return $data;
		
		$class		= htmlspecialchars($this->attribs['class']);			

		$data	.= "<?php echo '<td class=\"{$class}\"><a href=\"'.\$php_self.'?id='.\$id.'&start=0&limit='.\$limit.'\" class=\"minitext\">'; ?>\n";

		foreach ($this->children as $child) {
			$data	.= $child->__toString();
		}

		$data	.= "<?php echo '</a></td>'; ?>\n";
		
		return $data;
	}
}

class TPL_ListPrev extends TPL_Navigator {
	public function __toString() {
		$data	= '';

		if (!isset($this->attribs['class']))
			return $data;
		
		$class		= htmlspecialchars($this->attribs['class']);			
		
		$data	.= "<?php \$prev_start = ((\$start-\$limit) != 0 && (\$start-\$limit) > 0) ? (\$start-\$limit) : 0; ?>\n";
		$data	.= "<?php echo '<td class=\"{$class}\"><a href=\"'.\$php_self.'?id='.\$id.'&start='.\$prev_start.'&limit='.\$limit.'\" class=\"minitext\">'; ?>";

		foreach ($this->children as $child) {
			$data	.= $child->__toString();
		}

		$data	.= "<?php echo '</a></td>'; ?>";
		
		return $data;
	}
}

class TPL_ListPage extends TPL_Navigator {
	public function __toString() {
		$data	= '';

		if (!isset($this->attribs['class']) || !isset($this->attribs['separator']))
			return $data;

		$separator	= htmlspecialchars($this->attribs['separator']);
		$class		= htmlspecialchars($this->attribs['class']);
		
		$data	.= "<?php \$page_start = (((\$start/\$limit)-\$before) < 0) ? 0 : ((\$start/\$limit)-\$before); ?>\n";
		$data	.= "<?php \$page_end = (((\$start/\$limit)+(\$after+1)) > \$pages) ? \$pages : ((\$start/\$limit)+(\$after+1)); ?>\n";
		$data	.= "<?php \$extra = isset(\$_GET['display']) ? '&display='.\$_GET['display'] : NULL; ?>\n";

		$data	.= "<?php for(\$i=\$page_start;\$i<\$page_end;\$i++): ?>\n";

		$data	.= "<?php if(((\$limit*(\$i+1))-\$limit) == \$start): ?>\n";
		$data	.= "<?php echo '<td class=\"{$class}\"><a href=\"'.\$php_self.'?id='.\$id.'&start='.(\$i*\$limit).'&limit='.\$limit.\$extra.'\" class=\"minitext\"><strong>'.(\$i+1).'</strong></a></td>'; if((\$i+1) != \$page_end): echo '{$separator}'; endif; ?>\n";
		$data	.= "<?php else: echo '<td class=\"{$class}\"><a href=\"'.\$php_self.'?id='.\$id.'&start='.(\$i*\$limit).'&limit='.\$limit.\$extra.'\" class=\"minitext\">'.(\$i+1).'</a></td>'; if((\$i+1) != \$page_end): echo '{$separator}';  endif; ?>\n";
		
		$data	.= "<?php endif; endfor; ?>\n";
		
		
		return $data;
	}
}

class TPL_ListNext extends TPL_Navigator {
	public function __toString() {
		$data	= '';

		if (!isset($this->attribs['class']))
			return $data;
		
		$class		= htmlspecialchars($this->attribs['class']);			
		
		$data	.= "<?php \$next_start = ((\$start+\$limit) < ((\$pages*\$limit)-\$limit)) ? (\$start+\$limit) : ((\$pages*\$limit)-\$limit); ?>\n";
		$data	.= "<?php echo '<td class=\"{$class}\"><a href=\"'.\$php_self.'?id='.\$id.'&start='.\$next_start.'&limit='.\$limit.'\" class=\"minitext\">'; ?>\n";

		foreach ($this->children as $child) {
			$data	.= $child->__toString();
		}

		$data	.= "<?php echo '</a></td>'; ?>";
		
		return $data;
	}
}

class TPL_ListLast extends TPL_Navigator {
	public function __toString() {
		$data	= '';

		if (!isset($this->attribs['class']))
			return $data;
		
		$class		= htmlspecialchars($this->attribs['class']);			

		$data	.= "<?php echo '<td class=\"{$class}\"><a href=\"'.\$php_self.'?id='.\$id.'&start='.((\$pages*\$limit)-\$limit).'&limit='.\$limit.'\" class=\"minitext\">'; ?>\n";

		foreach ($this->children as $child) {
			$data	.= $child->__toString();
		}

		$data	.= "<?php echo '</a></td>'; ?>";
		
		return $data;
	}
}

/* Custom ones */

/* For the search field */

class TPL_UrlFirst extends TPL_Navigator {
	public function __toString() {
		$data	= '';

		if (!isset($this->attribs['class']))
			return $data;
		
		$class		= htmlspecialchars($this->attribs['class']);
		
		if(!isset($this->attribs['params']))
			return $data;
		

		$data	.= "<?php \$extra = NULL; \$url = new Url(\$php_self); \$get = explode(\";\", '".$this->attribs['params']."'); foreach(\$get as \$key) { if(isset(\$_GET[\$key]) && \$key != '' && \$_GET[\$key] != '') { \$url[\$key] = \$_GET[\$key]; } } if(\$_GET['act'] == 'list' && !@\$_GET['sort']) { \$url['sort'] = '#'; } ?>\n";
		$data	.= "<?php echo '<td class=\"{$class}\"><a href=\"'.\$url->__toString().'&start=0&limit='.\$limit.\$extra.'\" class=\"minitext\">'; ?>\n";

		foreach ($this->children as $child) {
			$data	.= $child->__toString();
		}

		$data	.= "<?php echo '</a></td>'; ?>\n";
		
		return $data;
	}
}

class TPL_UrlPrev extends TPL_Navigator {
	public function __toString() {
		$data	= '';

		if (!isset($this->attribs['class']))
			return $data;
		
		$class		= htmlspecialchars($this->attribs['class']);
		
		$data	.= "<?php \$prev_start = ((\$start-\$limit) != 0 && (\$start-\$limit) > 0) ? (\$start-\$limit) : 0; ?>\n";
		$data	.= "<?php echo '<td class=\"{$class}\"><a href=\"'.\$url->__toString().'&start='.\$prev_start.'&limit='.\$limit.\$extra.'\" class=\"minitext\">'; ?>";

		foreach ($this->children as $child) {
			$data	.= $child->__toString();
		}

		$data	.= "<?php echo '</a></td>'; ?>";
		
		return $data;
	}
}

class TPL_UrlPage extends TPL_Navigator {
	public function __toString() {
		$data	= '';

		if (!isset($this->attribs['class']) || !isset($this->attribs['separator']))
			return $data;

		$separator	= htmlspecialchars($this->attribs['separator']);
		$class		= htmlspecialchars($this->attribs['class']);
		
		$data	.= "<?php \$page_start = (((\$start/\$limit)-\$before) < 0) ? 0 : ((\$start/\$limit)-\$before); ?>\n";
		$data	.= "<?php \$page_end = (((\$start/\$limit)+(\$after+1)) > \$pages) ? \$pages : ((\$start/\$limit)+(\$after+1)); ?>\n";

		$data	.= "<?php for(\$i=\$page_start;\$i<\$page_end;\$i++): ?>\n";

		$data	.= "<?php if(((\$limit*(\$i+1))-\$limit) == \$start): ?>\n";
		$data	.= "<?php echo '<td class=\"{$class}\"><a href=\"'.\$url->__toString().'&start='.(\$i*\$limit).'&limit='.\$limit.\$extra.'\" class=\"minitext\"><strong>'.(\$i+1).'</strong></a></td>'; if((\$i+1) != \$page_end): echo '{$separator}'; endif; ?>\n";
		$data	.= "<?php else: echo '<td class=\"{$class}\"><a href=\"'.\$url->__toString().'&start='.(\$i*\$limit).'&limit='.\$limit.\$extra.'\" class=\"minitext\">'.(\$i+1).'</a></td>'; if((\$i+1) != \$page_end): echo '{$separator}';  endif; ?>\n";
		
		$data	.= "<?php endif; endfor; ?>\n";
		
		
		return $data;
	}
}

class TPL_UrlNext extends TPL_Navigator {
	public function __toString() {
		$data	= '';

		if (!isset($this->attribs['class']))
			return $data;
		
		$class		= htmlspecialchars($this->attribs['class']);
		
		$data	.= "<?php \$next_start = ((\$start+\$limit) < ((\$pages*\$limit)-\$limit)) ? (\$start+\$limit) : ((\$pages*\$limit)-\$limit); ?>\n";
		$data	.= "<?php echo '<td class=\"{$class}\"><a href=\"'.\$url->__toString().'&start='.\$next_start.'&limit='.\$limit.\$extra.'\" class=\"minitext\">'; ?>\n";

		foreach ($this->children as $child) {
			$data	.= $child->__toString();
		}

		$data	.= "<?php echo '</a></td>'; ?>";
		
		return $data;
	}
}

class TPL_UrlLast extends TPL_Navigator {
	public function __toString() {
		$data	= '';

		if (!isset($this->attribs['class']))
			return $data;
		
		$class		= htmlspecialchars($this->attribs['class']);	

		$data	.= "<?php echo '<td class=\"{$class}\"><a href=\"'.\$url->__toString().'&start='.((\$pages*\$limit)-\$limit).'&limit='.\$limit.\$extra.'\" class=\"minitext\">'; ?>\n";

		foreach ($this->children as $child) {
			$data	.= $child->__toString();
		}

		$data	.= "<?php echo '</a></td>'; ?>";
		
		return $data;
	}
}

?>