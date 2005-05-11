<?php 
error_reporting(E_ALL); 

if(!defined('IN_K4')) { 
	exit; 
}

$cache = array (
  'k4_datastore' => 
  array (
    'maxloggedin' => 
    array (
      'maxonline' => 2,
      'maxonlinedate' => 1115665205,
    ),
    'forumstats' => 
    array (
      'num_topics' => 2,
      'num_replies' => 0,
      'num_members' => 2,
    ),
    'search_spiders' => 
    array (
      'spiderstrings' => 'googlebot|lycos|ask jeeves|scooter|fast-webcrawler|slurp@inktomi|turnitinbot',
      'spidernames' => 
      array (
        'googlebot' => 'Google',
        'lycos' => 'Lycos',
        'ask jeeves' => 'Ask Jeeves',
        'scooter' => 'Altavista',
        'fast-webcrawler' => 'AllTheWeb',
        'slurp@inktomi' => 'Inktomi',
        'turnitinbot' => 'Turnitin.com',
      ),
    ),
  ),
  'k4_usergroups' => 
  array (
    4 => 
    array (
      'id' => '4',
      'name' => 'Administrators',
      'nicename' => 'Board Admin',
      'description' => 'Administrators of this board.',
      'mod_name' => '',
      'mod_id' => '0',
      'created' => '0',
      'min_perm' => '9',
      'max_perm' => '10',
      'display_legend' => '1',
      'custom_map_set' => '0',
      'color' => 'CC0035',
      'avatar' => 'administrator.gif',
    ),
    5 => 
    array (
      'id' => '5',
      'name' => 'Super Moderators',
      'nicename' => 'Super Moderator',
      'description' => 'Super Moderators of this board.',
      'mod_name' => '',
      'mod_id' => '0',
      'created' => '0',
      'min_perm' => '7',
      'max_perm' => '8',
      'display_legend' => '1',
      'custom_map_set' => '0',
      'color' => '0E9A04',
      'avatar' => 'moderator.gif',
    ),
    3 => 
    array (
      'id' => '3',
      'name' => 'Moderators',
      'nicename' => 'Moderator',
      'description' => 'Moderators of this board.',
      'mod_name' => '',
      'mod_id' => '0',
      'created' => '0',
      'min_perm' => '6',
      'max_perm' => '7',
      'display_legend' => '1',
      'custom_map_set' => '0',
      'color' => '3500CC',
      'avatar' => 'moderator.gif',
    ),
    6 => 
    array (
      'id' => '6',
      'name' => 'Super Members',
      'nicename' => 'Super Member',
      'description' => 'Super Members of this board.',
      'mod_name' => '',
      'mod_id' => '0',
      'created' => '0',
      'min_perm' => '5',
      'max_perm' => '6',
      'display_legend' => '1',
      'custom_map_set' => '0',
      'color' => 'D58A32',
      'avatar' => '',
    ),
    1 => 
    array (
      'id' => '1',
      'name' => 'Registered Users',
      'nicename' => 'Member',
      'description' => 'Registered users of this board.',
      'mod_name' => '',
      'mod_id' => '0',
      'created' => '0',
      'min_perm' => '5',
      'max_perm' => '5',
      'display_legend' => '0',
      'custom_map_set' => '0',
      'color' => '000000',
      'avatar' => '',
    ),
    2 => 
    array (
      'id' => '2',
      'name' => 'Pending Users',
      'nicename' => 'Wannabe',
      'description' => 'Users pending registration on this board.',
      'mod_name' => '',
      'mod_id' => '0',
      'created' => '0',
      'min_perm' => '4',
      'max_perm' => '4',
      'display_legend' => '0',
      'custom_map_set' => '0',
      'color' => '999999',
      'avatar' => '',
    ),
  ),
  'k4_setting' => 
  array (
    'bbactive' => '1',
    'bbclosedreason' => 'Yahtzee! The board has temporarily been closed, the Adminstrator probably has a good reason.',
    'bbtitle' => 'k4 v2.0',
    'bbdescription' => '2.0 Development Board',
    'bbsearchdescription' => 'k4 v2.0 - Powered by k4 Bulletin Board',
    'bbkeywords' => 'k4, bb, bulletin, board, bulletin board, forum, k4st, forums, message, board, message board',
    'copyrighttext' => '',
    'styleset' => 'Descent',
    'imageset' => 'Descent',
    'templateset' => 'Descent',
    'doctype' => '<!DOCTYPE html PUBLIC &quot;-//W3C//DTD XHTML 1.0 Transitional//EN&quot; &quot;http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd&quot;>',
    'contactuslink' => 'mailto:peter.goodman@gmail.com',
    'webmasteremail' => 'peter.goodman@gmail.com',
    'companyname' => '',
    'address' => '',
    'faxnumber' => '',
    'allowbbimagecode' => '0',
    'allowbbcode' => '1',
    'allowemoticons' => '1',
    'maximages' => '10',
    'smcolumns' => '3',
    'smtotal' => '15',
    'allowdynimg' => '0',
    'allowcodebuttons' => '1',
    'showsubforums' => '1',
    'showlocks' => '0',
    'hideprivateforums' => '1',
    'displayloggedin' => '1',
    'showforumdescription' => '1',
    'showbirthdays' => '1',
    'displayemails' => '0',
    'secureemail' => '1',
    'allowsignatures' => '1',
    'newuseremail' => '',
    'requireuniqueemail' => '1',
    'allowregistration' => '1',
    'verifyemail' => '1',
    'moderatenewmembers' => '0',
    'allowchangestyles' => '1',
    'minuserlength' => '3',
    'maxuserlength' => '16',
    'enablememberlist' => '1',
    'usememberlistadvsearch' => '1',
    'memberlistperpage' => '30',
    'maxposts' => '15',
    'postmaxchars' => '10000',
    'stopshoutingtitle' => '1',
    'stopshoutingmessage' => '1',
    'maxthreads' => '25',
    'hotnumberviews' => '150',
    'hotnumberposts' => '15',
    'linktopages' => '1',
    'movedthreadprefix' => 'Moved: ',
    'announcementthreadprefix' => 'Announcement: ',
    'stickythreadprefix' => 'Sticky: ',
    'pollthreadprefix' => 'Poll: ',
    'enablesearches' => '1',
    'searchperpage' => '25',
    'minsearchlength' => '4',
    'maxsearchlength' => '20',
    'allowwildcards' => '1',
    'showeditedby' => '1',
    'ontext' => 'ON',
    'offtext' => 'OFF',
    'enablepms' => '1',
    'privallowbbimgcode' => '1',
    'privallowbbcode' => '1',
    'privallowemoticons' => '1',
    'privallowicons' => '1',
    'pmquota' => '70',
    'checknewpm' => '1',
    'pmmaxchars' => '1000',
    'pmcancelledword' => 'Cancelled:',
    'pmcancelkill' => '0',
    'maxpolloptions' => '10',
    'updatelastpost' => '0',
    'avatarenabled' => '1',
    'avatarallowupload' => '1',
    'avatarallowwebsite' => '1',
    'avatarmaxdimension' => '50',
    'avatarmaxsize' => '20000',
    'maxattachsize' => '102400',
    'attachextensions' => 'gif jpg png txt zip bmp jpeg',
    'viewattachedimages' => '0',
    'maxattachwidth' => '0',
    'maxattachheight' => '0',
    'calendarenabled' => '1',
    'calbirthday' => '1',
    'calshowbirthdays' => '1',
    'enableblogs' => '1',
  ),
  'all_forums' => 
  array (
    1 => 
    array (
      'id' => '1',
      'parent_id' => '0',
      'row_left' => '1',
      'row_right' => '8',
      'row_type' => '1',
      'row_order' => '1',
      'name' => 'Test Category',
      'created' => '1115161251',
      'row_level' => '1',
    ),
    2 => 
    array (
      'id' => '2',
      'parent_id' => '1',
      'row_left' => '2',
      'row_right' => '7',
      'row_type' => '2',
      'row_order' => '1',
      'name' => 'Test Forum',
      'created' => '1115161281',
      'row_level' => '2',
    ),
  ),
  'k4_userprofilefields' => 
  array (
    'field1' => 
    array (
      'name' => 'field1',
      'title' => 'Address',
      'description' => 'Your home address.',
      'default_value' => '',
      'inputtype' => 'textarea',
      'user_maxlength' => '255',
      'inputoptions' => '',
      'min_perm' => '2',
      'display_register' => '1',
      'display_profile' => '1',
      'display_topic' => '0',
      'display_post' => '0',
      'display_image' => '',
      'display_memberlist' => '0',
      'display_size' => '35',
      'display_rows' => '3',
      'display_order' => '1',
      'is_editable' => '0',
      'is_private' => '0',
      'is_required' => '0',
      'special_pcre' => '',
      'html' => '<textarea name="field1" id="field1" cols="35" rows="3" class="inputbox"></textarea>',
    ),
    'field2' => 
    array (
      'name' => 'field2',
      'title' => 'Occupation',
      'description' => 'Your occupation.',
      'default_value' => '',
      'inputtype' => 'text',
      'user_maxlength' => '100',
      'inputoptions' => '',
      'min_perm' => '2',
      'display_register' => '1',
      'display_profile' => '1',
      'display_topic' => '0',
      'display_post' => '0',
      'display_image' => '',
      'display_memberlist' => '0',
      'display_size' => '20',
      'display_rows' => '0',
      'display_order' => '2',
      'is_editable' => '0',
      'is_private' => '0',
      'is_required' => '0',
      'special_pcre' => '',
      'html' => '<input type="text" class="inputbox" name="field2" id="field2" value="" size="20" maxlength="100" />',
    ),
    'field3' => 
    array (
      'name' => 'field3',
      'title' => 'Interests',
      'description' => 'A list of your interests.',
      'default_value' => '',
      'inputtype' => 'text',
      'user_maxlength' => '100',
      'inputoptions' => NULL,
      'min_perm' => '2',
      'display_register' => '0',
      'display_profile' => '1',
      'display_topic' => '0',
      'display_post' => '0',
      'display_image' => '',
      'display_memberlist' => '0',
      'display_size' => '20',
      'display_rows' => '0',
      'display_order' => '3',
      'is_editable' => '1',
      'is_private' => '0',
      'is_required' => '0',
      'special_pcre' => '',
      'html' => '<input type="text" class="inputbox" name="field3" id="field3" value="" size="20" maxlength="100" />',
    ),
    'field4' => 
    array (
      'name' => 'field4',
      'title' => 'Biography',
      'description' => 'A short description of yourself.',
      'default_value' => '',
      'inputtype' => 'textarea',
      'user_maxlength' => '255',
      'inputoptions' => NULL,
      'min_perm' => '2',
      'display_register' => '0',
      'display_profile' => '1',
      'display_topic' => '0',
      'display_post' => '0',
      'display_image' => '',
      'display_memberlist' => '0',
      'display_size' => '35',
      'display_rows' => '2',
      'display_order' => '4',
      'is_editable' => '1',
      'is_private' => '0',
      'is_required' => '0',
      'special_pcre' => '',
      'html' => '<textarea name="field4" id="field4" cols="35" rows="2" class="inputbox"></textarea>',
    ),
    'field5' => 
    array (
      'name' => 'field5',
      'title' => 'Homepage',
      'description' => 'Your website homepage.',
      'default_value' => '',
      'inputtype' => 'text',
      'user_maxlength' => '100',
      'inputoptions' => NULL,
      'min_perm' => '2',
      'display_register' => '0',
      'display_profile' => '1',
      'display_topic' => '0',
      'display_post' => '0',
      'display_image' => '',
      'display_memberlist' => '0',
      'display_size' => '30',
      'display_rows' => '0',
      'display_order' => '5',
      'is_editable' => '1',
      'is_private' => '0',
      'is_required' => '0',
      'special_pcre' => '',
      'html' => '<input type="text" class="inputbox" name="field5" id="field5" value="" size="30" maxlength="100" />',
    ),
    'field6' => 
    array (
      'name' => 'field6',
      'title' => 'User Title',
      'description' => 'User Title',
      'default_value' => 'Newbie',
      'inputtype' => 'text',
      'user_maxlength' => '50',
      'inputoptions' => '',
      'min_perm' => '2',
      'display_register' => '0',
      'display_profile' => '0',
      'display_topic' => '0',
      'display_post' => '0',
      'display_image' => '0',
      'display_memberlist' => '',
      'display_size' => '30',
      'display_rows' => '0',
      'display_order' => '6',
      'is_editable' => '0',
      'is_private' => '0',
      'is_required' => '0',
      'special_pcre' => '',
      'html' => '<input type="text" class="inputbox" name="field6" id="field6" value="Newbie" size="30" maxlength="50" />',
    ),
  ),
  'k4_maps' => 
  array (
    'global' => 
    array (
      'id' => '1',
      'row_left' => '1',
      'row_right' => '14',
      'row_level' => '1',
      'name' => 'Global Forum Permisions',
      'varname' => '',
      'value' => '',
      'is_global' => '1',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '1',
      'can_add' => '0',
      'can_edit' => '0',
      'can_del' => '0',
      'inherit' => '1',
    ),
    'can_see_board' => 
    array (
      'id' => '2',
      'row_left' => '2',
      'row_right' => '3',
      'row_level' => '2',
      'name' => 'Can Users see the Board?',
      'varname' => 'can_see_board',
      'value' => '',
      'is_global' => '1',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '1',
      'can_add' => '0',
      'can_edit' => '0',
      'can_del' => '0',
      'inherit' => '1',
    ),
    'can_see_WO' => 
    array (
      'id' => '3',
      'row_left' => '4',
      'row_right' => '9',
      'row_level' => '2',
      'name' => 'Who is Online',
      'varname' => 'can_see_WO',
      'value' => '',
      'is_global' => '1',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '1',
      'can_add' => '0',
      'can_edit' => '0',
      'can_del' => '0',
      'inherit' => '1',
    ),
    'can_see_WOloggedusers' => 
    array (
      'id' => '4',
      'row_left' => '5',
      'row_right' => '6',
      'row_level' => '3',
      'name' => 'Logged in Users?',
      'varname' => 'can_see_WOloggedusers',
      'value' => '',
      'is_global' => '1',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '1',
      'can_add' => '0',
      'can_edit' => '0',
      'can_del' => '0',
      'inherit' => '1',
    ),
    'can_see_WObirthdays' => 
    array (
      'id' => '5',
      'row_left' => '7',
      'row_right' => '8',
      'row_level' => '3',
      'name' => 'Birthdays?',
      'varname' => 'can_see_WObirthdays',
      'value' => '',
      'is_global' => '1',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '1',
      'can_add' => '0',
      'can_edit' => '0',
      'can_del' => '0',
      'inherit' => '1',
    ),
    'memberlist' => 
    array (
      'id' => '6',
      'row_left' => '10',
      'row_right' => '11',
      'row_level' => '2',
      'name' => 'Memberlist',
      'varname' => 'memberlist',
      'value' => '',
      'is_global' => '0',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '1',
      'can_add' => '0',
      'can_edit' => '0',
      'can_del' => '0',
      'inherit' => '1',
    ),
    'advsearch' => 
    array (
      'id' => '7',
      'row_left' => '12',
      'row_right' => '13',
      'row_level' => '2',
      'name' => 'Advanced Search',
      'varname' => 'advsearch',
      'value' => '',
      'is_global' => '0',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '1',
      'can_add' => '0',
      'can_edit' => '0',
      'can_del' => '0',
      'inherit' => '1',
    ),
    'faq' => 
    array (
      'id' => '8',
      'row_left' => '15',
      'row_right' => '16',
      'row_level' => '1',
      'name' => 'FAQ',
      'varname' => 'faq',
      'value' => '',
      'is_global' => '1',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '1',
      'can_add' => '9',
      'can_edit' => '9',
      'can_del' => '9',
      'inherit' => '1',
    ),
    'calendar' => 
    array (
      'id' => '9',
      'row_left' => '17',
      'row_right' => '24',
      'row_level' => '1',
      'name' => 'Calendar',
      'varname' => 'calendar',
      'value' => '',
      'is_global' => '0',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '1',
      'can_add' => '5',
      'can_edit' => '5',
      'can_del' => '7',
      'inherit' => '1',
    ),
    'calbbimagecode' => 
    array (
      'id' => '10',
      'row_left' => '18',
      'row_right' => '19',
      'row_level' => '2',
      'name' => 'BB Image Code in Event',
      'varname' => 'calbbimagecode',
      'value' => '',
      'is_global' => '1',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '5',
      'can_add' => '0',
      'can_edit' => '0',
      'can_del' => '0',
      'inherit' => '1',
    ),
    'calallowemoticons' => 
    array (
      'id' => '11',
      'row_left' => '20',
      'row_right' => '21',
      'row_level' => '2',
      'name' => 'Smilies in Event',
      'varname' => 'calallowemoticons',
      'value' => '',
      'is_global' => '1',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '5',
      'can_add' => '0',
      'can_edit' => '0',
      'can_del' => '0',
      'inherit' => '1',
    ),
    'calallowbbcode' => 
    array (
      'id' => '12',
      'row_left' => '22',
      'row_right' => '23',
      'row_level' => '2',
      'name' => 'BB Code in Event',
      'varname' => 'calallowbbcode',
      'value' => '',
      'is_global' => '1',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '5',
      'can_add' => '0',
      'can_edit' => '0',
      'can_del' => '0',
      'inherit' => '1',
    ),
    'usergroups' => 
    array (
      'id' => '13',
      'row_left' => '25',
      'row_right' => '26',
      'row_level' => '1',
      'name' => 'User Groups',
      'varname' => 'usergroups',
      'value' => '',
      'is_global' => '0',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '1',
      'can_add' => '9',
      'can_edit' => '9',
      'can_del' => '9',
      'inherit' => '1',
    ),
    'categories' => 
    array (
      'id' => '14',
      'row_left' => '27',
      'row_right' => '30',
      'row_level' => '1',
      'name' => 'Categories',
      'varname' => 'categories',
      'value' => '',
      'is_global' => '1',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '1',
      'can_add' => '9',
      'can_edit' => '9',
      'can_del' => '9',
      'inherit' => '1',
      1 => 
      array (
        'id' => '17',
        'row_left' => '28',
        'row_right' => '29',
        'row_level' => '2',
        'name' => 'Test Category',
        'varname' => 'category1',
        'value' => '',
        'is_global' => '0',
        'category_id' => '1',
        'forum_id' => '0',
        'user_id' => '0',
        'group_id' => '0',
        'can_view' => '1',
        'can_add' => '10',
        'can_edit' => '10',
        'can_del' => '10',
        'inherit' => '0',
      ),
    ),
    'forums' => 
    array (
      'id' => '15',
      'row_left' => '31',
      'row_right' => '82',
      'row_level' => '1',
      'name' => 'Forums',
      'varname' => 'forums',
      'value' => '',
      'is_global' => '1',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '1',
      'can_add' => '9',
      'can_edit' => '9',
      'can_del' => '9',
      'inherit' => '1',
      2 => 
      array (
        'id' => '18',
        'row_left' => '32',
        'row_right' => '81',
        'row_level' => '2',
        'name' => 'Test Forum',
        'varname' => 'forum2',
        'value' => '',
        'is_global' => '0',
        'category_id' => '1',
        'forum_id' => '2',
        'user_id' => '0',
        'group_id' => '0',
        'can_view' => '1',
        'can_add' => '10',
        'can_edit' => '10',
        'can_del' => '10',
        'inherit' => '0',
        'topics' => 
        array (
          'id' => '19',
          'row_left' => '33',
          'row_right' => '34',
          'row_level' => '3',
          'name' => 'Topics',
          'varname' => 'topics',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '1',
          'can_add' => '5',
          'can_edit' => '5',
          'can_del' => '6',
          'inherit' => '0',
        ),
        'other_topics' => 
        array (
          'id' => '20',
          'row_left' => '35',
          'row_right' => '36',
          'row_level' => '3',
          'name' => 'Other People\'s Topics',
          'varname' => 'other_topics',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '0',
          'can_add' => '0',
          'can_edit' => '6',
          'can_del' => '6',
          'inherit' => '0',
        ),
        'polls' => 
        array (
          'id' => '21',
          'row_left' => '37',
          'row_right' => '38',
          'row_level' => '3',
          'name' => 'Polls',
          'varname' => 'polls',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '1',
          'can_add' => '5',
          'can_edit' => '5',
          'can_del' => '6',
          'inherit' => '0',
        ),
        'other_polls' => 
        array (
          'id' => '22',
          'row_left' => '39',
          'row_right' => '40',
          'row_level' => '3',
          'name' => 'Other People\'s Polls',
          'varname' => 'other_polls',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '0',
          'can_add' => '0',
          'can_edit' => '6',
          'can_del' => '6',
          'inherit' => '0',
        ),
        'replies' => 
        array (
          'id' => '23',
          'row_left' => '41',
          'row_right' => '42',
          'row_level' => '3',
          'name' => 'Replies',
          'varname' => 'replies',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '1',
          'can_add' => '5',
          'can_edit' => '5',
          'can_del' => '6',
          'inherit' => '0',
        ),
        'other_replies' => 
        array (
          'id' => '24',
          'row_left' => '43',
          'row_right' => '44',
          'row_level' => '3',
          'name' => 'Other People\'s Replies',
          'varname' => 'other_replies',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '0',
          'can_add' => '0',
          'can_edit' => '6',
          'can_del' => '6',
          'inherit' => '0',
        ),
        'attachments' => 
        array (
          'id' => '25',
          'row_left' => '45',
          'row_right' => '46',
          'row_level' => '3',
          'name' => 'Attachments',
          'varname' => 'attachments',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '1',
          'can_add' => '5',
          'can_edit' => '5',
          'can_del' => '7',
          'inherit' => '0',
        ),
        'vote_on_poll' => 
        array (
          'id' => '26',
          'row_left' => '47',
          'row_right' => '48',
          'row_level' => '3',
          'name' => 'Vote on Polls',
          'varname' => 'vote_on_poll',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '0',
          'can_add' => '5',
          'can_edit' => '0',
          'can_del' => '0',
          'inherit' => '0',
        ),
        'rate_topic' => 
        array (
          'id' => '27',
          'row_left' => '49',
          'row_right' => '50',
          'row_level' => '3',
          'name' => 'Rate Topics',
          'varname' => 'rate_topic',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '0',
          'can_add' => '5',
          'can_edit' => '0',
          'can_del' => '7',
          'inherit' => '0',
        ),
        'sticky' => 
        array (
          'id' => '28',
          'row_left' => '51',
          'row_right' => '52',
          'row_level' => '3',
          'name' => 'Sticky Topics',
          'varname' => 'sticky',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '1',
          'can_add' => '7',
          'can_edit' => '7',
          'can_del' => '7',
          'inherit' => '0',
        ),
        'announce' => 
        array (
          'id' => '29',
          'row_left' => '53',
          'row_right' => '54',
          'row_level' => '3',
          'name' => 'Announcement Topics',
          'varname' => 'announce',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '1',
          'can_add' => '9',
          'can_edit' => '9',
          'can_del' => '9',
          'inherit' => '0',
        ),
        'global' => 
        array (
          'id' => '30',
          'row_left' => '55',
          'row_right' => '56',
          'row_level' => '3',
          'name' => 'Global',
          'varname' => 'global',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '1',
          'can_add' => '9',
          'can_edit' => '9',
          'can_del' => '9',
          'inherit' => '0',
        ),
        'closed' => 
        array (
          'id' => '31',
          'row_left' => '57',
          'row_right' => '58',
          'row_level' => '3',
          'name' => 'Closed Topics',
          'varname' => 'closed',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '1',
          'can_add' => '6',
          'can_edit' => '6',
          'can_del' => '6',
          'inherit' => '0',
        ),
        'avatars' => 
        array (
          'id' => '32',
          'row_left' => '59',
          'row_right' => '60',
          'row_level' => '3',
          'name' => 'User Avatars',
          'varname' => 'avatars',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '1',
          'can_add' => '0',
          'can_edit' => '0',
          'can_del' => '0',
          'inherit' => '0',
        ),
        'signatures' => 
        array (
          'id' => '33',
          'row_left' => '61',
          'row_right' => '62',
          'row_level' => '3',
          'name' => 'User Signatures',
          'varname' => 'signatures',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '1',
          'can_add' => '0',
          'can_edit' => '0',
          'can_del' => '0',
          'inherit' => '0',
        ),
        'html' => 
        array (
          'id' => '34',
          'row_left' => '63',
          'row_right' => '64',
          'row_level' => '3',
          'name' => 'HTML Code',
          'varname' => 'html',
          'value' => 'br,a,pre,ul,li,ol,p',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '0',
          'can_add' => '9',
          'can_edit' => '0',
          'can_del' => '0',
          'inherit' => '0',
        ),
        'bbcode' => 
        array (
          'id' => '35',
          'row_left' => '65',
          'row_right' => '66',
          'row_level' => '3',
          'name' => 'BB Code',
          'varname' => 'bbcode',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '0',
          'can_add' => '5',
          'can_edit' => '0',
          'can_del' => '0',
          'inherit' => '0',
        ),
        'bbimgcode' => 
        array (
          'id' => '36',
          'row_left' => '67',
          'row_right' => '68',
          'row_level' => '3',
          'name' => 'BB IMG Code',
          'varname' => 'bbimgcode',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '0',
          'can_add' => '5',
          'can_edit' => '0',
          'can_del' => '0',
          'inherit' => '0',
        ),
        'bbflashcode' => 
        array (
          'id' => '37',
          'row_left' => '69',
          'row_right' => '70',
          'row_level' => '3',
          'name' => 'BB Flash Code',
          'varname' => 'bbflashcode',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '0',
          'can_add' => '5',
          'can_edit' => '0',
          'can_del' => '0',
          'inherit' => '0',
        ),
        'emoticons' => 
        array (
          'id' => '38',
          'row_left' => '71',
          'row_right' => '72',
          'row_level' => '3',
          'name' => 'Emoticons',
          'varname' => 'emoticons',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '0',
          'can_add' => '5',
          'can_edit' => '0',
          'can_del' => '0',
          'inherit' => '0',
        ),
        'posticons' => 
        array (
          'id' => '39',
          'row_left' => '73',
          'row_right' => '74',
          'row_level' => '3',
          'name' => 'Post Icons',
          'varname' => 'posticons',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '0',
          'can_add' => '5',
          'can_edit' => '0',
          'can_del' => '0',
          'inherit' => '0',
        ),
        'post_save' => 
        array (
          'id' => '40',
          'row_left' => '75',
          'row_right' => '76',
          'row_level' => '3',
          'name' => 'Post Saving',
          'varname' => 'post_save',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '0',
          'can_add' => '5',
          'can_edit' => '0',
          'can_del' => '0',
          'inherit' => '0',
        ),
        'post_preview' => 
        array (
          'id' => '41',
          'row_left' => '77',
          'row_right' => '78',
          'row_level' => '3',
          'name' => 'Post Previewing',
          'varname' => 'post_preview',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '0',
          'can_add' => '5',
          'can_edit' => '0',
          'can_del' => '0',
          'inherit' => '0',
        ),
        'rss_feed' => 
        array (
          'id' => '42',
          'row_left' => '79',
          'row_right' => '80',
          'row_level' => '3',
          'name' => 'RSS Feeds',
          'varname' => 'rss_feed',
          'value' => '',
          'is_global' => '0',
          'category_id' => '1',
          'forum_id' => '2',
          'user_id' => '0',
          'group_id' => '0',
          'can_view' => '1',
          'can_add' => '0',
          'can_edit' => '0',
          'can_del' => '0',
          'inherit' => '0',
        ),
      ),
    ),
    'groups' => 
    array (
      'id' => '16',
      'row_left' => '83',
      'row_right' => '84',
      'row_level' => '1',
      'name' => 'Groups',
      'varname' => 'groups',
      'value' => '',
      'is_global' => '0',
      'category_id' => '0',
      'forum_id' => '0',
      'user_id' => '0',
      'group_id' => '0',
      'can_view' => '0',
      'can_add' => '10',
      'can_edit' => '0',
      'can_del' => '10',
      'inherit' => '1',
    ),
  ),
);
?>