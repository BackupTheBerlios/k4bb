<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     classes.php
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

// Forum & Posting stuff
require_once('classes/ancestors.class.php');
require_once('classes/forum.class.php');
require_once('classes/posting.class.php');
require_once('classes/prune.class.php');

// Calendar
require_once('classes/calendar.class.php');

// Misc
require_once('classes/online_users.class.php');
require_once('classes/birthdays.class.php');
require_once('classes/idiotfilter.class.php');
require_once('classes/lib.class.php');
require_once('classes/bbcode.class.php');
require_once('classes/css.class.php');
require_once('classes/config.class.php');
require_once('classes/error.class.php');
require_once('classes/dir.class.php');

// Member classes
require_once('classes/member/login.class.php');
require_once('classes/member/register.class.php');
require_once('classes/member/mliterator.class.php');
require_once('classes/member/usergroups.class.php');
require_once('classes/member/forgotpw.class.php');

// Threads
require_once('classes/threaditerator.class.php');
require_once('classes/polliterator.class.php');

// FAQ
require_once('classes/faqiterator.class.php');

// Private Messages
require_once('classes/privmsgs.class.php');

?>