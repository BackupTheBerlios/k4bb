<?php

error_reporting(E_ALL);
@set_time_limit(120);

/**
 * Start our session
 */
session_start();


/**
 * Define some base-line constants to work the install
 */
define('INSTALL_BASE_DIR', dirname(__FILE__));
define('FORUM_BASE_DIR', INSTALL_BASE_DIR .'/upload');
define('IN_K4', TRUE);
define('DEBUG_SQL', FALSE);


/**
 * Does the upload directory exist?
 */
if(!file_exists(FORUM_BASE_DIR)) {
	echo "Please make sure that k4 BB has been uploaded properly and that the 'upload' folder is intact.";
	exit;
}

/**
 * Does the functions.inc.php k4 file exist?
 */
if(!file_exists(FORUM_BASE_DIR .'/includes/framework/core/functions.inc.php')) {
	echo "Could not locate a critical PHP file within k4 Bulletin Board. Please make sure that k4 BB has been uploaded properly and that the 'upload' folder is intact.";
	exit;
}

/**
 * Does the database.php k4 file exist?
 */
if(!file_exists(FORUM_BASE_DIR .'/includes/framework/database/database.php')) {
	echo "Could not locate a critical PHP file within k4 Bulletin Board. Please make sure that k4 BB has been uploaded properly and that the 'upload' folder is intact.";
	exit;
}

/**
 * Include functions.inc.php
 */
include FORUM_BASE_DIR .'/includes/framework/core/functions.inc.php';
include FORUM_BASE_DIR .'/includes/framework/core/iterator.php';
include FORUM_BASE_DIR .'/includes/framework/core/error.class.php';
include FORUM_BASE_DIR .'/includes/framework/database/database.php';

/**
 * Does our config file exist?
 */
if(!file_exists(FORUM_BASE_DIR .'/config.php')) {
	compile_error("The config.php file does not exist.", '--', '--');
}

/**
 * Set this function because it is present but we don't want it
 */
function set_debug_item($stmt, $result) { }


/**
 * Unset all _REQUEST variables, and global ones in that case
 */
unset($_REQUEST);


/**
 * Set up a more 'solid' request variable to deal with, and the same for sessions
 */
$request			= $_GET + $_POST + $_COOKIE;
$session			= &$_SESSION;


/**
 * Display the HEADER portion of our install file
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
<head>
	<title>k4 v2.0 - Install - Powered by k4 BB</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	<meta name="generator" content="k4 Bulletin Board 2.0" />
	<meta name="robots" content="all" />
	<meta name="revisit-after" content="1 Days" />
	<meta http-equiv="Expires" content="1" />
	<meta name="description" content="k4 v2.0 - Powered by k4 Bulletin Board" />
	<meta name="keywords" content="k4, k4bb, bb, bulletin, board, bulletin board, forum, forums, message, board, message board, software, php, php5, mit, open, source, free" />
	<meta name="author" content="k4 Bulletin Board" />
	<meta name="distribution" content="GLOBAL" />
	<meta name="rating" content="general" />
	<link rel="icon" href="favicon.ico" type="image/x-icon" />
	<style type="text/css">
	* {
		font-family: Arial, Helvetica, Sans-Serif;
	}
	body {
		background-color: #FFFFFF;
		padding: 0px;
		margin: 0px;
	}
	a {
		font-size: 12px;
		color: #00000;
		text-decoration: none;
	}
	h5 {
		color: #045975;
		padding: 0px;
		margin: 0px;
		font-weight: bold;
		font-size: 18px;
	}
	h3 {
		padding-left: 15px;
		font-size: 16px;
		color: #E07591;
	}
	form {
		padding: 0px;
		margin: 0px;
	}
	.info_box {
		width: 520px;
		padding: 10px;
		text-align: justify;
	}
	.main_box_header {
		width: 520px;
		height: 10px;
		background-image: url('main_box_header.gif');
		background-position: left bottom;
		background-repeat: repeat-x;
		position: relative;
		top: 3px;
	}
	.main_box_footer {
		width: 520px;
		height: 10px;
		background-image: url('main_box_footer.gif');
		background-position: left top;
		background-repeat: repeat-x;
		position: relative;
		top: -3px;
	}
	.main_box {
		width: 520px;
		background-color: #FEF4F7;
		background-image: url('main_box_gradient.gif');
		background-position: left bottom;
		background-repeat: repeat-x;
		border-left: 1px solid #FEEAEF;
		border-right: 1px solid #FEE4EB;
		padding-bottom: 5px;
		text-align: left;
	}
	.main_box td {
		text-align: left;
		font-size: 13px;
		padding: 5px;
		border-bottom: 1px solid #FEEAEF;
	}
	.search_box {
		border: 1px solid #666666;
	}
	.button { 
		background-image: url('http://www.k4bb.org/k4/Images/Descent/background_button.gif');
		background-position: left top;
		background-repeat: repeat-x;
		font-size: 11px;
		font-family: verdana, geneva, lucida, arial, helvetica, sans-serif;
		border-right: 1px solid #B3B3B3;
		border-left: 1px solid #B3B3B3;
		border-top: 1px solid #F6F6F7;
		border-bottom: 1px solid #919194;
		margin-top: 5px;
	}
	.copyright {
		width: 175px;
		color: #666666;
		border-top: 1px dashed #666666;
		padding-top: 2px;
		font-size: 10px;
	}
	.copyright a {
		color: #000000;
		font-size: 10px;
	}
	.smalltext {
		font-size: 12px;
		color: #333333;
	}
	.inputbox {
		border: 1px solid #666666;
	}
	.inputfailed {
		border: 1px solid #FF0000;
	}
	</style>
	<script type="text/javascript">
	function document_location(url) {
		return document.location = url;
	}
	function redirect_page(seconds, url) {
		try {
			setTimeout("document_location('" + url + "')", (seconds * 1000));
		} catch(e) { }
	}

	var elements = new Array();
	var matches = new Array();
	var regexs = new Array();
	var errors = new Array();
	var messages = new Array();
	var error_classes = new Array();
	var base_classes = new Array();

	function resetErrors() {
		for (var i = 0; i < elements.length; i++)
		{
			var error = document.getElementById(errors[i]);
			if (error) error.style.display = 'none';

			var element = document.getElementById(elements[i]);
			if (element) element.className = base_classes[i];

			var message = document.getElementById(messages[i]);
			if (message) message.style.display = 'block';
		}
	}

	function showError(num)
	{
		var error = document.getElementById(errors[num]);
		if (error) error.style.display = 'block';

		var element = document.getElementById(elements[num]);
		if (element) element.className = error_classes[num];

		var message = document.getElementById(messages[num]);
		if (message) message.style.display = 'none';
	}
	function checkForm(form)
	{
		var valid = true;

		resetErrors();

		for (var i = 0; i < form.elements.length; i++)
		{
			var element = form.elements[i];
			for (var j = 0; j < elements.length; j++)
			{
				if (elements[j] == element.id)
				{
					var value = (element.options) ? element[element.selectedIndex].value : element.value;
					if (regexs[j] != '' && !regexs[j].test(value))
					{
						showError(j);
						valid = false;
						break;
					}
					if (matches[j]) {
						var match = document.getElementById(matches[j]);

						if (element.value != match.value)
						{
							element.value = '';
							match.value = '';

							showError(j);
							valid = false;
							break;
						}
					}
				}
			}
		}

		return valid;
	}
	function addMessage(id, message)
	{
		for (var i = 0; i < elements.length; i++) {
			if (elements[i] == id)
			{
				messages[i] = message;
			}
		}
	}
	function addVerification(id, regex, error, classname)
	{
		var num = elements.length;

		elements[num] = id;
		regexs[num] = new RegExp('^'+regex+'$');
		matches[num] = '';
		errors[num] = error;

		element = document.getElementById(id);
		base_classes[num] = element.className;
		error_classes[num] = (classname) ? classname : element.className;
	}
	function addCompare(id, match, error, classname)
	{
		var num = elements.length;

		elements[num] = id;
		regexs[num] = '';
		matches[num] = match;
		errors[num] = error;

		element = document.getElementById(id);
		base_classes[num] = element.className;
		error_classes[num] = (classname) ? classname : element.className;
	}
	</script>
</head>
<body>
<div align="center">
	<br />
	<table cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td><img src="http://www.k4bb.org/k4/Images/k4.gif" alt="k4 Bulletin Board" border="0" style="position:relative;left:-10px;" /></td>
			<td valign="bottom"><h5>Install k4 Bulletin Board</h5></td>
		</tr>
	</table>
	<br />
	<div class="info_box">
		<span class="smalltext">For this installation to work, you will be required to fill in fields concerning your server setup and where k4 will be located therein. If you do not feel that you will be able to fill these fields in, please contact your hosting service and ask them kindly for help.</span>
	</div>
	<div class="main_box_header">&nbsp;</div>
	<div class="main_box" align="center">
		<br />
<?php


$config_file				= "<?php
/**
* k4 Bulletin Board, config.php
*
* Copyright (c) 2005, Peter Goodman
*
* Permission is hereby granted, free of charge, to any person obtaining
* a copy of this software and associated documentation files (the
* \"Software\"), to deal in the Software without restriction, including
* without limitation the rights to use, copy, modify, merge, publish,
* distribute, sublicense, and/or sell copies of the Software, and to
* permit persons to whom the Software is furnished to do so, subject to
* the following conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED \"AS IS\", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
* BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
* ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
* CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*
* @author Peter Goodman
* @version \$Id: install.php,v 1.2 2005/05/27 00:23:32 k4st Exp $
* @package k42
*/

\$CONFIG['application']['action_var']	= 'act';
\$CONFIG['application']['lang']			= 'english';
\$CONFIG['application']['dba_name']		= '%s';

\$CONFIG['template']['path']				= dirname(__FILE__) . '/templates';
\$CONFIG['template']['force_compile']	= FALSE;
\$CONFIG['template']['ignore_white']		= FALSE;

\$CONFIG['ftp']['use_ftp']				= FALSE;
\$CONFIG['ftp']['username']				= '';
\$CONFIG['ftp']['password']				= '';
\$CONFIG['ftp']['server']				= '';

\$CONFIG['dba']['driver']				= '%s';
\$CONFIG['dba']['database']				= '%s';
\$CONFIG['dba']['directory']				= dirname(__FILE__) . '/includes/sqlite';
\$CONFIG['dba']['server']				= '%s';
\$CONFIG['dba']['user']					= '%s';
\$CONFIG['dba']['pass']					= '%s';

\$GLOBALS['_CONFIG']						= &\$CONFIG;

?>";


/**
 * Are we looking at the HTML form to install the forum?
 */
if(!isset($request['act']) || $request['act'] == ''):

	?>
	<h3>General Information</h3>
	<form action="install.php?act=install" method="post" enctype="multipart/form-data" onsubmit="return checkForm(this);" onreset="resetErrors();">
	<script type="text/javascript">resetErrors();</script>
	<table width="100%" cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td width="50%"><strong>Board Name:</strong></td>
			<td width="50%">
				<input type="text" name="bbtitle" id="bbtitle" value="" class="inputbox" class="inputbox" />
				<script type="text/javascript">addVerification('bbtitle', '.+', 'bbtitle_error', 'inputfailed');</script>
				<div id="bbtitle_error" style="display: none;">Please insert a name for your forum.</div>
			</td>
		</tr>
		<tr>
			<td width="50%"><strong>Board Description:</strong></td>
			<td width="50%">
				<input type="text" name="bbdescription" id="bbdescription" value="" class="inputbox" />
				<script type="text/javascript">addVerification('bbdescription', '.+', 'bbdescription_error', 'inputfailed');</script>
				<div id="bbdescription_error" style="display: none;">Please insert a short description of your forum.</div>
			</td>
		</tr>
	</table>
	<br />
	<h3>Database Information</h3>
	<form action="install.php?act=install" method="post" enctype="multipart/form-data">
	<table width="100%" cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td width="50%"><strong>Database Type:</strong></td>
			<td width="50%">
				<select name="dba" id="dba">
					<option value="mysql">MySQL</option>
					<option value="mysqli">MySQLi</option>
					<option value="sqlite">SQLite</option>
				</select>
				<script type="text/javascript">
					var dba		= document.getElementById('dba');
					var db_type	= dba[dba.selectedIndex].value;
				</script>
			</td>
		</tr>
		<tr>
			<td width="50%"><strong>Database User Name:</strong></td>
			<td width="50%">
				<input type="text" name="dba_username" id="dba_username" value="" class="inputbox" />
				<script type="text/javascript">if(db_type != 'sqlite') { addVerification('dba_username', '.+', 'dba_username_error', 'inputfailed'); } </script>
				<div id="dba_username_error" style="display: none;">Please insert a Database User Name.</div>
			</td>
		</tr>
		<tr>
			<td width="50%"><strong>Database User Password:</strong></td>
			<td width="50%">
				<input type="text" name="dba_password" id="dba_password" value="" class="inputbox" />
				<script type="text/javascript">if(db_type != 'sqlite') { addVerification('dba_password', '.+', 'dba_password_error', 'inputfailed'); } </script>
				<div id="dba_password_error" style="display: none;">Please insert a Database Password.</div>
			</td>
		</tr>
		<tr>
			<td width="50%"><strong>Database Name:</strong></td>
			<td width="50%">
				<input type="text" name="dba_name" id="dba_name" value="" class="inputbox" />
				<script type="text/javascript">addVerification('dba_name', '.+', 'dba_name_error', 'inputfailed');</script>
				<div id="dba_name_error" style="display: none;">Please insert a Database Name.</div>
			</td>
		</tr>
		<tr>
			<td width="50%"><strong>Database Server:</strong></td>
			<td width="50%">
				<input type="text" name="dba_server" id="dba_server" value="localhost" class="inputbox" />
				<script type="text/javascript">addVerification('dba_server', '.+', 'dba_server_error', 'inputfailed');</script>
				<div id="dba_server_error" style="display: none;">Please insert a Database Server.</div>
			</td>
		</tr>
	</table>
	<br />
	<h3>Administrator Information</h3>
	<form action="install.php?act=install" method="post" enctype="multipart/form-data">
	<table width="100%" cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td width="50%"><strong>Administrator User Name:</strong></td>
			<td width="50%">
				<input type="text" name="username" id="username" value="" class="inputbox" />
				<script type="text/javascript">addVerification('username', '.+', 'username_error', 'inputfailed');</script>
				<div id="username_error" style="display: none;">Please insert an Administrator Username.</div>
			</td>
		</tr>
		<tr>
			<td width="50%"><strong>Administrator Password:</strong></td>
			<td width="50%">
				<input type="password" name="password" id="password" value="" class="inputbox" />
				<script type="text/javascript">addVerification('password', '.+', 'password_error', 'inputfailed');</script>
				<div id="password_error" style="display: none;">Please insert an Administrator Password.</div>
			</td>
		</tr>
		<tr>
			<td width="50%"><strong>Administrator Email:</strong></td>
			<td width="50%">
				<input type="text" name="email" id="email" value="" class="inputbox" />
				<script type="text/javascript">addVerification('email', '.+', 'email_error', 'inputfailed');</script>
				<div id="email_error" style="display: none;">Please insert an Administrator Email.</div>
			</td>
		</tr>
	</table>
	<br />

	<div align="center">
		<input type="submit" value="Install Forum" class="button" />
	</div>
	</form>		
	<?php

elseif(isset($request['act']) && $request['act'] == 'install'):
	
	/**
	 * Add the tables to the database
	 */

	$session['install_vars']		= $request;

	$_CONFIG['dba']['driver']		= strtolower($request['dba']);
	$_CONFIG['dba']['user']			= $request['dba_username'];
	$_CONFIG['dba']['pass']			= $request['dba_password'];
	$_CONFIG['dba']['database']		= $request['dba_name'];
	$_CONFIG['dba']['server']		= $request['dba_server'];
	$_CONFIG['dba']['directory']	= FORUM_BASE_DIR . '/includes/sqlite';

	if(!file_exists(INSTALL_BASE_DIR .'/k4.'. $_CONFIG['dba']['driver'] .'.schema'))
		compile_error('You have chosen an invalid Database type. Please make sure that all of the database schema\'s are all present.', '--', '--');
	
	if(!file_exists(INSTALL_BASE_DIR .'/k4.data.schema'))
		compile_error('The SQL data file for k4 Bulletin Board does not exist.', '--', '--');

	$str							= file_get_contents(INSTALL_BASE_DIR .'/k4.'. $_CONFIG['dba']['driver'] .'.schema');
	$tables							= explode(";", $str);

	error::reset();
	$dba							= &Database::open($_CONFIG['dba']);
	if(error::grab())
		return critical_error();
	
	/* Loop through the database tables and add them to the database */
	foreach($tables as $table) {
		if($table != '') {
			$dba->executeUpdate($table);
		}
	}
	
	/**
	 * Display our current progress
	 */
	?>
	<h3>Database Tables Added...</h3>
	<script type="text/javascript">redirect_page(3, 'install.php?act=install2');</script>
	<meta http-equiv="refresh" content="3; url=install.php?act=install2" />
	<?php

elseif(isset($request['act']) && $request['act'] == 'install2'):
	
	/**
	 * Add the data to the database
	 */

	$_CONFIG['dba']['driver']		= strtolower($session['install_vars']['dba']);
	$_CONFIG['dba']['user']			= $session['install_vars']['dba_username'];
	$_CONFIG['dba']['pass']			= $session['install_vars']['dba_password'];
	$_CONFIG['dba']['database']		= $session['install_vars']['dba_name'];
	$_CONFIG['dba']['server']		= $session['install_vars']['dba_server'];
	$_CONFIG['dba']['directory']	= FORUM_BASE_DIR . '/includes/sqlite';

	if(!file_exists(INSTALL_BASE_DIR .'/k4.data.schema'))
		compile_error('The SQL data file for k4 Bulletin Board does not exist.', '--', '--');
	
	$str							= file_get_contents(INSTALL_BASE_DIR .'/k4.data.schema');
	$str							= @sprintf($str, $session['install_vars']['username'], $session['install_vars']['email'], md5($session['install_vars']['password']), $session['install_vars']['bbtitle'], $session['install_vars']['bbdescription'], $session['install_vars']['email'], $session['install_vars']['email']);
	$str							= preg_replace("~(\r\n|\r|\n)~", "\n", $str);
	$queries						= explode("\n", $str);

	error::reset();
	$dba							= &Database::open($_CONFIG['dba']);
	if(error::grab())
		return critical_error();
	
	/* Loop through the database tables and add them to the database */
	foreach($queries as $query) {
		if($query != '') {
			$dba->executeUpdate($query);
		}
	}

	/**
	 * Display our current progress
	 */
	?>
	<h3>Database Information Added...</h3>
	<script type="text/javascript">redirect_page(3, 'install.php?act=install3');</script>
	<meta http-equiv="refresh" content="3; url=install.php?act=install3" />
	<?php

elseif(isset($request['act']) && $request['act'] == 'install3'):
	
	$config_file			= sprintf($config_file, $session['install_vars']['dba_name'], $session['install_vars']['dba'], $session['install_vars']['dba_name'], $session['install_vars']['dba_server'], $session['install_vars']['dba_username'], $session['install_vars']['dba_password']);

	$handle					= fopen(FORUM_BASE_DIR .'/config.php', 'w');
	@chmod(FORUM_BASE_DIR .'/config.php', 0777);
	@fwrite($handle, $config_file);
	@fclose($handle);
	
	?>
	<h3>Config File Created...</h3>
	<script type="text/javascript">redirect_page(3, 'upload/index.php');</script>
	<meta http-equiv="refresh" content="3; url=upload/index.php" />
	<?php

endif;


/**
 * Display the FOOTER html for our install
 */
?>
	</div>
	<div class="main_box_footer">&nbsp;</div>
</div>
<br /><br />
<div align="center">
	<span class="copyright">
		[ <a href="http://www.k4bb.org" title="k4 Bulletin Board" target="_blank">Powered By: k4 Bulletin Board</a> ]
	</span>
	<br />
</div>
</body>
</html>
<?php

$_SESSION							=& $session;

?>