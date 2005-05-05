<?php
/**
* k4 Bulletin Board, common.php
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
* @author Peter Goodman
* @author Geoffrey Goodman
* @author James Logsdon
* @version $Id: common.php,v 1.13 2005/05/05 21:35:48 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);
set_time_limit(120);

if(!defined('IN_K4')) {
	exit;
}

define('DEBUG_SQL', TRUE);


/**
 * Constants that define what a category/forum/thread/etc is
 * DO NOT CHANGE
 */

define('CATEGORY', 1);
define('FORUM', 2);
define('TOPIC', 4);
define('REPLY', 8);
define('GALLERY', 16);
define('IMAGE', 32);


/**
 * Constants that represent all of the tables
 * Don't change these if you don't know what you're doing
 */

define('SESSIONS',			'k4_sessions');
define('SETTING_GROUP',		'k4_settinggroup');
define('SETTINGS',			'k4_setting');
define('CSS',				'k4_css');
define('STYLES',			'k4_styles');
define('USERS',				'k4_users');
define('USERINFO',			'k4_userinfo');
define('INFO',				'k4_information');
define('CATEGORIES',		'k4_categories');
define('FORUMS',			'k4_forums');
define('TOPICS',			'k4_topics');
define('REPLIES',			'k4_replies');
define('MAPS',				'k4_maps');
define('DATASTORE',			'k4_datastore');
define('POSTICONS',			'k4_posticons');
define('EMOTICONS',			'k4_emoticons');
define('USERGROUPS',		'k4_usergroups');
define('POLLOPTIONS',		'k4_polloptions');
define('POLLVOTES',			'k4_pollvotes');


/**
 * User permission levels, DO NOT CHANGE
 */

define('UNDEFINED',			0);
define('GUEST',				1);
define('PENDING_MEMBER',	4);
define('MEMBER',			5);
define('SUPERMEMBER',		6);
define('MODERATOR',			7);
define('SUPERMOD',			8);
define('ADMIN',				9);
define('SUPERADMIN',		10);

/**
 * Some session information
 */
define('USER_AGENT', $_SERVER['HTTP_USER_AGENT']);
define('USER_IP', $_SERVER['REMOTE_ADDR']);


/**
 * Topic Types, DO NOT CHANGE
 */
define('TOPIC_NORMAL',		1);
define('TOPIC_STICKY',		2);
define('TOPIC_ANNOUNCE',	3);
define('TOPIC_GLOBAL',		4);

/**
 * The interval between cache reloads
 */
define('CACHE_INTERVAL',	86400);
define('CACHE_FILE',		FORUM_BASE_DIR .'/tmp/cache/cache.php');

/**
 * Query Parameters for things such as forums, categories, users, etc
 */
$query_params['info']		= "i.id AS id, i.parent_id AS parent_id, i.row_left AS row_left, i.row_right AS row_right, i.row_type AS row_type, i.row_order AS row_order, i.name AS name, i.created AS created, i.row_level as row_level";
$query_params['category']	= ", c.category_id AS category_id, c.description AS description, c.suspended AS suspended, c.archive AS archive, c.moderating_groups AS moderating_groups, c.moderating_users AS moderating_users";
$query_params['forum']		= ", f.forum_id AS forum_id, f.category_id AS category_id, f.description AS description, f.archive AS archive, f.subforums AS subforums, f.is_forum AS is_forum, f.is_tracker AS is_tracker, f.is_link AS is_link, f.link_redirects AS link_redirects, f.link_show_redirects AS link_show_redirects, f.link_href AS link_href, f.moderating_groups AS moderating_groups, f.moderating_users AS moderating_users, f.pass AS pass, f.topics AS topics, f.replies AS replies, f.posts AS posts, f.topic_created AS topic_created, f.topic_name AS topic_name, f.topic_uname AS topic_uname, f.topic_id AS topic_id, f.topic_uid AS topic_uid, f.post_created AS post_created, f.post_name AS post_name, f.post_uname AS post_uname, f.post_id AS post_id, f.post_uid AS post_uid, f.topicsperpage AS topicsperpage, f.postsperpage AS postsperpage, f.maxpolloptions AS maxpolloptions, f.defaultlang AS defaultlang, f.num_viewing AS num_viewing, f.forum_rules AS forum_rules, f.topic_posticon AS topic_posticon, f.post_posticon AS post_posticon, f.defaultstyle AS defaultstyle, f.prune_frequency AS prune_frequency, f.prune_post_age AS prune_post_age, f.prune_post_viewed_age AS prune_post_viewed_age, f.prune_old_polls AS prune_old_polls, f.prune_announcements AS prune_announcements, f.prune_stickies AS prune_stickies";
$query_params['user']		= "u.id AS id, u.ip as ip, u.name AS name, u.email AS email, u.pass AS pass, u.priv_key AS priv_key, u.created AS created, u.login AS login, u.seen AS seen, u.last_seen AS last_seen, u.perms AS perms, u.invisible AS invisible, u.usergroups AS usergroups";
$query_params['userinfo']	= ", ui.user_id AS user_id, ui.fullname AS fullname, ui.num_posts AS num_posts, ui.timezone AS timezone, ui.address AS address, ui.occupation AS occupation, ui.interests AS interests, ui.biography AS biography, ui.icq AS icq, ui.aim AS aim, ui.msn AS msn, ui.yahoo AS yahoo, ui.jabber AS jabber, ui.avatar AS avatar, ui.signature AS signature, ui.birthday AS birthday, ui.homepage AS homepage, ui.language AS language, ui.styleset AS styleset, ui.imgset AS imgset, ui.tplset AS tplset, ui.banned AS banned, ui.topic_display AS topic_display, ui.lastpage AS lastpage, ui.notify_pm AS notify_pm, ui.popup_pm AS popup_pm, ui.viewflash AS viewflash, ui.viewemoticons AS viewemoticons, ui.viewsigs AS viewsigs, ui.viewavatars AS viewavatars, ui.viewcensors AS viewcensors, ui.attachsig AS attachsig";
$query_params['session']	= ", s.id AS sid, s.seen AS seen, s.name AS name, s.user_id AS user_id, s.data AS data, s.location_file AS location_file, s.location_act AS location_act, s.location_id AS location_id, s.user_agent as user_agent";
$query_params['maps']		= "m.id AS id, m.row_left AS row_left, m.row_right AS row_right, m.row_level AS row_level, m.name AS name, m.varname AS varname, m.is_global AS is_global, m.category_id AS category_id, m.forum_id AS forum_id, m.user_id AS user_id, m.group_id AS group_id, m.can_view AS can_view, m.can_add AS can_add, m.can_edit AS can_edit, m.can_del AS can_del, m.inherit AS inherit, m.value as value";
$query_params['topic']		= ", t.topic_id AS topic_id, t.forum_id AS forum_id, t.category_id AS category_id, t.edited_time AS edited_time, t.edited_username AS edited_username, t.edited_userid AS edited_userid, t.ratings_sum AS ratings_sum, t.ratings_num AS ratings_num, t.disable_html AS disable_html, t.disable_bbcode AS disable_bbcode, t.disable_emoticons AS disable_emoticons, t.disable_sig AS disable_sig, t.disable_areply AS disable_areply, t.disable_aurls AS disable_aurls, t.topic_locked AS topic_locked, t.description AS description, t.body_text AS body_text, t.posticon AS posticon, t.poster_name AS poster_name, t.poster_id AS poster_id, t.reply_time AS reply_time, t.reply_uname AS reply_uname, t.reply_id AS reply_id, t.reply_uid AS reply_uid, t.poll AS poll, t.poll_question AS poll_question, t.poll_id AS poll_id, t.poll_votes AS poll_votes, t.views AS views, t.is_draft AS is_draft, t.last_viewed as last_viewed, t.topic_type as topic_type, t.poster_ip as poster_ip, t.topic_expire AS topic_expire";
$query_params['reply']		= ", r.reply_id AS reply_id, r.topic_id AS topic_id, r.forum_id AS forum_id, r.category_id AS category_id, r.is_draft AS is_draft, r.body_text AS body_text, r.poster_name AS poster_name, r.poster_id AS poster_id, r.poster_ip AS poster_ip, r.edited_time AS edited_time, r.edited_username AS edited_username, r.edited_userid AS edited_userid, r.disable_html AS disable_html, r.disable_bbcode AS disable_bbcode, r.disable_emoticons AS disable_emoticons, r.disable_sig AS disable_sig, r.disable_areply AS disable_areply, r.disable_aurls AS disable_aurls, r.posticon AS posticon";

/**
 * Define all basic MAP items for categories, forums, etc.
 */
$map_items['category'][]	= array('can_view' => GUEST, 'can_add' => SUPERADMIN, 'can_edit' => SUPERADMIN, 'can_del' => SUPERADMIN);

$map_items['forum'][]		= array('can_view' => GUEST,			'can_add' => SUPERADMIN, 'can_edit' => SUPERADMIN, 'can_del' => SUPERADMIN);
$map_items['forum'][]		= array('varname' => 'topics',			'can_view' => GUEST, 'can_add' => MEMBER, 'can_edit' => MEMBER, 'can_del' => SUPERMEMBER);
$map_items['forum'][]		= array('varname' => 'other_topics',	'can_view' => 0, 'can_add' => 0, 'can_edit' => SUPERMEMBER, 'can_del' => SUPERMEMBER);
$map_items['forum'][]		= array('varname' => 'polls',			'can_view' => GUEST, 'can_add' => MEMBER, 'can_edit' => MEMBER, 'can_del' => SUPERMEMBER);
$map_items['forum'][]		= array('varname' => 'other_polls',		'can_view' => 0, 'can_add' => 0, 'can_edit' => SUPERMEMBER, 'can_del' => SUPERMEMBER);
$map_items['forum'][]		= array('varname' => 'replies',			'can_view' => GUEST, 'can_add' => MEMBER, 'can_edit' => MEMBER, 'can_del' => SUPERMEMBER);
$map_items['forum'][]		= array('varname' => 'other_replies',	'can_view' => 0, 'can_add' => 0, 'can_edit' => SUPERMEMBER, 'can_del' => SUPERMEMBER);
$map_items['forum'][]		= array('varname' => 'attachments',		'can_view' => GUEST, 'can_add' => MEMBER, 'can_edit' => MEMBER, 'can_del' => MODERATOR);
$map_items['forum'][]		= array('varname' => 'vote_on_poll',	'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['forum'][]		= array('varname' => 'rate_topic',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => MODERATOR);
$map_items['forum'][]		= array('varname' => 'sticky',			'can_view' => GUEST, 'can_add' => MODERATOR, 'can_edit' => MODERATOR, 'can_del' => MODERATOR);
$map_items['forum'][]		= array('varname' => 'announce',		'can_view' => GUEST, 'can_add' => ADMIN, 'can_edit' => ADMIN, 'can_del' => ADMIN);
$map_items['forum'][]		= array('varname' => 'global',			'can_view' => GUEST, 'can_add' => ADMIN, 'can_edit' => ADMIN, 'can_del' => ADMIN);
$map_items['forum'][]		= array('varname' => 'closed',			'can_view' => GUEST, 'can_add' => SUPERMEMBER, 'can_edit' => SUPERMEMBER, 'can_del' => SUPERMEMBER);
$map_items['forum'][]		= array('varname' => 'avatars',			'can_view' => GUEST, 'can_add' => 0, 'can_edit' => 0, 'can_del' => 0);
$map_items['forum'][]		= array('varname' => 'signatures',		'can_view' => GUEST, 'can_add' => 0, 'can_edit' => 0, 'can_del' => 0);
$map_items['forum'][]		= array('varname' => 'html',			'can_view' => 0, 'can_add' => ADMIN, 'can_edit' => 0, 'can_del' => 0, 'value' => 'br,a,pre,ul,li,ol,p');
$map_items['forum'][]		= array('varname' => 'bbcode',			'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['forum'][]		= array('varname' => 'bbimgcode',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['forum'][]		= array('varname' => 'bbflashcode',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['forum'][]		= array('varname' => 'emoticons',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['forum'][]		= array('varname' => 'posticons',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['forum'][]		= array('varname' => 'post_save',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['forum'][]		= array('varname' => 'post_preview',	'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['forum'][]		= array('varname' => 'rss_feed',		'can_view' => GUEST, 'can_add' => 0, 'can_edit' => 0, 'can_del' => 0);


$map_items['blog'][]		= array('can_view' => GUEST,			'can_add' => MEMBER, 'can_edit' => MEMBER, 'can_del' => MEMBER);
$map_items['blog'][]		= array('varname' => 'blogs',			'can_view' => GUEST, 'can_add' => MEMBER, 'can_edit' => MEMBER, 'can_del' => MEMBER);
$map_items['blog'][]		= array('varname' => 'other_blogs',		'can_view' => GUEST, 'can_add' => MODERATOR, 'can_edit' => MODERATOR, 'can_del' => MODERATOR);
$map_items['blog'][]		= array('varname' => 'comments',		'can_view' => GUEST, 'can_add' => MEMBER, 'can_edit' => MEMBER, 'can_del' => MEMBER);
$map_items['blog'][]		= array('varname' => 'other_comments',	'can_view' => 0, 'can_add' => 0, 'can_edit' => SUPERMEMBER, 'can_del' => SUPERMEMBER);
$map_items['blog'][]		= array('varname' => 'avatars',			'can_view' => GUEST, 'can_add' => 0, 'can_edit' => 0, 'can_del' => 0);
$map_items['blog'][]		= array('varname' => 'signatures',		'can_view' => GUEST, 'can_add' => 0, 'can_edit' => 0, 'can_del' => 0);
$map_items['blog'][]		= array('varname' => 'html',			'can_view' => 0, 'can_add' => ADMIN, 'can_edit' => 0, 'can_del' => 0, 'value' => 'br,a,pre,ul,li,ol,p');
$map_items['blog'][]		= array('varname' => 'bbcode',			'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['blog'][]		= array('varname' => 'bbimgcode',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['blog'][]		= array('varname' => 'bbflashcode',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['blog'][]		= array('varname' => 'emoticons',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['blog'][]		= array('varname' => 'posticons',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['blog'][]		= array('varname' => 'post_save',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['blog'][]		= array('varname' => 'post_preview',	'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$map_items['blog'][]		= array('varname' => 'trackback',		'can_view' => GUEST, 'can_add' => 0, 'can_edit' => 0, 'can_del' => 0);
$map_items['blog'][]		= array('varname' => 'private_blog',	'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);


/**
 * Get all of the settings into one big array
 */

/* Get the configuration options */
global $_CONFIG;


/* Get the database Object */
$_DBA							= &Database::open($_CONFIG['dba']);

/*
$query = "";

foreach(explode("\r\n", $query) as $q)
	if($q != '')
		$_DBA->executeUpdate($q);
exit;
*/

if(file_exists(CACHE_FILE) && is_readable(CACHE_FILE) && is_writable(CACHE_FILE)) {
	
	if((filemtime(CACHE_FILE) + CACHE_INTERVAL) > time()) {
		$rewrite_cache = FALSE;
	} else {

		/* Include the $cache array */
		include CACHE_FILE;
		
		if(!is_array($cache)) {
			$rewrite_cache	= TRUE;
		} else {
			$rewrite_cache	= FALSE;
		}
	}

} else {
	$rewrite_cache = TRUE;
}

if($rewrite_cache) {
	
	$cache									= array();

	/**
	 * Get the datastore 
	 */
	$result									= &$_DBA->executeQuery("SELECT * FROM ". DATASTORE);
	while ($result->next()) {
		$temp								= $result->current();
		$cache[DATASTORE][$temp['varname']] = @unserialize(stripslashes(str_replace('&quot;', '"', $temp['data'])));
	}
	$result->freeResult();

	/**
	 * Get the usergroups 
	 */
	$result									= &$_DBA->executeQuery("SELECT * FROM ". USERGROUPS ." ORDER BY max_perm DESC");
	while ($result->next()) {
		$temp								= $result->current();
		$cache[USERGROUPS][$temp['id']]		= $temp;
	}
	$result->freeResult();
	
	/**
	 * Get the settings
	 */
	$result									= &$_DBA->executeQuery("SELECT * FROM ". SETTINGS);
	while($result->next()) {
		$temp								= $result->current();
		$cache[SETTINGS][$temp['varname']]	= $temp['value'];
	}
	$result->freeResult();
	
	/**
	 * Get ALL of the categories/forums
	 */
	$result									= &$_DBA->executeQuery("SELECT ". $query_params['info'] ." FROM ". INFO ." i WHERE i.row_type = ". FORUM ." OR i.row_type = ". CATEGORY ." ORDER BY i.row_left ASC");
	while($result->next()) {
		$temp								= $result->current();
		$cache['all_forums'][$temp['id']]	= $temp;
	}
	$result->freeResult();

	/* Memory saving */
	unset($result);

	/* Get the MAP's */
	$cache[MAPS]							= get_maps();

	DBCache::createCache($cache);
} else {

	include_once CACHE_FILE;
}

/**
 * Set the super-globals 
 */
$GLOBALS['_DBA']					= &$_DBA;
$GLOBALS['_URL']					= &new Url('http://'. $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
$GLOBALS['_SETTINGS']				= $cache[SETTINGS];
$GLOBALS['_DATASTORE']				= $cache[DATASTORE];
$GLOBALS['_QUERYPARAMS']			= &$query_params;
$GLOBALS['_MAPITEMS']				= &$map_items;
$GLOBALS['_MAPS']					= $cache[MAPS];
$GLOBALS['_USERGROUPS']				= $cache[USERGROUPS];
$GLOBALS['_DEBUGITEMS']				= array();
$GLOBALS['_ALLFORUMS']				= $cache['all_forums'];

/* Memory Saving */
unset($cache);

?>