<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     install.php
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

// uncomment this line if you get an error like: Fatal error: session_start() [function.session-start]: Failed to initialize storage module: user
//session_module_name("files");

session_start();

require '../tables.inc.php';
require '../permissions.inc.php';
require '../classes/config.class.php';
require '../classes/bbcode.class.php';
require '../classes/idiotfilter.class.php';

if(file_exists('../install.lock.php'))
	exit('The k4 Bulletin Board has already been installed.');

/*
---------------------------------------------------
			do the PAGE HEADER
---------------------------------------------------
*/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>k4 Bulletin Board Installation</title>
<style type="text/css">
body {
	background-color: #E1E1E2;
	padding: 0px;
	margin: 0px;
	font-family: Arial, Helvetica, Sans-Serif;
}
h2 {
	color: #FF0000;
	text-align: center;
	margin-top: 50px;
	text-transform: uppercase;
}
h3 {
	color: #003366;
	width: 760px;
	background-color: #FFFFFF;
	padding: 10px;
	border-bottom: 2px solid #336699;
	margin: 0px;
	background-image:url('k4.gif');
	background-position: left bottom;
	background-repeat: no-repeat;
	height: 64px;
	text-align: right;
}
.waittext {
	color: #FF0000;
	font-size: 16px;
	font-weight: bold;
}
.tcat {
	background-color: #6C6C6C;
	color: #FFFFFF;
	text-align: center;
	font-weight: bold;
}
.tcat td { padding: 10px; }
.thead {
	padding: 10px;
}
.panel {
	padding: 10px;
}
.minitext {
	font-size: 11px;
}
.passed {
	font-weight: bold;
	color: green;
}
.failed {
	font-weight: bold;
	color: #FF0000;
}
#page_body {
	width: 770px;
	text-align: left;
	background-color: #FFFFFF;
	font-size: 12px;
	padding: 5px;
}
table {
	width: 770px;
}
</style>
</head>
<body>
<div align="center">
<h3>Welcome to the k4 Bulletin Board Installation</h1>
<div id="page_body">
For this installation to work, you will be required to fill in fields concerning your server setup and where k4 will be located therein. If you do not feel that you will be able to fill these fields in, please contact your hosting service and ask them kindly for help.
<?php

/*
---------------------------------------------------
			TRY AND GET THE DOCUMENT ROOT
---------------------------------------------------
*/

function get_root() {
	$first = $_SERVER['SCRIPT_FILENAME'];
	$last = $_SERVER['SCRIPT_NAME'];

	$path = explode("/", $first);
	$path = count($path) == 0 ? explode("\\", $first) : $path;

	$small_path = explode("/", $last);
	$small_path = count($small_path) == 0 ? explode("\\", $last) : $small_path;

	$new_path = null;
	/* We do -1 do go below the document root */
	for($i = 0; $i <= (count($path)-count($small_path)-1); $i++) {
		$new_path .= $path[$i].'/';
	}
	
	if(preg_match("~^[a-zA-Z]\:\/~is", $new_path)) {
		if(!isset($_SESSION['local_error'])) {
			$_SESSION['local_error'] = TRUE;
			exit('<br /><br /><span class="waittext">Apparently you are installing the k4 bulletin board on a Local Machine. k4 cannot guarantee that the installation will work, however, if you read up in the FAQ on the k4 website, you should be able to find an equally simple method of installing k4. To continue the install, please click the <strong>Refresh</strong> button on your Browser window.</span><br /><br />Note: for optimum efficiency, use a <strong>/</strong> to separate parts of a path. <br /></br />If you follow the last step, you should be fine :)');
		}
	}
	return $new_path;
}

/*
---------------------------------------------------
			INSTALLATION START INTERFACE
---------------------------------------------------
*/

function start_install() {
	$root = get_root();
	?>
	<form action="install.php?step=1" method="POST" enctype="multipart/form-data">
	<table width="100%" cellpadding="0" cellspacing="1" border="0">
		<tr class="tcat">
			<td>Installation Check</td>
		</tr>
		<tr class="panel">
			<td>
				PHP Version: <?php echo phpversion(); if(phpversion()>=5) { echo '<span class="passed">Passed</span>'; } else { echo '<span class="failed">Failed</span>'; exit; } ?><br />
				SQLite: <?php if(function_exists('sqlite_open')) { echo '<span class="passed">Passed</span>'; } else { echo '<span class="failed">Failed</span>'; } ?><br />
				MySQL: <?php if(function_exists('mysql_connect')) { echo '<span class="passed">Passed</span>'; } else { echo '<span class="failed">Failed</span>'; } ?><br />
				PostgreSQL: <?php if(function_exists('pg_connect')) { echo '<span class="passed">Passed</span>'; } else { echo '<span class="failed">Failed</span>'; } ?><br />
				SPL (Standard PHP Library): <?php if(class_exists('Iterator')) { echo '<span class="passed">Passed</span>'; } else { echo '<span class="failed">Not Sure</span>'; } ?><br />
			</td>
		</tr>
	</table>
	<table width="100%" cellpadding="0" cellspacing="1" border="0">
		<tr class="tcat">
			<td>Installation Types</td>
		</tr>
		<tr class="panel">
			<td>
				<script language="JavaScript" type="text/javascript">
				//<![CDATA[
				function showInstall(the_select) {
					var install		= document.getElementById('db_info');
					var sqlite		= document.getElementById('sqlite_info');
					var sqlite_dir	= document.getElementById('sqlite_dir');
					if(the_select[the_select.selectedIndex].value == 'mysql' || the_select[the_select.selectedIndex].value == 'pgsql') {
						if(confirm('The MySQL driver is untested and the PostgreSQL driver is generally not working due to my lack of PostgreSQL PHP functions knowlege, are you SURE you want to test drive these babies?')) {
							install.style.display		= 'block';
							sqlite.style.display		= 'none';
							sqlite_dir.style.display	= 'none';
						} else {
							the_select[the_select.selectedIndex] = 0;
							install.style.display		= 'none';
							sqlite.style.display		= 'block';
							sqlite_dir.style.display	= 'block';
						}
					} else {
						install.style.display		= 'none';
						sqlite.style.display		= 'block';
						sqlite_dir.style.display	= 'block';
					} 
				}
				//]]>
				</script>
				<strong>Please choose your installation type</strong>:<br />
				<select name="act" id="act" onChange="return showInstall(this);">
					<option value="1">Install</option>
					<option value="0">Uninstall</option>
				</select>
				<br />
				<strong>Please choose the type of database that you will be installing the software onto</strong>:<br />
				<select name="db" id="db" onChange="showInstall(this)">
					<option value="sqlite">SQLite</option>
					<!--<option value="mysql">MySQL</option>
					<option value="pgsql">PostgreSQL</option>-->
				</select>
				<br />
				<strong>Please choose the Language you would like to install k4 in</strong>:<br />
				<select name="language" id="language">
					<option value="english">English</option>
				</select>
			</td>
		</tr>
		<tr class="tcat">
			<td>Configuration Information</td>
		</tr>
		<tr class="panel">
			<td>
				<fieldset>
				<legend>Legend</legend>
				<span class="waittext">USE ABSOLUTE PATH</span>
				<br />
				<span class="waittext">USE ONLY <strong>/</strong> TO SEPARATE FOLDERS IN PATHS</span>
				<br />
				<strong>Document Root</strong>: Is the <strong>public_html</strong> or <strong>www</strong> folder.<br />
				<strong>Below the document Root</strong>: Is any folder which needs to be put where the Document Root folder is.<br />
				</fieldset>
				<br />
				<div id="sqlite_dir" style="display:block;">
					<strong>SQLite Directory</strong>:<br />
					<span class="minitext">(Below the document root ** USE ABSOLUTE PATH) (** Only needed for SQLite)</span><br />
					<input type="text" name="directory" value="<?php echo $root; ?>sqlite" size="100" /><br /><br />
				</div>
				<strong>Template Directory</strong>:<br />
				<span class="minitext">(Below the document root ** USE ABSOLUTE PATH)</span><br />
				<input type="text" name="path" value="<?php echo $root; ?>templates" size="100" /><br /><br />
				<strong>Current Template Folder</strong>:<br />
				<span class="minitext">(Directory within the 'Template Directory' where your templates are stored. If you're not sure, leave it as 'Descent' ** USE ABSOLUTE PATH)</span><br />
				<input type="text" name="folder" value="Descent" size="100" /><br /><br />
				<strong>Image Folder</strong>:<br />
				<span class="minitext">(The Image folder, it is the current image set you are using. If you are not sure, leave it as 'Descent' ** USE ABSOLUTE PATH)</span><br />
				<input type="text" name="imgfolder" value="Descent" size="100" /><br /><br />
				<strong>Direct Path to Forum Base</strong>:<br />
				<span class="minitext">(This will either be the document root or a folder in it, make sure that you adjust this ** USE ABSOLUTE PATH)</span><br />
				<input type="text" name="forumurl" value="<?php echo $root; ?>public_html" size="100" /><br /><br />
				<strong>Direct Path to Configuration Folder</strong>:<br />
				<span class="minitext">(This is in the 'framework' folder below the document root, this should end in '/framework/config' ** USE ABSOLUTE PATH)</span><br />
				<input type="text" name="configurl" value="<?php echo $root; ?>framework/config" size="100" />
			</td>
		</tr>
		<tbody id="db_info" style="display:none;">
			<tr class="tcat">
				<td>MySQL or PostgreSQL Configuration</td>
			</tr>
			<tr class="panel">
				<td>
					<strong>Database Name</strong>:<br /> <input type="text" name="db_name" id="database name" /><br />
					<strong>Database User Name</strong>:<br /> <input type="text" name="db_user" id="database user name" /><br />
					<strong>Database Password</strong>:<br /> <input type="text" name="db_pass" id="database password" /><br />
					<strong>Database Server</strong>:<br /> <input type="text" name="db_host" id="database server" value="localhost" />
				</td>
			</tr>
		</tbody>
		<tbody id="sqlite_info" style="display:block;" width="100%">
			<tr class="tcat">
				<td width="100%">SQLite Settings</td>
			</tr>
			<tr class="panel">
				<td>
					<strong>Database Name</strong>:<br />
					<input type="text" name="dbname" value="k4_forum" size="40" /><br />
					<strong>Database File</strong>:<br />
					<input type="text" name="database" value="k4_forum.sqlite" size="40" /><br />
				</td>
			</tr>
		</tbody>
		<tr class="tcat">
			<td>FTP Settings</td>
		</tr>
		<tr class="panel">
			<td>
				<strong>Use FTP</strong>:<br />
				<select name="use_ftp" id="use_ftp">
					<option value="true">Yes</option>
					<option value="false">No</option>
				</select><br />
				<strong>FTP Username</strong>:<br />
				<input type="text" name="username" value="" size="40" /><br />
				<strong>FTP Password</strong>:<br />
				<input type="password" name="password" value="" size="40" /><br />
				<strong>FTP Server</strong>:<br />
				<input type="text" name="server" value="" size="40" /><br />
			</td>
		</tr>
		<tr class="tcat">
			<td>Administrator Settings</td>
		</tr>
		<tr class="panel">
			<td>
				<strong>Username</strong>:<br />
				<input type="text" name="admin_username" value="" size="16" maxlength="16" /><br />
				<strong>Email</strong>:<br />
				<input type="text" name="admin_email" value="" size="40" /><br />
				<strong>Password</strong>:<br />
				<input type="password" name="admin_password" value="" size="16" maxlength="16" /><br />
			</td>
		</tr>
		<tr class="tcat">
			<td>Forum Settings</td>
		</tr>
		<tr class="panel">
			<td>
				<strong>Forum Name (The name of your Bulletin Board)</strong>:<br />
				<input type="text" name="forum_name" value="" size="40" /><br />
				<strong>Short Forum Description (The BB's description)</strong>:<br />
				<input type="text" name="forum_description" value="" size="40" /><br />
			</td>
		</tr>
		<tr class="tcat">
			<td>Site Settings</td>
		</tr>
		<tr class="panel">
			<td>
				<strong>Webmasters Email</strong>:<br />
				<input type="text" name="webmaster_email" value="" size="40" /><br />
				<strong>Contact Email</strong>:<br />
				<input type="text" name="contact_email" value="" size="40" /><br />
			</td>
		</tr>
		<tr class="tcat">
			<td>
				<input type="submit" value="Install it Baby!" onClick="javascript:alert('The page will automatically refresh itself several times. Don\'t be alarmed if you see this.');" />
			</td>
		</tr>
	</table>
	</form>
	<?php
}

/*
---------------------------------------------------
			FUNCTION : TO FIND OUT HOW MANY '../' WE NEED TO PUT IN FRONT OF SOMETHING
---------------------------------------------------
*/

function RelativeUrl($there) {
	
	$temp = $there;

	$here = substr($_SESSION['post_vars']['forumurl'], 1) . '/Install';
	$there = $there{0} == '/' ? substr($there, 1) : $there;

	$path = null;
	for($i=0; $i < (count(explode("/", $here))-2); $i++) {
		$path .= '../';
	}
	
	$new_there = null;

	$there = $there{0} == '/' ? substr($there, 1) : $there;
	$there = explode("/", $there);
	
	for($i=2;$i<count($there); $i++) {
		$new_there .= ($i != 1) ? '/' . $there[$i] : $there[$i];
	}
	
	if(substr($path, (strlen($path)-2)) == '//')
		$path  = substr($path, strlen($path)-1);

	$relative_url = $path . $new_there;
	
	/* Juggle the path around a bit, it however, should always be the first one. */

	if(is_dir($relative_url))
		return $relative_url;
	else if(is_dir('../' . $relative_url))
		return '../' . $relative_url;
	else if(is_dir(substr($relative_url, 3)))
		return substr($relative_url, 3);
	else
		exit('<br /><br />[CRITICAL ERROR] The system could not format the FTP path to: '. $temp .'.<br /><br />Please contact your administrator or ask for help on the official k4 support forums.');
}

/*
---------------------------------------------------
			STEP ONE : SEE IF THE FTP CONNECTION IS SECURE AND DO SOME ERROR CHECKING
---------------------------------------------------
*/
class StepOne {
	public		$conn_id = NULL;
	public		$r;

	/* the relative url's of things so we won't need to in the future */
	public		$rel_configurl;
	
	public function __construct() {
		$this->r = $_SESSION['post_vars'];
		$this->rel_configurl = RelativeUrl($this->r['configurl']);

		/* Check if we should use FTP, if so, logon to the FTP server */
		if($this->r['use_ftp'] == 'true') {
			if($this->conn_id = ftp_connect($this->r['server'])) {
				if(!ftp_login($this->conn_id, $this->r['username'], $this->r['password']))
					exit('There was an error while trying to log on to the FTP server. Please go back and correct this issue.');
			} else {
				exit('Could not connect to the FTP Server. Please Correct this issue.');
			}
		}
	}

	public function step_one_one() {
		/* Error Checking */
		
		if($this->r['db'] == 'sqlite') {
			if(!is_dir($this->r['directory']))
				exit('You have specified an invalid SQLite Directory. Please go back and Correct this error.');
		}
		if(!is_dir($this->r['path']))
			exit('You have specified an invalid Template Directory. Please go back and Correct this error.');
		if(!is_dir($this->r['forumurl']))
			exit('You have specified an invalid Forum Base URL. Please go back and Correct this error.');
		if(!is_dir($this->r['configurl']))
			exit('You have specified an invalid Configuration Files Folder Directory. Please go back and Correct this error.');
		
		/* Refresh the page */
		echo '<div align="center"><br /><br />Found and Validated the SQLite (only for sqlite), Templates, Forum Base and Configuration Files Directories.</div>';
		echo '<h2>Step 1.25 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
		echo '<meta http-equiv="refresh" content="3; url=install.php?step=1.5">';
	}

	public function step_one_two() {
		/* Modify the Configuration dir now */
		if(is_dir($this->rel_configurl)) {
			if($this->r['use_ftp'] == 'true') {
				if(!ftp_chmod($this->conn_id, 0777, $this->rel_configurl))
					exit('Could not change the file permissions on the configuration folder. Please Correct this error.');
				if(!ftp_chmod($this->conn_id, 0777, $this->rel_configurl.'/pagecraft.ini.php'))
					exit('Could not change the file permissions on configuration file pagecraft.ini.php.');
				if(!ftp_chmod($this->conn_id, 0777, $this->rel_configurl.'/databases.ini.php'))
					exit('Could not change the file permissions on configuration file databases.ini.php.');
			}
		}
		/* Refresh the page */
		echo '<div align="center"><br /><br />Successfully CHMODed the Configuration Files Directories and the databases.ini &amp; pagecraft.ini files.</div>';
		echo '<h2>Step 1.5 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
		echo '<meta http-equiv="refresh" content="3; url=install.php?step=1.75">';
	}

	public function step_one_three() {
		/* Figure out variables for either sqlite or for mysql/pgsql */
		switch($this->r['db']) {
			case 'sqlite': {
				$db_name = 'dba_name';
				$database = $this->r['dbname'];
				$db = $this->r['database'];
				$cat = $this->r['dbname'];
				break;
			}
			case 'mysql': {
				$db_name = 'dba_name';
				$database = $this->r['db_name'];
				$db = $this->r['db_name'];
				$cat = $this->r['db_name'];
				break;
			}
			case 'pgsql': {
				$db_name = 'dba_name';
				$database = $this->r['db_name'];
				$db = $this->r['db_name'];
				$cat = $this->r['db_name'];
				break;
			}
		}
		/* Modify the Configuration file now */
		if(file_exists($this->rel_configurl.'/pagecraft.ini.php')) {
			/* Reset Variables in the pagecraft.ini.php file */
			$this->r['language'] = !$this->r['language'] ? 'english' : $this->r['language'];
			ModConfig::ResetVar('pagecraft', array('styleset' => 1, 'lang' => $this->r['language'], $db_name => $database, 'configurl' => $this->r['configurl'], 'forumurl' => $this->r['forumurl'], 'imgfolder' => $this->r['imgfolder'], 'tplfolder' => $this->r['folder'], 'path' => $this->r['path'], 'directory' => $this->r['directory']), $this->rel_configurl);
			if($this->r['use_ftp'] == 'true') {
				ModConfig::ResetVar('pagecraft', array('use_ftp' => $this->r['use_ftp'], 'username' => $this->r['username'], 'password' => $this->r['password'], 'server' => $this->r['server']), $this->rel_configurl);
			}
		} else {
			exit('The \'pagecraft.ini.php\' file does not exist in the Configuration folder. Please Correct this error. Error found at: '. $this->rel_configurl.'/pagecraft.ini.php');
		}
		
		if(file_exists($this->rel_configurl.'/databases.ini.php')) {
			/* Reset Variables in the databases.ini.php file */
			ModConfig::ResetCategory('databases', 'k4_forum', $cat, $this->rel_configurl);
			ModConfig::ResetVar('databases', array('type' => $this->r['db'], 'database' => $db), $this->rel_configurl);
			ModConfig::ResetVar('databases', array('user' => $this->r['db_user'], 'pass' => $this->r['db_pass']), $this->rel_configurl); // 'host' => $this->r['db_host']
		} else {
			exit('The \'databases.ini.php\' file does not exist in the Configuration folder. Please Correct this error.');
		}
		/* Refresh the page */
		echo '<div align="center"><br /><br />Successfully updated your k4 Configuration Files.</div>';
		echo '<h2>Step 1.75 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
		echo '<meta http-equiv="refresh" content="3; url=install.php?step=2.16">';
	}
}
/*
---------------------------------------------------
			STEP TWO : CHMODDING EVERYTHING
---------------------------------------------------
*/
class StepTwo {
	protected $r;
	protected $conn_id;

	protected $dirs;

	protected $rel_forumurl;
	protected $rel_configurl;
	protected $rel_path;

	public function __construct() {
		$this->r = $_SESSION['post_vars'];

		$this->rel_forumurl = RelativeUrl($this->r['forumurl']);
		$this->rel_configurl = RelativeUrl($this->r['configurl']);
		$this->rel_path = RelativeUrl($this->r['path']);
		
		$this->dirs = array('/source/', '/source/usercp/', '/source/admin/', '/source/admin/css/', '/source/admin/files/', '/compiled/', '/compiled/usercp/', '/compiled/admin/', '/compiled/admin/css/', 'compiled/admin/files/');
		
		if($this->r['use_ftp'] == 'true') {
			if($this->conn_id = ftp_connect($this->r['server'])) {
				if(!ftp_login($this->conn_id, $this->r['username'], $this->r['password']))
					exit('There was an error while trying to log on to the FTP server. Please go back and correct this issue.');
			} else {
				exit('Could not connect to the FTP Server. Please Correct this issue.');
			}
		}
	}
	public function step_two_one() {
		if($this->r['use_ftp'] == 'true') {
			if(!ftp_chmod($this->conn_id, 0777, $this->rel_forumurl))
				exit('Could not change the file permissions of the Forum\'s root Directory. Please Correct this error.');
			if(!ftp_chmod($this->conn_id, 0777, $this->rel_forumurl .'/Images'))
				exit('Could not change the file permissions of the Forum\'s Images Directory. Please Correct this error.');
			if(!ftp_chmod($this->conn_id, 0777, $this->rel_forumurl .'/Uploads'))
				exit('Could not change the file permissions of the Uploads Directory. Please Correct this error.');
			if(!ftp_chmod($this->conn_id, 0777, $this->rel_forumurl .'/Uploads/Avatars'))
				exit('Could not change the file permissions of the Uploads Avatar Directory. Please Correct this error.');
		}
		/* Refresh the page */
		echo '<div align="center"><br /><br />Successfully CHMODed the Root, Images and Uploads Directories.</div>';
		echo '<h2>Step 2.16 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
		echo '<meta http-equiv="refresh" content="3; url=install.php?step=2.32">';
	}
	public function step_two_two() {
		if($this->r['use_ftp'] == 'true') {
		/* FTP CHMOD all of the folders within the /Images folder */
			if(!ftp_chmod($this->conn_id, 0777, $this->rel_forumurl .'/Images/Descent')) {
				exit('Could not change the file permissions of the Descent Image Set Directory. Please Correct this error.');
			}
			if(!ftp_chmod($this->conn_id, 0777, $this->rel_forumurl .'/Images/Descent/Uploads')) {
				exit('Could not change the file permissions of the Descent Image Set\'s Uploads Directory. Please Correct this error.');
			}
		}
		/* Refresh the page */
		echo '<div align="center"><br /><br />Successfully CHMODed the Descent Image Set Directories.</div>';
		echo '<h2>Step 2.32 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
		echo '<meta http-equiv="refresh" content="3; url=install.php?step=2.48">';
	}
	public function step_two_three() {
		if($this->r['use_ftp'] == 'true') {
			if(is_dir($this->rel_forumurl .'/Images/Descent/Icons')) {
				if(!ftp_chmod($this->conn_id, 0777, $this->rel_forumurl .'/Images/Descent/Icons/Emoticons'))
					exit('Could not change the file permissions of one of the Emoticons folder. Please Correct this error.');
				if(!ftp_chmod($this->conn_id, 0777, $this->rel_forumurl .'/Images/Descent/Icons/PostIcons'))
					exit('Could not change the file permissions of one of the Post Icons folder. Please Correct this error.');
			}
		}
		/* Refresh the page */
		echo '<div align="center"><br /><br />Successfully CHMODed the Emoticons and Post Icons Directories.</div>';
		echo '<h2>Step 2.48 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
		echo '<meta http-equiv="refresh" content="3; url=install.php?step=2.64">';
	}
	/* The following functions don't need any ftp_cmod() ing */
	public function step_two_four() {
		if($this->r['use_ftp'] == 'true') {
			if(!ftp_chmod($this->conn_id, 0777, RelativeUrl($this->r['directory'])))
				exit('Could not change the file permissions of the Forum\'s SQLite Directory. Please Correct this error.');
			if(!ftp_chmod($this->conn_id, 0777, $this->rel_path)) {
				exit('Could not change the file permissions of the Forum\'s Template Directory. Please Correct this error.');
			}
		}
		/* Refresh the page */
		echo '<div align="center"><br /><br />Successfully CHMODed the SQLite and Template Directories.</div>';
		echo '<h2>Step 2.64 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
		echo '<meta http-equiv="refresh" content="3; url=install.php?step=2.8">';
	}
	public function step_two_five() {
		if($this->r['use_ftp'] == 'true') {
			/* Try and FTP CHMOD all of the template directories */
			
			foreach($this->dirs as $dir) {
				if(!ftp_chmod($this->conn_id, 0777, $this->rel_path . '/Descent/' . $dir)) {
					exit('Could not change the file permissions of one the Forum\'s Template Directories. Please Correct this error.');
				}
			}
		}
		/* Refresh the page */
		echo '<div align="center"><br /><br />Successfully CHMODed all of the Template SubDirectories.</div>';
		echo '<h2>Step 2.8 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
		echo '<meta http-equiv="refresh" content="3; url=install.php?step=2.96">';
	}
	public function step_two_six() {
		/* Try to FTP CHMOD ALL of the source templates */
		if($this->r['use_ftp'] == 'true') {
			
			$dirs = array($this->dirs[0], $this->dirs[1], $this->dirs[2], $this->dirs[3], $this->dirs[4]);
			
			foreach($dirs as $dir) {
				$temp_dir = dir($this->rel_path . '/Descent/' . $dir);
				while(false !== ($tpl = $temp_dir->read())) {
					@ftp_chmod($this->conn_id, 0777, $this->rel_path . '/Descent/' . $dir . $tpl);
				}
			}
		}
		/* Refresh the page */
		echo '<div align="center"><br /><br />Successfully CHMODed all of the Source Templates.</div>';
		echo '<h2>Step 2.96 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
		echo '<meta http-equiv="refresh" content="3; url=install.php?step=2.99">';
	}
	public function step_two_seven() {
		/* Try to FTP CHMOD ALL of the compiled templates */
		if($this->r['use_ftp'] == 'true') {

			$dirs = array($this->dirs[5], $this->dirs[6], $this->dirs[7], $this->dirs[8], $this->dirs[9]);
			
			foreach($dirs as $dir) {
				$temp_dir = dir($this->rel_path . '/Descent/' . $dir);
				while(false !== ($tpl = $temp_dir->read())) {
					@ftp_chmod($this->conn_id, 0777, $this->rel_path . '/Descent/' . $dir . $tpl);
				}
			}
		}
		/* Refresh the page */
		echo '<div align="center"><br /><br />Successfully CHMODed all of the Compiled Templates.</div>';
		echo '<h2>Step <strong>2</strong> Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
		echo '<meta http-equiv="refresh" content="3; url=install.php?step=3">';
	}
}

/*
---------------------------------------------------
			STEP THREE : BUILDING THE DATABASE
---------------------------------------------------
*/

function step_three() {
	$r = $_SESSION['post_vars'];
	switch($r['db']) {
		case 'sqlite': {
			/* If we successfully create that file */
			if ($db = sqlite_open($r['directory'] .'/'. $r['database'], 0666, $sqlite_error)) { 
				foreach(explode(";", file_get_contents('forum.sqlite')) as $key=>$table) {
					if($table != '')
						sqlite_query($db, $table);
				}
				sqlite_close($db);
			} else {
				exit($sqlite_error);
			}
			break;
		}
		case 'mysql': {
			/* Create the MySQL database */
			$link = mysql_connect($r['db_host'], $r['db_user'], $r['db_pass']);
			if (!$link) {
				exit('Could not connect: ' . mysql_error());
			}
			if (!mysql_select_db($r['db_name'], $link)) {
				exit('Could not connect to the database: '. $r['db_name']);
			} else {
				foreach(explode(";", file_get_contents('forum.mysql')) as $key=>$table) {
					if($table != '')
						mysql_query($table, $link);
				}
			}
			break;
		}
		case 'pgsql': {
			/* Create the PgSQL database */
			$db = @pg_pconnect("host=". $r['db_host'] ." dbname=". $r['db_name'] ." user=". $r['db_user'] ." password=". $r['db_pass'] );
			if (!$db) {
				exit('Could not connect to the database: '. $r['db_name']);
			} else {
				foreach(explode(";", file_get_contents('forum.pgsql')) as $key=>$table) {
					if($table != '')
						pg_query($db, $table);
				}
			}
			break;
		}
	}
	/* Refresh the page */
	echo '<div align="center"><br /><br />Successfully Created and Populated the SQLite/MySQL/PostgreSQL database.</div>';
	echo '<h2>Step 3 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
	echo '<meta http-equiv="refresh" content="3; url=install.php?step=4">';
}

/*
---------------------------------------------------
			QUERY METHODS : USED IN FOLLOWING STEPS
---------------------------------------------------
*/

function Connect() {
	$r = $_SESSION['post_vars'];
	switch($r['db']) {
		case 'sqlite': {
			if (!($link = sqlite_open($r['directory'] .'/'. $r['database'], 0666, $sqlite_error)))
				exit($sqlite_error);
			break;
		}
		case 'mysql': {
			if(!($link = mysql_connect($r['db_host'], $r['db_user'], $r['db_pass'])))
				exit('Could not connect: ' . mysql_error());
			if(!mysql_select_db($r['db_name'], $link))
				exit('Could not connect to the database: '. $r['db_name']);
			break;
		}
		case 'pgsql': {
			if(!($link = @pg_pconnect("host=". $r['db_host'] ." dbname=". $r['db_name'] ." user=". $r['db_user'] ." password=". $r['db_pass'] )))
				exit('Could not connect to the database: '. $r['db_name']);
			break;
		}
	}

	return new DB($link);
}
class DB {
	protected $link;
	protected $r;
	public function __construct($db_handle) {
		$this->r = $_SESSION['post_vars'];
		$this->link = $db_handle;
	}
	public function Query($query) {
		if($this->r['db'] == 'sqlite') {
			return sqlite_query($this->link, $query);
		} else if($this->r['db'] == 'mysql') {
			return mysql_query($query, $this->link);
		} else if($this->r['db'] == 'pgsql') {
			return pg_query($this->link, $query);
		}
	}
	public function Quote($str) {
		if($this->r['db'] == 'sqlite') {
			return sqlite_escape_string($str);
		} else if($this->r['db'] == 'mysql') {
			return mysql_real_escape_string($str);
		} else if($this->r['db'] == 'pgsql') {
			return pg_escape_string($str);
		}
	}
}


function AddClass($name, $properties, $style_id, $description, $db_handle) {
	return $db_handle->Query("INSERT INTO ". CSS ." (name, properties, style_id, description) VALUES ('{$name}', '{$properties}', '{$style_id}', '{$description}')")
		or exit("Could not execute the following query: <br /><br />INSERT INTO ". CSS ." (name, properties, style_id, description) VALUES ('{$name}', '{$properties}', '{$style_id}', '{$description}')");
}

function AddFaqCat($name, $db_handle) {
	$name = $db_handle->Quote(htmlspecialchars($name));
	$db_handle->Query("INSERT INTO ". FAQ_CATEGORIES ." (name) VALUES ('{$name}')")
		or exit("Could not execute the following query: <br /><br />INSERT INTO ". FAQ_CATEGORIES ." (name) VALUES ('{$name}')");
}
function AddFaq($category, $question, $answer, $db_handle) {
	$question	= $db_handle->Quote($question);
	$parser		= new BBParser($answer, FALSE, FALSE, TRUE, array('allowbbcode' => 1, 'allowsmilies' => 1));
	$answer		= $db_handle->Quote($parser->Execute());
	$db_handle->Query("INSERT INTO ". FAQ ." (parent_id, question, answer) VALUES ($category, '{$question}', '{$answer}')")
		or exit("Could not execute the following query: <br /><br />INSERT INTO ". FAQ ." (parent_id, question, answer) VALUES ($category, '{$question}', '{$answer}')");
}

/*
---------------------------------------------------
			STEP FOUR : ADD POST ICONS AND EMOTICONS
---------------------------------------------------
*/

function step_four() {
	$r = $_SESSION['post_vars'];
	
	$db = Connect();

	$posticons = array();
	$posticons[] = "INSERT INTO ". POSTICONS ." (description, image) VALUES('Post', 'icon1.gif');";
	$posticons[] = "INSERT INTO ". POSTICONS ." (description, image) VALUES('Arrow', 'icon2.gif');";
	$posticons[] = "INSERT INTO ". POSTICONS ." (description, image) VALUES('Lighbulb', 'icon3.gif');";
	$posticons[] = "INSERT INTO ". POSTICONS ." (description, image) VALUES('Exclamation', 'icon4.gif');";
	$posticons[] = "INSERT INTO ". POSTICONS ." (description, image) VALUES('Question', 'icon5.gif');";
	$posticons[] = "INSERT INTO ". POSTICONS ." (description, image) VALUES('Cool', 'icon6.gif');";
	$posticons[] = "INSERT INTO ". POSTICONS ." (description, image) VALUES('Smile', 'icon7.gif');";
	$posticons[] = "INSERT INTO ". POSTICONS ." (description, image) VALUES('Angry', 'icon8.gif');";
	$posticons[] = "INSERT INTO ". POSTICONS ." (description, image) VALUES('Unhappy', 'icon9.gif');";
	$posticons[] = "INSERT INTO ". POSTICONS ." (description, image) VALUES('Talking', 'icon10.gif');";
	$posticons[] = "INSERT INTO ". POSTICONS ." (description, image) VALUES('Red Face', 'icon11.gif');";
	$posticons[] = "INSERT INTO ". POSTICONS ." (description, image) VALUES('Wink', 'icon12.gif');";
	$posticons[] = "INSERT INTO ". POSTICONS ." (description, image) VALUES('Thumbs Down', 'icon13.gif');";
	$posticons[] = "INSERT INTO ". POSTICONS ." (description, image) VALUES('Thumbs Up', 'icon14.gif');";
	foreach($posticons as $key=>$query) {
		$db->Query($query);
	}

	$emoticons = array();
	$emoticons[] = "INSERT INTO ". EMOTICONS ." (description, typed, image, clickable) VALUES('big grin', ':D', 'icon_biggrin.gif', '1');";
	$emoticons[] = "INSERT INTO ". EMOTICONS ." (description, typed, image, clickable) VALUES('confused', ':confused:', 'icon_confused.gif', '1');";
	$emoticons[] = "INSERT INTO ". EMOTICONS ." (description, typed, image, clickable) VALUES('cool', ':cool:', 'icon_cool.gif', '1');";
	$emoticons[] = "INSERT INTO ". EMOTICONS ." (description, typed, image, clickable) VALUES('eek!', ':eek:', 'icon_eek.gif', '1');";
	$emoticons[] = "INSERT INTO ". EMOTICONS ." (description, typed, image, clickable) VALUES('frown', ':(', 'icon_frown.gif', '1');";
	$emoticons[] = "INSERT INTO ". EMOTICONS ." (description, typed, image, clickable) VALUES('mad', ':mad:', 'icon_mad.gif', '1');";
	$emoticons[] = "INSERT INTO ". EMOTICONS ." (description, typed, image, clickable) VALUES('embarrasment', ':o', 'icon_redface.gif', '1');";
	$emoticons[] = "INSERT INTO ". EMOTICONS ." (description, typed, image, clickable) VALUES('roll eyes (sarcastic)', ':rolleyes:', 'icon_rolleyes.gif', '1');";
	$emoticons[] = "INSERT INTO ". EMOTICONS ." (description, typed, image, clickable) VALUES('smile', ':)', 'icon_smile.gif', '1');";
	$emoticons[] = "INSERT INTO ". EMOTICONS ." (description, typed, image, clickable) VALUES('stick out tongue', ':p', 'icon_razz.gif', '1');";
	$emoticons[] = "INSERT INTO ". EMOTICONS ." (description, typed, image, clickable) VALUES('wink', ';)', 'icon_wink.gif', '1');";
	$emoticons[] = "INSERT INTO ". EMOTICONS ." (description, typed, image, clickable) VALUES('twisted', ':twisted:', 'icon_twisted.gif', '1');";
	foreach($emoticons as $key=>$query) {
		$db->Query($query);
	}
	
	/* Refresh the page */
	echo '<div align="center"><br /><br />Successfully added the Post Icon and Emoticon Image sets to the database.</div>';
	echo '<h2>Step 4 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
	echo '<meta http-equiv="refresh" content="3; url=install.php?step=5">';
}

/*
---------------------------------------------------
			STEP FIVE : ADD STYLE SETS TO THE DB
---------------------------------------------------
*/

function step_five() {
	$r = $_SESSION['post_vars'];
	
	$db = Connect();

	$db->Query("INSERT INTO ". STYLES ." (name, description) VALUES ('Descent', 'Default theme for the k4 Bulletin Board.')");
	
	/* Include a file which adds all of the styles to the db (The reason for them being in their own file is that they would take up too much space) */
	include('styles.php');

	/* Refresh the page */
	//echo '<div align="center"><br /><br />Successfully Added the <strong>Descent</strong> and <strong>Dark Mountain</strong> Style Sets to the database.</div>';
	echo '<div align="center"><br /><br />Successfully Added the <strong>Descent</strong> Style Set to the database.</div>';
	echo '<h2>Step 5 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
	echo '<meta http-equiv="refresh" content="3; url=install.php?step=6">';
}

/*
---------------------------------------------------
			STEP SIX : ADD THE FAQ TO THE DB
---------------------------------------------------
*/

function step_six() {
	$r = $_SESSION['post_vars'];
	
	$db = Connect();

	/* Include a file which adds all of the FAQ items to the db (The reason for them being in their own file is that they would take up too much space) */
	include('faq.php');

	/* Refresh the page */
	echo '<div align="center"><br /><br />Successfully Added the FAQ items to the database.</div>';
	echo '<h2>Step 6 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
	echo '<meta http-equiv="refresh" content="3; url=install.php?step=7">';
}

/*
---------------------------------------------------
			STEP SEVEN : ADD THE ADMINISTRATOR AND THE USERGROUPS TO THE DB
---------------------------------------------------
*/

function step_seven() {
	$r = $_SESSION['post_vars'];
	
	$db = Connect();
	
	$user = $r['admin_username'];
	$email = $r['admin_email'];
	$pass = md5($r['admin_password']);
	$created = time();
	
	/* Add the Admin User */
	$db->Query("INSERT INTO ". USERS ." (name, pass, email, created, perms) VALUES ('{$user}', '{$pass}', '{$email}', '{$created}', '". ADMIN ."')");
	
	/* Add the user groups */
	$db->Query("INSERT INTO ". GROUPS ." (name, description, mod_name, mod_id, permissions, row_type, created) VALUES ('Admins', 'The people who are running this show.', '". $r['admin_username'] ."', '1', '". ADMIN ."', '1', '{$created}')");
	$db->Query("INSERT INTO ". USER_IN_GROUP ." (group_id, is_mod, id, name) VALUES ('1', '1', '1', '". $r['admin_username'] ."')");

	$db->Query("INSERT INTO ". GROUPS ." (name, description, mod_name, mod_id, permissions, row_type, created) VALUES ('Moderators', 'The people who keep the forums in check.', '". $r['admin_username'] ."', '1', '". MOD ."', '0', '{$created}')");
	$db->Query("INSERT INTO ". USER_IN_GROUP ." (group_id, is_mod, id, name) VALUES ('2', '1', '1', '". $r['admin_username'] ."')");
	
	$db->Query("INSERT INTO ". GROUPS ." (name, description, mod_name, mod_id, permissions, row_type, created) VALUES ('Members', 'Everyone.', '". $r['admin_username'] ."', '1', '". ALL ."', '1', '{$created}')");
	$db->Query("INSERT INTO ". USER_IN_GROUP ." (group_id, is_mod, id, name) VALUES ('3', '1', '1', '". $r['admin_username'] ."')");

	/* Add the Private Message folders */
	$db->Query("INSERT INTO ". PMSG_FOLDERS ." (name, admin_defined, is_inbox) VALUES ('Inbox', 1, 1)");
	$db->Query("INSERT INTO ". PMSG_FOLDERS ." (name, admin_defined, is_inbox) VALUES ('Sent Items', 1, 0)");
	$db->Query("INSERT INTO ". PMSG_FOLDERS ." (name, admin_defined, is_inbox) VALUES ('Saved Items', 1, 0)");

	/* Add Names to the Badnames table */
	$db->Query("INSERT INTO ". BADNAMES ." (name) VALUES ('administrator')");
	$db->Query("INSERT INTO ". BADNAMES ." (name) VALUES ('admin')");
	$db->Query("INSERT INTO ". BADNAMES ." (name) VALUES ('member')");
	$db->Query("INSERT INTO ". BADNAMES ." (name) VALUES ('guest')");
	$db->Query("INSERT INTO ". BADNAMES ." (name) VALUES ('moderator')");
	$db->Query("INSERT INTO ". BADNAMES ." (name) VALUES ('user')");

	/* Refresh the page */
	echo '<div align="center"><br /><br />Successfully Added the Administrator and the usergroups to the database.</div>';
	echo '<h2>Step 7 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
	echo '<meta http-equiv="refresh" content="3; url=install.php?step=7.25">';
}

function step_seven_twofive() {
	$db = Connect();
	//$db->Query("DELETE FROM ". SETTING_GROUP);
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (1,'Turn k4 on and off',1)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (2,'General Settings',2)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (3,'Contact Details',3)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (4,'Posting Code allowances (BB code, etc)',4)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (5,'Forums Home Page Options',5)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (6,'User and registration options',6)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (7,'Member List options',7)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (8,'Thread display options',8)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (9,'Forum Display Options',9)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (10,'Search Options',10)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (11,'Email Options',11)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (14,'Edit Options',12)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (18,'Language Options',15)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (19,'Private Messaging Options',16)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (25,'Polls',17)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (26,'Avatars',18)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (27,'Attachments',19)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (29,'Calendar',20)");
	$db->Query("INSERT INTO ". SETTING_GROUP ." VALUES (31,'". $db->Quote("Who's Online") ."', 22)");
	/* Refresh the page */
	echo '<div align="center"><br /><br />Successfully Inserted the Setting Groups.</div>';
	echo '<h2>Step 7.25 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
	echo '<meta http-equiv="refresh" content="3; url=install.php?step=7.5">';
}
function step_seven_five() {
	$db = Connect();
	$r = $_SESSION['post_vars'];
	$name = htmlspecialchars($r['forum_name']);
	$description = htmlspecialchars($r['forum_description']);
	
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,1,'Bulletin Board Active','bbactive','1','From time to time, you may want to turn your bulletin board off to the public while you perform maintenance, update versions, etc. When you turn your BB off, visitors will receive a message that states that the bulletin board is temporarily unavailable. <b>Administrators will still be able to see the board</b></p>\r\n<p>Use this as a master switch for your board. You can set options for individual user groups in the User Permissions area.','yesno',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,1,'Reason for turning board off','bbclosedreason','Yahtzee! The board has temporarily been closed, the Adminstrator probably has a good reason.','The text that is presented when the BB is closed.</p>\r\n<p>Note: you, as an administrator, will be able to see the forums as usual, even when you have turned them off to the public.','textarea',2)");
	
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,2,'Board Title','bbtitle','".$name."','Title of board. Appears in the title of every page','',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,2,'Board Description','bbdescription','".$description."','Short description of your forum, ie: k4 Bulletin Board.','',2)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,2,'Search Engine Description','bbsearchdescription','".$name." - Powered by k4 Bulletin Board','Brief description of your forum for search engines, around 10-30 words.','textarea',3)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,2,'Search Engine Keywords','bbkeywords','k4, bb, bulletin, board, bulletin board, forum, k4st, forums, message, board, message board','Comma separated keywords that search engines look for, ie: forum, forums, k4','textarea',4)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,2,'Copyright Text','copyrighttext','','Copyright text to insert the footer of the page.','',5)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,2,'Default Style Set','styleset','Descent','Default style set for the k4 Bulletin Board. (just write the name of it, if you do not know the name, check out the <a href=\"admin.php?act=css\"><strong>CSS</strong></a> section.','',6)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,2,'Default Image Set','imageset','Descent','Default image set for the k4 Bulletin Board. (just write the name of it, if youdo not know the name, look in the /Images/ directory, and the folders listed there will be the Image sets.','',7)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,2,'Default Template Set','templateset','Descent','Default template set for the k4 Bulletin Board. (just write the name of it, if you do not know the name, check out the templates folder, and in there should be a list of the template sets that you have.','',8)");

	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,3,'Contact Us Link','contactuslink','mailto:".$r['contact_email']."','Link for contacting the site. Can just be mailto:webmaster@whereever.com or your own form. Appears at the bottom of every page.','',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,3,'". $db->Quote("Webmaster's email") ."','webmasteremail','".$r['webmaster_email']."','Email address of the webmaster.','',2)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,3,'Company Name','companyname','','The name of your company. Required for COPPA.','',3)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,3,'Address','address','','Address of your company. This is required for COPPA forms to be posted to.','',4)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,3,'Fax Number','faxnumber','','Enter the fax number for your company here. COPPA forms will be faxed to it.\r\n</p>\r\n<p>You may wish to check out <a href=\"http://www.efax.com/\" target=_blank>http://www.efax.com/</a>','',5)");
	
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,4,'Allow BB IMG code in signatures?','allowbbimagecode','0','','yesno',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,4,'Allow BB code in signatures?','allowbbcode','1','','yesno',2)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,4,'Allow smilies in signatures?','allowsmilies','1','','yesno',3)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,4,'Maximum images per post/signature','maximages','10','Maximum number of images to allow in posts / signatures. Set this to 0 to have no effect.','',4)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,4,'Clickable Smilies per Row','smcolumns','3','When a user has enabled the clickable k4 code/smilies how many smilies do you want to show per row?','',5)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,4,'Clickable Smilies Total','smtotal','15','When a user has enabled the clickable k4 code/smilies how many smilies do you want to display on the screen before the user is prompted to click for more.','',6)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,4,'Allow Dynamic URL for [img] tags?','allowdynimg','0','". $db->Quote("If this is set to 'no', the [img] tag will not be displayed if the path to the image contains dynamic characters such as ? and &. This can prevent malicious use of the tag.") ."','yesno',7)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,4,'Allow BB code Buttons & Clickable Smilies?','allowcodebuttons','1','This global switch allows you to completely disable BB code buttons and clickable smilies.','yesno',8)");
	
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,5,'Show Sub Forums forums?','showsubforums','1','Display sub-forums of a forum withing the forum row?','yesno',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,5,'Show Locked forums?','showlocks','0','Do you wish to have the new post indicators shown on the index page (forum_on.gif and forum_off.gif) be shown with locks to guests & other members who have no access to post?','yesno',2)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,5,'Hide private forums','hideprivateforums','1','". $db->Quote("Select 'yes' here to hide private forums from users who are not allowed to access them. Users who do have permission to access them will have to log in before they can see these forums too. This option applies to the forum home page, and the Jump To... box.") ."','yesno',3)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,5,'Display logged in users on home page','displayloggedin','1','Display logged in and active members on the home page? This option displays those users that have been active in the last {your cookie timeout} seconds on the home page.','yesno',4)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,5,'Show forum descriptions on the homepage.','showforumdescription','1','','yesno',5)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,5,'". $db->Quote("Show today's birthdays on the homepage?") ."','showbirthdays','1','','yesno',6)");
	
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,6,'Display email addresses','displayemails','0','Allow public viewing of email addresses.','yesno',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,6,'Use email form?','secureemail','1','If \"display email addresses\" is set to yes, then how should the email address be displayed? If this is set to yes, then an online form must be filled in to send a user an email, thus hiding the destination email address. If secureemail is set to no, then the user is just given the email address.','yesno',2)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,6,'Allow signatures','allowsignatures','1','". $db->Quote("Allow registered users to have signatures. Don't forget to update these templates: newtopic newreply editpost modifyprofile register") ."','yesno',3)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,6,'Notify about new members','newuseremail','','Email address to receive email when a new user signs up. Leave blank for no email.','',4)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,6,'Require unique email addresses','requireuniqueemail','1','The default option is to require unique email addresses for each registered user. This means that no two users can have the same email address. You can disable this requirement by checking the \"Unique Email Not Required\" box.','yesno',5)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,6,'Allow new user registrations','allowregistration','0','If you would like to temporarily (or permanently) prevent anyone new from registering, you can do so. The REGISTER button will still be seen throughout the BB, but anyone attempting to register will be told that you are not accepting new registrations at this time.','yesno',6)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,6,'Verify Email address in registration','verifyemail','1','The password is emailed to the new member after they submit their registration to confirm their identity and email address. If account is not activated, they will remain in the \"users awaiting activation\" usergroup.','yesno',7)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,6,'Moderate New Members','moderatenewmembers','0','Allows you to validate new members before they are classified as registered members and are allowed to post.','yesno',8)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,6,'Allow Users To Change Styles','allowchangestyles','1','This allows users to set their preferred style set on registration or when editing their option. Setting this to \"no\" disables that option and will force them to use whatever style has been specified.','yesno',9)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,6,'Enable Access Masks?','enableaccess','0','". $db->Quote("Access masks are a simple way to manage forum permissions, however they add additional queries on most pages. If you don't use it, it is recommended that you disable it.") ."','yesno',10)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,6,'Minimum Username Length','minuserlength','3','Enter the minimum length a user can register with.','',11)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,6,'Maximum Username Length','maxuserlength','15','Enter the maximum length a user can register with.','',12)");
	
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,7,'Enable members list','enablememberlist','1','Enable the member list addon? This allows users to view a list of registered users and (optionally) search through it. ','yesno',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,7,'Allow advanced searches','usememberlistadvsearch','1','Allow the use of the advanced search tool for the member list.','yesno',2)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,7,'Members per page','memberlistperpage','30','The number of records per page that will be shown by default in the members list.','',3)");
	
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,8,'Maximum number of posts to display on a thread page before splitting over multiple pages','maxposts','15','','',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,8,'Maximum Characters per post','postmaxchars','10000','The maximum number of characters that you want to allow per post. Set this to 0 to disable it.','',2)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,8,'". $db->Quote("Stop 'Shouting' in titles") ."','stopshouting','1','". $db->Quote("Prevent your users 'shouting' in their thread titles by changing all-uppercase titles to capitalization only on the first letters of some words. Disable this for some international boards with different character sets, as this may cause problems.") ."','yesno',3)");
	
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,9,'Threads per Page','maxthreads','25','The number of threads to display on a forum page before splitting it over multiple pages.','',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,9,'Number of views to qualify as a hot thread','hotnumberviews','150','','',2)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,9,'Number of posts to qualify as a hot thread','hotnumberposts','15','','',3)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,9,'Link to individual pages of a multipage thread on the forum listing?','linktopages','1','','yesno',4)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,9,'Prefix for moved threads','movedthreadprefix','Moved: ','The text with which to prefix a thread that has been moved to another forum. (You may use HTML)','',5)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,9,'Prefix for Announcement threads','announcementthreadprefix','Announcement: ','Prefix to append to the beginning of thread titles that have been set to \"Announcement\". These threads always appear at the top of the thread list. (You may use HTML)','',6)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,9,'Prefix for Sticky threads','stickythreadprefix','Sticky: ','Prefix to append to the beginning of thread titles that have been set to \"Sticky\". These threads always appear at the top of the thread list. (You may use HTML)','',7)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,9,'Prefix for Polls','pollthreadprefix','Poll: ','Prefix to append to the beginning of Poll thread titles. (You may use HTML)','',8)");

	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,10,'Enable searches?','enablesearches','1','Allow searching for posts within the BB. This is quite a server intensive process so you may want to disable it.</p>\r\n<p>To disable searching of all forums, delete the option from the searchintro template. ','yesno',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,10,'Number of posts per page','searchperpage','25','Number of successful search items to display per page.','',2)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,10,'Minimum Word Length','minsearchlength','4','Enter the minimum word length that the search engine is to index.  The smaller this number is, the larger your search index, and conversely your database is going to be.','',3)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,10,'Maximum Word Length','maxsearchlength','20','Enter the maximum word length that the search engine is to index.  The larger this number is, the larger your search index, and conversely your database is going to be.','',4)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,10,'Allow Wild Cards?','allowwildcards','1','Allow users to use a star (*) in searches to match partial words?','yesno',5)");

	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,11,'Enable Email features?','enableemail','1','". $db->Quote("Enable email sending features: send to friend and email notification for posters and moderators. Don't forget to remove those links from the <i>forumdisplay, newreply, newtopic</i>, &amp; <i>editpost</i> templates<br><br>You can turn off the 'Send to Friend' feature for invidual user groups in the <strong>User Permissions area</strong>.") ."','yesno',1)");
	//$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,12,'Time Zone Offset','timeoffset','".intval(date("H",12*60*60)-gmdate("H",12*60*60))."','Time (in hours) that the server is offset from GMT. Please select the most appropriate option.','<select name=\\\\\"setting[\$setting[settingid]]\\\\\">\r\n<option value=\\\\\"-12\\\\\" \".iif(\$setting[value]==-12,\"selected\",\"\").\">(GMT -12:00 hours) Eniwetok, Kwajalein</option>\r\n<option value=\\\\\"-11\\\\\" \".iif(\$setting[value]==-11,\"selected\",\"\").\">(GMT -11:00 hours) Midway Island, Samoa</option>\r\n<option value=\\\\\"-10\\\\\" \".iif(\$setting[value]==-10,\"selected\",\"\").\">(GMT -10:00 hours) Hawaii</option>\r\n<option value=\\\\\"-9\\\\\" \".iif(\$setting[value]==-9,\"selected\",\"\").\">(GMT -9:00 hours) Alaska</option>\r\n<option value=\\\\\"-8\\\\\" \".iif(\$setting[value]==-8,\"selected\",\"\").\">(GMT -8:00 hours) Pacific Time (US & Canada)</option>\r\n<option value=\\\\\"-7\\\\\" \".iif(\$setting[value]==-7,\"selected\",\"\").\">(GMT -7:00 hours) Mountain Time (US & Canada)</option>\r\n<option value=\\\\\"-6\\\\\" \".iif(\$setting[value]==-6,\"selected\",\"\").\">(GMT -6:00 hours) Central Time (US & Canada), Mexico City</option>\r\n<option value=\\\\\"-5\\\\\" \".iif(\$setting[value]==-5,\"selected\",\"\").\">(GMT -5:00 hours) Eastern Time (US & Canada), Bogota, Lima, Quito</option>\r\n<option value=\\\\\"-4\\\\\" \".iif(\$setting[value]==-4,\"selected\",\"\").\">(GMT -4:00 hours) Atlantic Time (Canada), Caracas, La Paz</option>\r\n<option value=\\\\\"-3.5\\\\\" \".iif(\$setting[value]==-3.5,\"selected\",\"\").\">(GMT -3:30 hours) Newfoundland</option>\r\n<option value=\\\\\"-3\\\\\" \".iif(\$setting[value]==-3,\"selected\",\"\").\">(GMT -3:00 hours) Brazil, Buenos Aires, Georgetown</option>\r\n<option value=\\\\\"-2\\\\\" \".iif(\$setting[value]==-2,\"selected\",\"\").\">(GMT -2:00 hours) Mid-Atlantic</option>\r\n<option value=\\\\\"-1\\\\\" \".iif(\$setting[value]==-1,\"selected\",\"\").\">(GMT -1:00 hours) Azores, Cape Verde Islands</option>\r\n<option value=\\\\\"0\\\\\" \".iif(\$setting[value]==0,\"selected\",\"\").\">(GMT) Western Europe Time, London, Lisbon, Casablanca, Monrovia</option>\r\n<option value=\\\\\"+1\\\\\" \".iif(\$setting[value]==+1,\"selected\",\"\").\">(GMT +1:00 hours) CET(Central Europe Time), Angola, Libya</option>\r\n<option value=\\\\\"+2\\\\\" \".iif(\$setting[value]==+2,\"selected\",\"\").\">(GMT +2:00 hours) EET(Eastern Europe Time), Kaliningrad, South Africa</option>\r\n<option value=\\\\\"+3\\\\\" \".iif(\$setting[value]==+3,\"selected\",\"\").\">(GMT +3:00 hours) Baghdad, Kuwait, Riyadh, Moscow, St. Petersburg, Volgograd, Nairobi</option>\r\n<option value=\\\\\"+3.5\\\\\" \".iif(\$setting[value]==+3.5,\"selected\",\"\").\">(GMT +3:30 hours) Tehran</option>\r\n<option value=\\\\\"+4\\\\\" \".iif(\$setting[value]==+4,\"selected\",\"\").\">(GMT +4:00 hours) Abu Dhabi, Muscat, Baku, Tbilisi</option>\r\n<option value=\\\\\"+4.5\\\\\" \".iif(\$setting[value]==+4.5,\"selected\",\"\").\">(GMT +4:30 hours) Kabul</option>\r\n<option value=\\\\\"+5\\\\\" \".iif(\$setting[value]==+5,\"selected\",\"\").\">(GMT +5:00 hours) Ekaterinburg, Islamabad, Karachi, Tashkent</option>\r\n<option value=\\\\\"+5.5\\\\\" \".iif(\$setting[value]==+5.5,\"selected\",\"\").\">(GMT +5:30 hours) Bombay, Calcutta, Madras, New Delhi</option>\r\n<option value=\\\\\"+6\\\\\" \".iif(\$setting[value]==+6,\"selected\",\"\").\">(GMT +6:00 hours) Almaty, Dhaka, Colombo</option>\r\n<option value=\\\\\"+7\\\\\" \".iif(\$setting[value]==+7,\"selected\",\"\").\">(GMT +7:00 hours) Bangkok, Hanoi, Jakarta</option>\r\n<option value=\\\\\"+8\\\\\" \".iif(\$setting[value]==+8,\"selected\",\"\").\">(GMT +8:00 hours) Beijing, Perth, Singapore, Hong Kong, Chongqing, Urumqi, Taipei</option>\r\n<option value=\\\\\"+9\\\\\" \".iif(\$setting[value]==+9,\"selected\",\"\").\">(GMT +9:00 hours) Tokyo, Seoul, Osaka, Sapporo, Yakutsk</option>\r\n<option value=\\\\\"+9.5\\\\\" \".iif(\$setting[value]==+9.5,\"selected\",\"\").\">(GMT +9:30 hours) Adelaide, Darwin</option>\r\n<option value=\\\\\"+10\\\\\" \".iif(\$setting[value]==+10,\"selected\",\"\").\">(GMT +10:00 hours) EAST(East Australian Standard), Guam, Papua New Guinea, Vladivostok</option>\r\n<option value=\\\\\"+11\\\\\" \".iif(\$setting[value]==+11,\"selected\",\"\").\">(GMT +11:00 hours) Magadan, Solomon Islands, New Caledonia</option>\r\n<option value=\\\\\"+12\\\\\" \".iif(\$setting[value]==+12,\"selected\",\"\").\">(GMT +12:00 hours) Auckland, Wellington, Fiji, Kamchatka, Marshall Island</option>\r\n</select>',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,14,'". $db->Quote("Show the 'Edited by xxx on yyy' when a post is edited?") ."','showeditedby','1','','yesno',1)");

	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,18,'". $db->Quote("Text for 'on'") ."','ontext','ON','Text that means on. This is used to keep the code language independent. It is used with the BB code / HTML code On / Off settings for postings.','',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,18,'". $db->Quote("Text for 'off'") ."','offtext','OFF','Text that means off. This is used to keep the code language independent. It is used with the BB code / HTML code On / Off settings for postings.','',2)");
	
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,19,'Enable Private Messaging?','enablepms','1','Enabling this will add some performance overhead on the main index and it may not be a feature you wish to have at all.','yesno',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,19,'Allow IMG code in private messages?','privallowbbimagecode','1','','yesno',2)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,19,'Allow BB code in private messages?','privallowbbcode','1','','yesno',3)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,19,'Allow smilies in private messages?','privallowsmilies','1','','yesno',4)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,19,'Allow message icons','privallowicons','1','Allow the use of the standard message icons for private messages.','yesno',6)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,19,'Maximum saved messages','pmquota','70','Maximum number of saved messages a user can have. 0 means unlimited','',7)");
	//$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,19,'Inbox name','inboxname','Inbox','The name of the inbox folder.','',8)");
	//$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,19,'Sent Items Name','sentitemsname','Sent Items','The name of the Sent Items folder.','',9)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,19,'IM Support - Check for new PMs','checknewpm','1','Selecting yes for this option will cause the system to check the PM database every time a user loads a page, and will display a visible prompt for it.','yesno',10)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,19,'Maximum characters per private message','pmmaxchars','1000','Maximum characters to allow in a private message. Set this to 0 for no limit.','',12)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,19,'Word to denote cancelled message','pmcancelledword','Cancelled:','". $db->Quote("This is the word that will prefix the title of 'cancelled' messages in the <i>message tracking</i> section. (You may use HTML)") ."','','13')");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,19,'". $db->Quote("Delete 'cancelled' messages?") ."','pmcancelkill',0,'". $db->Quote("When users 'cancel' messages in the message tracking area, would you like to remove the message completely? WARNING: Selecting 'yes' could confuse users who have been notified by email about the message.") ."','yesno',17)");

	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,25,'Maximum Options','maxpolloptions','10','Maximum number of options a user can select for the poll. Set this to 0 to allow infinitely many options.','',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,25,'Update last post time','updatelastpost','0','Update the last post time for the thread (thus returning it to the top of a forum) when a vote is placed.','yesno',2)");
	
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,26,'Enable Avatars','avatarenabled','1','Use this option to enable/disable the overall use of avatars.<br><br>Avatars are small images displayed under usernames in thread display and user info pages.','yesno',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,26,'Allow uploads','avatarallowupload','1','Allow user to upload their own custom avatar if they have enough posts?','yesno',3)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,26,'Allow website uploads','avatarallowwebsite','1','Allow user to upload their own custom avatar from another website if they have enough posts?','yesno',4)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,26,'Maximum Dimensions','avatarmaxdimension','50','Maximum width and height (in pixels) that the custom avatar image can be.','',5)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,26,'Maximum File Size','avatarmaxsize','20000','The maximum file size (in bytes) that an avatar can be.\r\n\r\n1 KB = 1024 bytes\r\n1 MB = 1048576 bytes','',6)");

	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,27,'Maximum File Size','maxattachsize','102400','Specify the maximum size in bytes that an upload may be. Set this value to 0 to enable any sized uploads.\n\n1 KB = 1024 bytes\n1 MB = 1048576 bytes','',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,27,'Valid Extensions','attachextensions','gif jpg png txt zip bmp jpeg','Valid extensions that uploads may have. Separate each item with a space.\n\nFor each extension, you should have a file in the /images/attach/ folder called xxx.gif where xxx is the extension of the file. This allows you to have an icon for each file type.','',2)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,27,'View Images','viewattachedimages','0','Do you wish to display attached images in the threads? Select no to just generate a link to download the image.','yesno',3)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,27,'Maximum Image Width','maxattachwidth','0','Set this to the maximum width attached images (jpg and gif) may have. Set it to 0 to not limit the width.','',4)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,27,'Maximum Image Height','maxattachheight','0','Set this to the maximum height attached images (jpg and gif) may have. Set it to 0 to not limit the height.','',5)");
	
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,29,'Enable Calendar','calendarenabled','1','Disable/enable the calendar','yesno',1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,29,'Birthdays','calbirthday','1','Use birthdays?','yesno',2)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,29,'Individual Birthdays','calshowbirthdays','1','Show the individual birthdays for each user on the calendar? Set this to NO to just show a link if a particular day has birthdays on it.','yesno',3)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,29,'bbimagecode','calbbimagecode','1','Allow [IMG] code to be used in calendar events?','yesno',5)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,29,'smilies','calallowsmilies','1','Allow smilies to be used in calendar events?','yesno',6)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,29,'bbcode','calallowbbcode','1','Allow BB code to be used in calendar events?','yesno',7)");

	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,31, '". $db->Quote("Enable Who's Online") ."', 'WOLenable', '1', '". $db->Quote("Selecting NO will disable Who's Online for everyone.") ."', 'yesno', 1)");
	$db->Query("INSERT INTO ". SETTING ." VALUES (NULL,31, 'Enable Guests', 'WOLguests', '1', '". $db->Quote("Display Guest activity on Who's Online?") ."', 'yesno', 2)");
	/* Refresh the page */
	echo '<div align="center"><br /><br />Successfully Inserted the Default Settings.</div>';
	echo '<h2>Step 7.5 Completed</h2><br /><div align="center"><span class="waittext">Please Wait...</span></div>';
	echo '<meta http-equiv="refresh" content="3; url=install.php?step=8">';
}

function step_five_seven() {
	$db = Connect();
}

/*
---------------------------------------------------
			STEP EIGHT : CREATE THE install.lock FILE AND FINISH THE INSTALL
---------------------------------------------------
*/

function step_eight() {
	$r = $_SESSION['post_vars'];

	$db = Connect();
	
	$name = htmlspecialchars($r['forum_name']);
	$description = htmlspecialchars($r['forum_description']);
	
	$db->Query("INSERT INTO ". FORUMS ." (name, description, row_left, row_right, styleset) VALUES ('{$name}', '{$description}', 1, 6, 1)");
	$db->Query("INSERT INTO ". FORUMS ." (name, description, row_left, row_right, styleset, row_level, f_order) VALUES ('Test Category', '', 2, 5, 1, 1, 1)");
	$db->Query("INSERT INTO ". FORUMS ." (name, description, row_left, row_right, styleset, row_level, f_order) VALUES ('Test Forum', 'If you can see this, your forum is working.', 3, 4, 1, 2, 1)");
	
	$new_url = null;
	/* Strip out the first ../ and the /config -> relative url returns the relative path from the install folder to that area */
	$url = explode('/', RelativeUrl($r['configurl']));
	for($i=1;$i<count($url)-1;$i++) {
		$new_url .= $url[$i].'/';
	}
	
	if(substr($new_url, (strlen($new_url)-7)) == '/config')
		$new_url = substr($new_url, 0, (strlen($new_url)-7));
	
	$text = "<?php\n";
	$text .= "/* k4 Bulletin Board - Installed on ". date("F j, Y, g:i a", time()) ." */\n";
	$text .= "define('PC_REL_URL', '". $new_url ."/pagecraft.php');\n";
	$text .= "?>";

	if($fp = fopen('../install.lock.php', 'w+')) {
		fwrite($fp, $text);
	}
	if(!isset($_SESSION['local_error']) || !$_SESSION['local_error'] || $_SESSION['local_error'] == FALSE) {
		@mail("peter.goodman@gmail.com, ". $r['admin_email'], "k4 Bulletin Board Installation", "k4 Bulletin Board has been installed at: ". $_SERVER['HTTP_HOST'] .".", "From: \"k4 Bulletin Board Mailer\" <". $r['webmaster_email'] .">");
	}
}

/*
---------------------------------------------------
			FINAL SWITCH STATEMENT
---------------------------------------------------
*/

$step = @$_GET['step'];

/* Store the POST Variables in a session for future use in the next steps */
$_SESSION['post_vars'] = $step == 1 ? $_POST : @$_SESSION['post_vars'];

if($step >= 1 && $step < 2)
	$one = new StepOne;

if($step >= 2 && $step < 3)
	$two = new StepTwo;

switch($step) {
	case '1': {		
		$one->step_one_one();
		break;
	}
		case '1.5': {
			$one->step_one_two();
			break;
		}
			case '1.75': {
				$one->step_one_three();
				break;
			}
	case '2.16': {
		$two->step_two_one();
		break;
	}
		case '2.32': {
			$two->step_two_two();
			break;
		}
			case '2.48': {
				$two->step_two_three();
				break;
			}
				case '2.64': {
					$two->step_two_four();
					break;
				}
					case '2.8': {
						$two->step_two_five();
						break;
					}
						case '2.96': {
							$two->step_two_six();
							break;
						}
							case '2.99': {
								$two->step_two_seven();
								break;
							}
	case '3': {
		step_three();
		break;
	}
	case '4': {
		step_four();
		break;
	}
	case '5': {
		step_five();
		break;
	}
	case '6': {
		step_six();
		break;
	}
	case '7': {
		step_seven();
		break;
	}
		case '7.25': { 
			step_seven_twofive();
			break;
		}
			case '7.5': {
				step_seven_five();
				break;
			}
	case '8': {
		step_eight();
		echo '<div align="center"><h2>Installation Complete!</h2><br /><br /><strong>Please Rename or Remove the Install Folder.</strong></div>';
		break;
	}
	default: {
		echo start_install();
		break;
	}
}

/*
---------------------------------------------------
			do the PAGE FOOTER
---------------------------------------------------
*/

?>
</div>
</div>
</body>
</html>
<?php

?>