<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     config.class.php
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

class ModConfig {
	static public function ResetCategory($file, $var, $new_var, $configurl = FALSE) {
		$configurl = !$configurl ? @get_setting('config', 'configurl') : $configurl;
		$file = $configurl .'/'. $file .'.ini.php';
		$fp = fopen($file, 'r+b');
		$text = fread($fp, filesize( $file ));
		$text = preg_replace("~\[{$var}\]~", "[{$new_var}] ", $text);

		rewind($fp);
		fwrite($fp, $text);
		fclose($fp);
	}
	static public function ResetVar($file, $array, $configurl = FALSE) {
		$configurl = !$configurl ? @get_setting('config', 'configurl') : $configurl;
		$file = $configurl .'/'. $file .'.ini.php';
		$fp = fopen($file, 'r+b');
		$text = fread($fp, filesize( $file ));
		foreach($array as $var=>$val) {
			$text = preg_replace("~{$var}\s+\=([^\n]+)~", " {$var} = {$val} ", $text);
		}
		rewind($fp);
		fwrite($fp, $text);
		fclose($fp);
	}
}

?>