<?php
/**********************************************************************************
 *     File Arts
 *     Copyright (c) 2005, Geoffrey Goodman, BestWebEver.com
 *********************************************************************************/

class SAXParser {
	var $parser;
	var $trans;

	function SAXParser() {
		$this->initialize();

		$trans = get_html_translation_table(HTML_ENTITIES);

		foreach ($trans as $ent => $name) {
			$this->trans[substr($name, 1, -1)] = $name;
		}
	}

	function parse($data, $is_final = TRUE) {
		if ($this->parser == NULL) {
			$this->parser = xml_parser_create();

			xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
			
			xml_set_object($this->parser, $this);
			xml_set_character_data_handler($this->parser, '_charData');
			xml_set_default_handler($this->parser, '_charData');
			xml_set_element_handler($this->parser, '_openTag', '_closeTag');
		}

		$data = preg_replace('/&([a-zA-Z0-9#]+);/', '{ent{$1}}', $data);

		if (!($ret = xml_parse($this->parser, $data, $is_final))) {
			$code = xml_get_error_code($this->parser);
			Error::pitch(new FAError(xml_error_string($code), __FILE__, __LINE__));
		}

		if ($is_final) {
			xml_parser_free($this->parser);
			$this->parser = NULL;
			$this->finalize();
		}

		return $ret;
	}

	function initialize() {}
	function finalize() {}

	function _charData($parser, $data) {
		$this->handleCharData($data);
	}
	function _openTag($parser, $name, $attribs) { $this->handleOpenTag($name, $attribs); }
	function _closeTag($parser, $name) { $this->handleCloseTag($name); }

	function handleCharData($data) {}
	function handleOpenTag($name, $attribs) {}
	function handleCloseTag($name) {}
}

?>