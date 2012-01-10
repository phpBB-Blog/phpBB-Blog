<?php
/**
* @author Unknown Bliss (Michael Cullum of http://unknownbliss.co.uk)
* @package umil
* @copyright (c) 2008 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
define('UMIL_AUTO', true);
define('IN_PHPBB', true);
define('IN_INSTALL', true);

$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup(); 

if (!file_exists($phpbb_root_path . 'umil/umil_auto.' . $phpEx))
{
	trigger_error('Please download the latest UMIL (Unified MOD Install Library) from: <a href="http://www.phpbb.com/mods/umil/">phpBB.com/mods/umil</a>', E_USER_ERROR);
}

// Some blog files we need
require "{$phpbb_root_path}includes/mods/constants_blog.$phpEx";

// The name of the mod to be displayed during installation.
$mod_name = 'BLOG_MOD';

/*
* The name of the config variable which will hold the currently installed version
* You do not need to set this yourself, UMIL will handle setting and updating the version itself.
*/
$version_config_name = 'blog_version';

/*
* The language file which will be included when installing
* Language entries that should exist in the language file for UMIL (replace $mod_name with the mod's name you set to $mod_name above)
*/
$language_file = 'mods/info_acp_blog_install';
/*
* Optionally we may specify our own logo image to show in the upper corner instead of the default logo.
* $phpbb_root_path will get prepended to the path specified
* Image height should be 50px to prevent cut-off or stretching.
*/
//$logo_img = 'styles/prosilver/imageset/site_logo.gif';

/*
* The array of versions and actions within each.
* You do not need to order it a specific way (it will be sorted automatically), however, you must enter every version, even if no actions are done for it.
*
* You must use correct version numbering.  Unless you know exactly what you can use, only use X.X.X (replacing X with an integer).
* The version numbering must otherwise be compatible with the version_compare function - http://php.net/manual/en/function.version-compare.php
*/
$versions = array(
	'1.0.0' => array(
		'config_add' => array(
			array('blog_description', 'The forum\'s blog.'),
			array('blog_short_msg', 500),
			array('blog_title', 'Blog'),
			array('blog_postlimit', 10),
			array('blog_cmntlimit', 10),
			array('blog_on', true),
			array('blog_off_msg', ''),
			array('blog_act_name', 'action'),
			array('blog_cat_on', true),
			array('blog_tag_on', true),
			array('blog_forum_post_on', false),
			array('blog_forum_post_id', ''),
			array('blog_forum_post_msg', ''),
			array('blog_rss_feed_on', false),
			array('blog_bbcode_on', true),
			array('blog_emote_on', true),
		),		 
		'table_add' => array(
			array(BLOGS_TABLE, array(
					'COLUMNS'		=> array(
						'blog_id'			=> array('UINT', NULL, 'auto_increment'),
						'blog_title'		=> array('VCHAR_UNI', ''),
						'blog_desc'			=> array('VCHAR_UNI', ''),
						'blog_poster_id'	=> array('UINT', 0),
						'blog_cat_id'		=> array('UINT', 0),
						'blog_text'			=> array('TEXT_UNI', ''),
						'blog_posted_time'	=> array('TIMESTAMP', 0),
						'blog_last_edited'	=> array('TIMESTAMP', 0),
						'bbcode_bitfield'	=> array('VCHAR:255', ''),
						'bbcode_uid'		=> array('VCHAR:8', ''),
						'enable_bbcode'		=> array('TINT:1', 1),
						'enable_smilies'	=> array('TINT:1', 1),
						'enable_magic_url'	=> array('TINT:1', 1),
						'blog_tags'			=> array('VCHAR:255', ''),
						'blog_allow_cmnt'	=> array('TINT:1', 1),
					),
					'PRIMARY_KEY'	=> 'blog_id',
				),
			),
			array(BLOG_CMNTS_TABLE, array(
					'COLUMNS'	=> array(
						'cmnt_id'			=> array('UINT', NULL, 'auto_increment'),
						'cmnt_blog_id'		=> array('UINT', 0),
						'cmnt_poster_id'	=> array('UINT', 0),
						'cmnt_approved'		=> array('UINT', 0),
						'cmnt_text'			=> array('TEXT_UNI', ''),
						'cmnt_posted_time'	=> array('TIMESTAMP', 0),
						'cmnt_last_edited'	=> array('TIMESTAMP', 0),
						'bbcode_bitfield'	=> array('VCHAR:255', ''),
						'bbcode_uid'		=> array('VCHAR:8', ''),
						'enable_bbcode'		=> array('TINT:1', 1),
						'enable_smilies'	=> array('TINT:1', 1),
						'enable_magic_url'	=> array('TINT:1', 1),
					),
					'PRIMARY_KEY'	=> 'cmnt_id',
				),
			),
			array(BLOG_CATS_TABLE, array(
					'COLUMNS'	=> array(
						'cat_id'			=>	array('UINT', NULL, 'auto_increment'),
						'cat_title'			=>	array('VCHAR_UNI', ''),
						'cat_desc'			=>	array('VCHAR_UNI', ''),
						'forum_post_id'		=> 	array('UINT', 0),
						'forum_post_bool'	=> 	array('TINT:1', 0),
					),
					'PRIMARY_KEY'	=> 'cat_id',
				),
			),
		),
		'table_insert'	=> array(
			array(BLOG_CMNTS_TABLE, array(
				array(
					'cat_id'		=> 1,
					'cat_title'		=> 'News',
					'cat_desc'		=> 'Site news will be posted here',
				),
			)),
		),
		'module_add' => array(
							  
			array('acp', '', 'ACP_CAT_BLOG'),
			array('acp', 'ACP_CAT_BLOG', 'ACP_BLOG'),
			array('acp', 'ACP_BLOG', array(
					'module_basename'		=> 'blog',
					'modes'					=> array('index','settings','cats'),
				),
			),
		),
		'permission_add' => array(
			array('a_blog_manage', true),
			
			array('u_blog_post',			true),
			array('u_blog_post_manage',		true),
			array('u_blog_comment',			true),
			array('u_blog_approved',		true),
			array('u_blog_bbcode',			true),
			array('u_blog_emote',			true),
			array('u_blog_comment_manage',	true),
			array('u_blog_view',			true),
			array('u_blog_view_full',		true),
			array('u_blog_view_comments',	true),
		),
		'permission_set' => array(
			array('ROLE_ADMIN_FULL', 'a_blog_manage'),
			
			array('REGISTERED', 
				 array('u_blog_comment',
					   'u_blog_approved',
					   'u_blog_comment_manage',
					   'u_blog_view',
					   'u_blog_view_full',
					   'u_blog_view_comments',
				 ),				 
				 'group',
			),
		),		
	),
);

// Include the UMIF Auto file and everything else will be handled automatically.
include($phpbb_root_path . 'umil/umil_auto.' . $phpEx);
