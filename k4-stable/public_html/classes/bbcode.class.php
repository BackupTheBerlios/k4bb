<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     bbcode.class.php
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

class BB {
	static public function Open($str, $quick = FALSE, $use_dba = TRUE, $use_idiot = TRUE) {
		return new BBParser($str, $quick, $use_dba, $use_idiot);
	}
}

class BBParser {
	protected		$omitted		= array();
	protected		$str			= '';
	public			$str_final		= '';
	protected		$to_omit		= array();
	protected		$to_switch		= array();
	protected		$customs		= array();
	protected		$quick_customs	= array();
	protected		$dba;
	
	public function __construct($str, $quick = FALSE, $use_dba = TRUE, $use_idiot = TRUE, $allow = FALSE) {
		global $settings;
		$allow = !$allow ? $settings : $allow;

		$this->str = (!$quick) ? str_replace('>', '&gt;', str_replace('<', '&lt;', $str)) : $str;
		if($use_idiot == TRUE) {
			$idiot = new IdiotFilter($this->str);
			$this->str = $idiot->str;
		}
		if($allow['allowbbcode'] == 1) {
			$this->addOmit('omit', 'omit');
			/* These can all be called outside of the class */
			$this->addBBcode('b',		'b',		'<strong>',										'</strong>');
			$this->addBBcode('i',		'i',		'<em>',											'</em>');
			$this->addBBcode('u',		'u',		'<u>',											'</u>');
			//$this->addBBcode('code',	'code',		'<fieldset><legend>CODE: </legend><div style="overflow-x:scroll;"><pre>',			'</pre></div></fieldset>');
			$this->addBBcode('left',	'left',		'<div align="left">',							'</div>');
			$this->addBBcode('right',	'right',	'<div align="right">',							'</div>');
			$this->addBBcode('center',	'center',	'<div align="center">',							'</div>');
			$this->addBBcode('justify', 'justify',	'<div align="justify">',						'</div>');
			$this->addBBcode('strike',	'strike',	'<span style="text-decoration:strikethrough;">','</span>');
			$this->addCustom(new BBList);
			$this->addCustom(new BBImg);
			$this->addCustom(new BBEmail);
			$this->AddCustom(new BBUrls);
			//$this->addCustom(new BBParseUrls);
			$this->AddCustom(new BBCode);
			if($allow['allowsmilies'] == 1)
				$this->addCustom(new BBEmoticons($use_dba));

			$this->addCustom(new BBFont);
			$this->AddCustom(new BBFontSize);
			$this->AddCustom(new BBFontColor);
			$this->AddCustom(new BBQuote);
			$this->AddCustom(new BBBasicQuote);
			$this->AddCustom(new BadWordFilter($use_dba));
		}
	}
	private function Omit() {
		$this->omitTokenizer();
		$keys	= array_keys($this->omitted[0][1]);
		$values = array_values($this->omitted[0][1]);
		$str	= $this->switchCustoms($this->omitted[0][0]);
		$str	= $this->switchBBcode($str);
		
		for($i=0; $i<count($keys); $i++) {
			$keys[$i];
			$str = str_replace($keys[$i], '<!-- OMIT -->'.$values[$i].'<!-- /OMIT -->', $str); 
		}
		
		$this->str_final = $str;
	}
	private function omitTokenizer() {
		foreach($this->to_omit as $tag) {
			preg_match_all('/\['. $tag[0] .'\](.+?)\[\/'. $tag[1] .'\]/si', $this->str, $match);
		}
		$omitted = array();
		if(isset($match) && !empty($match) && is_array($match[0])) {
			foreach($match[0] as $key => $val) {
				$identifier = "[!".md5(microtime())."!]";
				$omitted[$identifier] = $match[1][$key];
				$this->str = str_replace($val, $identifier, $this->str);
			}
		}
		if($this->omitted[] = array($this->str, $omitted))
			return TRUE;
	}
	public function addOmit($start_tag, $end_tag) {
		if($this->to_omit[] = array($start_tag, $end_tag))
			return TRUE;
	}
	public function addBBcode($start_tag, $end_tag, $new_start, $new_end) {
		if($this->to_switch[] = array($start_tag, $end_tag, $new_start, $new_end))
			return TRUE;
	}
	private function switchBBcode($str) {
		foreach($this->to_switch as $tag) {
			$tag_before = '~\['. $tag[0] .'\](.*?)\[\/'. $tag[1] .'\]~is';
			$tag_after = $tag[2] .'\\1'. $tag[3];
			$str = preg_replace($tag_before, $tag_after, $str);
		}
		return $str;
	}
	private function switchCustoms($str) {
		foreach($this->customs as $instance) {
			$str = $instance->Execute($str);
		}
		return $str;
	}
	public function addCustom($instance) {
		$this->customs[] = $instance;
	}
	public function addQuickCustom($instance) {
		$this->quick_customs[] = $instance;
	}
	private function BackSlash($str) {
		$str = str_replace('<', '\<', $str);
		$str = str_replace('>', '\>', $str);
		return $str;
	}
	public function Revert($str) {
		global $lang;
		$str = str_replace("<br />", "", $str);
		foreach($this->to_switch as $tag) {
			$str = preg_replace('~'. $this->BackSlash($tag[2]) .'(.*?)'. $this->BackSlash($tag[3]) .'~is', '['. $tag[0] .']$1[/'. $tag[1] .']', $str);
		}
		
		$str = str_replace('<!-- NEWLINE -->', "\n", $str);
		$str = str_replace('<!-- OMIT -->', '[omit]', $str);
		$str = str_replace('<!-- /OMIT -->', '[/omit]', $str);
		$str = preg_replace('~\<ul type="(.*?)">~is', '[list=$1]', $str);
		$str = preg_replace('~\<li>(.*?)</li>~is', '[*]$1', $str);
		$str = str_replace('<ul>', '[list]', $str);
		$str = str_replace('</ul>', '[/list]', $str);
		$str = preg_replace('~\<!-- URLBASIC --><a href="(.*?)" alt="(.*?)">(.*?)</a><!-- \/ URLBASIC -->~is', '[url]$1[/url]', $str);
		$str = preg_replace('~\<!-- URLADV --><a href="(.*?)" alt="(.*?)">(.*?)</a><!-- \/ URLADV -->~is', '[url=$1]$3[/url]', $str);
		$str = preg_replace('~\<!-- EMAILBASIC --><a href="mailto: (.*?)" alt="(.*?)">(.*?)</a><!-- \/ EMAILBASIC -->~is', '[email]$1[/email]', $str);
		$str = preg_replace('~\<!-- EMAILADV --><a href="mailto: (.*?)" alt="(.*?)">(.*?)</a><!-- \/ EMAILADV -->~is', '[email=$1]$3[/email]', $str);
		$str = preg_replace('~\<img src="(.*?)" alt="" border="" \/\>~is', '[img]$1[/img]', $str);
		$str = preg_replace('~\<a href="(.*?)" target="_blank"\>(.*?)\<\/a\>~is', '$2', $str);
		$str = preg_replace('~\<span style="font-family: (.*?);"\>(.*?)\<\/span\>~is', '[font=$1]$2[/font]', $str);
		$str = preg_replace('~\<span style="color: (.*?);"\>(.*?)\<\/span\>~is', '[color=$1]$2[/color]', $str);
		$str = preg_replace('~\<span style="font-size: (.*?)em;"\>(.*?)\<\/span\>~is', '[size=$1]$2[/size]', $str);
		$str = preg_replace('~\<!-- EMO-(.*?) --><img src="(.*?)" alt="(.*?)" \/\><!-- /EMO -->~', '$1', $str);
		$str = preg_replace('~\<div align="center" ><table width="90%" cellpadding="0" cellspacing="0" border="0" class="forum_content" style="max-height: 200px;text-align:left;"><tr class="thead"><td>CODE:<\/td><\/tr><tr class="alt3"><td><div style="overflow:scroll;height:150px;"><pre>(.+)</pre></div><\/td><\/tr><\/table><\/div>~is', '[code]$1[/code]', $str);
		//$str = preg_replace('~\<fieldset class\=\"quote\"\>\<legend\>(.*?)\: \<\/legend\>(.*?)\<\/fieldset\>~is', '[quote=$1]$2[/quote]', $str);
		$str = preg_replace('~\<fieldset class\=\"quote\"\>\<legend\>'. strtoupper($lang['L_QUOTE']) .'\: \<\/legend\>\<span\>~is', '[quote]', $str);
		$str = preg_replace('~\<fieldset class\=\"quote\"\>\<legend\>(.*?)\: \<\/legend\>\<span\>~is', '[quote=$1]', $str);
		$str = str_replace('</span></fieldset>', '[/quote]', $str);
		$str = str_replace('&gt;', '>', str_replace('&lt;', '<', $str));
		$str = str_replace('\n', '', $str);
		$str = str_replace("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", "\t", $str);
		
		return stripslashes($str);
	}
	public function Execute() {
		$this->Omit();
		$str = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $this->str_final);
		return nl2br($str);
	}
	public function QuickExecute() {
		$this->AddQuickCustom(new BBParseNames);
		$this->AddQuickCustom(new BBNewLineException);
		$this->AddQuickCustom(new BBPHP);
		$this->AddQuickCustom(new BBAutoUrls);
		foreach($this->quick_customs as $instance) {
			$this->str = $instance->Execute($this->str);
		}
		return $this->str;
	}
}
abstract class BBCodeCustom {
	abstract public function Execute($str);
}
/* Make a LIST bb code */
class BBList extends BBCodeCustom {

	protected $tags				= array();
	protected $item_tag			= '';

	protected $replace_tags		= array();
	protected $replace_item		= '';
	
	/* Set the 3 types of list regexs, the list items, and all of their replacements */
	public function __construct() {
		/* NOTICE: the 'e' at the end of the regex, that means execute (means you can call a function */
		$this->tags = array('~\[list=([0-9]*?)\](.*?)\[\/list\]~ise', '~\[list=([a-z]*?)\](.*?)\[\/list\]~ise', '~\[list\](.*?)\[\/list\]~ise');
		$this->item = '~\[\*\]([^\n]*)~is';

		$this->replace_tags = array('\'<ul type="$1">\'.$this->ReplaceItems(\'$2\').\'</ul>\'', '\'<ul type="$1">\'.$this->ReplaceItems(\'$2\').\'</ul>\'', '\'<ul>\'.$this->ReplaceItems(\'$1\').\'</ul>\'');
		$this->replace_item = '<li>$1</li>';
	}
	/* Callback function to do the right kind of replacing on lists */
	public function ReplaceItems($str) {
		$str = preg_replace($this->item, $this->replace_item, $str);
		$str = str_replace('\n', '', $str);
		return $str;
	}
	/* Search and replace all of the list tags, and then call the callback function (see regex's above) to deal with list items */
	public function SearchAndReplace($str) {
		$i=0;
		foreach($this->tags as $tag) {
			$str = preg_replace($tag, $this->replace_tags[$i], $str);
			$i++;
		}
		return $str;
	}
	/* return the final product */
	public function Execute($str) {
		return $this->SearchAndReplace($str);
	}
}
/* Make an IMG bb code */
class BBImg extends BBCodeCustom {
	public function Execute($str) {
		return preg_replace('~\[img\](.*?)\[\/img\]~is', '<img src="$1" alt="" border="" />', $str);
	}
}
/* Make an email BB code */
class BBEmail extends BBCodeCustom {
	public function Execute($str) {
		$str = preg_replace('~\[email\](.*?)\[\/email\]~is', '<!-- EMAILBASIC --><a href="mailto: $1" alt="Email $1">$1</a><!-- / EMAILBASIC -->', $str);
		$str = preg_replace('~\[email=(.*?)\](.*?)\[\/email\]~is', '<!-- EMAILADV --><a href="mailto: $1" alt="Email $1">$2</a><!-- / EMAILADV -->', $str);
		return $str;
	}
}
/* Parse all of the URLs */
class BBParseUrls extends BBCodeCustom {
	public function Execute($str) {
		return preg_replace('~^[^\]]\b((http(s?):\/\/)|(www\.))([\w\.]+)([\/\w+\.]+)\b[^\[]$~is', '<a href="http$3://$4$5$6 " target="_blank">$2$4$5$6</a>', $str);
	}
}
/* Make the actual URL bbcodes */
class BBUrls extends BBCodeCustom {
	public function Execute($str) {
		$str = preg_replace("~\[url\=(.+?)\](.+?)\[\/url\]~is",'<!-- URLBASIC --><a href="$1" alt="$1">$2</a><!-- / URLBASIC -->', $str);
		$str = preg_replace("~\[url\](.+?)\[\/url\]~is",'<!-- URLADV --><a href="$1" alt="$1">$1</a><!-- / URLADV -->', $str);
		return $str;
	}
}
class BBAutoUrls extends BBCodeCustom {
	public function Execute($str) {
		$str = str_replace("<br />", " <br />", $str);
		// /((http|ftp)+(s)?:(\/\/)([\w]+(.[\w]+))([\w\-\.,@?^=%&:;\/~\+#]*[\w\-\@?^=%&:;\/~\+#])?)/i
		// ~(\s|\A)(((http|ftp)(s?):\/\/)|(www\.))(.+?)\b~is
		// /(((((http|ftp)(s?):\/\/)|(www\.))+(.[\w]+))([\w\-\.,@?^=%&:;\/~\+#]*[\w\-\@?^=%&:;\/~\+#])?)/i
		$str = preg_replace_callback('/\s((http(s?):\/\/)|(www\.))([\w\-\.,@?^=%&:;\/~\+#]*[\w\-\@?^=%&:;\/~\+#])\s/i', 'AutoUrls', $str .' ');

		return $str;
	}
}
function AutoUrls($matches) {
	$new_url = strlen($matches[0]) > 45 ? substr($matches[0], 0, 15) .'...'. substr($matches[0], -15) : $matches[0];
	$str = '<a href="'. $matches[0] .'" target="_blank">'. $new_url .'</a> ';

	return $str;
}
/* Make all of the emoticons */
class BBEmoticons extends BBCodeCustom {
	protected $use_db;
	public function __construct($use_db) {
		$this->use_db = $use_db;
	}
	public function Execute($str) {
		if($this->use_db) {
			foreach(DBA::Open()->Query("SELECT * FROM ". EMOTICONS ) as $emo) {
				//$str = preg_replace("~\s". addcslashes($emo['typed'] , "\$\%\@\!\*\#\(\:\)\\\/\+\-\[\]") ."\s~is", '<!-- EMO-'. $emo['typed'] .' --><img src="Images/'.append_slash(get_setting('template', 'imgfolder')).'Icons/Emoticons/'. $emo['image'] .'" alt="'. $emo['description'] .'" /><!-- /EMO -->', $str);
				$str = str_ireplace($emo['typed'], '<!-- EMO-'. $emo['typed'] .' --><img src="Images/'.append_slash(get_setting('template', 'imgfolder')).'Icons/Emoticons/'. $emo['image'] .'" alt="'. $emo['description'] .'" /><!-- /EMO -->', $str);
			}
		}
		return $str;
	}
}

/* Make a font tag */
class BBFont extends BBCodeCustom {
	public function Execute($str) {
		return preg_replace('~\[font=(.*?)\](.*?)\[\/font\]~is', '<span style="font-family: $1;">$2</span>', $str);
	}
}
class BBFontColor extends BBCodeCustom {
	public function Execute($str) {
		return preg_replace('~\[color=(.*?)\](.*?)\[\/color\]~is', '<span style="color: $1;">$2</span>', $str);
	}
}
class BBFontSize extends BBCodeCustom {
	public function Execute($str) {
		return preg_replace('~\[size=(.*?)\](.*?)\[\/size\]~is', '<span style="font-size: $1em;">$2</span>', $str);
	}
}
class BBQuote extends BBCodeCustom {
	public function Execute($str) {
		//([^\]]+)
		while (preg_match('~\[quote=(.+)([^\]]+)\](.+)\[\/quote\]~isU', $str)) {
			$str = preg_replace('~\[quote=([^\]]+)\](.+)\[\/quote\]~isU', '<fieldset class="quote"><legend>$1: </legend><span>$2</span></fieldset>', $str);
		}
		return $str;
	}
}
class BBBasicQuote extends BBCodeCustom {
	public function Execute($str) {
		global $lang;
		while (preg_match('~\[quote\](.+)\[\/quote\]~isU', $str)) {
			$str = preg_replace('~\[quote\](.+)\[\/quote\]~isU', '<fieldset class="quote"><legend>'. strtoupper($lang['L_QUOTE']) .': </legend><span>$1</span></fieldset>', $str);
		}
		return $str;
	}
}
class BadWordFilter extends BBCodeCustom {
	protected $use_db;
	public function __construct($use_db) {
		$this->use_db = $use_db;
	}
	public function Execute($str) {
		if($this->use_db) {
			foreach(DBA::Open()->Query("SELECT * FROM ". BADWORDS ) as $word) {
				if($word['method'] == 0) // Exact
					$str = preg_replace("~\b". $word['word'] ."\b~is", $word['replacement'], $str);
				else if($word['method'] == 1) // Loose
					$str = preg_replace("~". $word['word'] ."~is", $word['replacement'], $str);
			}
		}
		return $str;
	}
}
class BBParseNames extends BBCodeCustom {
	public function Execute($str) {
		$str = preg_replace("~\B\/you~is", $_SESSION['user']['name'], $str);
		return $str;
	}
}
class BBNewLineException extends BBCodeCustom {
	public function Execute($str) {
		$str = str_replace('<!-- NEWLINE -->', "\r\n", $str);
		return $str;
	}
}
function RemoveBRs($matches) {
	$matches[1] = nl2br($matches[1]);
	$matches[1] = str_replace("<br />", " ", $matches[1]);
	$matches[1] = str_replace("\r\n", "<!-- NEWLINE -->", $matches[1]);
	//return "<fieldset><legend>CODE: </legend><div style=\"overflow-x:scroll;\"><pre>". $matches[1] ."</pre></div></fieldset>";
	return '<div align="center" ><table width="90%" cellpadding="0" cellspacing="0" border="0" class="forum_content" style="max-height: 200px;text-align:left;"><tr class="thead"><td>CODE:</td></tr><tr class="alt3"><td><div style="overflow:scroll;height:150px;"><pre>'. $matches[1] .'</pre></div></td></tr></table></div>';
}
class BBCode extends BBCodeCustom {
	public function Execute($str) {
		$str = preg_replace_callback("~\[code\](.+)\[\/code\]~is", "RemoveBRs", $str);
		return $str;
	}
}
function HighlightPHP($matches) {
	$matches[1] = str_replace("<br />", " ", $matches[1]);
	$matches[1] = str_replace("&lt;", '<', $matches[1]);
	$matches[1] = str_replace("&gt;", '>', $matches[1]);
	//$matches[1] = str_replace("\r\n", "\n", $matches[1]);
	return '<div align="center" ><table width="90%" cellpadding="0" cellspacing="0" border="0" class="forum_content" style="text-align:left;"><tr class="thead"><td>PHP:</td></tr><tr class="alt3"><td>'. highlight_string($matches[1], TRUE) .'</td></tr></table></div>';
}
class BBPHP extends BBCodeCustom {
	public function Execute($str) {
		$str = preg_replace_callback("~\[php\](.+)\[\/php\]~is", "HighlightPHP", $str);
		return $str;
	}
}
?>