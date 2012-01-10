<?php
/**
* Main file
*
* @package Blog
* @version 1.0.1
* @copyright (c) 2010, 2011 Michael Cullum (Unknown Bliss of http://michaelcullum.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
*
*/

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

/*
 * Don't register the hooks when in the phpBB installer
 */
if (defined('IN_INSTALL'))
{
	return;
}

/**
 * Register all hooks
 * @param	phpbb_hook	$phpbb_hook	The phpBB hook object
 * @return	void
 */
function phpbb_blog_register_hooks(&$phpbb_hook)
{
	$phpbb_hook->register('phpbb_user_session_handler', 'initialise_phpbb_blog');
	$phpbb_hook->register(array('template', 'display'), 'phpbb_blog_pre_display');
}

/**
 * Initialise the MOD
 * @param	phpbb_hook	$phpbb_hook	The phpBB hook object
 * @return	void
 */
function initialise_phpbb_blog(&$phpbb_hook)
{
	global $phpbb_root_path, $phpEx;
	global $table_prefix;	// Required for the include!

	require "{$phpbb_root_path}includes/mods/constants_blog.$phpEx";
}

/**
 * Hook that is called in template::display()
 * @param	phpbb_hook	$phpbb_hook	The phpBB hook object
 * @return	void
 */
function phpbb_blog_pre_display(&$phpbb_hook)
{
	global $config, $template;
	global $phpbb_root_path, $phpEx;

	// Assign the blog's main template variables
	$template->assign_vars(array(
		'U_BLOG'				=> append_sid("{$phpbb_root_path}blog.$phpEx"),
		'OVERALL_BLOG_TITLE'	=> isset($config['blog_title']) ? $config['blog_title'] : '',
	));
}


// Register them all
phpbb_blog_register_hooks($phpbb_hook);

// Don't break the UMIL install
if(!defined('UMIL_AUTO'))
{
	$phpbb_hook->register('phpbb_user_session_handler', 'phpbb_blog_register_hooks');
}
