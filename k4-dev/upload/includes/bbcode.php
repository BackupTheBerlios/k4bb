<?php

/**
* k4 Bulletin Board, bbcode.php
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
* @version $Id: bbcode.php,v 1.3 2005/04/19 21:51:02 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class BBCodex {
	
	var $settings;
	var $dba;
	var $queryparams;

	var $user;
	var $text;
	var $forum_id;
	
	var $html;
	var $bbcode;
	var $emoticons;
	var $auto_urls;

	var $bbcodes	= array();
	var $customs	= array();
	
	/**
	 * Constructor, set some variables
	 */
	function BBCodex(&$user, $text, $forum_id, $html, $bbcode, $emoticons, $auto_urls) {
		global $_SETTINGS, $_DBA, $_QUERYPARAMS;

		$this->settings		= $_SETTINGS;
		$this->dba			= &$_DBA;
		$this->queryparams	= $_QUERYPARAMS;

		$this->user			= &$user;
		$this->text			= $text;
		$this->forum_id		= $forum_id;
		
		$this->html			= (bool)$html;
		$this->bbcode		= (bool)$bbcode;
		$this->emoticons	= (bool)$emoticons;
		$this->auto_urls	= (bool)$auto_urls;
	}

	/**
	 * Initialize all of the buffers with the bbcodex
	 */
	function init() {

		/* Transform unwanted html entities */
		$this->add_custom('htmlentities', new BBHTMLentities($this));
		
		if($this->bbcode) {
		
			/* Simple bb codes */
			$this->add_bbcode('b', 'b', 'span style="font-weight: bold;"', 'span');
			$this->add_bbcode('i', 'i', 'span style="font-style: italic;"', 'span');
			$this->add_bbcode('u', 'u', 'span style="text-decoration: underline;"', 'span');
			$this->add_bbcode('left', 'left', 'div align="left"', 'div');
			$this->add_bbcode('right', 'right', 'div align="right"', 'div');
			$this->add_bbcode('center', 'center', 'div align="center"', 'div');
			$this->add_bbcode('justify', 'justify', 'div align="justify"', 'div');
			$this->add_bbcode('strike', 'strike', 'span style="text-decoration: line-through;"', 'span');
			$this->add_bbcode('indent', 'indent', 'blockquote', 'blockquote');
			
			/* More advanced bb codes */
			$this->add_custom('url', new BBUrl($this));
			$this->add_custom('img', new BBImg($this));
			$this->add_custom('font', new BBFont($this));
			$this->add_custom('size', new BBSize($this));
			$this->add_custom('color', new BBColor($this));
			$this->add_custom('quote', new BBQuote($this));
			$this->add_custom('code', new BBCode($this));
			$this->add_custom('list', new BBList($this));
			
			if($this->emoticons)
				$this->add_custom('emoticons', new BBEmoticons($this));

			if($this->html)
				$this->add_custom('html', new BBHtml($this));
		}
	}

	/**
	 * transform bbcode into html
	 */
	function parse() {

		/* Initialize the bbcodes */
		$this->init();
		
		/* Transform the advanced tags to html */
		$this->advbbcode_to_html();

		/* Transform them to html */
		$this->bbcode_to_html();

		return $this->text;
	}

	/**
	 * transform html into bbcode
	 */
	function revert() {

		/* Initialize the bbcodes */
		$this->init();
		
		/* Transform the advanced tags to bbcode */
		$this->advhtml_to_bbcode();

		/* Transform them to html */
		$this->html_to_bbcode();

		return $this->text;
	}

	/**
	 * Add a simple bbcode to the parser
	 */
	function add_bbcode($tag_open, $tag_close, $html_open, $html_close) {
		$this->bbcodes[] = array('open' => $tag_open, 'close' => $tag_close, 'html_open' => $html_open, 'html_close' => $html_close);
	}

	/**
	 * Add a custom bbcode to the parser
	 */
	function add_custom($identifier, &$class) {
		$this->customs[] = array('identifier' => $identifier, 'class' => &$class);
	}

	/**
	 * Transform the simple bbcodes into executeable html
	 */
	function bbcode_to_html() {
		foreach($this->bbcodes as $bb) {
			$this->text = preg_replace("~\[". $bb['open'] ."\](.+?)\[\/". $bb['close'] ."\]~iU", "<". $bb['html_open'] .">$1</". $bb['html_close'] .">", $this->text);
		}
	}

	/**
	 * Transform the simple html into readable bbcode
	 */
	function html_to_bbcode() {
		foreach($this->bbcodes as $bb) {
			$this->text = preg_replace("~\<". $bb['html_open'] ."\>(.*?)\<\/". $bb['html_close'] ."\>~is", "[". $bb['open'] ."]$1[/". $bb['close'] ."]", $this->text);
		}
	}
	
	/**
	 * Transform the advanced bbcodes into executeable html
	 */
	function advbbcode_to_html() {
		foreach($this->customs as $bb) {
			$bb['class']->to_html();
		}
	}
	
	/**
	 * Transform the advanced html into readable bbcode
	 */
	function advhtml_to_bbcode() {
		foreach($this->customs as $bb) {
			$bb['class']->to_bbcode();
		}
	}
}

/**
 * Interface for bbcode custom tags classes
 */
class BBCodeTag {
	function to_html() { return TRUE; }
	function to_bbcode() { return TRUE; }
}

/**
 * Deal with URL tags
 */
function bbcode_format_url($url, $type, $alt) {
	
	$nice_url		= $url;
	if(strlen($url) > 45)
		$nice_url	= substr($url, 0, 15) .'...'. substr($url, -15);
	
	if($type == 'adv') {
		$url	= '<!-- URLADV --><a class="bbcode_url" href="'. $url .'" title="'. $alt .'" target="_blank">'. $alt .'</a><!-- / URLADV -->';
	} else {
		$url	= '<!-- URLBASIC --><a class="bbcode_url" href="'. $url .'" title="" target="_blank">'. $nice_url .'</a><!-- / URLBASIC -->';
	}

	return $url;
}
class BBUrl extends BBCodeTag {
	var $instance;

	function BBUrl(&$instance) {
		$this->instance		= &$instance;
	}
	function to_html() {
		$this->instance->text = preg_replace('~\[url\=(((https?://|www\.))([^<>*"?&]+|[a-z0-9/\\._\- !]+?))\](.+?)\[\/url\]~iUe', "bbcode_format_url('\\1', 'adv', '\\5')", $this->instance->text);
		$this->instance->text = preg_replace('~\[url\](((https?://|www\.))([^<>*"?&]+|[a-z0-9/\\._\- !]+?))\[\/url\]~iUe', "bbcode_format_url('\\1', 'basic', NULL)", $this->instance->text);
		
		if($this->instance->auto_urls) {
			$this->instance->text = preg_replace('~(\s|\n)(((https?://|www\.))([^<>*"?&]+|[a-z0-9/\\._\- !]+?))(\s|\n)~iUe', "bbcode_format_url('\\2', 'basic', NULL)", $this->instance->text);
		}

		return $this->instance->text;
	}
	function to_bbcode() {
		$this->instance->text = preg_replace('~\<!-- URLBASIC --><a class="bbcode_url" href="(((https?://|www\.))([^<>*"?&]+|[a-z0-9/\\._\- !]+?))" title="" target="_blank">(.*?)</a><!-- \/ URLBASIC -->~iU', '[url]\\1[/url]', $this->instance->text);
		$this->instance->text = preg_replace('~\<!-- URLADV --><a class="bbcode_url" href="(((https?://|www\.))([^<>*"?&]+|[a-z0-9/\\._\- !]+?))" title="(.*?)" target="_blank">(.*?)</a><!-- \/ URLADV -->~iU', '[url=\\1]\\5[/url]', $this->instance->text);
	
		return $this->instance->text;
	}
}

/**
 * Deal with IMG tags
 */
class BBImg extends BBCodeTag {
	var $instance;

	function BBImg(&$instance) {
		$this->instance		= &$instance;
	}
	function to_html() {
		$this->instance->text = preg_replace('~\[img\](https?://([^<>*"'. iif($this->instance->settings['allowdynimg'], '?&', '') .']+|[a-z0-9/\\._\- !]+?))\[\/img\]~iU','<!-- IMG --><img class="bbcode_img" src="\\1" alt="" border="0" /><!-- / IMG -->', $this->instance->text);
		
		return $this->instance->text;
	}
	function to_bbcode() {
		$this->instance->text = preg_replace('~\<!-- IMG --><img class="bbcode_img" src="(https?://([^<>*"'. iif($this->instance->settings['allowdynimg'], '?&', '') .']+|[a-z0-9/\\._\- !]+?))" alt="" border="0" /><!-- / IMG -->~iU', '[img]\\1[/img]', $this->instance->text);
	
		return $this->instance->text;
	}
}

/**
 * Deal with FONT tags
 */
class BBFont extends BBCodeTag {
	var $instance;

	function BBFont(&$instance) {
		$this->instance		= &$instance;
	}
	function to_html() {
		$this->instance->text = preg_replace('~\[font=([a-zA-Z\-\,\s]+?)\](.*?)\[\/font\]~is', '<span style="font-family: \\1;">\\2</span>', $this->instance->text);
		
		return $this->instance->text;
	}
	function to_bbcode() {
		$this->instance->text = preg_replace('~<span style="font-family: ([a-zA-Z\-\,\s]+?);">(.*?)</span>~is', '[font=\\1]\\2[/font]', $this->instance->text);
	
		return $this->instance->text;
	}
}

/**
 * Deal with SIZE tags
 */
class BBSize extends BBCodeTag {
	var $instance;

	function BBSize(&$instance) {
		$this->instance		= &$instance;
	}
	function to_html() {
		$this->instance->text = preg_replace('~\[size=([0-9]+?)\](.*?)\[\/size\]~is', '<span style="font-size: \\1pt;">\\2</span>', $this->instance->text);
		
		return $this->instance->text;
	}
	function to_bbcode() {
		$this->instance->text = preg_replace('~<span style="font-size: ([0-9]+?)pt;">(.*?)</span>~is', '[size=\\1]\\2[/size]', $this->instance->text);
	
		return $this->instance->text;
	}
}

/**
 * deal with the COLOR tags
 */
class BBColor extends BBCodeTag {
	var $instance;

	function BBColor(&$instance) {
		$this->instance		= &$instance;
	}
	function to_html() {
		$this->instance->text = preg_replace('~\[color=([a-zA-Z]+?)\](.*?)\[\/color\]~is', '<span style="color: \\1;">\\2</span>', $this->instance->text);
		
		return $this->instance->text;
	}
	function to_bbcode() {
		$this->instance->text = preg_replace('~<span style="color: ([a-zA-Z]+?);">(.*?)</span>~is', '[color=\\1]\\2[/color]', $this->instance->text);
	
		return $this->instance->text;
	}
}

/**
 * Deal with QUOTE tags
 */
class BBQuote extends BBCodeTag {
	var $instance;
	var $lang;

	function BBQuote(&$instance) {
		$this->instance		= &$instance;
		$this->lang			= return_language();
	}
	function to_html() {
		
		while(preg_match('~\[quote\](.+?)\[\/quote\]~iU', $this->instance->text))
			$this->instance->text = preg_replace('~\[quote\](.+?)\[\/quote\]~iU', '<div class="quotetitle">'. strtoupper($this->lang['L_QUOTE']) .': </div><div class="quotecontent">\\1</div>', $this->instance->text);
		
		while(preg_match('~\[quote=([^\]]+)\](.+)\[\/quote\]~iU', $this->instance->text))
			$this->instance->text = preg_replace('~\[quote=([^\]]+)\](.+)\[\/quote\]~iU', '<div class="quotetitle">'. strtoupper($this->lang['L_QUOTE']) .' ( \\1 ): </div><div class="quotecontent">\\2</div>', $this->instance->text);
		
		return $this->instance->text;
	}
	function to_bbcode() {

		while(preg_match('~<div class="quotetitle">'. strtoupper($this->lang['L_QUOTE']) .'\: </div><div class="quotecontent">(.+)</div>~is', $this->instance->text))
			$this->instance->text = preg_replace('~<div class="quotetitle">'. strtoupper($this->lang['L_QUOTE']) .'\: </div><div class="quotecontent">(.+)</div>~is', '[quote]\\1[/quote]', $this->instance->text);
		
		while(preg_match('~<div class="quotetitle">'. strtoupper($this->lang['L_QUOTE']) .'\ \( (.*?) \): </div><div class="quotecontent">(.+)</div>~is', $this->instance->text))
			$this->instance->text = preg_replace('~<div class="quotetitle">'. strtoupper($this->lang['L_QUOTE']) .'\ \( (.*?) \): </div><div class="quotecontent">(.+)</div>~is', '[quote=\\1]\\2[/quote]', $this->instance->text);
	
		return $this->instance->text;
	}
}

/**
 * Deal with CODE tags
 */
class BBCode extends BBCodeTag {
	var $instance;
	var $lang;

	function BBCode(&$instance) {
		$this->instance		= &$instance;
		$this->lang			= return_language();
	}
	function to_html() {
		
		while(preg_match('~\[code\](.+?)\[\/code\]~iU', $this->instance->text))
			$this->instance->text = preg_replace('~\[code\](.+?)\[\/code\]~iU', '<div class="codetitle">'. strtoupper($this->lang['L_CODE']) .': </div><div class="codecontent">\\1</div>', $this->instance->text);
				
		return $this->instance->text;
	}
	function to_bbcode() {

		while(preg_match('~<fieldset class="quote"><legend>'. strtoupper($this->lang['L_CODE']) .'\: </legend><span>(.+)</span></fieldset>~is', $this->instance->text))
			$this->instance->text = preg_replace('~<div class="codetitle">'. strtoupper($this->lang['L_QUOTE']) .'\: </div><div class="codecontent">(.+)</div>~is', '[code]\\1[/code]', $this->instance->text);
			
		return $this->instance->text;
	}
}

/**
 * Deal with LIST and * tags
 */
class BBList extends BBCodeTag {
	var $instance;

	var $tags			= array();
	var $replace_tags	= array();

	function BBList(&$instance) {
		$this->instance		= &$instance;

		$this->tags = array('~\[list=([0-9]+?)\]((\n|.)+?)\[\/list\]~iUe', '~\[list=([a-zA-Z]+?)\]((\n|.)+?)\[\/list\]~iUe', '~\[list\]((\n|.)+?)\[\/list\]~iUe');

		$this->replace_tags = array('\'<!-- LIST --><ul type="\\1">\'.$this->replace_items(\'\\2\').\'</ul><!-- / LIST -->\'', '\'<!-- LIST --><ul type="\\1">\'.$this->replace_items(\'\\2\').\'</ul><!-- / LIST -->\'', '\'<!-- LIST --><ul>\'.$this->replace_items(\'\\1\').\'</ul><!-- / LIST -->\'');
	}
	/* Callback function to do the right kind of replacing on lists */
	function replace_items($str) {
		
		$str = preg_replace('~<!-- NEWLINE (\r\n|\n|\r) --><br /><!-- / NEWLINE -->~', '\\1', $str);

		/* Deal with nested lists */
		$i = 0;
		foreach($this->tags as $tag) {

			/* This is the key to working nested lists */
			while(preg_match($tag, $str))
				$str = preg_replace($tag, $this->replace_tags[$i], $str);
			
			$i++;
		}

		$str = preg_replace('~\[\*\]([^\r\n\s\b\W]*)~is', '<!-- LIST ITEM --><li>\\1</li><!-- / LIST ITEM -->', $str);
		
		return $str;
	}
	function to_html() {

		$i = 0;
		foreach($this->tags as $tag) {
			$this->instance->text = preg_replace($tag, $this->replace_tags[$i], $this->instance->text);
			$i++;
		}
		
		return $this->instance->text;
	}
	function to_bbcode() {
		
		/**
		 * Recursing through the nested list bbcodes can be hazardous, so we look for the
		 * proper opening and closing list syntax. When found, we replace each item
		 */

		/* Advanced lists */
		$this->instance->text = preg_replace('~<!-- LIST --><ul type="([a-zA-Z0-9]+?)">~i', '[list=\\1]', $this->instance->text);
		
		/* Basic lists */
		$this->instance->text = preg_replace('~<!-- LIST --><ul>~i', '[list]', $this->instance->text);
		
		/* Close a list */
		$this->instance->text = preg_replace('~</ul><!-- / LIST -->~i', '[/list]', $this->instance->text);

		/* List items */
		while(preg_match('~\<!-- LIST ITEM --><li>(.*?)</li><!-- / LIST ITEM -->~is', $this->instance->text))
			$this->instance->text = preg_replace('~\<li>(.*?)</li>~is', '[*]\\1', $this->instance->text);
	
		return $this->instance->text;
	}
}

/**
 * Deal with Emoticons
 */
class BBEmoticons extends BBCodeTag {
	var $instance;
	var $dba;

	function BBEmoticons(&$instance) {

		global $_DBA;

		$this->instance		= &$instance;
		$this->emoticons	= &$_DBA->executeQuery("SELECT * FROM ". EMOTICONS);
	}
	function to_html() {
		
		while($this->emoticons->next()) {
			
			$smilie						= $this->emoticons->current();
			
			$file						= 'tmp/upload/emoticons/'. $smilie['image'];

			if(file_exists(FORUM_BASE_DIR .'/'. $file)) {
				
				$proportions			= getimagesize($file);

				$this->instance->text	= str_ireplace($smilie['typed'], '<!-- EMOTICON '. $smilie['typed'] .' --><img src="'. $file .'" alt="'. $smilie['description'] .'" width="'. $proportions[0] .'" height="'. $proportions[1] .'" border="0" /><!-- / EMOTICON -->', $this->instance->text);
			}
		}		
		return $this->instance->text;
	}
	function to_bbcode() {

		while($this->emoticons->next()) {
			
			$smilie						= $this->emoticons->current();
			$this->instance->text		= preg_replace('~<!-- EMOTICON (.+?) --><img (.+?) border="0" /><!-- / EMOTICON -->~i', '\\1', $this->instance->text);
		}
			
		return $this->instance->text;
	}
}

/**
 * Deal with HTML tags
 */
class BBHtml extends BBCodeTag {
	var $instance;
	var $lang;

	function BBHtml(&$instance) {
		$this->instance		= &$instance;
		$this->lang			= return_language();
	}
	function to_html() {
		
		if($this->instance->user['maps']['forums'][$this->instance->forum_id]['html']['can_add'] <= $this->instance->user['perms']) {
			
			$html				= $this->instance->user['maps']['forums'][$this->instance->forum_id]['html']['value'];
			$html				= str_replace(' ', '', $html);
			$tags				= explode(",", $html);

			foreach($tags as $tag) { // $this->instance->text
									
				switch($tag) {
					case 'a': {
						$this->instance->text = preg_replace('~&lt;a href=&quot;(((https?://|www\.))([^<>*"?&]+|[a-z0-9/\\._\- !]+?))&quot;&gt;([^"]+)&lt;/a&gt;~i', '<!-- URLADV --><a class="bbcode_url" href="\\1" title="\\5" target="_blank">\\5</a><!-- / URLADV -->', $this->instance->text);
						break;
					}
					case 'br': {
						$this->instance->text = preg_replace('~&lt;br(([\s]/)?)&gt;~i', '<br />', $this->instance->text);
					}
					default: {
						$this->instance->text = preg_replace('~&lt;'. $tag .'&gt;([^"]+)&lt;/'. $tag .'&gt;~i', '<!-- HTML '. $tag .' --><'. $tag .'>\\1</'. $tag .'><!-- / HTML '. $tag .' -->', $this->instance->text);
						break;
					}
				}
			}
		}
		
		return $this->instance->text;
	}
	function to_bbcode() {
		
		if($this->instance->user['maps']['forums'][$this->instance->forum_id]['html']['can_add'] <= $this->instance->user['perms']) {

			$html				= $this->instance->user['maps']['forums'][$this->instance->forum_id]['html']['value'];
			$html				= str_replace(' ', '', $html);
			$tags				= explode(",", $html);

			foreach($tags as $tag) { // $this->instance->text
									
				switch($tag) {
					case 'a': {
						break;
					}
					case 'br': {
						break;
					}
					default: {
						$this->instance->text = preg_replace('~<!-- HTML '. $tag .' --><'. $tag .'>([^"]+)</'. $tag .'><!-- / HTML '. $tag .' -->~i', '<'. $tag .'>\\1</'. $tag .'>', $this->instance->text);
						break;
					}
				}
			}
		}
			
		return $this->instance->text;
	}
}

/**
 * deal with html entities
 */
class BBHTMLentities extends BBCodeTag {
	var $instance;

	function BBHTMLentities(&$instance) {
		$this->instance		= &$instance;
	}
	function to_html() {
		
		/* Change html entities so that their components are not altered */
		//$this->instance->text = preg_replace('~&([a-zA-Z0-9\#]+?);~', '{ent{\\1}}', $this->instance->text);
		
		/* Ampersands */
		$this->instance->text = str_replace('&', '&amp;', $this->instance->text);

		/* < and > */
		$this->instance->text = str_replace('<', '&lt;', $this->instance->text);
		$this->instance->text = str_replace('>', '&gt;', $this->instance->text);

		/* Quote */
		$this->instance->text = str_replace('"', '&quot;', $this->instance->text);
		

		/* Change the html entities back to what they were */
		//$this->instance->text = preg_replace('~{ent{([a-zA-Z0-9\#]+?)}}~', '&\\1;', $this->instance->text);
		
		/* New Lines, replacement for nl2br() */
		$this->instance->text = preg_replace('~(\r\n|\n|\r)~', '<!-- NEWLINE \\1 --><br /><!-- / NEWLINE -->', $this->instance->text);

		return $this->instance->text;
	}
	function to_bbcode() {
		
		/* New Lines */
		$this->instance->text = preg_replace('~<!-- NEWLINE (\r\n|\n|\r) --><br /><!-- / NEWLINE -->~is', "\\1", $this->instance->text);

		/* < and > */
		$this->instance->text = str_replace('&lt;', '<', $this->instance->text);
		$this->instance->text = str_replace('&gt;', '>', $this->instance->text);

		/* Quote */
		$this->instance->text = str_replace('&quot;', '"', $this->instance->text);

		/* Ampersands */
		$this->instance->text = str_replace('&amp;', '&', $this->instance->text);
	
		return $this->instance->text;
	}
}
?>
