<?php

/*
* The highly expandable BBParser Class and all other classes partaining to it,
* (c) 2004 Peter Goodman, All Rights Reserved, (but you can borrow :) )
* Please do not re-distribute without putting my name in somewhere, and that I (Peter Goodman) made this Parser
* Unlike usual BB Code parsers, this, to me seems highly scalable.
* ALSO, it has its own unique OMIT TOKENIZER, notice it.

--- DOCUMENTATION ---

// Basic Variables

protected $omitted	- An array containing all omissions
protected $str		- The first version of the String passed to the class
public $str_final	- The end Result of the String passed to the class
protected $to_omit	- Only specify 1 type of omit tag, this tag represents a bb code
protected $to_switch- All the BB codes that need to be switched
protected $customs	- An array containing all instances of custom bb code classes

// Basic functions

__construct($str)	- Main: change < & > to &gt; &lt; and sets $this-str = $str
					- The rest can be added from outside of the class
Omit()				- Puts mostly everything together, read on.
omitTokenizer()		- Finds instances of [omit][/omit] (or whatever you choose)
					- and replaces those with tokens. Omit() replaces those tokens
					- with their normal contents
addOmit()			- sets what the omit tag will be (use only 1) ie: [omit][/omit]
addBBcode()			- adds a simple bb code to the $to_switch array
switchBBcode()		- Switches all of the BB codes into XHTML compliant HTML
switchCustoms()		- Switches all the custom BB codes
addCustom()			- Adds a custom BB code class to the $customs array
BackSlash()			- Just a required function to go in there as to not screw up regex
Revert()			- Revert all HTML into their former BB codes
Execute()			- Return the final product

// The rest

self-explanatory

*/

class BBParser {
	protected		$omitted		= array();
	protected		$str			= '';
	public			$str_final		= '';
	protected		$to_omit		= array();
	protected		$to_switch		= array();
	protected		$customs		= array();
	
	public function __construct($str) {
		$this->str = str_replace('>', '&gt;', str_replace('<', '&lt;', $str));
		$this->addOmit('omit', 'omit');
		/* These can all be called outside of the class */
		$this->addBBcode('b', 'b', '<strong>', '</strong>');
		$this->addBBcode('i', 'i', '<em>', '</em>');
		$this->addBBcode('u', 'u', '<u>', '</u>');
		$this->addBBcode('code', 'code', '<fieldset><legend>CODE</legend>', '</fieldset>');
		$this->addCustom(new BBList);
		$this->addCustom(new BBImg);
		$this->addCustom(new BBEmail);
		$this->addCustom(new BBParseUrls);
		$this->addCustom(new BBEmoticons);
		$this->addCustom(new BBFont);
	}
	private function Omit() {
		$this->omitTokenizer();
		$keys = array_keys($this->omitted[0][1]);
		$values = array_values($this->omitted[0][1]);
		$str = $this->switchCustoms($this->omitted[0][0]);
		$str = $this->switchBBcode($str);
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
		foreach($match[0] as $key => $val) {
			$identifier = "[!".md5(microtime())."!]";
			$omitted[$identifier] = $match[1][$key];
			$this->str = str_replace($val, $identifier, $this->str);
		}
		if($this->omitted[] = array($this->str, $omitted))
			return TRUE;
	}
	private function addOmit($start_tag, $end_tag) {
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
	private function BackSlash($str) {
		$str = str_replace('<', '\<', $str);
		$str = str_replace('>', '\>', $str);
		return $str;
	}
	public function Revert($str) {
		$str = str_replace('<br />', '\n', $str);
		foreach($this->to_switch as $tag) {
			$str = preg_replace('~'. $this->BackSlash($tag[2]) .'(.*?)'. $this->BackSlash($tag[3]) .'~is', '['. $tag[0] .']$1[/'. $tag[1] .']', $str);
		}
		$str = str_replace('<!-- OMIT -->', '[omit]', $str);
		$str = str_replace('<!-- /OMIT -->', '[/omit]', $str);
		$str = preg_replace('~\<ul type="(.*?)">~is', '[list=$1]', $str);
		$str = preg_replace('~\<li>(.*?)</li>~is', '[*]$1', $str);
		$str = str_replace('<ul>', '[list]', $str);
		$str = str_replace('</ul>', '[/list]', $str);
		$str = preg_replace('~\<a href="mailto: (.*?)" alt="(.*?)">(.*?)</a>~is', '[email]$1[/email]', $str);
		$str = preg_replace('~\<img src="(.*?)" alt="" border="" \/\>~is', '[img]$1[/img]', $str);
		$str = preg_replace('~\<a href="(.*?)" target="_blank"\>(.*?)\<\/a\>~is', '$2', $str);
		$str = preg_replace('~\<span style="font-family: (.*?);"\>(.*?)\<\/span\>~is', '[font=$1]$2[/font]', $str);
		$str = preg_replace('~\<!-- EMO-(.*?) --><img src="(.*?)" alt="(.*?)" \/\><!-- /EMO -->~', '$1', $str);
		
		$str = str_replace('&gt;', '>', str_replace('&lt;', '<', $str));
		return $str;
	}
	public function Execute() {
		$this->Omit();
		$this->str_final = str_replace('\n', '<br />', $this->str_final);
		return $this->str_final;
	}
}
/* Make a LIST bb code */
class BBList {

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
		return preg_replace($this->item, $this->replace_item, $str);
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
class BBImg {
	public function Execute($str) {
		return preg_replace('~\[img\](.*?)\[\/img\]~ise', '<img src="$1" alt="" border="" />', $str);
	}
}
/* Make an email BB code */
class BBEmail {
	public function Execute($str) {
		return preg_replace('~\[email\](.*?)\[\/email\]~is', '<a href="mailto: $1" alt="Email $1">$1</a>', $str);
	}
}
/* Parse all of the URLs */
class BBParseUrls {
	public function Execute($str) {
		return preg_replace('~\b((http(s?):\/\/)|(www\.))([\w\.]+)([\/\w+\.]+)\b~is', '<a href="http$3://$4$5$6" target="_blank">$2$4$5$6</a>', $str);
	}
}
/* Make all of the emoticons */
class BBEmoticons {
	protected $codes		= array();
	protected $images		= array();
	protected $alts			= array();
	//protected $symbols		= array();
	public function __construct() {
		/* Examples of possible emoticon images, alternatives & codes */
		$this->codes = array(':)', ':P', ':(');
		$this->images = array('smile.gif', 'tongue.gif', 'sad.gif');
		$this->alts = array('Smile', 'Tongue Out', 'Sad');
	}
	public function Execute($str) {
		foreach($this->codes as $key => $val) {
			$str = str_replace($this->codes[$key], '<!-- EMO-'. $this->codes[$key] .' --><img src="'. $this->images[$key] .'" alt="'. $this->alts[$key] .'" /><!-- /EMO -->', $str);
		}
		return $str;
	}
}
/* Make a font tag */
class BBFont {
	public function Execute($str) {
		return preg_replace('~\[font=(.*?)\](.*?)\[\/font\]~is', '<span style="font-family: $1;">$2</span>', $str);
	}
}

/* EXAMPLE OF USE 
* The next part is a random block of text with all sorts of bb codes in it
* This is just an example.
* NOTE: you can also do $parser->addBBcode(*); AND/OR $parser->addCustom(new *); instead of having them all in the constructor
* (The * being either all the vars which are required for that function or the class required for that function)
*/
$text = "hello, this [b]stuff[/b] is great.. [omit]yep, [i]this is being[/i] omitted[/omit] [i]<-- all stuff within omit tags, is removed, unexecuted, and then put right back in.[/i] [omit]blam![/omit]";
$text .= "[code][list][*]heya\n[*]damn\n[/list][list=1][*]grammy!\n[/list][list=a][*]loglo\n[*]franchulate[/list][/code]";
$text .= "[font=arial]email me:[/font] [email]info@bestwebever.com[/email] [php]helloo<b>hey, this shouldn't be bold btw</b>[/php] www.bestwebever.com :P :) :( ";

echo '<strong>From:</strong> <PRE>'.$text .'</PRE>';
$parser = new BBParser($text);
echo '<br /><br /><strong>To:</strong> '.$parser->Execute();
echo '<br /><br /><strong>Revert:</strong> <PRE>'.$parser->Revert($parser->Execute()) .'</PRE>';
$parser = new BBParser($parser->Revert($parser->Execute()));
echo '<br /><br /><strong>Re-Revert:</strong> '.$parser->Execute();

/*
* The final $parser->Execute(); is what displays the text
*/

?>