<?php
/**
* k4 Bulletin Board, template.php
*
* Copyright (c) 2005, Geoffrey Goodman
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
* @author Geoffrey Goodman
* @author Peter Goodman
* @version $Id: template.php,v 1.1 2005/04/05 03:21:47 k4st Exp $
* @package k42
*/

define('TPL_BASE_DIR', dirname(__FILE__));
define('TPL_FORCE_COMPILE', FALSE);
define('TPL_DIR', FORUM_BASE_DIR . DIRECTORY_SEPARATOR .'templates'. DIRECTORY_SEPARATOR);
define('TPL_CACHING', TRUE);
define('TPL_COMPILE_SUBDIR', 'compiled');


define('TPL_SOURCE', 1);
define('TPL_COMPILED', 2);
define('TPL_FILENAME', 3);

class TPL_Source {
	var $filename;

	function TPL_Source($filename) {
		$this->filename = $filename;
	}

	function &parse() {
		$parser = &new TPL_Parser();

		$parser->initialize();
		$parser->parse(file_get_contents($this->filename));
		$parser->finalize();

		return $parser->getRoot();
	}

	function compile() {
		require_once TPL_BASE_DIR.'/compiler.php';

		$root = $this->parse();
		$compiler = &new TPL_Compiler();
		$buffer = $compiler->compile($root);
		$buffer = preg_replace('/{\$([a-zA-Z_\.]+?)}/', '<?php echo $context->getVar("$1"); ?>', $buffer);
		$buffer = preg_replace('/{\@([a-zA-Z_\.]+?)}/', '$context->getVar("$1")', $buffer);
		$buffer = preg_replace('/\?><\?php/', '', $buffer);
		$buffer = preg_replace('/{ent{(.+?)}}/', '&$1;', $buffer);

		return $buffer;
	}

	function compileTo($filename) {
		$dirname = dirname($filename);

		if (!is_dir($dirname)) {
			assert(is_writable(dirname($dirname)) || exit("Unable to create the compiled template directory: ".dirname($dirname)));

			$umask = umask(0);
			mkdir($dirname, 0755);
			umask($umask);
		}

		if ($fp = @fopen($filename, 'w')) {
			fwrite($fp, $this->compile());
			fclose($fp);
		}
		else {
			exit("Unable to open the compiled template file for writing: $filename");
		}
	}
}

class Template {
	var $filename;
	var $dirname;

	var $context;

	var $cache;
	var $force;
	var $show;

	var $vars = array();
	var $lists = array();
	var $pagers = array();
	var $blocks = array();

	function Template($filename, $dirname = TPL_DIR, $force = TPL_FORCE_COMPILE, $cache = TPL_CACHING) {
		$this->setDirname($dirname);
		$this->setFilename($filename);
		$this->setForce($force);
		$this->setCaching($cache);
	}

	// Caching and Forcing

	function getCaching() {
		return $this->cache;
	}

	function getForce() {
		return $this->force;
	}

	function setCaching($cache) {
		$this->cache = (bool)($cache);
	}

	function setForce($force) {
		$this->force = (bool)($force);
	}

	// Template filenames

	function getFilename($which = TPL_FILENAME) {
		switch ($which) {
			case TPL_SOURCE: return $this->getSourceDir().$this->getTemplate();
			case TPL_COMPILED: return $this->getCompileDir().$this->getTemplate();
			default: return $this->getTemplate();
		}
	}

	function setFilename($filename) {
		$this->filename = $filename;
	}

	// Template files and directories

	function getTemplate() {
		return basename($this->filename);
	}

	function getCompileDir() {
		return $this->getSourceDir().TPL_COMPILE_SUBDIR.DIRECTORY_SEPARATOR;
	}

	function getSourceDir() {
		$dir = $this->source_dir;
		if (($subdir = dirname($this->filename)) != '.')
			$dir .= $subdir.DIRECTORY_SEPARATOR;
		return $dir;
	}

	function setDirname($dirname) {
		$this->source_dir = $dirname.DIRECTORY_SEPARATOR;
	}

	// Rendering

	function render() {
		$filename = $this->getFilename(TPL_SOURCE);
		$compiled = $this->getFilename(TPL_COMPILED);

		assert($filename != $compiled || exit("The source and compiled templates are the same: $filename"));
		assert(is_readable($filename) && file_exists($filename) || exit("Cannot read template file or file does not exist: $filename"));

		require_once TPL_BASE_DIR.'/runtime.php' ;

		if ($this->context == NULL)
			$this->context = &new TPL_Context($this->vars, $this->lists);

		$template = $this;
		$context = &$this->context;

		if (!$this->cache) {
			$source = &new TPL_Source($filename);
			$buffer = $source->compile();

			eval(" ?>$buffer<?php ");
		}
		else {
			if ($this->getForce() || !file_exists($compiled) || filemtime($filename) > filemtime($compiled)) {
				$source = &new TPL_Source($filename);
				$source->compileTo($compiled);
			}

			include $compiled;
		}
	}

	// Setters for pre-runtime

	function setFile($name, $file) {
		$this->files[$name] = $file;
	}

	function setList($name, &$list) {
		assert('is_a($list, "FAIterator")');

		$this->lists[$name] = &$list;
	}

	function setPager($name, &$pager) {
		assert('is_a($pager, "TPL_Paginator")');

		$this->pagers[$name] = &$pager;
	}

	function setVar($name, $value) {
		$this->vars[$name] = $value;
	}

	function setVarArray($array) {
		if (is_array($array)) {
			$this->vars = array_merge($this->vars, $array);
		}
	}

	function setError($section, $error, $backbutton = TRUE) {
		$this->setFile($section, 'information.html');
		$this->setVar('information', $error);
		if($backbutton)
			$this->show('info_back_button');
		else
			$this->hide('info_back_button');
	}

	function setInfo($section, $msg, $backbutton = FALSE) {
		$this->setError($section, $msg, $backbutton);
	}

	function setRedirect($url, $seconds) {
		$this->setVar('meta_redirect', '<meta http-equiv="refresh" content="'. $seconds .'; url='. $url .'">');
	}

	// Getters for during runtime

	function getFile($name, $default) {
		$name = $this->evaluateVars($name);

		if (isset($this->files[$name]))
			return $this->files[$name];

		return $default;
	}

	function &getPager($name) {
		$name = $this->evaluateVars($name);

		if (isset($this->pagers[$name]))
			return $this->pagers[$name];
	}

	function getVar($name) {
		$name = $this->evaluateVars($name);
		
		return isset($this->vars[$name]) ? $this->vars[$name] : NULL;
	}

	// Blocks and visibility

	function hide($name) {
		$this->blocks[$name] = FALSE;
	}

	function show($name) {
		$this->blocks[$name] = TRUE;
	}

	function showAll($regex = '//') {
		$this->show = $regex;
	}

	function isVisible($name, $default = TRUE) {
		$name = $this->evaluateVars($name);

		if ($this->show && preg_match($this->show, $name))
			return TRUE;

		if (isset($this->blocks[$name]))
			return $this->blocks[$name];

		return $default;
	}

	// Runtime variables

	function evaluateVars($string) {
		return preg_replace('/{\@([a-zA-Z_\.]+?)}/e', '$this->context->getVar("$1")', $string);
	}
}

?>
