<?php
/**
 * Constants
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

/**
 * Hard limit for number of blogs on index/cat
 */
define('MAX_BLOG_CNT_DISPLAY', 100);

/**
 * Table definitions
 */
define('BLOGS_TABLE',		$table_prefix . 'blog');
define('BLOG_CMNTS_TABLE',	$table_prefix . 'blog_comments');
define('BLOG_CATS_TABLE',	$table_prefix . 'blog_categories');
