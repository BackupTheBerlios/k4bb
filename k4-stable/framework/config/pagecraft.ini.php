;<?php return; ?>
;Do NOT change the above line

[application]
action_var		= act

dba_name = k4_forum

[sqlite]
;Absolute path to the sqlite directory
directory = this_is_where_the_directory_goes

mode		= 0666

[template]
;Absolute path to the folder which holds all the template SETS (not the compiled versions, not the source versions)

path = this_is_where_the_path_goes 


;Folder where your compiled & source templates are (not slashes)

tplfolder = Descent 


;Folder where your current image set is

imgfolder = Descent


;Determines whether templates are always compiled or not.
;This should be 'no' for most situations, set it to 'yes' to debug template components' output

force_compile	= no


;Determines whether the template parser will strip out empty whitespaces
;Note: space before and after a character data block will NOT be stripped
;only empty character data blocks will be skipped

ignore_white	= no

[theme]

styleset = 1


[config]

lang = english


forumurl = forum_url_here 


configurl = config_url_here



[ftp]
use_ftp			= false
username		= username_here
password		= password_here
server			= server_here