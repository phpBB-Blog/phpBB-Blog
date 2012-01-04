<?php
/**
* Main file
*
* @package Blog RSS Feed
* @version 1.0.0
* @copyright (c) 2010, 2011 Michael Cullum (Unknown Bliss of http://michaelcullum.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
*
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
include($phpbb_root_path . 'includes/mods/functions_blog.' . $phpEx);
include($phpbb_root_path . 'includes/bbcode.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);

$user->setup('mods/blog');

// Initial var setup
$blog_id	= request_var('id', '');
$cat_id		= request_var('cid', '');
$blog 		= new blog;
$redirect	= request_var('redirect', '');
$act_name 	= $config['blog_act_name'];
$action		= request_var($act_name, 'index');
if($config['blog_rss_feed_on'])
{
	$feed = $blog->getrssfeed();
}
else
{
	$user->lang['FEED_DISABLED'];
}
echo $feed;
?>