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

	'MOD'					=> 'phpBB Blog System',
	'BLOG_DISABLED'			=> 'This functionality has been disabled by the administrator.',
	'BLOG'					=> 'Blog',
	'BLOG_RET'				=> 'Return to Blog',
	'BLOG_CAT'				=> 'Categories',
	'INVALID_BLOG_ID'		=> 'Invalid Blog ID.',
	'INVALID_CAT_ID'		=> 'Invalid Category ID.',

	'BLOGS'					=> 'Blog Posts',
	'CMNTS'					=> 'Comments',
	'CMNT'					=> 'Comment',
	'UCMNT'					=> 'Unapproved Comment',
	'UCMNTS'				=> 'Unapproved Comments',
	'POST_CMNT'				=> 'Post Comment',
	'LAST_POST'				=> 'Latest Blog Post',
	'LATEST_POSTS'			=> 'Latest Blog Posts',
	'POSTS_IN'				=> 'Posts in',
	'NO_BLOGS'				=> 'This category has no blog posts.',
	'NO_BLOGS_EXIST'		=> 'There are no blogs.',
	'NO_CATS'				=> 'There are no categories.',
	'NO_COMMENTS'			=> 'This blog post current has no comments posted to it.',
	'POSTED_BY'				=> 'Posted by',
	'IN'					=> 'in',
	'ON'					=> 'on',
	'MORE'					=> 'Read the rest of this entry &raquo;',
	'UPDATED'				=> 'Last updated: ',
	'EDIT'					=> 'Edit',
	'EDIT_COMMENT'			=> 'Edit Comment',
	'APPROVE'				=> 'This comment is unapproved. <a href="%1$s"><strong>Approve?</strong></a>',
	'TAG'					=> 'Blog Tags',
	'TAG_EXPLAIN'			=> 'Separate each entry with a comma.<BR />(e.g. "dog,cat,bird")',
	'NO_TAGS'				=> 'No tags',
	'ALLOW_CMNT'			=> 'Allow users to post comments',
	'CMNT_DISABLED'			=> 'Commenting has been disabled by the blog author.',
	'VIEW_MORE'				=> '(view more)',
	
	'BLOG_TAGCLOUD'			=> 'Tag Cloud',
	'RSS_FEED'				=> 'RSS Feed',
	'FEED_DISABLED'			=> 'The RSS feed for this websites\'s blog has been disabled.',

	'INVALID_CMNT_ID'		=> 'Invalid comment ID.',
	'CMNTSUCCESSUNAPPROVED'	=> 'Your comment has been submitted successfully and is awaiting approval. It will be visible once it has been approved. <b>To avoid waiting, you can register to have your comment automatically posted.</b>',
	'CMNTSUCCESS'			=> 'Your comment has been submitted successfully.',
	'RETURN_CMNT'			=> 'View your comment',
	
	'GENERIC_ERROR'			=> 'The action you were attempting to perform failed.',
	'GENERIC_SUCCESS'		=> 'The operation completed successfully.',
	
	'BLOG_POST_SUCCESS'		=> 'Your blog post has been submitted successfully.',
	'RETURN_POST'			=> 'View your blog post',
	'RETURN_CAT'			=> 'Return to %s',
	'RETURN'				=> 'Return to the previous page',
	
	'NEW_POST'				=> 'New Blog Post',
	'TITLE'					=> 'Blog Title',
	'DESC'					=> 'Description',
	'CAT'					=> 'Category',
	
	'SELECTONE'				=> 'Select One',
	'UNAUTHED'				=> 'Access denied - Insufficient privileges!',
	
	//Confirmation
	'CONF_DEL_POST'			=> 'Delete Blog Post',
	'CONF_DEL_POST_CONFIRM'	=> 'Are you sure you want to delete this blog post?',
	'CONF_DEL_CMNT'			=> 'Delete Blog Comment',
	'CONF_DEL_CMNT_CONFIRM'	=> 'Are you sure you want to delete this blog comment?',
	
	//Pagination
	'LIST_CMNT'				=> '1 Comment',
	'LIST_CMNTS'			=> '%1$s Comments',
	'LIST_BLOG'				=> '1 Blog Post',
	'LIST_BLOGS'			=> '%1$s Blog Posts',
	
	//New forum post on new blog post
	'FP_SUBJECT'			=> 'Blog: %1$s',
	'FP_MESSAGE'			=> 'A new blog has been posted. A preview is available here:<hr />[quote]%2$s[/quote]Click <a href="%1$s">here</a> to view and discuss it.',
));
?>