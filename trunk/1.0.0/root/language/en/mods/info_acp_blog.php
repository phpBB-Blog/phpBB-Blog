<?php
if (!defined('IN_PHPBB'))
{
	exit;
}
/**
* DO NOT CHANGE
*/
if (empty($lang) || !is_array($lang))
{
    $lang = array();
}
// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
//BLOG
	'ACP_BLOG'					=> 'phpBB Blog System',
	'ACP_BLOG_OVERVIEW'			=> 'Overview',
	'ACP_BLOG_OVERVIEW_FULL'	=> 'phpBB Blog System Overview',
	'ACP_BLOG_OVERVIEW_EXPLAIN'	=> 'This page gives you an overview of information about your installation of the phpBB Blog MOD',
	'ACP_BLOG_INDEX_TITLE'		=> 'Manage Blog',
	'ACP_BLOG_MANAGE'			=> 'Blog Settings',
	'ACP_BLOG_EXPLAIN'			=> 'This page allows you to alter your blog\'s settings.',
	'ACP_BLOG_CATS'				=> 'Manage Categories',
	'ACP_BLOG_CATS_MANAGE'		=> 'Manage Categories',
	'ACP_BLOG_CATS_EXPLAIN'		=> 'This page allows you to add, modify, and remove blog categories',
	'ACP_BLOG_CAT'				=> 'Category',
	'ACP_BLOG_CAT_DESC'			=> 'Description',
	
	
	'LATEST_BLOG_VERSION'		=> 'Latest Version:',
	'YOUR_BLOG_VERSION'			=> 'Current Version:',
	'UPDATE_TO'					=> 'Update to %1$s',
	'SERVER_DOWN'				=> 'The update server appears to be down. Try again in a few minutes. If the problem persists for more than a day, please check the development topic at phpBB.com for the latest version information.',
	'UP2DATE'					=> 'The installed version of phpBB Blog System is up to date.',
	'NUP2DATE'					=> 'The installed version of phpBB Blog System is <b>NOT</b> up to date.',
	
	'ACP_CAT_BLOG'				=> 'Blog',
	'NOROWS'					=> 'There are no blog categories. Use the new category button to add a new category.',
	
	'ACP_BLOG_TITLE'			=> 'Title',
	'ACP_BLOG_DESCRIPTION'		=> 'Description',
	'BLOG_ON'					=> 'Enable/disable Blog MOD',
	'BLOG_ON_EXPLAIN'			=> 'When set to Yes, the blog is enabled. When set to No, the blog is disabled.',
	'BLOG_OFF_MSG'				=> 'Disabled blog message',
	'BLOG_CAT_ON'				=> 'Enable/disable Blog Categories',
	'BLOG_FORUM_POST_ON'		=> 'Enable/disable posting in the forum when a new Blog post is posted',
	'BLOG_FORUM_POST_ON_EXPLAIN'=> 'If set to Yes, a new topic will be created in the specified forum upon the creation of a new blog post',
	'BLOG_FORUM_POST_ID'		=> 'Forum ID',
	'BLOG_FORUM_POST_ID_EXPLAIN'=> 'This is the ID of the forum to which the new topic will be posted upon the creation of a blog post. Only applicable if the previous option is set to Yes',
	'BLOG_RSS_FEED_ON'			=> 'Enable/disable rss feed of blog posts/comments',
	'BLOG_TAGCLOUD_ON'			=> 'Enable/disable tag cloud',
	'BLOG_BBCODE_ON'			=> 'Enable BBCode in blog posts and comments',
	'BLOG_EMOTE_ON'				=> 'Enable Smileys in blog posts and comments',
	'BLOG_POSTLIMIT'			=> 'Blog posts per page',
	'BLOG_CMNTLIMIT'			=> 'Blog comments per page',
	'BLOG_SHORT_MSG'			=> 'Blog Preview',
	'BLOG_SHORT_MSG_EXPLAIN'	=> 'The number of characters shown before the rest of the post is replaced with a View More link. Set to 0 to disable.',
	
	'FORM_INVALID'				=> 'The submitted form is invalid. Please start over and try again.',

	'NOROWS_POSTS'				=> 'No posts have been created.',
	'NOROWS_CATS'				=> 'No categories have been created.',
	'NOROWS_COMMENTS'			=> 'There are no comments on this blog post.',
	
	'CAT_CREATED'				=> 'Your multi-mod has been created successfully.',
	'CAT_CREATE_ERROR'			=> 'There was an error creating your multi-mod. Please try again.',
	'CAT_EDITED'				=> 'This multi-mod has been edited successfully.',
	'CAT_EDIT_ERROR'			=> 'There was an error editing your multi-mod. Please try again.',
	'BBCODEALLOW'				=> 'Allow BBCode in blog comments',
	'EMOTEALLOW'				=> 'Allow Smileys in blog comments',
	
	'YES'						=> 'Yes',
	'NO'						=> 'No',
	'SUBMIT'					=> 'Submit',
	'RESET'						=> 'Reset',
	'ACP_GEN_BLOG_SUCCESS'		=> 'The operation you selected has been completed successfully.',
	'ACP_GEN_BLOG_ERROR'		=> 'There was an error attempting to perform the selected operation.',
	'ACP_BLOG_FPO'				=> 'Forum Post Options',
	'ACP_BLOG_DISP'				=> 'Display and Posting Options',
	'ACP_BLOG_PLUGINS'			=> 'Plugins',
	'ACP_BLOG_OVERALL_TITLE'	=> 'The title of your blog',
	'BLOG_ACT_NAME'				=> 'URL "action" variable',
	'BLOG_ACT_NAME_EXPLAIN'		=> 'The URL will look like: ?<b>%s</b>=',
	'CATS_OFF'					=> 'Blog Categories Disabled',
	'CATS_OFF_EXPLAIN'			=> 'Categories have been disabled in the blog settings. Any changes you make now will be saved, but will not take affect until categories are enabled via the Blog Settings page.',
	
	'DELETE_POST'				=> 'Are you sure you would like to delete this blog post? This cannot be undone.',
	'DELETE_COMMENT'			=> 'Are you sure you would like to remove the selected comment(s)? This cannot be undone.',
));
?>