<?php
/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
    exit;
}

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

// Adding new category
$lang['permission_cat']['blog'] = 'phpBB Blog System';

// Adding the permissions
$lang = array_merge($lang, array(
	//ADMIN
    'acl_a_blog_manage'			=> array('lang'	=> 'Can manage the phpBB Blog System',			'cat' => 'blog'),
	//USER
	'acl_u_blog_post'			=> array('lang'	=> 'User can post to the blog',					'cat' => 'blog'),
	'acl_u_blog_post_manage'	=> array('lang' => 'User can edit and delete own blog posts',	'cat' => 'blog'),
	'acl_u_blog_comment'		=> array('lang'	=> 'User can comment on existing blog posts',	'cat' => 'blog'),
	'acl_u_blog_approved'		=> array('lang'	=> 'User can comment without approval',			'cat' => 'blog'),
	'acl_u_blog_bbcode'			=> array('lang'	=> 'User can use BBCode in comments',			'cat' => 'blog'),
	'acl_u_blog_emote'			=> array('lang'	=> 'User can use Smileys in comments',			'cat' => 'blog'),
	'acl_u_blog_comment_manage'	=> array('lang'	=> 'User can edit and delete own comments',		'cat' => 'blog'),
	'acl_u_blog_view'			=> array('lang'	=> 'User can view blog post previews',			'cat' => 'blog'),
	'acl_u_blog_view_full'		=> array('lang'	=> 'User can view full blog posts',				'cat' => 'blog'),
	'acl_u_blog_view_comments'	=> array('lang'	=> 'User can view comments made on blogs',		'cat' => 'blog'),
));
?>