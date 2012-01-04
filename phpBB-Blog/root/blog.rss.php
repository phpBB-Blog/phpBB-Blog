<?php
/**
* Main file
*
* @package Blog RSS Feed
* @version 1.0.1
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
include($phpbb_root_path . 'includes/mods/functions_blog.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);

$user->setup('mods/blog');

if($config['blog_rss_feed_on'])
{
	$feed = blog::getrssfeed();
}
else
{
	$feed = $user->lang['FEED_DISABLED'];
}
echo $feed;
