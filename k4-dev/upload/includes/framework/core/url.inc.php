<?php
/**
* k4 Bulletin Board, url.inc.php
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
* @author Geoffrey Goodman
* @version $Id: url.inc.php,v 1.5 2005/05/11 17:41:55 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

/**
 * @author			Geoffrey Goodman
 * @param scheme	The Scheme variable returned from the parse_url function
 * @param host		The host of the url
 * @param path		The path of the url
 * @param port		The port of the url, if any was defined
 * @param fragment	The fragment of the url
 */
class Url {
	var $args = array();
	var $scheme;
	var $user;
	var $pass;
	var $host;
	var $port;
	var $path;
	var $file;
	var $anchor;

	function Url($url) {
		$query = parse_url($url);

		if (isset($query['scheme']))
			$this->scheme = $query['scheme'];

		if (isset($query['user']))
			$this->user = $query['user'];

		if (isset($query['pass']))
			$this->user = $query['pass'];

		if (isset($query['host']))
			$this->user = $query['host'];

		if (isset($query['port']))
			$this->user = $query['port'];

		if (isset($query['path'])) {

			$path = dirname($query['path']);

			if ($path == '/' || $path == '\\')
				$path = '';

			$this->path = $path;
			$this->file = basename($query['path']);
		}

		if (isset($query['anchor']))
			$this->anchor = $query['anchor'];

		if (isset($query['query'])) {
			$args = explode('&', $query['query']);
			foreach ($args as $arg) {
				if ($arg) {
					$temp = explode('=', $arg);
					if (!empty($temp))
						$this->args[$temp[0]] = $temp[1];
				}
			}
		}
	}

	function __toString() {
		$url = '';
		
		if ($this->scheme) $url .= "{$this->scheme}://";
		
		if ($this->user) {
			$url .= $this->user;
			if ($this->pass) $url .= ":{$this->pass}";
			if($this->user && $this->host) $url .= '@';
		}
		if ($this->host) $url .= $this->host;
		if ($this->path) $url .= "{$this->path}";
		if ($this->file) $url .= "/{$this->file}";
		for ($i = 0; list($key, $value) = each($this->args); $i++)
			$url .= ($i > 0) ? "&$key=$value" : "?$key=$value";
		if ($this->anchor) $url .= "#{$this->anchor}";

		reset($this->args);

		return $url;
	}
}


?>