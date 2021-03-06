<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     template.defs.php
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

define_class('Template', dirname(__FILE__) . '/template.class.php');

define_class('TPL_Element', dirname(__FILE__) . '/parser.inc.php');
define_class('TPL_Data', dirname(__FILE__) . '/parser.inc.php');
define_class('TPL_Tag', dirname(__FILE__) . '/parser.inc.php');
define_class('TPL_Root', dirname(__FILE__) . '/parser.inc.php');
define_class('TPL_Component', dirname(__FILE__) . '/parser.inc.php');
define_class('TPL_Parser', dirname(__FILE__) . '/parser.inc.php');

?>