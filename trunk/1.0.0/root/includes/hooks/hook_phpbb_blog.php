<?php
/**
 * Main file
 *
 * @package Blog
 * @version 0.0.1
 * @copyright (c) 2010 Michael Cullum (Unknown Bliss of http://unknownbliss.co.uk)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 *
 */

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
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
	// Define the table constants
	global $table_prefix;
	define('BLOGS_TABLE',		$table_prefix . 'blog');
	define('BLOG_CMNTS_TABLE',	$table_prefix . 'blog_comments');
	define('BLOG_CATS_TABLE',	$table_prefix . 'blog_categories');
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
		'OVERALL_BLOG_TITLE'	=> $config['blog_title'],
	));
}


// Register them all
phpbb_blog_register_hooks($phpbb_hook);