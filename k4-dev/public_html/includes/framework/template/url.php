<?php
/**********************************************************************************
 *     File Arts
 *     Copyright (c) 2005, Geoffrey Goodman, BestWebEver.com
 *********************************************************************************/

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

			if ($path == DIRECTORY_SEPARATOR)
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

	function toString() {
		$url = '';

		if ($this->scheme) $url .= "{$this->scheme}://";
		if ($this->user) {
			$url .= $this->user;
			if ($this->pass) $url .= ":{$this->pass}";
			$url .= '@';
		}
		if ($this->host) $url .= $this->host;
		if ($this->path) $url .= "{$this->path}/";
		if ($this->file) $url .= $this->file;
		for ($i = 0; list($key, $value) = each($this->args); $i++)
			$url .= ($i > 0) ? "&$key=$value" : "?$key=$value";
		if ($this->anchor) $url .= "#{$this->anchor}";

		reset($this->args);

		return $url;
	}
}

?>
